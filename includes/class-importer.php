<?php
/**
 * Elementor Component Importer Class
 * Handles importing Elementor v4 components into the component library
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECS_Importer {

	/**
	 * Import component from file
	 *
	 * @param array $file File data from $_FILES
	 * @return array|WP_Error Import result or error
	 */
	public function import( $file ) {
		if ( ! isset( $file['tmp_name'] ) || ! isset( $file['name'] ) ) {
			return new WP_Error( 'invalid_file', __( 'Invalid file data', 'elementor-component-sync' ) );
		}

		// Verify file type
		$file_extension = pathinfo( $file['name'], PATHINFO_EXTENSION );
		if ( 'json' !== $file_extension ) {
			return new WP_Error( 'invalid_extension', __( 'Only JSON files are allowed', 'elementor-component-sync' ) );
		}

		// Read file
		$file_content = file_get_contents( $file['tmp_name'] );
		$import_data  = json_decode( $file_content, true );

		if ( ! is_array( $import_data ) ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON file', 'elementor-component-sync' ) );
		}

		// Validate import data
		$validation = $this->validate_import_data( $import_data );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Determine import type
		$export_type = isset( $import_data['export_type'] ) ? $import_data['export_type'] : 'component';

		if ( 'library' === $export_type ) {
			return $this->import_library( $import_data );
		} else {
			return $this->import_component( $import_data );
		}
	}

	/**
	 * Validate import data
	 *
	 * @param array $import_data The import data
	 * @return true|WP_Error
	 */
	private function validate_import_data( $import_data ) {
		if ( ! isset( $import_data['version'] ) ) {
			return new WP_Error( 'missing_version', __( 'Import file is missing version info', 'elementor-component-sync' ) );
		}

		if ( ! isset( $import_data['elementor_data'] ) ) {
			return new WP_Error( 'missing_elementor_data', __( 'Import file is missing Elementor data', 'elementor-component-sync' ) );
		}

		if ( ! isset( $import_data['export_type'] ) || 'component' !== $import_data['export_type'] && 'library' !== $import_data['export_type'] ) {
			return new WP_Error( 'invalid_export_type', __( 'Invalid export type', 'elementor-component-sync' ) );
		}

		return true;
	}

	/**
	 * Import single component into Elementor library
	 *
	 * @param array $import_data The import data
	 * @return array|WP_Error Import result or error
	 */
	private function import_component( $import_data ) {
		$component_data = isset( $import_data['component_data'] ) ? $import_data['component_data'] : [];
		$elementor_data = $import_data['elementor_data'];

		// Prepare component title
		$component_title = isset( $component_data['title'] ) ? $component_data['title'] : 'Imported Component';
		$component_title .= ' (Imported - ' . current_time( 'F j, Y' ) . ')';

		// Create new component in Elementor library
		$new_component = [
			'post_title'   => $component_title,
			'post_content' => isset( $component_data['description'] ) ? $component_data['description'] : '',
			'post_type'    => 'elementor_library',
			'post_status'  => 'publish', // Components should be published
		];

		$component_id = wp_insert_post( $new_component );

		if ( is_wp_error( $component_id ) ) {
			return new WP_Error( 'component_creation_failed', __( 'Failed to create component', 'elementor-component-sync' ) );
		}

		// Set as component type
		update_post_meta( $component_id, '_elementor_template_type', 'component' );

		// Save Elementor data
		$elementor_data_json = wp_json_encode( $elementor_data );
		update_post_meta( $component_id, '_elementor_data', $elementor_data_json );
		update_post_meta( $component_id, '_elementor_edit_mode', 'builder' );

		// Save CSS if available
		if ( ! empty( $import_data['elementor_css'] ) ) {
			update_post_meta( $component_id, '_elementor_css', $import_data['elementor_css'] );
		}

		// Save custom CSS if available
		if ( ! empty( $import_data['custom_css'] ) ) {
			update_post_meta( $component_id, '_elementor_custom_css', $import_data['custom_css'] );
		}

		// Save meta data
		if ( ! empty( $import_data['meta_data'] ) ) {
			foreach ( $import_data['meta_data'] as $key => $value ) {
				update_post_meta( $component_id, $key, $value );
			}
		}

		// Assign categories if available
		if ( ! empty( $component_data['categories'] ) && taxonomy_exists( 'elementor_library_category' ) ) {
			$categories = array_map( 'sanitize_text_field', $component_data['categories'] );
			wp_set_post_terms( $component_id, $categories, 'elementor_library_category' );
		}

		// Assign tags if available
		if ( ! empty( $component_data['tags'] ) && taxonomy_exists( 'elementor_library_type' ) ) {
			$tags = array_map( 'sanitize_text_field', $component_data['tags'] );
			wp_set_post_terms( $component_id, $tags, 'elementor_library_type' );
		}

		// Clear Elementor cache
		$this->clear_elementor_cache( $component_id );

		return [
			'success'      => true,
			'component_id' => $component_id,
			'title'        => $new_component['post_title'],
			'edit_url'     => get_edit_post_link( $component_id, 'raw' ),
			'view_url'     => get_permalink( $component_id ),
			'message'      => sprintf(
				__( 'Component "%s" imported to library successfully. %s', 'elementor-component-sync' ),
				esc_html( isset( $component_data['title'] ) ? $component_data['title'] : 'Component' ),
				'<a href="' . esc_url( admin_url( 'edit.php?post_type=elementor_library&elementor_library_category=component' ) ) . '">' . __( 'View Library', 'elementor-component-sync' ) . '</a>'
			),
		];
	}

	/**
	 * Import library (multiple components)
	 *
	 * @param array $import_data The import data
	 * @return array|WP_Error Import result or error
	 */
	private function import_library( $import_data ) {
		if ( ! isset( $import_data['components'] ) || ! is_array( $import_data['components'] ) ) {
			return new WP_Error( 'invalid_library', __( 'Invalid library format', 'elementor-component-sync' ) );
		}

		$imported       = [];
		$failed         = [];
		$total_components = count( $import_data['components'] );

		foreach ( $import_data['components'] as $component_data ) {
			$result = $this->import_component( $component_data );

			if ( is_wp_error( $result ) ) {
				$failed[] = [
					'title'   => isset( $component_data['component_data']['title'] ) ? $component_data['component_data']['title'] : 'Unknown',
					'error'   => $result->get_error_message(),
				];
			} else {
				$imported[] = $result;
			}
		}

		return [
			'success'          => true,
			'total'            => $total_components,
			'imported'         => count( $imported ),
			'failed'           => count( $failed ),
			'imported_items'   => $imported,
			'failed_items'     => $failed,
			'library_url'      => admin_url( 'edit.php?post_type=elementor_library&elementor_library_category=component' ),
			'message'          => sprintf(
				__( 'Successfully imported %d of %d components to your library', 'elementor-component-sync' ),
				count( $imported ),
				$total_components
			),
		];
	}

	/**
	 * Clear Elementor cache for a component
	 *
	 * @param int $component_id The component post ID
	 */
	private function clear_elementor_cache( $component_id ) {
		// Clear Elementor CSS cache
		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			$css_file = new \Elementor\Core\Files\CSS\Post( $component_id );
			$css_file->update();
		}

		// Clear general cache
		delete_transient( 'elementor_' . $component_id );
		delete_post_meta( $component_id, '_elementor_css_status' );

		// Invalidate Elementor library cache
		if ( class_exists( '\Elementor\Core\Files\CSS\Global_CSS' ) ) {
			$global_css = new \Elementor\Core\Files\CSS\Global_CSS();
			if ( method_exists( $global_css, 'delete_cache' ) ) {
				$global_css->delete_cache();
			}
		}
	}

	/**
	 * Get import preview
	 *
	 * @param string $file_path Path to the import file
	 * @return array|WP_Error Preview data or error
	 */
	public function get_preview( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', __( 'File not found', 'elementor-component-sync' ) );
		}

		$file_content = file_get_contents( $file_path );
		$import_data  = json_decode( $file_content, true );

		if ( ! is_array( $import_data ) ) {
			return new WP_Error( 'invalid_json', __( 'Invalid JSON file', 'elementor-component-sync' ) );
		}

		$export_type = isset( $import_data['export_type'] ) ? $import_data['export_type'] : 'component';

		if ( 'library' === $export_type ) {
			$total = isset( $import_data['total'] ) ? $import_data['total'] : 0;

			return [
				'type'       => 'library',
				'total'      => $total,
				'components' => isset( $import_data['components'] ) ? array_map(
					function ( $component ) {
						return [
							'title' => isset( $component['component_data']['title'] ) ? $component['component_data']['title'] : 'Untitled',
						];
					},
					$import_data['components']
				) : [],
			];
		} else {
			$component_data = isset( $import_data['component_data'] ) ? $import_data['component_data'] : [];
			return [
				'type'  => 'component',
				'title' => isset( $component_data['title'] ) ? $component_data['title'] : 'Untitled',
			];
		}
	}
}

<?php
/**
 * Elementor Component Exporter Class
 * Handles exporting Elementor v4 components from the component library
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECS_Exporter {

	/**
	 * Get all available Elementor components from the library
	 *
	 * @return array Array of components
	 */
	public function get_available_components() {
		$components = [];

		// Try multiple approaches to find components in Elementor v4

		// Approach 1: Check elementor_library post type with component meta
		$args = [
			'post_type'      => 'elementor_library',
			'posts_per_page' => -1,
			'post_status'    => ['publish', 'draft'],
		];

		$library_items = get_posts( $args );

		foreach ( $library_items as $item ) {
			$template_type = get_post_meta( $item->ID, '_elementor_template_type', true );
			$template_source = get_post_meta( $item->ID, '_elementor_template_source', true );
			
			// Check if it's a component (various ways Elementor v4 might mark it)
			if ( 'component' === $template_type || 'kit' === $template_type || 'block' === $template_type ) {
				$components[] = [
					'id'    => $item->ID,
					'title' => $item->post_title,
					'type'  => $template_type ?: 'unknown',
					'source' => $template_source,
				];
			}
		}

		// If no components found via template type, try by checking for _elementor_data
		if ( empty( $components ) ) {
			$args = [
				'post_type'      => 'elementor_library',
				'posts_per_page' => -1,
				'post_status'    => ['publish', 'draft'],
				'meta_query'     => [
					[
						'key'     => '_elementor_data',
						'compare' => 'EXISTS',
					],
				],
			];

			$library_items = get_posts( $args );

			foreach ( $library_items as $item ) {
				$template_type = get_post_meta( $item->ID, '_elementor_template_type', true );
				$components[] = [
					'id'    => $item->ID,
					'title' => $item->post_title,
					'type'  => $template_type ?: 'section',
				];
			}
		}

		// If still no components, check for pages with Elementor data
		if ( empty( $components ) ) {
			$args = [
				'post_type'      => ['page', 'post'],
				'posts_per_page' => -1,
				'post_status'    => ['publish', 'draft'],
				'meta_query'     => [
					[
						'key'     => '_elementor_data',
						'compare' => 'EXISTS',
					],
				],
			];

			$pages = get_posts( $args );

			foreach ( $pages as $page ) {
				$components[] = [
					'id'    => $page->ID,
					'title' => $page->post_title . ' (Page)',
					'type'  => 'page',
				];
			}
		}

		return $components;
	}

	/**
	 * Get diagnostic info about available posts and their Elementor data
	 * Used for debugging component detection
	 *
	 * @return array Diagnostic information
	 */
	public function get_diagnostic_info() {
		global $wpdb;

		$info = [
			'elementor_library_items' => [],
			'pages_with_elementor'    => [],
			'posts_with_elementor'    => [],
			'all_elementor_posts'     => [],
		];

		// Check elementor_library post type
		$library_query = "SELECT ID, post_title, post_type FROM {$wpdb->posts} WHERE post_type = 'elementor_library' LIMIT 20";
		$library_posts = $wpdb->get_results( $library_query );

		foreach ( (array) $library_posts as $post ) {
			$template_type = get_post_meta( $post->ID, '_elementor_template_type', true );
			$has_elementor_data = metadata_exists( 'post', $post->ID, '_elementor_data' );

			$info['elementor_library_items'][] = [
				'id'                    => $post->ID,
				'title'                 => $post->post_title,
				'template_type'         => $template_type,
				'has_elementor_data'    => $has_elementor_data,
			];
		}

		// Check all posts with _elementor_data meta
		$elementor_query = "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' LIMIT 50";
		$elementor_post_ids = $wpdb->get_col( $elementor_query );

		foreach ( (array) $elementor_post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( $post ) {
				$template_type = get_post_meta( $post_id, '_elementor_template_type', true );
				$info['all_elementor_posts'][] = [
					'id'            => $post->ID,
					'title'         => $post->post_title,
					'post_type'     => $post->post_type,
					'template_type' => $template_type,
				];
			}
		}

		return $info;
	}

	/**
	 * Export a component from the Elementor library
	 *
	 * @param int $component_id The component post ID
	 * @return array|WP_Error Export data or error
	 */
	public function export( $component_id ) {
		$component = get_post( $component_id );

		if ( ! $component || 'elementor_library' !== $component->post_type ) {
			return new WP_Error( 
				'invalid_component', 
				__( 'Invalid component or component not found', 'elementor-component-sync' ) 
			);
		}

		$template_type = get_post_meta( $component_id, '_elementor_template_type', true );
		if ( 'component' !== $template_type ) {
			return new WP_Error( 
				'not_component', 
				__( 'This item is not a component. Only components can be exported.', 'elementor-component-sync' ) 
			);
		}

		if ( ! did_action( 'elementor/loaded' ) ) {
			return new WP_Error( 'elementor_not_loaded', __( 'Elementor is not loaded', 'elementor-component-sync' ) );
		}

		// Get Elementor data
		$elementor_data = get_post_meta( $component_id, '_elementor_data', true );

		if ( ! $elementor_data ) {
			return new WP_Error( 'no_elementor_data', __( 'No Elementor data found for this component', 'elementor-component-sync' ) );
		}

		// Decode if it's a JSON string
		if ( is_string( $elementor_data ) ) {
			$elementor_data = json_decode( $elementor_data, true );
		}

		// Get Elementor CSS
		$elementor_css = get_post_meta( $component_id, '_elementor_css', true );

		// Get custom CSS if available
		$custom_css = get_post_meta( $component_id, '_elementor_custom_css', true );

		// Get component category/tags
		$categories = get_the_terms( $component_id, 'elementor_library_category' );
		$category_names = [];
		if ( ! is_wp_error( $categories ) && $categories ) {
			foreach ( $categories as $cat ) {
				$category_names[] = $cat->name;
			}
		}

		// Get component tags
		$tags = get_the_terms( $component_id, 'elementor_library_type' );
		$tag_names = [];
		if ( ! is_wp_error( $tags ) && $tags ) {
			foreach ( $tags as $tag ) {
				$tag_names[] = $tag->name;
			}
		}

		// Prepare export data
		$export_data = [
			'version'         => ELEMENTOR_VERSION,
			'export_type'     => 'component',
			'exported_at'     => current_time( 'mysql' ),
			'site_url'        => get_site_url(),
			'component_data'  => [
				'id'             => $component->ID,
				'title'          => $component->post_title,
				'slug'           => $component->post_name,
				'description'    => $component->post_content,
				'status'         => $component->post_status,
				'categories'     => $category_names,
				'tags'           => $tag_names,
				'template_type'  => $template_type,
				'is_component'   => true,
			],
			'elementor_data'  => $elementor_data,
			'elementor_css'   => $elementor_css,
			'custom_css'      => $custom_css,
			'meta_data'       => $this->get_component_meta_data( $component_id ),
		];

		return [
			'data'     => $export_data,
			'filename' => sanitize_file_name( $component->post_title . '-component-' . time() . '.json' ),
		];
	}

	/**
	 * Get component meta data
	 *
	 * @param int $component_id The component post ID
	 * @return array Component meta data
	 */
	private function get_component_meta_data( $component_id ) {
		$meta_keys = [
			'_elementor_edit_mode',
			'_elementor_template_type',
			'_elementor_page_settings',
			'_elementor_library_settings',
		];

		$meta_data = [];

		foreach ( $meta_keys as $key ) {
			$value = get_post_meta( $component_id, $key, true );
			if ( ! empty( $value ) ) {
				$meta_data[ $key ] = $value;
			}
		}

		return $meta_data;
	}

	/**
	 * Export multiple components as a library
	 *
	 * @param array $component_ids Array of component IDs
	 * @return array|WP_Error Library data or error
	 */
	public function export_library( $component_ids ) {
		if ( empty( $component_ids ) ) {
			return new WP_Error( 'no_components', __( 'No components selected', 'elementor-component-sync' ) );
		}

		$components = [];
		$errors     = [];

		foreach ( $component_ids as $component_id ) {
			$result = $this->export( $component_id );

			if ( is_wp_error( $result ) ) {
				$errors[] = sprintf(
					__( 'Component %d: %s', 'elementor-component-sync' ),
					$component_id,
					$result->get_error_message()
				);
			} else {
				$components[] = $result['data'];
			}
		}

		if ( empty( $components ) ) {
			return new WP_Error( 'no_valid_components', __( 'No valid components to export', 'elementor-component-sync' ) );
		}

		$library_data = [
			'version'         => ELEMENTOR_VERSION,
			'export_type'     => 'library',
			'exported_at'     => current_time( 'mysql' ),
			'site_url'        => get_site_url(),
			'components'      => $components,
			'total'           => count( $components ),
			'errors'          => $errors,
			'library_version' => '1.0',
		];

		return [
			'data'     => $library_data,
			'filename' => 'elementor-components-library-' . time() . '.json',
		];
	}
}

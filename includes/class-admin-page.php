<?php
/**
 * Admin Page for Elementor Component Sync
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ECS_Admin_Page {

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page', 'elementor-component-sync' ) );
		}

		wp_enqueue_style( 'ecs-admin-style', ECS_PLUGIN_URL . 'assets/css/admin.css', [], ECS_PLUGIN_VERSION );
		wp_enqueue_script( 'ecs-admin-script', ECS_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery' ], ECS_PLUGIN_VERSION, true );

		wp_localize_script(
			'ecs-admin-script',
			'ecsData',
			[
				'nonce' => wp_create_nonce( 'ecs_nonce' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'strings' => [
					'exportSuccess' => __( 'Component exported successfully', 'elementor-component-sync' ),
					'importSuccess' => __( 'Component imported successfully', 'elementor-component-sync' ),
					'error' => __( 'An error occurred', 'elementor-component-sync' ),
					'selectComponent' => __( 'Please select a component to export', 'elementor-component-sync' ),
					'selectFile' => __( 'Please select a file to import', 'elementor-component-sync' ),
					'downloading' => __( 'Downloading...', 'elementor-component-sync' ),
					'importing' => __( 'Importing...', 'elementor-component-sync' ),
				],
			]
		);

		?>
		<div class="wrap ecs-admin-wrap">
			<h1><?php esc_html_e( 'Elementor Component Sync', 'elementor-component-sync' ); ?></h1>

			<div class="ecs-tabs">
				<nav class="nav-tab-wrapper">
					<a href="#ecs-export" class="nav-tab nav-tab-active" data-tab="export">
						<?php esc_html_e( 'Export', 'elementor-component-sync' ); ?>
					</a>
					<a href="#ecs-import" class="nav-tab" data-tab="import">
						<?php esc_html_e( 'Import', 'elementor-component-sync' ); ?>
					</a>
					<a href="#ecs-diagnostic" class="nav-tab" data-tab="diagnostic">
						<?php esc_html_e( 'Diagnostic', 'elementor-component-sync' ); ?>
					</a>
					<a href="#ecs-help" class="nav-tab" data-tab="help">
						<?php esc_html_e( 'Help', 'elementor-component-sync' ); ?>
					</a>
				</nav>

				<!-- Export Tab -->
				<div id="ecs-export" class="ecs-tab-content active">
					<?php self::render_export_tab(); ?>
				</div>

				<!-- Import Tab -->
				<div id="ecs-import" class="ecs-tab-content">
					<?php self::render_import_tab(); ?>
				</div>

				<!-- Diagnostic Tab -->
				<div id="ecs-diagnostic" class="ecs-tab-content">
					<?php self::render_diagnostic_tab(); ?>
				</div>

				<!-- Help Tab -->
				<div id="ecs-help" class="ecs-tab-content">
					<?php self::render_help_tab(); ?>
				</div>
			</div>
		</div>
		<?php
	}

	private static function render_export_tab() {
		?>
		<div class="ecs-tab-panel">
			<h2><?php esc_html_e( 'Export Elementor v4 Components', 'elementor-component-sync' ); ?></h2>
			<p class="description" style="margin-bottom: 20px;">
				<?php esc_html_e( 'Export components from your Elementor library to use on other WordPress sites.', 'elementor-component-sync' ); ?>
			</p>
			
			<div class="ecs-form-group">
				<label for="ecs-export-type"><?php esc_html_e( 'What would you like to export?', 'elementor-component-sync' ); ?></label>
				<select id="ecs-export-type" class="ecs-select">
					<option value="single"><?php esc_html_e( 'Single Component', 'elementor-component-sync' ); ?></option>
					<option value="multiple"><?php esc_html_e( 'Multiple Components (Library)', 'elementor-component-sync' ); ?></option>
				</select>
			</div>

			<!-- Single Export -->
			<div id="ecs-single-export" class="ecs-export-section active">
				<div class="ecs-form-group">
					<label for="ecs-export-component"><?php esc_html_e( 'Select Component', 'elementor-component-sync' ); ?></label>
					<select id="ecs-export-component" class="ecs-select">
						<option value=""><?php esc_html_e( 'Choose a component...', 'elementor-component-sync' ); ?></option>
						<?php self::render_component_options(); ?>
					</select>
				</div>

				<button type="button" class="button button-primary" id="ecs-export-btn">
					<?php esc_html_e( 'Export Component', 'elementor-component-sync' ); ?>
				</button>
			</div>

			<!-- Multiple Export -->
			<div id="ecs-multiple-export" class="ecs-export-section" style="display: none;">
				<div class="ecs-form-group">
					<label><?php esc_html_e( 'Select Components', 'elementor-component-sync' ); ?></label>
					<div class="ecs-checkbox-list">
						<?php self::render_component_checkboxes(); ?>
					</div>
				</div>

				<button type="button" class="button button-primary" id="ecs-export-library-btn">
					<?php esc_html_e( 'Export Library', 'elementor-component-sync' ); ?>
				</button>
			</div>

			<div id="ecs-export-message" class="ecs-message" style="display: none;"></div>
		</div>
		<?php
	}

	private static function render_import_tab() {
		?>
		<div class="ecs-tab-panel">
			<h2><?php esc_html_e( 'Import Elementor v4 Components', 'elementor-component-sync' ); ?></h2>
			<p class="description" style="margin-bottom: 20px;">
				<?php esc_html_e( 'Import components exported from other sites directly into your Elementor library.', 'elementor-component-sync' ); ?>
			</p>
			
			<div class="ecs-form-group">
				<label for="ecs-import-file"><?php esc_html_e( 'Select File to Import', 'elementor-component-sync' ); ?></label>
				<div class="ecs-file-upload">
					<input type="file" id="ecs-import-file" accept=".json" />
					<p class="description"><?php esc_html_e( 'Upload a JSON file exported from another site', 'elementor-component-sync' ); ?></p>
				</div>
			</div>

			<div id="ecs-import-preview" class="ecs-import-preview" style="display: none;">
				<h3><?php esc_html_e( 'Preview', 'elementor-component-sync' ); ?></h3>
				<div id="ecs-preview-content"></div>
			</div>

			<button type="button" class="button button-primary" id="ecs-import-btn">
				<?php esc_html_e( 'Import Component', 'elementor-component-sync' ); ?>
			</button>

			<div id="ecs-import-message" class="ecs-message" style="display: none;"></div>
		</div>
		<?php
	}

	private static function render_help_tab() {
		?>
		<div class="ecs-tab-panel">
			<h2><?php esc_html_e( 'Help & Documentation', 'elementor-component-sync' ); ?></h2>
			
			<h3><?php esc_html_e( 'How to Export', 'elementor-component-sync' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Go to the Export tab', 'elementor-component-sync' ); ?></li>
				<li><?php esc_html_e( 'Select whether you want to export a single component or a library', 'elementor-component-sync' ); ?></li>
				<li><?php esc_html_e( 'Choose the component(s) you want to export', 'elementor-component-sync' ); ?></li>
				<li><?php esc_html_e( 'Click the export button', 'elementor-component-sync' ); ?></li>
				<li><?php esc_html_e( 'A JSON file will be downloaded to your computer', 'elementor-component-sync' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'How to Import', 'elementor-component-sync' ); ?></h3>
			<ol>
				<li><?php esc_html_e( 'Go to the Import tab', 'elementor-component-sync' ); ?></li>
				<li><?php esc_html_e( 'Select the JSON file exported from another site', 'elementor-component-sync' ); ?></li>
				<li><?php esc_html_e( 'Review the preview (optional)', 'elementor-component-sync' ); ?></li>
				<li><?php esc_html_e( 'Click the import button', 'elementor-component-sync' ); ?></li>
				<li><?php esc_html_e( 'The component(s) will be imported as drafts', 'elementor-component-sync' ); ?></li>
			</ol>

			<h3><?php esc_html_e( 'Important Notes', 'elementor-component-sync' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Imported components are created as draft posts', 'elementor-component-sync' ); ?></li>
				<li><?php esc_html_e( 'External images and resources are imported with their original URLs', 'elementor-component-sync' ); ?></li>
				<li><?php esc_html_e( 'Custom fonts and plugins may need to be installed on the destination site', 'elementor-component-sync' ); ?></li>
				<li><?php esc_html_e( 'You can edit imported components in the Elementor editor', 'elementor-component-sync' ); ?></li>
			</ul>

			<h3><?php esc_html_e( 'Supported Elementor Versions', 'elementor-component-sync' ); ?></h3>
			<p><?php esc_html_e( 'This plugin supports Elementor v4.0 and later', 'elementor-component-sync' ); ?></p>
		</div>
		<?php
	}

	private static function render_diagnostic_tab() {
		?>
		<div class="ecs-tab-panel">
			<h2><?php esc_html_e( 'Diagnostic Information', 'elementor-component-sync' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'This tool helps diagnose where your Elementor components are stored and how they are structured.', 'elementor-component-sync' ); ?>
			</p>

			<div class="ecs-diagnostic-section">
				<h3><?php esc_html_e( 'Available Components', 'elementor-component-sync' ); ?></h3>
				<?php
				$exporter = new ECS_Exporter();
				$components = $exporter->get_available_components();

				if ( empty( $components ) ) {
					echo '<div class="ecs-message error">' . esc_html__( 'No Elementor components found on your site.', 'elementor-component-sync' ) . '</div>';
				} else {
					echo '<table class="widefat striped">';
					echo '<thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Source</th></tr></thead>';
					echo '<tbody>';
					foreach ( $components as $comp ) {
						echo '<tr>';
						echo '<td>' . esc_html( $comp['id'] ) . '</td>';
						echo '<td>' . esc_html( $comp['title'] ) . '</td>';
						echo '<td>' . esc_html( $comp['type'] ?: 'unknown' ) . '</td>';
						echo '<td>' . esc_html( $comp['source'] ?: 'N/A' ) . '</td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
				}
				?>
			</div>

			<div class="ecs-diagnostic-section">
				<h3><?php esc_html_e( 'Database Diagnostic Info', 'elementor-component-sync' ); ?></h3>
				<?php
				$diagnostic = $exporter->get_diagnostic_info();
				?>

				<h4><?php esc_html_e( 'Elementor Library Items', 'elementor-component-sync' ); ?></h4>
				<?php
				if ( empty( $diagnostic['elementor_library_items'] ) ) {
					echo '<p><code>elementor_library</code> post type not found</p>';
				} else {
					echo '<table class="widefat striped">';
					echo '<thead><tr><th>ID</th><th>Title</th><th>Template Type</th><th>Has Data</th></tr></thead>';
					echo '<tbody>';
					foreach ( $diagnostic['elementor_library_items'] as $item ) {
						echo '<tr>';
						echo '<td>' . esc_html( $item['id'] ) . '</td>';
						echo '<td>' . esc_html( $item['title'] ) . '</td>';
						echo '<td>' . esc_html( $item['template_type'] ?: 'not set' ) . '</td>';
						echo '<td>' . ( $item['has_elementor_data'] ? '✓' : '✗' ) . '</td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
				}
				?>

				<h4><?php esc_html_e( 'All Posts with Elementor Data', 'elementor-component-sync' ); ?></h4>
				<?php
				if ( empty( $diagnostic['all_elementor_posts'] ) ) {
					echo '<p>' . esc_html__( 'No posts with Elementor data found', 'elementor-component-sync' ) . '</p>';
				} else {
					echo '<table class="widefat striped">';
					echo '<thead><tr><th>ID</th><th>Title</th><th>Post Type</th><th>Template Type</th></tr></thead>';
					echo '<tbody>';
					foreach ( $diagnostic['all_elementor_posts'] as $item ) {
						echo '<tr>';
						echo '<td>' . esc_html( $item['id'] ) . '</td>';
						echo '<td>' . esc_html( $item['title'] ) . '</td>';
						echo '<td>' . esc_html( $item['post_type'] ) . '</td>';
						echo '<td>' . esc_html( $item['template_type'] ?: 'not set' ) . '</td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
				}
				?>
			</div>

			<div class="ecs-diagnostic-section">
				<h3><?php esc_html_e( 'Instructions', 'elementor-component-sync' ); ?></h3>
				<ol>
					<li><?php esc_html_e( 'Check if "Elementor Library Items" shows any items', 'elementor-component-sync' ); ?></li>
					<li><?php esc_html_e( 'Look at the Template Type column to see how components are labeled', 'elementor-component-sync' ); ?></li>
					<li><?php esc_html_e( 'Check "All Posts with Elementor Data" to see all pages/posts with Elementor content', 'elementor-component-sync' ); ?></li>
					<li><?php esc_html_e( 'Share this information if you need help diagnosing the issue', 'elementor-component-sync' ); ?></li>
				</ol>
			</div>
		</div>
		<?php
	}

	private static function render_component_options() {
		$exporter = new ECS_Exporter();
		$components = $exporter->get_available_components();

		if ( empty( $components ) ) {
			echo '<option value="">' . esc_html__( 'No components found in your library', 'elementor-component-sync' ) . '</option>';
			return;
		}

		foreach ( $components as $component ) {
			echo '<option value="' . esc_attr( $component['id'] ) . '">' . esc_html( $component['title'] ) . '</option>';
		}
	}

	private static function render_component_checkboxes() {
		$exporter = new ECS_Exporter();
		$components = $exporter->get_available_components();

		if ( empty( $components ) ) {
			echo '<p class="ecs-no-components">' . esc_html__( 'No components found in your library', 'elementor-component-sync' ) . '</p>';
			return;
		}

		foreach ( $components as $component ) {
			echo '<label class="ecs-checkbox-item">';
			echo '<input type="checkbox" value="' . esc_attr( $component['id'] ) . '" class="ecs-component-checkbox" />';
			echo esc_html( $component['title'] );
			echo '</label>';
		}
	}
}

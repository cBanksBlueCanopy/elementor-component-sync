<?php
/**
 * Plugin Name: Elementor Component Sync
 * Description: Export and import Elementor v4 components between WordPress sites
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * Text Domain: elementor-component-sync
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Elementor tested up to: 4.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'ECS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ECS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ECS_PLUGIN_VERSION', '1.0.0' );

// Check if Elementor is active
function ecs_is_elementor_active() {
	return defined( 'ELEMENTOR_VERSION' );
}

// Main plugin class
class Elementor_Component_Sync {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		// Load required files
		$this->load_files();

		// Initialize hooks
		add_action( 'plugins_loaded', [ $this, 'init_plugin' ] );
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'wp_ajax_ecs_export_component', [ $this, 'handle_export' ] );
		add_action( 'wp_ajax_ecs_import_component', [ $this, 'handle_import' ] );
	}

	public function load_files() {
		require_once ECS_PLUGIN_PATH . 'includes/class-exporter.php';
		require_once ECS_PLUGIN_PATH . 'includes/class-importer.php';
		require_once ECS_PLUGIN_PATH . 'includes/class-admin-page.php';
	}

	public function init_plugin() {
		if ( ! ecs_is_elementor_active() ) {
			add_action( 'admin_notices', [ $this, 'elementor_not_active_notice' ] );
			return;
		}

		// Plugin is ready
		load_plugin_textdomain( 'elementor-component-sync', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function register_menu() {
		if ( ! ecs_is_elementor_active() ) {
			return;
		}

		add_menu_page(
			__( 'Component Sync', 'elementor-component-sync' ),
			__( 'Component Sync', 'elementor-component-sync' ),
			'manage_options',
			'ecs-component-sync',
			[ ECS_Admin_Page::class, 'render' ],
			'dashicons-database',
			26
		);
	}

	public function handle_export() {
		check_ajax_referer( 'ecs_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied', 'elementor-component-sync' ), 403 );
		}

		$component_id = isset( $_POST['component_id'] ) ? intval( $_POST['component_id'] ) : 0;
		$export_type  = isset( $_POST['export_type'] ) ? sanitize_text_field( $_POST['export_type'] ) : 'component';

		if ( ! $component_id ) {
			wp_send_json_error( __( 'Invalid component ID', 'elementor-component-sync' ) );
		}

		$exporter = new ECS_Exporter();
		$result   = $exporter->export( $component_id, $export_type );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	public function handle_import() {
		check_ajax_referer( 'ecs_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied', 'elementor-component-sync' ), 403 );
		}

		if ( ! isset( $_FILES['component_file'] ) ) {
			wp_send_json_error( __( 'No file provided', 'elementor-component-sync' ) );
		}

		$importer = new ECS_Importer();
		$result   = $importer->import( $_FILES['component_file'] );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	public function elementor_not_active_notice() {
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php
				echo esc_html__(
					'Elementor Component Sync requires Elementor to be installed and activated.',
					'elementor-component-sync'
				);
				?>
			</p>
		</div>
		<?php
	}
}

// Initialize the plugin
Elementor_Component_Sync::get_instance();

// Activation hook
register_activation_hook( __FILE__, function() {
	if ( ! ecs_is_elementor_active() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'This plugin requires Elementor to be installed and activated.', 'elementor-component-sync' )
		);
	}
} );

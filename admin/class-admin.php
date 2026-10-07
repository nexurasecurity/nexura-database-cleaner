<?php
/**
 * Admin Coordinator
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Admin;

use NexuraDatabaseCleaner\Includes\Capabilities;
use NexuraDatabaseCleaner\Includes\Database;
use NexuraDatabaseCleaner\Includes\Safety;
use NexuraDatabaseCleaner\Includes\Detector;
use NexuraDatabaseCleaner\Includes\Cleaner;
use NexuraDatabaseCleaner\Includes\Scanner;
use NexuraDatabaseCleaner\Includes\Optimizer;
use NexuraDatabaseCleaner\Includes\Reporter;
use NexuraDatabaseCleaner\Modules\Options\Options;
use NexuraDatabaseCleaner\Modules\Cron\Cron;
use NexuraDatabaseCleaner\Includes\Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin {

	/**
	 * Singleton instance.
	 *
	 * @var Admin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Register AJAX actions
		$this->register_ajax_actions();
	}

	/**
	 * Register Admin Menus according to Section 29 of PRD.
	 */
	public function register_admin_menus() {
		$cap = Capabilities::REQUIRED_CAP;

		// Main Menu
		add_menu_page(
			__( 'Database Cleaner', 'nexura-database-cleaner' ),
			__( 'Database Cleaner', 'nexura-database-cleaner' ),
			$cap,
			'nexura-database-cleaner',
			array( $this, 'render_dashboard_page' ),
			'dashicons-database-view',
			78
		);

		// Submenus
		add_submenu_page(
			'nexura-database-cleaner',
			__( 'Dashboard &lsaquo; Database Cleaner', 'nexura-database-cleaner' ),
			__( 'Dashboard', 'nexura-database-cleaner' ),
			$cap,
			'nexura-database-cleaner',
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			'nexura-database-cleaner',
			__( 'Scan Database &lsaquo; Database Cleaner', 'nexura-database-cleaner' ),
			__( 'Scan Database', 'nexura-database-cleaner' ),
			$cap,
			'nexura-database-cleaner-scan',
			array( $this, 'render_scan_page' )
		);

		add_submenu_page(
			'nexura-database-cleaner',
			__( 'Cleanup &lsaquo; Database Cleaner', 'nexura-database-cleaner' ),
			__( 'Cleanup', 'nexura-database-cleaner' ),
			$cap,
			'nexura-database-cleaner-cleanup',
			array( $this, 'render_cleanup_page' )
		);

		add_submenu_page(
			'nexura-database-cleaner',
			__( 'Optimization &lsaquo; Database Cleaner', 'nexura-database-cleaner' ),
			__( 'Optimization', 'nexura-database-cleaner' ),
			$cap,
			'nexura-database-cleaner-optimization',
			array( $this, 'render_optimization_page' )
		);

		add_submenu_page(
			'nexura-database-cleaner',
			__( 'Reports &lsaquo; Database Cleaner', 'nexura-database-cleaner' ),
			__( 'Reports', 'nexura-database-cleaner' ),
			$cap,
			'nexura-database-cleaner-reports',
			array( $this, 'render_reports_page' )
		);

		add_submenu_page(
			'nexura-database-cleaner',
			__( 'Settings &lsaquo; Database Cleaner', 'nexura-database-cleaner' ),
			__( 'Settings', 'nexura-database-cleaner' ),
			$cap,
			'nexura-database-cleaner-settings',
			array( $this, 'render_settings_page' )
		);

		add_submenu_page(
			'nexura-database-cleaner',
			__( 'Pro Tools &lsaquo; Database Cleaner', 'nexura-database-cleaner' ),
			__( 'Pro Tools', 'nexura-database-cleaner' ),
			$cap,
			'nexura-database-cleaner-pro',
			array( $this, 'render_pro_page' )
		);
	}

	/**
	 * Enqueue CSS & JS on plugin admin pages.
	 *
	 * @param string $hook
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only enqueue on our plugin pages
		if ( strpos( $hook, 'nexura-database-cleaner' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'nexura-database-cleaner-admin',
			NEXURA_DATABASE_CLEANER_URL . 'admin/css/admin.css',
			array(),
			NEXURA_DATABASE_CLEANER_VERSION
		);

		wp_enqueue_script(
			'nexura-database-cleaner-admin',
			NEXURA_DATABASE_CLEANER_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			NEXURA_DATABASE_CLEANER_VERSION,
			true
		);

		$settings = get_option( 'nexura_database_cleaner_settings', array( 'batch_size' => 100 ) );

		wp_localize_script(
			'nexura-database-cleaner-admin',
			'nexuraDatabaseCleanerData',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'nonce'      => Capabilities::create_nonce( 'nexura_database_cleaner_admin_nonce' ),
				'batch_size' => isset( $settings['batch_size'] ) ? absint( $settings['batch_size'] ) : 100,
				'strings'    => array(
					'scanning'         => __( 'Scanning database...', 'nexura-database-cleaner' ),
					'scan_complete'    => __( 'Database scan completed successfully.', 'nexura-database-cleaner' ),
					'cleaning'         => __( 'Cleaning in progress...', 'nexura-database-cleaner' ),
					'dry_running'      => __( 'Running preview dry-run...', 'nexura-database-cleaner' ),
					'cleaning_batch'   => __( 'Processing batch...', 'nexura-database-cleaner' ),
					'completed'        => __( 'Operation completed successfully.', 'nexura-database-cleaner' ),
					'confirm_cleanup'  => __( 'Are you sure you want to clean this item? A database backup is recommended.', 'nexura-database-cleaner' ),
					'confirm_optimize' => __( 'Are you sure you want to optimize this table?', 'nexura-database-cleaner' ),
					'error'            => __( 'An error occurred during execution. Please check server logs.', 'nexura-database-cleaner' ),
				),
			)
		);
	}

	/**
	 * Register all AJAX actions with permissions checks.
	 */
	private function register_ajax_actions() {
		add_action( 'wp_ajax_nexura_database_cleaner_run_scan', array( $this, 'ajax_run_scan' ) );
		add_action( 'wp_ajax_nexura_database_cleaner_preview_item', array( $this, 'ajax_preview_item' ) );
		add_action( 'wp_ajax_nexura_database_cleaner_clean_batch', array( $this, 'ajax_clean_batch' ) );
		add_action( 'wp_ajax_nexura_database_cleaner_optimize_table', array( $this, 'ajax_optimize_table' ) );
		add_action( 'wp_ajax_nexura_database_cleaner_analyze_table', array( $this, 'ajax_analyze_table' ) );
		add_action( 'wp_ajax_nexura_database_cleaner_log_cleanup', array( $this, 'ajax_log_cleanup' ) );
		add_action( 'wp_ajax_nexura_database_cleaner_clear_history', array( $this, 'ajax_clear_history' ) );
		add_action( 'wp_ajax_nexura_database_cleaner_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_nexura_database_cleaner_toggle_autoload', array( $this, 'ajax_toggle_autoload' ) );
		add_action( 'wp_ajax_nexura_database_cleaner_delete_cron', array( $this, 'ajax_delete_cron' ) );
	}

	/**
	 * AJAX: Run database scan.
	 */
	public function ajax_run_scan() {
		Capabilities::check_ajax_permissions();
		check_ajax_referer( 'nexura_database_cleaner_admin_nonce', 'nonce' );

		$results = Scanner::run_scan();

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Preview items before cleaning.
	 */
	public function ajax_preview_item() {
		Capabilities::check_ajax_permissions();
		check_ajax_referer( 'nexura_database_cleaner_admin_nonce', 'nonce' );

		$item_id = isset( $_POST['item_id'] ) ? sanitize_key( wp_unslash( $_POST['item_id'] ) ) : '';
		if ( empty( $item_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing item ID.', 'nexura-database-cleaner' ) ) );
		}

		$defs = Detector::get_item_definitions();
		if ( ! isset( $defs[ $item_id ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid item.', 'nexura-database-cleaner' ) ) );
		}

		$preview_rows = Detector::preview_item( $item_id, 20 );
		$count        = Detector::count_item( $item_id );

		wp_send_json_success( array(
			'item'         => $defs[ $item_id ],
			'item_id'      => $item_id,
			'total_count'  => $count,
			'preview_rows' => $preview_rows,
		) );
	}

	/**
	 * AJAX: Clean a single batch safely.
	 */
	public function ajax_clean_batch() {
		Capabilities::check_ajax_permissions();
		check_ajax_referer( 'nexura_database_cleaner_admin_nonce', 'nonce' );

		$item_id    = isset( $_POST['item_id'] ) ? sanitize_key( wp_unslash( $_POST['item_id'] ) ) : '';
		$dry_run    = ! empty( $_POST['dry_run'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['dry_run'] ) );
		$batch_size = isset( $_POST['batch_size'] ) ? absint( wp_unslash( $_POST['batch_size'] ) ) : 100;
		$job_id     = isset( $_POST['job_id'] ) ? sanitize_text_field( wp_unslash( $_POST['job_id'] ) ) : '';

		$result = Cleaner::clean_batch( $item_id, $batch_size, $dry_run, $job_id );

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX: Optimize a table.
	 */
	public function ajax_optimize_table() {
		Capabilities::check_ajax_permissions();
		check_ajax_referer( 'nexura_database_cleaner_admin_nonce', 'nonce' );

		$table_name = isset( $_POST['table_name'] ) ? sanitize_text_field( wp_unslash( $_POST['table_name'] ) ) : '';
		$result     = Optimizer::optimize( $table_name );

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX: Analyze a table.
	 */
	public function ajax_analyze_table() {
		Capabilities::check_ajax_permissions();
		check_ajax_referer( 'nexura_database_cleaner_admin_nonce', 'nonce' );

		$table_name = isset( $_POST['table_name'] ) ? sanitize_text_field( wp_unslash( $_POST['table_name'] ) ) : '';
		$result     = Optimizer::analyze( $table_name );

		if ( ! empty( $result['success'] ) ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * AJAX: Log a completed cleanup to audit history.
	 * Kept for backward compatibility; authoritative logging is now performed server-side.
	 */
	public function ajax_log_cleanup() {
		Capabilities::check_ajax_permissions();
		check_ajax_referer( 'nexura_database_cleaner_admin_nonce', 'nonce' );

		wp_send_json_success();
	}

	/**
	 * AJAX: Clear audit log history.
	 */
	public function ajax_clear_history() {
		Capabilities::check_ajax_permissions();
		check_ajax_referer( 'nexura_database_cleaner_admin_nonce', 'nonce' );

		Reporter::clear_history();
		wp_send_json_success();
	}

	/**
	 * AJAX: Save plugin settings.
	 */
	public function ajax_save_settings() {
		Capabilities::check_ajax_permissions();
		check_ajax_referer( 'nexura_database_cleaner_admin_nonce', 'nonce' );

		$batch_size = isset( $_POST['batch_size'] ) ? absint( wp_unslash( $_POST['batch_size'] ) ) : 100;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$settings = array(
			'batch_size' => $batch_size,
		);

		update_option( 'nexura_database_cleaner_settings', $settings );

		// Scheduled automated cleanup settings
		$schedule_enabled       = ! empty( $_POST['schedule_enabled'] );
		$schedule_frequency     = isset( $_POST['schedule_frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule_frequency'] ) ) : 'weekly';
		$schedule_items         = isset( $_POST['schedule_items'] ) && is_array( $_POST['schedule_items'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['schedule_items'] ) ) : array();
		$schedule_email         = ! empty( $_POST['schedule_email_report'] );
		$email_recipient        = isset( $_POST['schedule_email_recipient'] ) ? sanitize_email( wp_unslash( $_POST['schedule_email_recipient'] ) ) : '';
		$email_only_on_cleaned  = ! empty( $_POST['schedule_email_only_cleaned'] );

		Scheduler::save_settings( $schedule_enabled, $schedule_frequency, $schedule_items, $schedule_email, $email_recipient, $email_only_on_cleaned );

		wp_send_json_success( array( 'message' => __( 'Settings updated successfully.', 'nexura-database-cleaner' ) ) );
	}

	/**
	 * AJAX: Toggle option autoload status.
	 */
	public function ajax_toggle_autoload() {
		Capabilities::check_ajax_permissions();
		check_ajax_referer( 'nexura_database_cleaner_admin_nonce', 'nonce' );

		$option_id = isset( $_POST['option_id'] ) ? absint( wp_unslash( $_POST['option_id'] ) ) : 0;
		$autoload  = ( isset( $_POST['autoload'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['autoload'] ) ) ) ? 'yes' : 'no';

		$res = Options::set_autoload( $option_id, $autoload );
		if ( ! empty( $res['success'] ) ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( $res );
		}
	}

	/**
	 * AJAX: Delete orphaned cron event.
	 */
	public function ajax_delete_cron() {
		Capabilities::check_ajax_permissions();
		check_ajax_referer( 'nexura_database_cleaner_admin_nonce', 'nonce' );

		$hook      = isset( $_POST['hook'] ) ? sanitize_text_field( wp_unslash( $_POST['hook'] ) ) : '';
		$timestamp = isset( $_POST['timestamp'] ) ? absint( wp_unslash( $_POST['timestamp'] ) ) : 0;

		$res = Cron::remove_cron_event( $hook, $timestamp );
		if ( ! empty( $res['success'] ) ) {
			wp_send_json_success( $res );
		} else {
			wp_send_json_error( $res );
		}
	}

	/**
	 * View renderers delegating to admin controllers
	 */
	public function render_dashboard_page() {
		Dashboard::render();
	}

	public function render_scan_page() {
		Scan::render();
	}

	public function render_cleanup_page() {
		Cleanup::render();
	}

	public function render_optimization_page() {
		$tables = Optimizer::get_tables_overview();
		include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/optimize.php';
	}

	public function render_reports_page() {
		$history = Reporter::get_history( 100 );
		include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/reports.php';
	}

	public function render_settings_page() {
		Settings::render();
	}

	/**
	 * Pro tools when a license is active, otherwise the upgrade screen.
	 */
	public function render_pro_page() {
		if ( class_exists( '\NexuraDatabaseCleaner\Premium\Loader' ) ) {
			\NexuraDatabaseCleaner\Premium\Loader::render_page();
			return;
		}

		include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/pro-upgrade.php';
	}
}

<?php
/**
 * Main Plugin Coordinator Singleton
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Includes;

use NexuraDatabaseCleaner\Admin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Admin coordinator instance.
	 *
	 * @var Admin|null
	 */
	public $admin = null;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
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
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		// Automated background scheduler
		Scheduler::init();

		// Admin interface
		if ( is_admin() ) {
			require_once NEXURA_DATABASE_CLEANER_PATH . 'admin/class-admin.php';
			$this->admin = Admin::instance();
		}

		// Load premium features only inside the premium build and only with a valid license.
		$this->load_premium_modules();
	}

	/**
	 * Load premium modules securely.
	 */
	private function load_premium_modules() {
		if ( ! function_exists( 'nexura_database_cleaner_fs' ) ) {
			return;
		}

		// <fs_premium_only>
		if ( nexura_database_cleaner_fs()->can_use_premium_code() && file_exists( NEXURA_DATABASE_CLEANER_PATH . 'premium/class-premium-loader.php' ) ) {
			require_once NEXURA_DATABASE_CLEANER_PATH . 'premium/class-premium-loader.php';
			if ( class_exists( '\NexuraDatabaseCleaner\Premium\Loader' ) ) {
				\NexuraDatabaseCleaner\Premium\Loader::init();
			}
		}
		// </fs_premium_only>
	}
}

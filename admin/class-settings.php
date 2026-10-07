<?php
/**
 * Admin Settings Controller
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings {

	/**
	 * Render the Settings and Analyzers page.
	 */
	public static function render() {
		$settings = get_option( 'nexura_database_cleaner_settings', array( 'batch_size' => 100 ) );
		include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/settings.php';
	}
}

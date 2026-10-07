<?php
/**
 * Admin Dashboard Controller
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Admin;

use NexuraDatabaseCleaner\Includes\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Dashboard {

	/**
	 * Render the Dashboard page.
	 */
	public static function render() {
		$scan_data = Scanner::get_cached_or_fresh_scan();
		include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/dashboard.php';
	}
}

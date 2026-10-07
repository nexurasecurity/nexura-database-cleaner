<?php
/**
 * Admin Scan Controller
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Admin;

use NexuraDatabaseCleaner\Includes\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Scan {

	/**
	 * Render the Scan Results page.
	 */
	public static function render() {
		$scan_data = Scanner::get_cached_or_fresh_scan();
		include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/scan-results.php';
	}
}

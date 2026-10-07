<?php
/**
 * Admin Cleanup Controller
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Admin;

use NexuraDatabaseCleaner\Includes\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cleanup {

	/**
	 * Render the Cleanup & Batch Execution page.
	 */
	public static function render() {
		$scan_data = Scanner::get_cached_or_fresh_scan();
		include NEXURA_DATABASE_CLEANER_PATH . 'admin/views/cleanup-preview.php';
	}
}

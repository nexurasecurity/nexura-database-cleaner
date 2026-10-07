<?php
/**
 * Database Scanner Engine
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Includes;

use NexuraDatabaseCleaner\Modules\Optimizer\Table_Optimizer;
use NexuraDatabaseCleaner\Modules\Options\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Scanner {

	/**
	 * Run a full database scan and calculate health metrics.
	 *
	 * @return array
	 */
	public static function run_scan() {
		// Prevent PHP execution timeout on large 2GB+ databases (repot.text Section 36 & 37)
		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
			@set_time_limit( 300 );
		}
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}

		$db_stats    = Database::get_database_stats();
		$detections  = Detector::detect_all();
		$overhead    = Table_Optimizer::get_total_overhead();
		$autoload    = Options::get_autoload_stats();

		// Calculate total cleanable items and size
		$total_cleanable_items = 0;
		$total_cleanable_bytes = 0;

		$high_priority = array();
		$safe_items    = array();
		$review_items  = array();

		foreach ( $detections as $id => $item ) {
			if ( $item['count'] > 0 ) {
				$total_cleanable_items += $item['count'];
				$total_cleanable_bytes += $item['estimated_bytes'];

				if ( 0 === strpos( $id, 'orphan_' ) || 'spam_comments' === $id ) {
					$high_priority[] = $item;
				} elseif ( 'revisions' === $id || 'expired_transients' === $id || 'expired_site_transients' === $id ) {
					$safe_items[] = $item;
				} else {
					$review_items[] = $item;
				}
			}
		}

		// Calculate transparent health score
		$health_score = self::calculate_health_score( $total_cleanable_items, $overhead, $autoload['total_bytes'] );

		$scan_results = array(
			'timestamp'             => current_time( 'timestamp' ),
			'health_score'          => $health_score,
			'db_stats'              => $db_stats,
			'detections'            => $detections,
			'total_cleanable_items' => $total_cleanable_items,
			'total_cleanable_bytes' => $total_cleanable_bytes,
			'total_cleanable_human' => Database::format_size( $total_cleanable_bytes ),
			'total_overhead_bytes'  => $overhead,
			'total_overhead_human'  => Database::format_size( $overhead ),
			'autoload'              => $autoload,
			'groups'                => array(
				'high_priority' => $high_priority,
				'safe'          => $safe_items,
				'review'        => $review_items,
			),
		);

		// Store scan results for quick dashboard loading
		update_option( 'nexura_database_cleaner_last_scan_results', $scan_results, false );
		update_option( 'nexura_database_cleaner_last_scan_timestamp', current_time( 'timestamp' ), false );

		return $scan_results;
	}

	/**
	 * Get cached scan results or generate initial scan if empty.
	 *
	 * @return array
	 */
	public static function get_cached_or_fresh_scan() {
		$cached = get_option( 'nexura_database_cleaner_last_scan_results', false );
		if ( ! empty( $cached ) && is_array( $cached ) ) {
			return $cached;
		}

		return self::run_scan();
	}

	/**
	 * Transparent health score calculation (0 - 100).
	 *
	 * Formula:
	 * - Base: 100 points
	 * - Cleanable rows penalty: -1 point per 200 cleanable items (max -40 points)
	 * - Unallocated table overhead: -1 point per 2MB of table fragmentation (max -20 points)
	 * - Autoloaded options size: -1 point per 100KB over 800KB (max -20 points)
	 * Score is clamped between 10 and 100.
	 *
	 * @param int   $cleanable_count
	 * @param float $overhead_bytes
	 * @param float $autoload_bytes
	 * @return int
	 */
	public static function calculate_health_score( $cleanable_count, $overhead_bytes, $autoload_bytes ) {
		$score = 100;

		// Deduct for accumulated clutter rows
		$clutter_deduction = min( 40, (int) floor( $cleanable_count / 200 ) );
		$score -= $clutter_deduction;

		// Deduct for fragmentation overhead
		$overhead_mb = $overhead_bytes / ( 1024 * 1024 );
		$overhead_deduction = min( 20, (int) floor( $overhead_mb / 2 ) );
		$score -= $overhead_deduction;

		// Deduct for excessive autoload size (WordPress standard guideline suggests < 800KB)
		$autoload_kb = $autoload_bytes / 1024;
		if ( $autoload_kb > 800 ) {
			$autoload_excess_deduction = min( 20, (int) floor( ( $autoload_kb - 800 ) / 100 ) );
			$score -= $autoload_excess_deduction;
		}

		return max( 10, min( 100, $score ) );
	}
}

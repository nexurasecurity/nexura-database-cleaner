<?php
/**
 * Transients cleaner module (Expired transients and site transients)
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Modules\Transients;

use NexuraDatabaseCleaner\Includes\Safety;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery
class Transients {

	/**
	 * Count expired transients.
	 *
	 * @return int
	 */
	public static function count_expired_transients() {
		global $wpdb;
		$time = time();

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				$time
			)
		);
	}

	/**
	 * Count expired site transients.
	 *
	 * @return int
	 */
	public static function count_expired_site_transients() {
		global $wpdb;
		$time = time();

		// In multisite, site transients are in sitemeta
		if ( is_multisite() ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND meta_value < %d",
					$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
					$time
				)
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
				$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
				$time
			)
		);
	}

	/**
	 * Preview expired transients.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_expired_transients( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		$time  = time();

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_id, option_name, option_value, (%d - option_value) AS expired_seconds_ago FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d ORDER BY option_id DESC LIMIT %d",
				$time,
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				$time,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Clean a batch of expired transients.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_expired_transients( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );
		$time       = time();

		$transient_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d LIMIT %d",
				$wpdb->esc_like( '_transient_timeout_' ) . '%',
				$time,
				$batch_size
			)
		);

		if ( empty( $transient_names ) ) {
			return 0;
		}

		$cleaned = 0;
		foreach ( $transient_names as $timeout_key ) {
			// Extract actual transient name: _transient_timeout_foo -> foo
			$transient_name = str_replace( '_transient_timeout_', '', $timeout_key );

			delete_transient( $transient_name );
			// Direct fallback cleanup for orphaned timeout keys
			$data_key = '_transient_' . $transient_name;
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name IN (%s, %s)",
					$timeout_key,
					$data_key
				)
			);
			$cleaned++;
		}

		return $cleaned;
	}

	/**
	 * Clean a batch of expired site transients.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_expired_site_transients( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );
		$time       = time();

		if ( is_multisite() ) {
			$keys = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT meta_key FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s AND meta_value < %d LIMIT %d",
					$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
					$time,
					$batch_size
				)
			);

			if ( empty( $keys ) ) {
				return 0;
			}

			$cleaned = 0;
			foreach ( $keys as $timeout_key ) {
				$transient_name = str_replace( '_site_transient_timeout_', '', $timeout_key );
				delete_site_transient( $transient_name );
				$data_key = '_site_transient_' . $transient_name;
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->sitemeta} WHERE meta_key IN (%s, %s)",
						$timeout_key,
						$data_key
					)
				);
				$cleaned++;
			}
			return $cleaned;
		}

		// Single site: site transients live in options table
		$keys = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d LIMIT %d",
				$wpdb->esc_like( '_site_transient_timeout_' ) . '%',
				$time,
				$batch_size
			)
		);

		if ( empty( $keys ) ) {
			return 0;
		}

		$cleaned = 0;
		foreach ( $keys as $timeout_key ) {
			$transient_name = str_replace( '_site_transient_timeout_', '', $timeout_key );
			delete_site_transient( $transient_name );
			$data_key = '_site_transient_' . $transient_name;
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name IN (%s, %s)",
					$timeout_key,
					$data_key
				)
			);
			$cleaned++;
		}

		return $cleaned;
	}
}

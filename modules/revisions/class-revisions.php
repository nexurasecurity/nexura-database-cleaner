<?php
/**
 * Post Revisions, Auto-drafts, and Trash cleaner module
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Modules\Revisions;

use NexuraDatabaseCleaner\Includes\Safety;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery
class Revisions {

	/**
	 * Count post revisions.
	 *
	 * @return int
	 */
	public static function count_revisions() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'" );
	}

	/**
	 * Count auto-draft posts.
	 *
	 * @return int
	 */
	public static function count_auto_drafts() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'" );
	}

	/**
	 * Count trashed posts.
	 *
	 * @return int
	 */
	public static function count_trashed_posts() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'" );
	}

	/**
	 * Preview revisions.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_revisions( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_parent, post_date, post_modified FROM {$wpdb->posts} WHERE post_type = 'revision' ORDER BY ID DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Preview auto-drafts.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_auto_drafts( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_date FROM {$wpdb->posts} WHERE post_status = 'auto-draft' ORDER BY ID DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Preview trashed posts.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_trashed_posts( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, post_type, post_modified FROM {$wpdb->posts} WHERE post_status = 'trash' ORDER BY ID DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Clean a batch of post revisions.
	 *
	 * @param int $batch_size
	 * @return int Number of rows cleaned.
	 */
	public static function clean_revisions( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision' LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$cleaned = 0;
		foreach ( $ids as $post_id ) {
			// Use WordPress core API exclusively per repot.text Section 10
			$deleted = wp_delete_post_revision( (int) $post_id );
			if ( $deleted instanceof \WP_Post || ! empty( $deleted ) ) {
				$cleaned++;
			} else {
				// Log failure and skip item - never execute raw SQL fallback
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( sprintf( 'Database Cleaner: Could not safely remove revision ID %d via wp_delete_post_revision. Skipped.', (int) $post_id ) );
				}
			}
		}

		return $cleaned;
	}

	/**
	 * Clean a batch of auto drafts.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_auto_drafts( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'auto-draft' LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$cleaned = 0;
		foreach ( $ids as $post_id ) {
			if ( wp_delete_post( (int) $post_id, true ) ) {
				$cleaned++;
			}
		}

		return $cleaned;
	}

	/**
	 * Clean a batch of trashed posts.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_trashed_posts( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'trash' LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$cleaned = 0;
		foreach ( $ids as $post_id ) {
			if ( wp_delete_post( (int) $post_id, true ) ) {
				$cleaned++;
			}
		}

		return $cleaned;
	}
}

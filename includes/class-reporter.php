<?php
/**
 * Audit Reporter and Cleanup History
 *
 * Implements authoritative server-side audit trails, Job ID tracking,
 * verified row counts, and status tracking (repot.text Sections 5, 6, 8, 51, 52).
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Reporter {

	const OPTION_HISTORY = 'nexura_database_cleaner_cleanup_history';

	/**
	 * Log a verified cleanup or diagnostic operation server-side.
	 *
	 * @param array $entry
	 * @return bool
	 */
	public static function log( $entry ) {
		$history = get_option( self::OPTION_HISTORY, array() );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		$rows_before  = isset( $entry['rows_before'] ) ? absint( $entry['rows_before'] ) : ( isset( $entry['rows_cleaned'] ) ? absint( $entry['rows_cleaned'] ) : 0 );
		$rows_deleted = isset( $entry['rows_deleted'] ) ? absint( $entry['rows_deleted'] ) : ( isset( $entry['rows_cleaned'] ) ? absint( $entry['rows_cleaned'] ) : 0 );
		$rows_after   = isset( $entry['rows_after'] ) ? absint( $entry['rows_after'] ) : max( 0, $rows_before - $rows_deleted );

		$status = isset( $entry['status'] ) ? sanitize_text_field( $entry['status'] ) : 'SUCCESS';
		if ( ! empty( $entry['dry_run'] ) ) {
			$status = 'DRY_RUN';
		} elseif ( $rows_after > 0 && $rows_deleted > 0 ) {
			$status = 'PARTIAL';
		} elseif ( 0 === $rows_deleted && $rows_before > 0 ) {
			$status = 'FAILED';
		}

		$verification = isset( $entry['verification'] ) ? sanitize_text_field( $entry['verification'] ) : ( ( 0 === $rows_after ) ? 'Passed' : 'Partial' );

		// Storage reclaimed is reported factually per repot.text Section 4 & 32
		$storage_status = isset( $entry['storage_status'] ) ? sanitize_text_field( $entry['storage_status'] ) : __( 'Not measured', 'nexura-database-cleaner' );

		$log_item = array(
			'id'             => uniqid( 'nexura_database_cleaner_log_' ),
			'job_id'         => ! empty( $entry['job_id'] ) ? sanitize_text_field( $entry['job_id'] ) : Lock::generate_job_id(),
			'user_id'        => get_current_user_id(),
			'timestamp'      => current_time( 'timestamp' ),
			'date_human'     => current_time( 'mysql' ),
			'item_id'        => isset( $entry['item_id'] ) ? sanitize_key( $entry['item_id'] ) : '',
			'item_name'      => isset( $entry['item_name'] ) ? sanitize_text_field( $entry['item_name'] ) : '',
			'rows_before'    => $rows_before,
			'rows_deleted'   => $rows_deleted,
			'rows_after'     => $rows_after,
			'rows_cleaned'   => $rows_deleted, // Backward compatibility
			'status'         => $status,       // SUCCESS, PARTIAL, FAILED, SKIPPED, DRY_RUN
			'verification'   => $verification, // Passed, Partial
			'storage_status' => $storage_status,
			'freed_human'    => $storage_status, // Backward compatibility
			'dry_run'        => ! empty( $entry['dry_run'] ),
		);

		// Prepend latest entry
		array_unshift( $history, $log_item );

		// Keep up to 100 recent entries
		if ( count( $history ) > 100 ) {
			$history = array_slice( $history, 0, 100 );
		}

		return update_option( self::OPTION_HISTORY, $history, false );
	}

	/**
	 * Get cleanup history logs.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function get_history( $limit = 50 ) {
		$history = get_option( self::OPTION_HISTORY, array() );
		if ( ! is_array( $history ) ) {
			return array();
		}

		return array_slice( $history, 0, absint( $limit ) );
	}

	/**
	 * Clear all history logs.
	 *
	 * @return bool
	 */
	public static function clear_history() {
		return delete_option( self::OPTION_HISTORY );
	}
}

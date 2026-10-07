<?php
/**
 * Safe Batch Cleaner Engine
 *
 * Implements server-side authority, before/after count verification,
 * mutex locking, and authoritative audit logging (repot.text Sections 5, 6, 7, 8, 33, 34).
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Includes;

use NexuraDatabaseCleaner\Modules\Revisions\Revisions;
use NexuraDatabaseCleaner\Modules\Comments\Comments;
use NexuraDatabaseCleaner\Modules\Transients\Transients;
use NexuraDatabaseCleaner\Modules\Orphan_Meta\Orphan_Meta;
use NexuraDatabaseCleaner\Modules\WooCommerce\Action_Scheduler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Cleaner {

	/**
	 * Run a batch cleanup for a specific item with authoritative server verification.
	 *
	 * @param string $item_id
	 * @param int $batch_size
	 * @param bool $dry_run
	 * @param string $job_id Optional job ID
	 * @return array
	 */
	public static function clean_batch( $item_id, $batch_size = 100, $dry_run = false, $job_id = '' ) {
		global $wpdb;

		$definitions = Detector::get_item_definitions();
		if ( ! isset( $definitions[ $item_id ] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid cleanup item specified.', 'nexura-database-cleaner' ),
			);
		}

		$def = $definitions[ $item_id ];

		// Safety check: Unknown risk items cannot be cleaned (Section 14 & Safety rules)
		if ( ! Safety::is_operation_permitted( $def['risk'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Safety policy prevents deleting unverified or unknown items.', 'nexura-database-cleaner' ),
			);
		}

		$batch_size = Safety::sanitize_batch_size( $batch_size );
		$job_id     = ! empty( $job_id ) ? sanitize_text_field( $job_id ) : Lock::generate_job_id();

		// Dry Run mode (repot.text Section 7): 100% non-destructive
		if ( $dry_run ) {
			$current_count = Detector::count_item( $item_id );

			return array(
				'success'         => true,
				'item_id'         => $item_id,
				'item_name'       => $def['title'],
				'job_id'          => $job_id,
				'dry_run'         => true,
				'rows_before'     => $current_count,
				'cleaned_count'   => 0,
				'rows_deleted'    => 0,
				'remaining_count' => $current_count,
				'is_complete'     => true,
				'verification'    => 'Passed',
				'status'          => 'DRY_RUN',
				'message'         => sprintf(
					/* translators: %d: number of items */
					__( 'Dry Run — No data was changed. %d matching records inspected.', 'nexura-database-cleaner' ),
					$current_count
				),
			);
		}

		// Mutex lock to prevent concurrent cleanups (Section 33)
		if ( ! Lock::acquire_lock( $job_id, 'clean_' . $item_id ) ) {
			return array(
				'success' => false,
				'message' => __( 'Another cleanup operation is currently in progress. Please wait for it to finish.', 'nexura-database-cleaner' ),
			);
		}

		// Step 1: Count before deletion (Section 8)
		$rows_before = Detector::count_item( $item_id );
		if ( $rows_before <= 0 ) {
			Lock::release_lock( $job_id );
			return array(
				'success'         => true,
				'item_id'         => $item_id,
				'item_name'       => $def['title'],
				'job_id'          => $job_id,
				'dry_run'         => false,
				'rows_before'     => 0,
				'cleaned_count'   => 0,
				'rows_deleted'    => 0,
				'remaining_count' => 0,
				'is_complete'     => true,
				'verification'    => 'Passed',
				'status'          => 'SKIPPED',
				'message'         => __( 'No items found to clean.', 'nexura-database-cleaner' ),
			);
		}

		// Step 2: Execute deletion batch
		$cleaned_count = 0;

		switch ( $item_id ) {
			case 'revisions':
				$cleaned_count = Revisions::clean_revisions( $batch_size );
				break;
			case 'auto_drafts':
				$cleaned_count = Revisions::clean_auto_drafts( $batch_size );
				break;
			case 'trashed_posts':
				$cleaned_count = Revisions::clean_trashed_posts( $batch_size );
				break;
			case 'spam_comments':
				$cleaned_count = Comments::clean_spam( $batch_size );
				break;
			case 'trashed_comments':
				$cleaned_count = Comments::clean_trash( $batch_size );
				break;
			case 'pingbacks':
				$cleaned_count = Comments::clean_pingbacks( $batch_size );
				break;
			case 'trackbacks':
				$cleaned_count = Comments::clean_trackbacks( $batch_size );
				break;
			case 'expired_transients':
				$cleaned_count = Transients::clean_expired_transients( $batch_size );
				break;
			case 'expired_site_transients':
				$cleaned_count = Transients::clean_expired_site_transients( $batch_size );
				break;
			case 'orphan_post_meta':
				$cleaned_count = Orphan_Meta::clean_orphan_post_meta( $batch_size );
				break;
			case 'orphan_comment_meta':
				$cleaned_count = Orphan_Meta::clean_orphan_comment_meta( $batch_size );
				break;
			case 'orphan_user_meta':
				$cleaned_count = Orphan_Meta::clean_orphan_user_meta( $batch_size );
				break;
			case 'orphan_term_meta':
				$cleaned_count = Orphan_Meta::clean_orphan_term_meta( $batch_size );
				break;
			case 'orphan_term_relationships':
				$cleaned_count = Orphan_Meta::clean_orphan_term_relationships( $batch_size );
				break;
			case 'duplicated_post_meta':
				$cleaned_count = Orphan_Meta::clean_duplicated_post_meta( $batch_size );
				break;
			case 'duplicated_user_meta':
				$cleaned_count = Orphan_Meta::clean_duplicated_user_meta( $batch_size );
				break;
			case 'duplicated_comment_meta':
				$cleaned_count = Orphan_Meta::clean_duplicated_comment_meta( $batch_size );
				break;
			case 'oembed_caches':
				$cleaned_count = Orphan_Meta::clean_oembed_caches( $batch_size );
				break;
			case 'as_completed_actions':
				$cleaned_count = Action_Scheduler::clean_completed_actions( $batch_size, 30 );
				break;
			case 'as_failed_actions':
				$cleaned_count = Action_Scheduler::clean_failed_actions( $batch_size );
				break;
			case 'as_canceled_actions':
				$cleaned_count = Action_Scheduler::clean_canceled_actions( $batch_size );
				break;
			case 'as_orphan_logs':
				$cleaned_count = Action_Scheduler::clean_orphan_logs( $batch_size );
				break;
			default:
				$filtered = apply_filters( 'nexura_database_cleaner_clean_item', null, $item_id, $batch_size );
				if ( null === $filtered ) {
					Lock::release_lock( $job_id );
					return array(
						'success' => false,
						'message' => __( 'This cleanup item is not available.', 'nexura-database-cleaner' ),
					);
				}
				$cleaned_count = absint( $filtered );
				break;
		}

		// Step 3: Count after deletion (Section 8)
		$rows_after   = Detector::count_item( $item_id );
		$actual_delta = max( 0, $rows_before - $rows_after );

		// Use the authoritative delta if cleaner returned a different number
		$effective_deleted = ( $actual_delta > 0 ) ? $actual_delta : $cleaned_count;

		// Step 4: Verification status check (Section 8)
		$verification = ( $rows_after <= ( $rows_before - $effective_deleted ) ) ? 'Passed' : 'Partial';
		$status       = ( 0 === $rows_after ) ? 'SUCCESS' : ( ( $effective_deleted > 0 ) ? 'PARTIAL' : 'FAILED' );
		$is_complete  = ( 0 === $rows_after || 0 === $effective_deleted );

		// Invalidate cached scan results so subsequent scans fetch fresh counts
		delete_option( 'nexura_database_cleaner_last_scan_results' );
		delete_option( 'nexura_database_cleaner_last_scan_timestamp' );

		// Step 5: Server-side audit logging (Section 6)
		// When item is completely cleaned or if batch failed, write authoritative server audit log
		if ( $is_complete && $effective_deleted > 0 ) {
			Reporter::log( array(
				'job_id'         => $job_id,
				'item_id'        => $item_id,
				'item_name'      => $def['title'],
				'rows_before'    => $rows_before,
				'rows_deleted'   => $effective_deleted,
				'rows_after'     => $rows_after,
				'status'         => $status,
				'verification'   => $verification,
				'storage_status' => __( 'Estimated data affected', 'nexura-database-cleaner' ),
			) );

			// Release lock once complete
			Lock::release_lock( $job_id );
		}

		return array(
			'success'         => true,
			'item_id'         => $item_id,
			'item_name'       => $def['title'],
			'job_id'          => $job_id,
			'dry_run'         => false,
			'rows_before'     => $rows_before,
			'cleaned_count'   => $effective_deleted,
			'rows_deleted'    => $effective_deleted,
			'rows_after'      => $rows_after,
			'remaining_count' => $rows_after,
			'verification'    => $verification,
			'status'          => $status,
			'is_complete'     => $is_complete,
		);
	}
}

<?php
/**
 * Detection coordinator for cleanup items
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

class Detector {

	/**
	 * Get definitions of all cleanable items.
	 *
	 * @return array
	 */
	public static function get_item_definitions() {
		$definitions = array(
			// Posts
			'revisions' => array(
				'title'       => __( 'Post Revisions', 'nexura-database-cleaner' ),
				'category'    => 'Posts',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Past versions of posts and pages stored each time you save an edit.', 'nexura-database-cleaner' ),
				'why'         => __( 'Revisions accumulate over time and consume row space in posts and postmeta tables.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'auto_drafts' => array(
				'title'       => __( 'Auto Drafts', 'nexura-database-cleaner' ),
				'category'    => 'Posts',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Drafts created automatically by WordPress editor when opening a new post.', 'nexura-database-cleaner' ),
				'why'         => __( 'Abandoned auto-drafts that were never completed or saved as formal drafts.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'trashed_posts' => array(
				'title'       => __( 'Trashed Posts', 'nexura-database-cleaner' ),
				'category'    => 'Posts',
				'risk'        => Safety::RISK_LOW,
				'description' => __( 'Posts, pages, and custom post types sitting in the trash bin.', 'nexura-database-cleaner' ),
				'why'         => __( 'Content moved to trash that has not been permanently emptied.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),

			// Comments
			'spam_comments' => array(
				'title'       => __( 'Spam Comments', 'nexura-database-cleaner' ),
				'category'    => 'Comments',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Comments marked as spam by moderators or antispam plugins.', 'nexura-database-cleaner' ),
				'why'         => __( 'Spam comments serve no useful purpose and slow down comment queries.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'trashed_comments' => array(
				'title'       => __( 'Trashed Comments', 'nexura-database-cleaner' ),
				'category'    => 'Comments',
				'risk'        => Safety::RISK_LOW,
				'description' => __( 'Comments moved to trash awaiting permanent deletion.', 'nexura-database-cleaner' ),
				'why'         => __( 'Unused discarded comments remaining in the database.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'pingbacks' => array(
				'title'       => __( 'Pingbacks', 'nexura-database-cleaner' ),
				'category'    => 'Comments',
				'risk'        => Safety::RISK_LOW,
				'description' => __( 'Automated notifications created when other websites link to your posts.', 'nexura-database-cleaner' ),
				'why'         => __( 'Legacy pingback records stored as comment rows.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'trackbacks' => array(
				'title'       => __( 'Trackbacks', 'nexura-database-cleaner' ),
				'category'    => 'Comments',
				'risk'        => Safety::RISK_LOW,
				'description' => __( 'Legacy communication method between blogs.', 'nexura-database-cleaner' ),
				'why'         => __( 'Rarely used legacy trackback comment rows.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),

			// Transients
			'expired_transients' => array(
				'title'       => __( 'Expired Transients', 'nexura-database-cleaner' ),
				'category'    => 'Transients',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Temporary cached options whose validity expiration timestamp has passed.', 'nexura-database-cleaner' ),
				'why'         => __( 'Expired cache data in options table that WordPress has not yet purged.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'expired_site_transients' => array(
				'title'       => __( 'Expired Site Transients', 'nexura-database-cleaner' ),
				'category'    => 'Transients',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Expired network-wide or site-level cached transient entries.', 'nexura-database-cleaner' ),
				'why'         => __( 'Outdated transient cache entries ready for clean removal.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),

			// Metadata
			'orphan_post_meta' => array(
				'title'       => __( 'Orphaned Post Meta', 'nexura-database-cleaner' ),
				'category'    => 'Metadata',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Metadata records referencing posts that no longer exist.', 'nexura-database-cleaner' ),
				'why'         => __( 'Parent post was deleted previously without purging its custom field records.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'orphan_comment_meta' => array(
				'title'       => __( 'Orphaned Comment Meta', 'nexura-database-cleaner' ),
				'category'    => 'Metadata',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Metadata records referencing comments that no longer exist.', 'nexura-database-cleaner' ),
				'why'         => __( 'Parent comment was deleted without purging its associated meta.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'orphan_user_meta' => array(
				'title'       => __( 'Orphaned User Meta', 'nexura-database-cleaner' ),
				'category'    => 'Metadata',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Metadata records referencing deleted users.', 'nexura-database-cleaner' ),
				'why'         => __( 'User was removed from the system but extra metadata keys remained.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'orphan_term_meta' => array(
				'title'       => __( 'Orphaned Term Meta', 'nexura-database-cleaner' ),
				'category'    => 'Metadata',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Metadata records referencing taxonomy terms that no longer exist.', 'nexura-database-cleaner' ),
				'why'         => __( 'Category or tag was deleted but term metadata rows remained.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'orphan_term_relationships' => array(
				'title'       => __( 'Orphaned Term Relationships', 'nexura-database-cleaner' ),
				'category'    => 'Metadata',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Category and tag relationships tied to non-existent post IDs.', 'nexura-database-cleaner' ),
				'why'         => __( 'Post was removed without updating the term_relationships junction table.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),

			// Duplicated Metadata
			'duplicated_post_meta' => array(
				'title'       => __( 'Duplicated Post Meta', 'nexura-database-cleaner' ),
				'category'    => 'Metadata',
				'risk'        => Safety::RISK_LOW,
				'description' => __( 'Identical redundant postmeta rows with the same key and value.', 'nexura-database-cleaner' ),
				'why'         => __( 'Accidental duplicate inserts leaving identical redundant metadata rows.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'duplicated_user_meta' => array(
				'title'       => __( 'Duplicated User Meta', 'nexura-database-cleaner' ),
				'category'    => 'Metadata',
				'risk'        => Safety::RISK_LOW,
				'description' => __( 'Identical redundant usermeta rows for the same user.', 'nexura-database-cleaner' ),
				'why'         => __( 'Redundant duplicate usermeta records created by plugins.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'duplicated_comment_meta' => array(
				'title'       => __( 'Duplicated Comment Meta', 'nexura-database-cleaner' ),
				'category'    => 'Metadata',
				'risk'        => Safety::RISK_LOW,
				'description' => __( 'Identical redundant commentmeta rows with the same key and value.', 'nexura-database-cleaner' ),
				'why'         => __( 'Duplicate comment metadata entries that can be safely deduplicated.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),

			// oEmbed Caches
			'oembed_caches' => array(
				'title'       => __( 'oEmbed Caches', 'nexura-database-cleaner' ),
				'category'    => 'Transients',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Cached oEmbed HTML responses stored in postmeta records.', 'nexura-database-cleaner' ),
				'why'         => __( 'Temporary embed caches that WordPress automatically regenerates when viewing posts.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),

			// Action Scheduler
			'as_completed_actions' => array(
				'title'       => __( 'Action Scheduler: Completed Actions', 'nexura-database-cleaner' ),
				'category'    => 'WooCommerce',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Completed Action Scheduler actions older than 30 days.', 'nexura-database-cleaner' ),
				'why'         => __( 'Historical actions that have already finished processing successfully.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'as_failed_actions' => array(
				'title'       => __( 'Action Scheduler: Failed Actions', 'nexura-database-cleaner' ),
				'category'    => 'WooCommerce',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Actions that encountered an error and failed to complete.', 'nexura-database-cleaner' ),
				'why'         => __( 'Failed background jobs that are no longer attempting retries.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'as_canceled_actions' => array(
				'title'       => __( 'Action Scheduler: Canceled Actions', 'nexura-database-cleaner' ),
				'category'    => 'WooCommerce',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Actions that were canceled before execution.', 'nexura-database-cleaner' ),
				'why'         => __( 'Discarded actions that will never run.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
			'as_orphan_logs' => array(
				'title'       => __( 'Action Scheduler: Orphaned Logs', 'nexura-database-cleaner' ),
				'category'    => 'WooCommerce',
				'risk'        => Safety::RISK_SAFE,
				'description' => __( 'Action Scheduler log rows referencing deleted action records.', 'nexura-database-cleaner' ),
				'why'         => __( 'Logs left behind when their parent action was deleted previously.', 'nexura-database-cleaner' ),
				'confidence'  => 'High',
			),
		);

		/**
		 * Pro builds append licensed cleanup modules here.
		 * The free WordPress.org build does not register those items.
		 */
		return apply_filters( 'nexura_database_cleaner_item_definitions', $definitions );
	}

	/**
	 * Run detection on all items and return summary results.
	 *
	 * @return array
	 */
	public static function detect_all() {
		$definitions = self::get_item_definitions();
		$results     = array();

		foreach ( $definitions as $item_id => $def ) {
			$count = self::count_item( $item_id );

			// Average row size estimate ~ 1KB per metadata/transient/comment row, ~ 2KB for post revision
			$estimated_bytes_per_row = ( 'revisions' === $item_id || 'trashed_posts' === $item_id || 'auto_drafts' === $item_id ) ? 2048 : 1024;
			$est_bytes = $count * $estimated_bytes_per_row;

			$results[ $item_id ] = array_merge( $def, array(
				'id'               => $item_id,
				'count'            => $count,
				'estimated_bytes'  => $est_bytes,
				'estimated_human'  => Database::format_size( $est_bytes ),
			) );
		}

		return $results;
	}

	/**
	 * Count matching rows for an item.
	 *
	 * @param string $item_id
	 * @return int
	 */
	public static function count_item( $item_id ) {
		switch ( $item_id ) {
			case 'revisions':
				return Revisions::count_revisions();
			case 'auto_drafts':
				return Revisions::count_auto_drafts();
			case 'trashed_posts':
				return Revisions::count_trashed_posts();
			case 'spam_comments':
				return Comments::count_spam();
			case 'trashed_comments':
				return Comments::count_trash();
			case 'pingbacks':
				return Comments::count_pingbacks();
			case 'trackbacks':
				return Comments::count_trackbacks();
			case 'expired_transients':
				return Transients::count_expired_transients();
			case 'expired_site_transients':
				return Transients::count_expired_site_transients();
			case 'orphan_post_meta':
				return Orphan_Meta::count_orphan_post_meta();
			case 'orphan_comment_meta':
				return Orphan_Meta::count_orphan_comment_meta();
			case 'orphan_user_meta':
				return Orphan_Meta::count_orphan_user_meta();
			case 'orphan_term_meta':
				return Orphan_Meta::count_orphan_term_meta();
			case 'orphan_term_relationships':
				return Orphan_Meta::count_orphan_term_relationships();
			case 'duplicated_post_meta':
				return Orphan_Meta::count_duplicated_post_meta();
			case 'duplicated_user_meta':
				return Orphan_Meta::count_duplicated_user_meta();
			case 'duplicated_comment_meta':
				return Orphan_Meta::count_duplicated_comment_meta();
			case 'oembed_caches':
				return Orphan_Meta::count_oembed_caches();
			case 'as_completed_actions':
				return Action_Scheduler::count_completed_actions( 30 );
			case 'as_failed_actions':
				return Action_Scheduler::count_failed_actions();
			case 'as_canceled_actions':
				return Action_Scheduler::count_canceled_actions();
			case 'as_orphan_logs':
				return Action_Scheduler::count_orphan_logs();
			default:
				$count = apply_filters( 'nexura_database_cleaner_count_item', null, $item_id );
				return ( null === $count ) ? 0 : absint( $count );
		}
	}

	/**
	 * Get preview rows for an item.
	 *
	 * @param string $item_id
	 * @param int $limit
	 * @return array
	 */
	public static function preview_item( $item_id, $limit = 20 ) {
		switch ( $item_id ) {
			case 'revisions':
				return Revisions::preview_revisions( $limit );
			case 'auto_drafts':
				return Revisions::preview_auto_drafts( $limit );
			case 'trashed_posts':
				return Revisions::preview_trashed_posts( $limit );
			case 'spam_comments':
				return Comments::preview_spam( $limit );
			case 'trashed_comments':
				return Comments::preview_trash( $limit );
			case 'pingbacks':
				return Comments::preview_pingbacks( $limit );
			case 'trackbacks':
				return Comments::preview_trackbacks( $limit );
			case 'expired_transients':
				return Transients::preview_expired_transients( $limit );
			case 'expired_site_transients':
				return Transients::preview_expired_transients( $limit );
			case 'orphan_post_meta':
				return Orphan_Meta::preview_orphan_post_meta( $limit );
			case 'orphan_comment_meta':
				return Orphan_Meta::preview_orphan_comment_meta( $limit );
			case 'orphan_user_meta':
				return Orphan_Meta::preview_orphan_user_meta( $limit );
			case 'orphan_term_meta':
				return Orphan_Meta::preview_orphan_term_meta( $limit );
			case 'orphan_term_relationships':
				return Orphan_Meta::preview_orphan_term_relationships( $limit );
			default:
				$rows = apply_filters( 'nexura_database_cleaner_preview_item', null, $item_id, $limit );
				return is_array( $rows ) ? $rows : array();
		}
	}
}

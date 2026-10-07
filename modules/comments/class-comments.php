<?php
/**
 * Comments cleaner module (Spam, Trash, Pingbacks, Trackbacks)
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Modules\Comments;

use NexuraDatabaseCleaner\Includes\Safety;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery
class Comments {

	/**
	 * Count spam comments.
	 *
	 * @return int
	 */
	public static function count_spam() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'" );
	}

	/**
	 * Count trashed comments.
	 *
	 * @return int
	 */
	public static function count_trash() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'" );
	}

	/**
	 * Count pingbacks.
	 *
	 * @return int
	 */
	public static function count_pingbacks() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'pingback'" );
	}

	/**
	 * Count trackbacks.
	 *
	 * @return int
	 */
	public static function count_trackbacks() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'trackback'" );
	}

	/**
	 * Preview spam comments.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_spam( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT comment_ID, comment_author, comment_author_email, comment_date, SUBSTRING(comment_content, 1, 100) AS snippet FROM {$wpdb->comments} WHERE comment_approved = 'spam' ORDER BY comment_ID DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Preview trashed comments.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_trash( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT comment_ID, comment_author, comment_date, SUBSTRING(comment_content, 1, 100) AS snippet FROM {$wpdb->comments} WHERE comment_approved = 'trash' ORDER BY comment_ID DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Preview pingbacks.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_pingbacks( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT comment_ID, comment_author, comment_author_url, comment_date FROM {$wpdb->comments} WHERE comment_type = 'pingback' ORDER BY comment_ID DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Preview trackbacks.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_trackbacks( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT comment_ID, comment_author, comment_author_url, comment_date FROM {$wpdb->comments} WHERE comment_type = 'trackback' ORDER BY comment_ID DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Clean a batch of spam comments.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_spam( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = 'spam' LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$cleaned = 0;
		foreach ( $ids as $comment_id ) {
			if ( wp_delete_comment( (int) $comment_id, true ) ) {
				$cleaned++;
			}
		}

		return $cleaned;
	}

	/**
	 * Clean a batch of trashed comments.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_trash( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = 'trash' LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$cleaned = 0;
		foreach ( $ids as $comment_id ) {
			if ( wp_delete_comment( (int) $comment_id, true ) ) {
				$cleaned++;
			}
		}

		return $cleaned;
	}

	/**
	 * Clean a batch of pingbacks.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_pingbacks( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT comment_ID FROM {$wpdb->comments} WHERE comment_type = 'pingback' LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$cleaned = 0;
		foreach ( $ids as $comment_id ) {
			if ( wp_delete_comment( (int) $comment_id, true ) ) {
				$cleaned++;
			}
		}

		return $cleaned;
	}

	/**
	 * Clean a batch of trackbacks.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_trackbacks( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT comment_ID FROM {$wpdb->comments} WHERE comment_type = 'trackback' LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$cleaned = 0;
		foreach ( $ids as $comment_id ) {
			if ( wp_delete_comment( (int) $comment_id, true ) ) {
				$cleaned++;
			}
		}

		return $cleaned;
	}
}

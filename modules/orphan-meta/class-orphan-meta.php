<?php
/**
 * Orphan metadata and relationships cleaner module
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Modules\Orphan_Meta;

use NexuraDatabaseCleaner\Includes\Safety;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery
class Orphan_Meta {

	/**
	 * Count orphaned post metadata.
	 *
	 * @return int
	 */
	public static function count_orphan_post_meta() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} pm
			 LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			 WHERE p.ID IS NULL"
		);
	}

	/**
	 * Count orphaned comment metadata.
	 *
	 * @return int
	 */
	public static function count_orphan_comment_meta() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->commentmeta} cm
			 LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
			 WHERE c.comment_ID IS NULL"
		);
	}

	/**
	 * Count orphaned user metadata.
	 *
	 * @return int
	 */
	public static function count_orphan_user_meta() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->usermeta} um
			 LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
			 WHERE u.ID IS NULL"
		);
	}

	/**
	 * Count orphaned term metadata.
	 *
	 * @return int
	 */
	public static function count_orphan_term_meta() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->termmeta} tm
			 LEFT JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
			 WHERE t.term_id IS NULL"
		);
	}

	/**
	 * Count orphaned term relationships.
	 *
	 * @return int
	 */
	public static function count_orphan_term_relationships() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->term_relationships} tr
			 LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			 WHERE p.ID IS NULL"
		);
	}

	/**
	 * Preview orphaned post metadata.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_orphan_post_meta( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_id, pm.post_id, pm.meta_key, SUBSTRING(pm.meta_value, 1, 100) AS meta_value
				 FROM {$wpdb->postmeta} pm
				 LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				 WHERE p.ID IS NULL
				 ORDER BY pm.meta_id DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Preview orphaned comment metadata.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_orphan_comment_meta( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT cm.meta_id, cm.comment_id, cm.meta_key, SUBSTRING(cm.meta_value, 1, 100) AS meta_value
				 FROM {$wpdb->commentmeta} cm
				 LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
				 WHERE c.comment_ID IS NULL
				 ORDER BY cm.meta_id DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Preview orphaned user metadata.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_orphan_user_meta( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT um.umeta_id, um.user_id, um.meta_key, SUBSTRING(um.meta_value, 1, 100) AS meta_value
				 FROM {$wpdb->usermeta} um
				 LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
				 WHERE u.ID IS NULL
				 ORDER BY um.umeta_id DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Preview orphaned term metadata.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_orphan_term_meta( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tm.meta_id, tm.term_id, tm.meta_key, SUBSTRING(tm.meta_value, 1, 100) AS meta_value
				 FROM {$wpdb->termmeta} tm
				 LEFT JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
				 WHERE t.term_id IS NULL
				 ORDER BY tm.meta_id DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Preview orphaned term relationships.
	 *
	 * @param int $limit
	 * @return array
	 */
	public static function preview_orphan_term_relationships( $limit = 20 ) {
		global $wpdb;
		$limit = absint( $limit );
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tr.object_id, tr.term_taxonomy_id
				 FROM {$wpdb->term_relationships} tr
				 LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
				 WHERE p.ID IS NULL
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Clean a batch of orphaned post metadata.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_orphan_post_meta( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$meta_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT pm.meta_id FROM {$wpdb->postmeta} pm
				 LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
				 WHERE p.ID IS NULL
				 LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $meta_ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $meta_ids ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_id IN ({$id_list})" );
	}

	/**
	 * Clean a batch of orphaned comment metadata.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_orphan_comment_meta( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$meta_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT cm.meta_id FROM {$wpdb->commentmeta} cm
				 LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
				 WHERE c.comment_ID IS NULL
				 LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $meta_ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $meta_ids ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE meta_id IN ({$id_list})" );
	}

	/**
	 * Clean a batch of orphaned user metadata.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_orphan_user_meta( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$meta_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT um.umeta_id FROM {$wpdb->usermeta} um
				 LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
				 WHERE u.ID IS NULL
				 LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $meta_ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $meta_ids ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE umeta_id IN ({$id_list})" );
	}

	/**
	 * Clean a batch of orphaned term metadata.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_orphan_term_meta( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$meta_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT tm.meta_id FROM {$wpdb->termmeta} tm
				 LEFT JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
				 WHERE t.term_id IS NULL
				 LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $meta_ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $meta_ids ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE meta_id IN ({$id_list})" );
	}

	/**
	 * Clean a batch of orphaned term relationships.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_orphan_term_relationships( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$pairs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT tr.object_id, tr.term_taxonomy_id FROM {$wpdb->term_relationships} tr
				 LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
				 WHERE p.ID IS NULL
				 LIMIT %d",
				$batch_size
			),
			ARRAY_A
		);

		if ( empty( $pairs ) ) {
			return 0;
		}

		$cleaned = 0;
		foreach ( $pairs as $pair ) {
			$wpdb->delete(
				$wpdb->term_relationships,
				array(
					'object_id'        => (int) $pair['object_id'],
					'term_taxonomy_id' => (int) $pair['term_taxonomy_id'],
				),
				array( '%d', '%d' )
			);
			$cleaned++;
		}

		return $cleaned;
	}

	/**
	 * Count duplicated post metadata.
	 * Optimized for large (2GB - 4GB+) databases.
	 *
	 * @return int
	 */
	public static function count_duplicated_post_meta() {
		global $wpdb;

		if ( function_exists( 'set_time_limit' ) ) {
			// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
			@set_time_limit( 120 );
		}

		return (int) $wpdb->get_var(
			"SELECT COUNT(pm1.meta_id)
			 FROM {$wpdb->postmeta} pm1
			 INNER JOIN {$wpdb->postmeta} pm2 
			   ON pm1.post_id = pm2.post_id 
			   AND pm1.meta_key = pm2.meta_key 
			   AND LEFT(pm1.meta_value, 255) = LEFT(pm2.meta_value, 255)
			   AND (pm1.meta_value = pm2.meta_value OR (pm1.meta_value IS NULL AND pm2.meta_value IS NULL))
			   AND pm1.meta_id > pm2.meta_id"
		);
	}

	/**
	 * Clean a batch of duplicated post metadata.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_duplicated_post_meta( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT pm1.meta_id
				 FROM {$wpdb->postmeta} pm1
				 INNER JOIN {$wpdb->postmeta} pm2 
				   ON pm1.post_id = pm2.post_id 
				   AND pm1.meta_key = pm2.meta_key 
				   AND LEFT(pm1.meta_value, 255) = LEFT(pm2.meta_value, 255)
				   AND (pm1.meta_value = pm2.meta_value OR (pm1.meta_value IS NULL AND pm2.meta_value IS NULL))
				   AND pm1.meta_id > pm2.meta_id
				 LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $ids ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_id IN ({$id_list})" );
	}

	/**
	 * Count duplicated user metadata.
	 *
	 * @return int
	 */
	public static function count_duplicated_user_meta() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(um1.umeta_id)
			 FROM {$wpdb->usermeta} um1
			 INNER JOIN {$wpdb->usermeta} um2 
			   ON um1.user_id = um2.user_id 
			   AND um1.meta_key = um2.meta_key 
			   AND (um1.meta_value = um2.meta_value OR (um1.meta_value IS NULL AND um2.meta_value IS NULL))
			   AND um1.umeta_id > um2.umeta_id"
		);
	}

	/**
	 * Clean a batch of duplicated user metadata.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_duplicated_user_meta( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT um1.umeta_id
				 FROM {$wpdb->usermeta} um1
				 INNER JOIN {$wpdb->usermeta} um2 
				   ON um1.user_id = um2.user_id 
				   AND um1.meta_key = um2.meta_key 
				   AND (um1.meta_value = um2.meta_value OR (um1.meta_value IS NULL AND um2.meta_value IS NULL))
				   AND um1.umeta_id > um2.umeta_id
				 LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $ids ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE umeta_id IN ({$id_list})" );
	}

	/**
	 * Count duplicated comment metadata.
	 *
	 * @return int
	 */
	public static function count_duplicated_comment_meta() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(cm1.meta_id)
			 FROM {$wpdb->commentmeta} cm1
			 INNER JOIN {$wpdb->commentmeta} cm2 
			   ON cm1.comment_id = cm2.comment_id 
			   AND cm1.meta_key = cm2.meta_key 
			   AND (cm1.meta_value = cm2.meta_value OR (cm1.meta_value IS NULL AND cm2.meta_value IS NULL))
			   AND cm1.meta_id > cm2.meta_id"
		);
	}

	/**
	 * Clean a batch of duplicated comment metadata.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_duplicated_comment_meta( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT cm1.meta_id
				 FROM {$wpdb->commentmeta} cm1
				 INNER JOIN {$wpdb->commentmeta} cm2 
				   ON cm1.comment_id = cm2.comment_id 
				   AND cm1.meta_key = cm2.meta_key 
				   AND (cm1.meta_value = cm2.meta_value OR (cm1.meta_value IS NULL AND cm2.meta_value IS NULL))
				   AND cm1.meta_id > cm2.meta_id
				 LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $ids ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( "DELETE FROM {$wpdb->commentmeta} WHERE meta_id IN ({$id_list})" );
	}

	/**
	 * Count oEmbed cache entries in postmeta.
	 *
	 * @return int
	 */
	public static function count_oembed_caches() {
		global $wpdb;
		$like = $wpdb->esc_like( '_oembed_' ) . '%';
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
				$like
			)
		);
	}

	/**
	 * Clean a batch of oEmbed cache entries.
	 *
	 * @param int $batch_size
	 * @return int
	 */
	public static function clean_oembed_caches( $batch_size = 100 ) {
		global $wpdb;
		$batch_size = Safety::sanitize_batch_size( $batch_size );
		$like       = $wpdb->esc_like( '_oembed_' ) . '%';

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key LIKE %s LIMIT %d",
				$like,
				$batch_size
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$id_list = implode( ',', array_map( 'absint', $ids ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_id IN ({$id_list})" );
	}
}

<?php
/**
 * Cleanup Lock & Job ID Manager
 *
 * Prevents concurrent cleanup executions using a transient mutex lock.
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lock {

	const LOCK_TRANSIENT = 'nexura_database_cleaner_lock';
	const DEFAULT_TTL    = 300; // 5 minutes

	/**
	 * Generate a unique Job ID.
	 * Format: NEXURA-YYYYMMDD-XXXXXX
	 *
	 * @return string
	 */
	public static function generate_job_id() {
		return 'NEXURA-' . gmdate( 'Ymd' ) . '-' . strtoupper( substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 6 ) );
	}

	/**
	 * Attempt to acquire the cleanup lock.
	 *
	 * @param string $job_id
	 * @param string $operation
	 * @param int $ttl
	 * @return bool True if lock acquired, false if already locked.
	 */
	public static function acquire_lock( $job_id, $operation = 'batch_cleanup', $ttl = self::DEFAULT_TTL ) {
		$existing = get_transient( self::LOCK_TRANSIENT );

		if ( ! empty( $existing ) && is_array( $existing ) ) {
			// If lock has not expired and belongs to a different job, reject
			if ( isset( $existing['expires'] ) && $existing['expires'] > time() && isset( $existing['job_id'] ) && $existing['job_id'] !== $job_id ) {
				return false;
			}
		}

		$lock_data = array(
			'job_id'     => sanitize_text_field( $job_id ),
			'operation'  => sanitize_text_field( $operation ),
			'user_id'    => get_current_user_id(),
			'started_at' => time(),
			'expires'    => time() + $ttl,
		);

		return set_transient( self::LOCK_TRANSIENT, $lock_data, $ttl );
	}

	/**
	 * Release the cleanup lock.
	 *
	 * @param string|null $job_id Optional job ID to ensure only owner releases it.
	 * @return bool
	 */
	public static function release_lock( $job_id = null ) {
		if ( null !== $job_id ) {
			$existing = get_transient( self::LOCK_TRANSIENT );
			if ( is_array( $existing ) && isset( $existing['job_id'] ) && $existing['job_id'] !== $job_id ) {
				// Don't release lock held by another job
				return false;
			}
		}

		return delete_transient( self::LOCK_TRANSIENT );
	}

	/**
	 * Check if a cleanup lock is currently active.
	 *
	 * @return bool
	 */
	public static function is_locked() {
		$existing = get_transient( self::LOCK_TRANSIENT );
		if ( empty( $existing ) || ! is_array( $existing ) ) {
			return false;
		}

		return isset( $existing['expires'] ) && $existing['expires'] > time();
	}

	/**
	 * Get current active lock info.
	 *
	 * @return array|null
	 */
	public static function get_lock_info() {
		$existing = get_transient( self::LOCK_TRANSIENT );
		if ( empty( $existing ) || ! is_array( $existing ) ) {
			return null;
		}

		return $existing;
	}
}

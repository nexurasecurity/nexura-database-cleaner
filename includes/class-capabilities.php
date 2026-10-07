<?php
/**
 * Security and capabilities management
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Capabilities {

	/**
	 * Required capability to run scans and cleanups.
	 *
	 * @var string
	 */
	const REQUIRED_CAP = 'manage_options';

	/**
	 * Check if current user has administrator capability.
	 *
	 * @return bool
	 */
	public static function current_user_can() {
		return current_user_can( self::REQUIRED_CAP );
	}

	/**
	 * Check capability and throw JSON error if unauthorized.
	 */
	public static function check_ajax_permissions() {
		if ( ! self::current_user_can() ) {
			wp_send_json_error( array(
				'message' => __( 'You do not have sufficient permissions to access this page.', 'nexura-database-cleaner' ),
			), 403 );
		}
	}

	/**
	 * Verify admin action nonce.
	 *
	 * @param string $action Nonce action name.
	 * @param string $query_arg Key in $_REQUEST.
	 * @return bool
	 */
	public static function verify_nonce( $action = 'nexura_database_cleaner_admin_nonce', $query_arg = 'nonce' ) {
		$nonce = isset( $_REQUEST[ $query_arg ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $query_arg ] ) ) : '';
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $action ) ) {
			wp_send_json_error( array(
				'message' => __( 'Security verification failed. Please refresh the page and try again.', 'nexura-database-cleaner' ),
			), 403 );
		}
		return true;
	}

	/**
	 * Create an admin action nonce.
	 *
	 * @param string $action Nonce action.
	 * @return string
	 */
	public static function create_nonce( $action = 'nexura_database_cleaner_admin_nonce' ) {
		return wp_create_nonce( $action );
	}
}

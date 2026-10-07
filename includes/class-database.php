<?php
/**
 * Database abstraction layer
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery
class Database {

	/**
	 * Get all tables for the current WordPress instance.
	 *
	 * @param bool $all_tables Whether to include non-prefix tables.
	 * @return array
	 */
	public static function get_tables( $all_tables = false ) {
		global $wpdb;

		$prefix = $wpdb->prefix;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( 'SHOW TABLE STATUS' );
		if ( empty( $results ) ) {
			return array();
		}

		$tables = array();
		foreach ( $results as $row ) {
			$name = $row->Name;

			// If not showing all tables, filter by prefix unless multisite base prefix applies
			if ( ! $all_tables ) {
				if ( 0 !== strpos( $name, $prefix ) && 0 !== strpos( $name, $wpdb->base_prefix ) ) {
					continue;
				}
			}

			$data_length   = (float) $row->Data_length;
			$index_length  = (float) $row->Index_length;
			$data_free     = (float) ( isset( $row->Data_free ) ? $row->Data_free : 0 );
			$total_size    = $data_length + $index_length;

			$tables[] = array(
				'name'        => $name,
				'engine'      => isset( $row->Engine ) ? $row->Engine : 'Unknown',
				'rows'        => (int) ( isset( $row->Rows ) ? $row->Rows : 0 ),
				'data_size'   => $data_length,
				'index_size'  => $index_length,
				'free_size'   => $data_free,
				'total_size'  => $total_size,
				'collation'   => isset( $row->Collation ) ? $row->Collation : '',
				'is_core'     => Safety::is_core_table( $name ),
			);
		}

		return $tables;
	}

	/**
	 * Get total database size and statistics.
	 *
	 * @return array
	 */
	public static function get_database_stats() {
		$tables = self::get_tables( true );

		$total_size   = 0;
		$data_size    = 0;
		$index_size   = 0;
		$free_size    = 0;
		$total_rows   = 0;
		$engines      = array();
		$collations   = array();

		foreach ( $tables as $table ) {
			$total_size += $table['total_size'];
			$data_size  += $table['data_size'];
			$index_size += $table['index_size'];
			$free_size  += $table['free_size'];
			$total_rows += $table['rows'];

			if ( ! empty( $table['engine'] ) ) {
				$engines[ $table['engine'] ] = ( isset( $engines[ $table['engine'] ] ) ? $engines[ $table['engine'] ] : 0 ) + 1;
			}
			if ( ! empty( $table['collation'] ) ) {
				$collations[ $table['collation'] ] = ( isset( $collations[ $table['collation'] ] ) ? $collations[ $table['collation'] ] : 0 ) + 1;
			}
		}

		return array(
			'total_tables'     => count( $tables ),
			'total_size'       => $total_size,
			'total_size_human' => self::format_size( $total_size ),
			'data_size'        => $data_size,
			'data_size_human'  => self::format_size( $data_size ),
			'index_size'       => $index_size,
			'index_size_human' => self::format_size( $index_size ),
			'free_size'        => $free_size,
			'free_size_human'  => self::format_size( $free_size ),
			'total_rows'       => $total_rows,
			'engines'          => $engines,
			'collations'       => $collations,
		);
	}

	/**
	 * Format bytes into human-readable representation.
	 *
	 * @param float|int $bytes
	 * @param int $precision
	 * @return string
	 */
	public static function format_size( $bytes, $precision = 2 ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );
		$bytes = max( (float) $bytes, 0 );
		$pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow   = min( $pow, count( $units ) - 1 );

		$bytes /= pow( 1024, $pow );

		return round( $bytes, $precision ) . ' ' . $units[ $pow ];
	}
}

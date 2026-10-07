<?php
/**
 * Database Optimizer Coordinator
 *
 * @package NexuraDatabaseCleaner
 */

namespace NexuraDatabaseCleaner\Includes;

use NexuraDatabaseCleaner\Modules\Optimizer\Table_Optimizer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Optimizer {

	/**
	 * Get table analysis list.
	 *
	 * @return array
	 */
	public static function get_tables_overview() {
		return Table_Optimizer::get_optimizable_tables();
	}

	/**
	 * Optimize table by name.
	 *
	 * @param string $table_name
	 * @return array
	 */
	public static function optimize( $table_name ) {
		return Table_Optimizer::optimize_table( $table_name );
	}

	/**
	 * Analyze table by name.
	 *
	 * @param string $table_name
	 * @return array
	 */
	public static function analyze( $table_name ) {
		return Table_Optimizer::analyze_table( $table_name );
	}
}

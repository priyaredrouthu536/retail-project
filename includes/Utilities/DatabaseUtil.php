<?php

namespace PinakaPosWp\Utilities;

/**
 * Class DatabaseUtil
 *
 * @since   2.0.0
 * @package PinakaPosWp\Utilities
 */
class DatabaseUtil {

	/**
	 * Drop tables from the database.
	 *
	 * @param string|array $tables The table name.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function drop_tables( $tables ) {
		global $wpdb;
		$tables = wp_parse_list( $tables );
		$tables = array_filter( array_unique( $tables ) );

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * Drop columns from a table.
	 *
	 * @param string       $table The table name.
	 * @param string|array $columns The column name.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public static function drop_columns( $table, $columns ) {
		global $wpdb;
		$table   = $wpdb->prefix . $table;
		$columns = wp_parse_list( $columns );
		$columns = array_filter( array_unique( $columns ) );
		$cols    = $wpdb->get_col( "DESC {$table}", 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// remove any columns that do not exist.
		$columns = array_intersect( $columns, $cols );
		// make query to drop multiple columns.
		if ( ! empty( $columns ) ) {
			$query = '';
			foreach ( $columns as $column ) {
				$query .= "DROP COLUMN `{$column}`,";
			}
			$query = rtrim( $query, ',' );

			$wpdb->query( "ALTER TABLE {$table} {$query}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
}

<?php

namespace WP_STATISTICS;

class DB {
	/**
	 * List Of wp-statistics Mysql Table
	 *
	 * @var array
	 */
	public static $db_table = array(
		'useronline',
		'visit',
		'visitor',
		'exclusions',
		'pages',
		'search',
		'historical',
		'visitor_relationships'
	);

	/**
	 * WP-Statistics Table name Structure in Database
	 *
	 * @var string
	 */
	public static $tbl_name = '[prefix]statistics_[name]';

	/**
	 * Get WordPress Table Prefix
	 */
	public static function prefix() {
		global $wpdb;
		return $wpdb->prefix;
	}

	/**
	 * Get WP-Statistics Table name
	 *
	 * @param $tbl
	 * @return mixed
	 */
	public static function getTableName( $tbl ) {
		return str_ireplace( array( "[prefix]", "[name]" ), array( self::prefix(), $tbl ), self::$tbl_name );
	}

	/**
	 * Check Exist Table in Database
	 *
	 * @param $tbl_name
	 * @return bool
	 */
	public static function ExistTable( $tbl_name ) {
		global $wpdb;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$tbl_name'" ) == $tbl_name ) {
			return true;
		}
		return false;
	}

	/**
	 * Table List WP-Statistics
	 *
	 * @param string $export
	 * @param array $except
	 * @return array|null
	 */
	public static function table( $export = 'all', $except = array() ) {

		# Create Empty Object
		$list = array();

		# Convert except String to array
		if ( is_string( $except ) ) {
			$except = array( $except );
		}

		# Check Except List
		$mysql_list_table = array_diff( self::$db_table, $except );

		# Get List
		foreach ( $mysql_list_table as $tbl ) {

			# WP-Statistics table name
			$table_name = self::getTableName( $tbl );

			if ( $export == "all" ) {
				if ( self::ExistTable( $table_name ) ) {
					$list[ $tbl ] = $table_name;
				}
			} else {
				$list[ $tbl ] = $table_name;
			}
		}

		# Export Data
		return ( $export == 'all' ? $list : ( array_key_exists( $export, $list ) ? $list[ $export ] : null ) );
	}

	/**
	 * Delete All record From Table
	 *
	 * @param bool $table_name
	 * @return string
	 */
	public static function EmptyTable( $table_name = false ) {
		global $wpdb;

		if ( $table_name ) {
			$result = $wpdb->query( 'DELETE FROM ' . $table_name );

			if ( $result ) {
				return sprintf( __( '%s table data deleted successfully.', 'wp-statistics' ), '<code>' . $table_name . '</code>' );
			}
		}

		return sprintf( __( 'Error, %s not emptied!', 'wp-statistics' ), $table_name );
	}

}
<?php

use WP_STATISTICS\Pages;

/**
 * Get Current Users online
 *
 * @param array $options
 * @return mixed
 */
function wp_statistics_useronline( $options = array() ) {
	global $wpdb;

	//Check Parameter
	$defaults = array(
		/**
		 * Type Of Page in Wordpress
		 * @See Frontend\get_page_type
		 *
		 * -- Acceptable values --
		 *
		 * post     -> WordPress Post single page From All of public post Type
		 * page     -> Wordpress page single page
		 * product  -> WooCommerce product single page
		 * home     -> Home Page website
		 * category -> Wordpress Category Page
		 * post_tag -> Wordpress Post Tags Page
		 * tax      -> Wordpress Term Page for all Taxonomies
		 * author   -> Wordpress Users page
		 * 404      -> 404 Not Found Page
		 * archive  -> Wordpress Archive Page
		 * all      -> All Site Page
		 *
		 */
		'type'         => 'all',
		/**
		 * Wordpress Query object ID
		 * @example array('type' => 'product', 'ID' => 5)
		 */
		'ID'           => 0,
		/**
		 * Get number of logged users or all users
		 *
		 * -- Acceptable values --
		 * false  -> Get Number of all users
		 * true   -> Get Number of all logged users in wordpress
		 */
		'logged_users' => false,
		/**
		 * Get number User From Custom Country
		 *
		 * -- Acceptable values --
		 * ISO Country Code -> For Get List @See \wp-statistics\includes\functions\country-code.php
		 *
		 */
		'location'     => 'all',
		/**
		 * Search Filter by User agent name
		 * e.g : Firefox , Chrome , Safari , Unknown ..
		 * @see wp_statistics_get_browser_list()
		 *
		 */
		'agent'        => 'all',
		/**
		 * Search filter by User Platform name
		 * e.g : Windows, iPad, Macintosh, Unknown, ..
		 *
		 */
		'platform'     => 'all'
	);

	// Parse incoming $args into an array and merge it with $defaults
	$arg = wp_parse_args( $options, $defaults );

	//Basic SQL
	$sql = "SELECT COUNT(*) FROM " . WP_STATISTICS\DB::table( 'useronline' );

	//Check Where Condition
	$where = false;

	//Check Type of Page
	if ( $arg['type'] != "all" ) {
		$where[] = "`type`='" . $arg['type'] . "' AND `page_id` = " . $arg['ID'];
	}

	//Check Custom user
	if ( $arg['logged_users'] === true ) {
		$where[] = "`user_id` > 0";
	}

	//Check Location
	if ( $arg['location'] != "all" ) {
		$ISOCountryCode = \WP_STATISTICS\Helper::get_country_codes();
		if ( array_key_exists( $arg['location'], $ISOCountryCode ) ) {
			$where[] = "`location` = '" . $arg['location'] . "'";
		}
	}

	//Check User Agent
	if ( $arg['agent'] != "all" ) {
		$where[] = "`agent` = '" . $arg['agent'] . "'";
	}

	//Check User Platform
	if ( $arg['platform'] != "all" ) {
		$where[] = "`platform` = '" . $arg['platform'] . "'";
	}

	//Push Conditions to SQL
	if ( ! empty( $where ) ) {
		$sql .= ' WHERE ' . implode( ' AND ', $where );
	}

	//Return Number od user Online
	return $wpdb->get_var( $sql );
}

/**
 * Create Condition Where Time in MySql
 *
 * @param string $field : date column name in database table
 * @param string $time : Time return
 * @param array $range : an array contain two Date e.g : array('start' => 'xx-xx-xx', 'end' => 'xx-xx-xx', 'is_day' => true, 'current_date' => true)
 *
 * ---- Time Range -----
 * today
 * yesterday
 * week
 * month
 * year
 * total
 * “-x” (i.e., “-10” for the past 10 days)
 * ----------------------
 *
 * @return string|bool
 */
function wp_statistics_mysql_time_conditions( $field = 'date', $time = 'total', $range = array() ) {
	global $WP_Statistics;

	//Get Current Date From WP
	$current_date = \WP_STATISTICS\TimeZone::getCurrentDate( 'Y-m-d' );

	//Create Field Sql
	$field_sql = function ( $time ) use ( $current_date, $field, $WP_Statistics, $range ) {
		$is_current     = array_key_exists( 'current_date', $range );
		$getCurrentDate = \WP_STATISTICS\TimeZone::getCurrentDate( 'Y-m-d', (int) $time );
		return "`$field` " . ( $is_current === true ? '=' : 'BETWEEN' ) . " '{$getCurrentDate}'" . ( $is_current === false ? " AND '{$current_date}'" : "" );
	};

	//Check Time
	switch ( $time ) {
		case 'today':
			$where = "`$field` = '{$current_date}'";
			break;
		case 'yesterday':
			$getCurrentDate = \WP_STATISTICS\TimeZone::getCurrentDate( 'Y-m-d', - 1 );
			$where          = "`$field` = '{$getCurrentDate}'";
			break;
		case 'week':
			$where = $field_sql( - 7 );
			break;
		case 'month':
			$where = $field_sql( - 30 );
			break;
		case 'year':
			$where = $field_sql( - 365 );
			break;
		case 'total':
			$where = "";
			break;
		default:
			if ( array_key_exists( 'is_day', $range ) ) {
				//Check a day
				$getCurrentDate = \WP_STATISTICS\TimeZone::getCurrentDate( 'Y-m-d', $time );
				$where          = "`$field` = '{$getCurrentDate}'";
			} elseif ( array_key_exists( 'start', $range ) and array_key_exists( 'end', $range ) ) {
				//Check Between Two Time
				$getCurrentDate    = \WP_STATISTICS\TimeZone::getCurrentDate( 'Y-m-d', '-0', strtotime( $range['start'] ) );
				$getCurrentEndDate = \WP_STATISTICS\TimeZone::getCurrentDate( 'Y-m-d', '-0', strtotime( $range['end'] ) );
				$where             = "`$field` BETWEEN '{$getCurrentDate}' AND '{$getCurrentEndDate}'";
			} else {
				//Check From a Date To Now
				$where = $field_sql( $time );
			}
	}

	return $where;
}

/**
 * This function get the visit statistics for a given time frame
 *
 * @param $time
 * @param null $daily
 * @return int
 */
function wp_statistics_visit( $time, $daily = null ) {
	global $wpdb;

	//Date Column Name in visits table
	$table_name  = WP_STATISTICS\DB::table( 'visit' );
	$date_column = 'last_counter';

	//Prepare Selector Sql
	$selector = 'SUM(visit)';
	if ( $daily == true ) {
		$selector = '*';
	}

	//Generate Base Sql
	$sql = "SELECT {$selector} FROM {$table_name}";

	//Create Sum Visits variable
	$sum = 0;

	//Check if daily Report
	if ( $daily == true ) {

		$result = $wpdb->get_row( $sql . " WHERE `$date_column` = '" . \WP_STATISTICS\TimeZone::getCurrentDate( 'Y-m-d', $time ) . "'" );
		if ( null !== $result ) {
			$sum = $result->visit;
		}

	} else {

		//Generate MySql Time Conditions
		$mysql_time_sql = wp_statistics_mysql_time_conditions( $date_column, $time );
		if ( ! empty( $mysql_time_sql ) ) {
			$sql = $sql . ' WHERE ' . $mysql_time_sql;
		}

		//Request To database
		$result = $wpdb->get_var( $sql );

		//Custom Action
		if ( $time == "total" ) {
			$result += WP_STATISTICS\Historical::get( 'visits' );
		}

		$sum = $result;
	}

	return ! is_numeric( $sum ) ? 0 : $sum;
}

/**
 * This function gets the visitor statistics for a given time frame.
 *
 * @param $time
 * @param null $daily
 * @param bool $count_only
 * @param array $options
 * @return int|null|string
 */
function wp_statistics_visitor( $time, $daily = null, $count_only = false, $options = array() ) {
	global $wpdb;

	//Check Parameter
	$defaults = array(
		/**
		 * Type Of Page in Wordpress
		 * @See Frontend\get_page_type
		 *
		 * -- Acceptable values --
		 *
		 * post     -> WordPress Post single page From All of public post Type
		 * page     -> Wordpress page single page
		 * product  -> WooCommerce product single page
		 * home     -> Home Page website
		 * category -> Wordpress Category Page
		 * post_tag -> Wordpress Post Tags Page
		 * tax      -> Wordpress Term Page for all Taxonomies
		 * author   -> Wordpress Users page
		 * 404      -> 404 Not Found Page
		 * archive  -> Wordpress Archive Page
		 * all      -> All Site Page
		 *
		 */
		'type'     => 'all',
		/**
		 * Wordpress Query object ID
		 * @example array('type' => 'product', 'ID' => 5)
		 */
		'ID'       => 0,
		/**
		 * Get number User From Custom Country
		 *
		 * -- Acceptable values --
		 * ISO Country Code -> For Get List @See \wp-statistics\includes\functions\country-code.php
		 *
		 */
		'location' => 'all',
		/**
		 * Search Filter by User agent name
		 * e.g : Firefox , Chrome , Safari , Unknown ..
		 * @see wp_statistics_get_browser_list()
		 *
		 */
		'agent'    => 'all',
		/**
		 * Search filter by User Platform name
		 * e.g : Windows, iPad, Macintosh, Unknown, ..
		 *
		 */
		'platform' => 'all'
	);

	// Parse incoming $args into an array and merge it with $defaults
	$arg = wp_parse_args( $options, $defaults );

	//Create History Visitors variable
	$history = 0;

	//Prepare Selector Sql
	$date_column = 'last_counter';
	$selector    = '*';
	if ( $count_only == true ) {
		$selector = 'count(last_counter)';
	}

	//Generate Base Sql
	if ( $arg['type'] != "all" and WP_STATISTICS\Option::get( 'visitors_log' ) == true ) {
		$sql = "SELECT {$selector} FROM `" . WP_STATISTICS\DB::table( 'visitor' ) . "` INNER JOIN `" . WP_STATISTICS\DB::table( "visitor_relationships" ) . "` ON `" . WP_STATISTICS\DB::table( "visitor_relationships" ) . "`.`visitor_id` = `" . WP_STATISTICS\DB::table( 'visitor' ) . "`.`ID`  INNER JOIN `" . WP_STATISTICS\DB::table( 'pages' ) . "` ON `" . WP_STATISTICS\DB::table( 'pages' ) . "`.`page_id` = `" . WP_STATISTICS\DB::table( "visitor_relationships" ) . "` . `page_id`";
	} else {
		$sql = "SELECT {$selector} FROM `" . WP_STATISTICS\DB::table( 'visitor' ) . "`";
	}

	//Check Where Condition
	$where = false;

	//Check Type of Page
	if ( $arg['type'] != "all" and WP_STATISTICS\Option::get( 'visitors_log' ) == true ) {
		$where[] = "`" . WP_STATISTICS\DB::table( 'pages' ) . "`.`type`='" . $arg['type'] . "' AND `" . WP_STATISTICS\DB::table( 'pages' ) . "`.`page_id` = " . $arg['ID'];
	}

	//Check Location
	if ( $arg['location'] != "all" ) {
		$ISOCountryCode = \WP_STATISTICS\Helper::get_country_codes();
		if ( array_key_exists( $arg['location'], $ISOCountryCode ) ) {
			$where[] = "`" . WP_STATISTICS\DB::table( 'visitor' ) . "`.`location` = '" . $arg['location'] . "'";
		}
	}

	//Check User Agent
	if ( $arg['agent'] != "all" ) {
		$where[] = "`" . WP_STATISTICS\DB::table( 'visitor' ) . "`.`agent` = '" . $arg['agent'] . "'";
	}

	//Check User Platform
	if ( $arg['platform'] != "all" ) {
		$where[] = "`" . WP_STATISTICS\DB::table( 'visitor' ) . "`.`platform` = '" . $arg['platform'] . "'";
	}

	//Check Date Time report
	if ( $daily == true ) {

		//Get Only Current Day Visitors
		$where[] = "`" . WP_STATISTICS\DB::table( 'visitor' ) . "`.`last_counter` = '" . \WP_STATISTICS\TimeZone::getCurrentDate( 'Y-m-d', $time ) . "'";
	} else {

		//Generate MySql Time Conditions
		$mysql_time_sql = wp_statistics_mysql_time_conditions( $date_column, $time );
		if ( ! empty( $mysql_time_sql ) ) {
			$where[] = $mysql_time_sql;
		}
	}

	//Push Conditions to SQL
	if ( ! empty( $where ) ) {
		$sql .= ' WHERE ' . implode( ' AND ', $where );
	}

	//Custom Action
	if ( $time == "total" and $arg['type'] == "all" ) {
		$history = WP_STATISTICS\Historical::get( 'visitors' );
	}

	// Execute the SQL call, if we're only counting we can use get_var(), otherwise we use query().
	if ( $count_only == true ) {
		$sum = $wpdb->get_var( $sql );
		$sum += $history;
	} else {
		$sum = $wpdb->query( $sql );
	}

	return $sum;
}

/**
 * This function returns the statistics for a given page.
 *
 * @param $time
 * @param string $page_uri
 * @param int $id
 * @param null $rangestartdate
 * @param null $rangeenddate
 * @param bool $type
 * @return int|null|string
 */
function wp_statistics_pages( $time, $page_uri = '', $id = - 1, $rangestartdate = null, $rangeenddate = null, $type = false ) {
	global $wpdb;

	//Date Column Name in visits table
	$table_name  = WP_STATISTICS\DB::table( 'pages' );
	$date_column = 'date';
	$history     = 0;

	//Check Where Condition
	$where = false;

	//Check Query By Page ID or Page Url
	if ( $type != false and $id != - 1 ) {
		$where[] = "`type`='" . $type . "' AND `page_id` = " . $id;
	} else {

		// If no page URI has been passed in, get the current page URI.
		if ( $page_uri == '' ) {
			$page_uri = Pages::get_page_uri();
		}
		$page_uri_sql = esc_sql( $page_uri );

		// If a page/post ID has been passed, use it to select the rows, otherwise use the URI.
		if ( $id != - 1 ) {
			$where[]     = "`id`= " . absint( $id );
			$history_key = 'page';
			$history_id  = absint( $id );
		} else {
			$where[]     = "`URI` = '{$page_uri_sql}'";
			$history_key = 'uri';
			$history_id  = $page_uri;
		}

		//Custom Action
		if ( $time == "total" ) {
			$history = WP_STATISTICS\Historical::get( $history_key, $history_id );
		}
	}

	//Prepare Time
	$time_array = array();
	if ( is_numeric( $time ) ) {
		$time_array['is_day'] = true;
	}
	if ( ! is_null( $rangestartdate ) and ! is_null( $rangeenddate ) ) {
		$time_array = array( 'start' => $rangestartdate, 'end' => $rangeenddate );
	}

	//Check MySql Time Conditions
	$mysql_time_sql = wp_statistics_mysql_time_conditions( $date_column, $time, $time_array );
	if ( ! empty( $mysql_time_sql ) ) {
		$where[] = $mysql_time_sql;
	}

	//Generate Base Sql
	$sql = "SELECT SUM(count) FROM {$table_name}";

	//Push Conditions to SQL
	if ( ! empty( $where ) ) {
		$sql .= ' WHERE ' . implode( ' AND ', $where );
	}

	//Request Get data
	$sum = $wpdb->get_var( $sql );
	$sum += $history;

	//Return Number Statistic
	return ( $sum == '' ? 0 : $sum );
}

// This function converts a page URI to a page/post ID.  It does this by looking up in the pages database
// the URI and getting the associated ID.  This will only work if the page has been visited at least once.
function wp_statistics_uri_to_id( $uri ) {
	global $wpdb;

	// Create the SQL query to use.
	$sqlstatement = $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}statistics_pages WHERE `URI` = %s AND id > 0 ORDER BY date DESC", $uri );

	// Execute the query.
	$result = $wpdb->get_var( $sqlstatement );

	// If we returned a false or some other 0 equivalent value, make sure $result is set to an integer 0.
	if ( $result == 0 ) {
		$result = 0;
	}

	return $result;
}

// We need a quick function to pass to usort to properly sort the most popular pages.
function wp_stats_compare_uri_hits( $a, $b ) {
	return $a[1] < $b[1];
}

// This function returns a multi-dimensional array, with the total number of pages and an array or URI's sorted in order with their URI, count, id and title.
function wp_statistics_get_top_pages( $rangestartdate = null, $rangeenddate = null ) {
	global $wpdb;

	// Get every unique URI from the pages database.
	if ( $rangestartdate != null && $rangeenddate != null ) {
		$result = $wpdb->get_results( $wpdb->prepare( "SELECT `uri`,`id`,`type` FROM {$wpdb->prefix}statistics_pages WHERE `date` BETWEEN %s AND %s GROUP BY `uri`", $rangestartdate, $rangeenddate ), ARRAY_N );
	} else {
		$result = $wpdb->get_results( "SELECT `uri`,`id`,`type` FROM {$wpdb->prefix}statistics_pages GROUP BY `uri`", ARRAY_N );
	}

	$total = 0;
	$uris  = array();

	// Now get the total page visit count for each unique URI.
	foreach ( $result as $out ) {
		// Increment the total number of results.
		$total ++;

		//Prepare item
		list( $url, $page_id, $page_type ) = $out;

		//Get Page Title
		$page_info = \WP_STATISTICS\Helper::get_page_info( $page_id, $page_type );
		$title     = mb_substr( $page_info['title'], 0, 200, "utf-8" );
		$page_url  = $page_info['link'];

		// Check age Title if page id or type not exist
		if ( $page_info['link'] == "" ) {
			$page_url = htmlentities( path_join( get_site_url(), $url ), ENT_QUOTES );
			$id       = wp_statistics_uri_to_id( $out[0] );
			$post     = get_post( $id );
			if ( is_object( $post ) ) {
				$title = $post->post_title;
			} else {
				if ( $out[0] == '/' ) {
					$title = get_bloginfo();
				} else {
					$title = '';
				}
			}
		}

		//Check Title is empty
		if ( empty( $title ) ) {
			$title = '-';
		}

		// Add the current post to the array.
		if ( $rangestartdate != null && $rangeenddate != null ) {
			$uris[] = array(
				$out[0],
				wp_statistics_pages( 'range', $out[0], - 1, $rangestartdate, $rangeenddate ),
				$page_id,
				$title,
				$page_url,
			);
		} else {
			$uris[] = array( $out[0], wp_statistics_pages( 'total', $out[0] ), $page_id, $title, $page_url );
		}
	}

	// If we have more than one result, let's sort them using usort.
	if ( count( $uris ) > 1 ) {
		// Sort the URI's based on their hit count.
		usort( $uris, 'wp_stats_compare_uri_hits' );
	}

	return array( $total, $uris );
}



// This function returns all unique user agents in the database.
function wp_statistics_ua_list( $rangestartdate = null, $rangeenddate = null ) {

	global $wpdb;

	if ( $rangestartdate != null && $rangeenddate != null ) {
		if ( $rangeenddate == 'CURDATE()' ) {
			$result = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT agent FROM {$wpdb->prefix}statistics_visitor WHERE `last_counter` BETWEEN %s AND CURDATE()", $rangestartdate ), ARRAY_N );
		} else {
			$result = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT agent FROM {$wpdb->prefix}statistics_visitor WHERE `last_counter` BETWEEN %s AND %s", $rangestartdate, $rangeenddate ), ARRAY_N );
		}

	} else {
		$result = $wpdb->get_results( "SELECT DISTINCT agent FROM {$wpdb->prefix}statistics_visitor", ARRAY_N );
	}

	$Browsers        = array();
	$default_browser = wp_statistics_get_browser_list();

	foreach ( $result as $out ) {
		//Check Browser is defined in wp-statistics
		if ( array_key_exists( strtolower( $out[0] ), $default_browser ) ) {
			$Browsers[] = $out[0];
		}
	}

	return $Browsers;
}

/**
 * Count User By User Agent
 *
 * @param $agent
 * @param null $rangestartdate
 * @param null $rangeenddate
 * @return mixed
 */
function wp_statistics_useragent( $agent, $rangestartdate = null, $rangeenddate = null ) {
	global $wpdb;

	if ( $rangestartdate != null && $rangeenddate != null ) {
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(agent) FROM {$wpdb->prefix}statistics_visitor WHERE `agent` = %s AND `last_counter` BETWEEN %s AND %s",
				$agent,
				$rangestartdate,
				$rangeenddate
			)
		);
	} else {
		$result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(agent) FROM {$wpdb->prefix}statistics_visitor WHERE `agent` = %s", $agent ) );
	}

	return $result;
}

// This function returns all unique platform types from the database.
function wp_statistics_platform_list( $rangestartdate = null, $rangeenddate = null ) {

	global $wpdb;

	if ( $rangestartdate != null && $rangeenddate != null ) {
		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT platform FROM {$wpdb->prefix}statistics_visitor WHERE `last_counter` BETWEEN %s AND %s",
				$rangestartdate,
				$rangeenddate
			),
			ARRAY_N
		);
	} else {
		$result = $wpdb->get_results( "SELECT DISTINCT platform FROM {$wpdb->prefix}statistics_visitor", ARRAY_N );
	}

	$Platforms = array();

	foreach ( $result as $out ) {
		$Platforms[] = $out[0];
	}

	return $Platforms;
}

// This function returns the count of a given platform in the database.
function wp_statistics_platform( $platform, $rangestartdate = null, $rangeenddate = null ) {
	global $wpdb;

	if ( $rangestartdate != null && $rangeenddate != null ) {
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(platform) FROM {$wpdb->prefix}statistics_visitor WHERE `platform` = %s AND `last_counter` BETWEEN %s AND %s",
				$platform,
				$rangestartdate,
				$rangeenddate
			)
		);
	} else {
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(platform) FROM {$wpdb->prefix}statistics_visitor WHERE `platform` = %s",
				$platform
			)
		);
	}

	return $result;
}

// This function returns all unique versions for a given agent from the database.
function wp_statistics_agent_version_list( $agent, $rangestartdate = null, $rangeenddate = null ) {
	global $wpdb;

	if ( $rangestartdate != null && $rangeenddate != null ) {
		$result = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT version FROM {$wpdb->prefix}statistics_visitor WHERE agent = %s AND `last_counter` BETWEEN %s AND %s",
				$agent,
				$rangestartdate,
				$rangeenddate
			),
			ARRAY_N
		);
	} else {
		$result = $wpdb->get_results(
			$wpdb->prepare( "SELECT DISTINCT version FROM {$wpdb->prefix}statistics_visitor WHERE agent = %s", $agent ),
			ARRAY_N
		);
	}

	$Versions = array();

	foreach ( $result as $out ) {
		$Versions[] = $out[0];
	}

	return $Versions;
}

// This function returns the statistics for a given agent/version pair from the database.
function wp_statistics_agent_version( $agent, $version, $rangestartdate = null, $rangeenddate = null ) {
	global $wpdb;

	if ( $rangestartdate != null && $rangeenddate != null ) {
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(version) FROM {$wpdb->prefix}statistics_visitor WHERE agent = %s AND version = %s AND `last_counter` BETWEEN %s AND %s",
				$agent,
				$version,
				$rangestartdate,
				$rangeenddate
			)
		);
	} else {
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(version) FROM {$wpdb->prefix}statistics_visitor WHERE agent = %s AND version = %s",
				$agent,
				$version
			)
		);
	}

	return $result;
}



// This function will return the SQL WHERE clause for getting the search words for a given search engine.
function wp_statistics_searchword_query( $search_engine = 'all' ) {

	// Get a complete list of search engines
	$searchengine_list = WP_STATISTICS\SearchEngine::getList();
	$search_query      = '';

	if ( WP_STATISTICS\Option::get( 'search_converted' ) ) {
		// Are we getting results for all search engines or a specific one?
		if ( strtolower( $search_engine ) == 'all' ) {
			// For all of them?  Ok, look through the search engine list and create a SQL query string to get them all from the database.
			foreach ( $searchengine_list as $key => $se ) {
				$search_query .= "( `engine` = '{$key}' AND `words` <> '' ) OR ";
			}

			// Trim off the last ' OR ' for the loop above.
			$search_query = substr( $search_query, 0, strlen( $search_query ) - 4 );
		} else {
			$search_query .= "`engine` = '{$search_engine}' AND `words` <> ''";
		}
	} else {
		// Are we getting results for all search engines or a specific one?
		if ( strtolower( $search_engine ) == 'all' ) {
			// For all of them?  Ok, look through the search engine list and create a SQL query string to get them all from the database.
			// NOTE:  This SQL query can be *VERY* long.
			foreach ( $searchengine_list as $se ) {
				// The SQL pattern for a search engine may be an array if it has to handle multiple domains (like google.com and google.ca) or other factors.
				if ( is_array( $se['sqlpattern'] ) ) {
					foreach ( $se['sqlpattern'] as $subse ) {
						$search_query .= "(`referred` LIKE '{$subse}{$se['querykey']}=%' AND `referred` NOT LIKE '{$subse}{$se['querykey']}=&%' AND `referred` NOT LIKE '{$subse}{$se['querykey']}=') OR ";
					}
				} else {
					$search_query .= "(`referred` LIKE '{$se['sqlpattern']}{$se['querykey']}=%' AND `referred` NOT LIKE '{$se['sqlpattern']}{$se['querykey']}=&%' AND `referred` NOT LIKE '{$se['sqlpattern']}{$se['querykey']}=')  OR ";
				}
			}

			// Trim off the last ' OR ' for the loop above.
			$search_query = substr( $search_query, 0, strlen( $search_query ) - 4 );
		} else {
			// For just one?  Ok, the SQL pattern for a search engine may be an array if it has to handle multiple domains (like google.com and google.ca) or other factors.
			if ( is_array( $searchengine_list[ $search_engine ]['sqlpattern'] ) ) {
				foreach ( $searchengine_list[ $search_engine ]['sqlpattern'] as $se ) {
					$search_query .= "(`referred` LIKE '{$se}{$searchengine_list[$search_engine]['querykey']}=%' AND `referred` NOT LIKE '{$se}{$searchengine_list[$search_engine]['querykey']}=&%' AND `referred` NOT LIKE '{$se}{$searchengine_list[$search_engine]['querykey']}=') OR ";
				}

				// Trim off the last ' OR ' for the loop above.
				$search_query = substr( $search_query, 0, strlen( $search_query ) - 4 );
			} else {
				$search_query .= "(`referred` LIKE '{$searchengine_list[$search_engine]['sqlpattern']}{$searchengine_list[$search_engine]['querykey']}=%' AND `referred` NOT LIKE '{$searchengine_list[$search_engine]['sqlpattern']}{$searchengine_list[$search_engine]['querykey']}=&%' AND `referred` NOT LIKE '{$searchengine_list[$search_engine]['sqlpattern']}{$searchengine_list[$search_engine]['querykey']}=')";
			}
		}
	}

	return $search_query;
}

// This function will return the SQL WHERE clause for getting the search engine.
function wp_statistics_searchengine_query( $search_engine = 'all' ) {

	// Get a complete list of search engines
	$searchengine_list = WP_STATISTICS\SearchEngine::getList();
	$search_query      = '';

	if ( WP_STATISTICS\Option::get( 'search_converted' ) ) {
		// Are we getting results for all search engines or a specific one?
		if ( strtolower( $search_engine ) == 'all' ) {
			// For all of them?  Ok, look through the search engine list and create a SQL query string to get them all from the database.
			foreach ( $searchengine_list as $key => $se ) {
				$key          = esc_sql( $key );
				$search_query .= "`engine` = '{$key}' OR ";
			}

			// Trim off the last ' OR ' for the loop above.
			$search_query = substr( $search_query, 0, strlen( $search_query ) - 4 );
		} else {
			$search_engine = esc_sql( $search_engine );
			$search_query  .= "`engine` = '{$search_engine}'";
		}
	} else {
		// Are we getting results for all search engines or a specific one?
		if ( strtolower( $search_engine ) == 'all' ) {
			// For all of them?  Ok, look through the search engine list and create a SQL query string to get them all from the database.
			// NOTE:  This SQL query can be long.
			foreach ( $searchengine_list as $se ) {
				// The SQL pattern for a search engine may be an array if it has to handle multiple domains (like google.com and google.ca) or other factors.
				if ( is_array( $se['sqlpattern'] ) ) {
					foreach ( $se['sqlpattern'] as $subse ) {
						$subse        = esc_sql( $subse );
						$search_query .= "`referred` LIKE '{$subse}' OR ";
					}
				} else {
					$se['sqlpattern'] = esc_sql( $se['sqlpattern'] );
					$search_query     .= "`referred` LIKE '{$se['sqlpattern']}' OR ";
				}
			}

			// Trim off the last ' OR ' for the loop above.
			$search_query = substr( $search_query, 0, strlen( $search_query ) - 4 );
		} else {
			// For just one?  Ok, the SQL pattern for a search engine may be an array if it has to handle multiple domains (like google.com and google.ca) or other factors.
			if ( is_array( $searchengine_list[ $search_engine ]['sqlpattern'] ) ) {
				foreach ( $searchengine_list[ $search_engine ]['sqlpattern'] as $se ) {
					$se           = esc_sql( $se );
					$search_query .= "`referred` LIKE '{$se}' OR ";
				}

				// Trim off the last ' OR ' for the loop above.
				$search_query = substr( $search_query, 0, strlen( $search_query ) - 4 );
			} else {
				$searchengine_list[ $search_engine ]['sqlpattern'] = esc_sql( $searchengine_list[ $search_engine ]['sqlpattern'] );
				$search_query                                      .= "`referred` LIKE '{$searchengine_list[$search_engine]['sqlpattern']}'";
			}
		}
	}

	return $search_query;
}

/**
 * Get Search engine Statistics
 *
 * @param string $search_engine
 * @param string $time
 * @param string $search_by [query / name]
 * @return mixed
 */
function wp_statistics_get_search_engine_query( $search_engine = 'all', $time = 'total', $search_by = 'query' ) {
	global $wpdb;

	//Prepare Table Name
	$table_name = $wpdb->prefix . 'statistics_';
	if ( WP_STATISTICS\Option::get( 'search_converted' ) ) {
		$table_name .= 'search';
	} else {
		$table_name .= 'visitor';
	}

	//Date Column table
	$date_column = 'last_counter';

	// Get a complete list of search engines
	if ( $search_by == "query" ) {
		$search_query = wp_statistics_searchengine_query( $search_engine );
	} else {
		$search_query = wp_statistics_searchword_query( $search_engine );
	}

	//Generate Base Sql
	$sql = "SELECT * FROM {$table_name} WHERE ({$search_query})";

	//Generate MySql Time Conditions
	$mysql_time_sql = wp_statistics_mysql_time_conditions( $date_column, $time, array( 'current_date' => true ) );
	if ( ! empty( $mysql_time_sql ) ) {
		$sql = $sql . ' AND (' . $mysql_time_sql . ')';
	}

	//Request Data
	$result = $wpdb->query( $sql );
	return $result;
}

/**
 * This function will return the statistics for a given search engine.
 *
 * @param string $search_engine
 * @param string $time
 * @return mixed
 */
function wp_statistics_searchengine( $search_engine = 'all', $time = 'total' ) {
	return wp_statistics_get_search_engine_query( $search_engine, $time, $search_by = 'query' );
}

//This Function will return the referrer list
function wp_statistics_referrer( $time = null ) {
	global $wpdb;

	$timezone = array(
		'today'     => 0,
		'yesterday' => - 1,
		'week'      => - 7,
		'month'     => - 30,
		'year'      => - 365,
		'total'     => 'ALL',
	);
	$sql      = "SELECT `referred` FROM `" . $wpdb->prefix . "statistics_visitor` WHERE referred <> ''";
	if ( array_key_exists( $time, $timezone ) ) {
		if ( $time != "total" ) {
			$sql .= " AND (`last_counter` = '" . \WP_STATISTICS\TimeZone::getCurrentDate( 'Y-m-d', $timezone[ $time ] ) . "')";
		}
	} else {
		//Set Default
		$sql .= " AND (`last_counter` = '" . \WP_STATISTICS\TimeZone::getCurrentDate( 'Y-m-d', $time ) . "')";
	}
	$result = $wpdb->get_results( $sql );

	$urls = array();
	foreach ( $result as $item ) {
		$url = parse_url( $item->referred );
		if ( empty( $url['host'] ) || stristr( get_bloginfo( 'url' ), $url['host'] ) ) {
			continue;
		}
		$urls[] = $url['scheme'] . '://' . $url['host'];
	}
	$get_urls = array_count_values( $urls );

	return count( $get_urls );
}

/**
 * This function will return the statistics for a given search engine for a given time frame.
 *
 * @param string $search_engine
 * @param string $time
 * @return mixed
 */
function wp_statistics_searchword( $search_engine = 'all', $time = 'total' ) {
	return wp_statistics_get_search_engine_query( $search_engine, $time, $search_by = 'word' );
}

// This function will return the total number of posts in WordPress.
function wp_statistics_countposts() {
	$count_posts = wp_count_posts( 'post' );

	$ret = 0;
	if ( is_object( $count_posts ) ) {
		$ret = $count_posts->publish;
	}
	return $ret;
}

// This function will return the total number of pages in WordPress.
function wp_statistics_countpages() {
	$count_pages = wp_count_posts( 'page' );

	$ret = 0;
	if ( is_object( $count_pages ) ) {
		$ret = $count_pages->publish;
	}
	return $ret;
}

// This function will return the total number of comments in WordPress.
function wp_statistics_countcomment() {
	global $wpdb;

	$countcomms = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '1'" );
	return $countcomms;
}

// This function will return the total number of spam comments *IF* akismet is installed.
function wp_statistics_countspam() {
	return number_format_i18n( get_option( 'akismet_spam_count' ) );
}

// This function will return the total number of users in WordPress.
function wp_statistics_countusers() {
	$result = count_users();
	return $result['total_users'];
}

// This function will return the last date a post was published on your site.
function wp_statistics_lastpostdate() {
	global $wpdb;

	$db_date = $wpdb->get_var( "SELECT post_date FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish' ORDER BY post_date DESC LIMIT 1" );
	$date_format = get_option( 'date_format' );
	return \WP_STATISTICS\TimeZone::getCurrentDate_i18n( $date_format, $db_date, false );
}

// This function will return the average number of posts per day that are published on your site.
// Alternatively if $days is set to true it returns the average number of days between posts on your site.
function wp_statistics_average_post( $days = false ) {

	global $wpdb;

	$get_first_post = $wpdb->get_var(
		"SELECT post_date FROM {$wpdb->posts} WHERE post_status = 'publish' ORDER BY post_date LIMIT 1"
	);
	$get_total_post = $wpdb->get_var(
		"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type = 'post'"
	);

	$days_spend = intval(
		( time() - strtotime( $get_first_post ) ) / 86400
	); // 86400 = 60 * 60 * 24 = number of seconds in a day

	if ( $days == true ) {
		if ( $get_total_post == 0 ) {
			$get_total_post = 1;
		} // Avoid divide by zero errors.

		return round( $days_spend / $get_total_post, 0 );
	} else {
		if ( $days_spend == 0 ) {
			$days_spend = 1;
		} // Avoid divide by zero errors.

		return round( $get_total_post / $days_spend, 2 );
	}
}

// This function will return the average number of comments per day that are published on your site.
// Alternatively if $days is set to true it returns the average number of days between comments on your site.
function wp_statistics_average_comment( $days = false ) {

	global $wpdb;

	$get_first_comment = $wpdb->get_var( "SELECT comment_date FROM {$wpdb->comments} ORDER BY comment_date LIMIT 1" );
	$get_total_comment = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '1'" );

	$days_spend = intval(
		( time() - strtotime( $get_first_comment ) ) / 86400
	); // 86400 = 60 * 60 * 24 = number of seconds in a day

	if ( $days == true ) {
		if ( $get_total_comment == 0 ) {
			$get_total_comment = 1;
		} // Avoid divide by zero errors.

		return round( $days_spend / $get_total_comment, 0 );
	} else {
		if ( $days_spend == 0 ) {
			$days_spend = 1;
		} // Avoid divide by zero errors.

		return round( $get_total_comment / $days_spend, 2 );
	}
}

// This function will return the average number of users per day that are registered on your site.
// Alternatively if $days is set to true it returns the average number of days between user registrations on your site.
function wp_statistics_average_registeruser( $days = false ) {

	global $wpdb;

	$get_first_user = $wpdb->get_var( "SELECT user_registered FROM {$wpdb->users} ORDER BY user_registered LIMIT 1" );
	$get_total_user = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );

	$days_spend = intval(
		( time() - strtotime( $get_first_user ) ) / 86400
	); // 86400 = 60 * 60 * 24 = number of seconds in a day

	if ( $days == true ) {
		if ( $get_total_user == 0 ) {
			$get_total_user = 1;
		} // Avoid divide by zero errors.

		return round( $days_spend / $get_total_user, 0 );
	} else {
		if ( $days_spend == 0 ) {
			$days_spend = 1;
		} // Avoid divide by zero errors.

		return round( $get_total_user / $days_spend, 2 );
	}
}

/**
 * This function is used to calculate the number of days and their respective unix timestamps.
 *
 * @param $days
 * @param $start
 * @param $end
 * @return array
 */
function wp_statistics_date_range_calculator( $days, $start, $end ) {

	$daysToDisplay = $days;
	$rangestart    = $start;
	$rangeend      = $end;

	//Check Exist params
	if ( ! empty( $daysToDisplay ) and ! empty( $rangestart ) and ! empty( $rangeend ) ) {
		return array( $daysToDisplay, strtotime( $rangestart ), strtotime( $rangeend ) );
	}

	//Check Not Exist day to display
	if ( $daysToDisplay == - 1 ) {
		$rangestart_utime = \WP_STATISTICS\TimeZone::strtotimetz( $rangestart );
		$rangeend_utime   = \WP_STATISTICS\TimeZone::strtotimetz( $rangeend );
		$daysToDisplay    = (int) ( ( $rangeend_utime - $rangestart_utime ) / 24 / 60 / 60 );

		if ( $rangestart_utime == false || $rangeend_utime == false ) {
			$daysToDisplay    = 20;
			$rangeend_utime   = \WP_STATISTICS\TimeZone::timetz();
			$rangestart_utime = $rangeend_utime - ( $daysToDisplay * 24 * 60 * 60 );
		}
	} else {
		$rangeend_utime   = \WP_STATISTICS\TimeZone::timetz();
		$rangestart_utime = $rangeend_utime - ( $daysToDisplay * 24 * 60 * 60 );
	}

	return array( $daysToDisplay, $rangestart_utime, $rangeend_utime );
}

/**
 * This function creates a small JavaScript that will load the contents of a overview or dashboard widget.
 *
 * @param $widget
 * @param null $container_id
 */
function wp_statistics_generate_widget_load_javascript( $widget, $container_id = null ) {
	if ( null == $container_id ) {
		$container_id = str_replace( '.', '_', $widget . '_postbox' );
	}
	?>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            wp_statistics_get_widget_contents('<?php echo $widget; ?>', '<?php echo $container_id; ?>');
        });
    </script>
	<?php
}

/**
 * Generate RGBA colors
 *
 * @param        $num
 * @param string $opacity
 *
 * @return string
 */
function wp_statistics_generate_rgba_color( $num, $opacity = '1' ) {
	$hash = md5( 'color' . $num );

	return sprintf(
		"'rgba(%s, %s, %s, %s)'",
		hexdec( substr( $hash, 0, 2 ) ),
		hexdec( substr( $hash, 2, 2 ) ),
		hexdec( substr( $hash, 4, 2 ) ),
		$opacity
	);
}

/**
 * This function will validate that a capability exists,
 * if not it will default to returning the 'manage_options' capability.
 *
 * @param string $capability Capability
 * @return string 'manage_options'
 */
function wp_statistics_validate_capability( $capability ) {
	global $wp_roles;

	if ( ! is_object( $wp_roles ) || ! is_array( $wp_roles->roles ) ) {
		return 'manage_options';
	}

	foreach ( $wp_roles->roles as $role ) {
		$cap_list = $role['capabilities'];

		foreach ( $cap_list as $key => $cap ) {
			if ( $capability == $key ) {
				return $capability;
			}
		}
	}

	return 'manage_options';
}

/**
 * Check User Access To WP-Statistics Admin
 *
 * @param string $type [manage | read ]
 * @param string|boolean $export
 * @return bool
 */
function wp_statistics_check_access_user( $type = 'both', $export = false ) {

	//List Of Default Cap
	$list = array(
		'manage' => array( 'manage_capability', 'manage_options' ),
		'read'   => array( 'read_capability', 'manage_options' )
	);

	//User User Cap
	$cap = 'both';
	if ( ! empty( $type ) and array_key_exists( $type, $list ) ) {
		$cap = $type;
	}

	//Check Export Cap name or Validation current_can_user
	if ( $export == "cap" ) {
		return wp_statistics_validate_capability( WP_STATISTICS\Option::get( $list[ $cap ][0], $list[ $cap ][1] ) );
	}

	//Check Access
	switch ( $type ) {
		case "manage":
		case "read":
			return current_user_can( wp_statistics_validate_capability( WP_STATISTICS\Option::get( $list[ $cap ][0], $list[ $cap ][1] ) ) );
			break;
		case "both":
			foreach ( array( 'manage', 'read' ) as $c ) {
				if ( wp_statistics_check_access_user( $c ) === true ) {
					return true;
				}
			}
			break;
	}

	return false;
}

/**
 * Get All Browser List For Detecting
 *
 * @param bool $all
 * @area utility
 * @return array|mixed
 */
function wp_statistics_get_browser_list( $all = true ) {

	//List Of Detect Browser in WP Statistics
	$list        = array(
		"chrome"  => __( "Chrome", 'wp-statistics' ),
		"firefox" => __( "Firefox", 'wp-statistics' ),
		"msie"    => __( "Internet Explorer", 'wp-statistics' ),
		"edge"    => __( "Edge", 'wp-statistics' ),
		"opera"   => __( "Opera", 'wp-statistics' ),
		"safari"  => __( "Safari", 'wp-statistics' )
	);
	$browser_key = array_keys( $list );

	//Return All Browser List
	if ( $all === true ) {
		return $list;
		//Return Browser Keys For detect
	} elseif ( $all == "key" ) {
		return $browser_key;
	} else {
		//Return Custom Browser Name by key
		if ( array_search( strtolower( $all ), $browser_key ) !== false ) {
			return $list[ strtolower( $all ) ];
		} else {
			return __( "Unknown", 'wp-statistics' );
		}
	}
}

/**
 * Modify For IGNORE insert Query
 *
 * @hook add_action('query', function_name, 10);
 * @param $query
 * @return string
 */
function wp_statistics_ignore_insert( $query ) {
	$count = 0;
	$query = preg_replace( '/^(INSERT INTO)/i', 'INSERT IGNORE INTO', $query, 1, $count );
	return $query;
}

/**
 * Get WebSite IP Server And Country Name
 *
 * @param $url string domain name e.g : wp-statistics.com
 * @return array
 */
function wp_statistics_get_domain_server( $url ) {

	//Create Empty Object
	$result = array(
		'ip'      => '',
		'country' => ''
	);

	//Get Ip by Domain
	if ( function_exists( 'gethostbyname' ) ) {
		$ip = gethostbyname( $url );
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$result['ip'] = $ip;
			//Get country Code
			if ( WP_STATISTICS\Option::get( 'geoip' ) ) {
				$geoip_reader = \WP_STATISTICS\GeoIP::Loader( 'country' ); //TODO Change Method
				if ( $geoip_reader != false ) {
					try {
						$record            = $geoip_reader->country( $ip );
						$result['country'] = $record->country->isoCode;
					} catch ( Exception $e ) {
					}
				}
			}
		}
	}

	return $result;
}

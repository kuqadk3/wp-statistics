<?php

namespace WP_STATISTICS;

use Jaybizzle\CrawlerDetect\CrawlerDetect;
use IPTools\IP;
use IPTools\Range;

class Exclusion {
	/**
	 * Get Exclusion List
	 *
	 * @var array
	 */
	public static $exclusion_list = array(
		'Ajax',
		'Cron job',
		'Crawler Detect',
		'Robot',
		'Broken Link',
		'IP Match',
		'Self Referral',
		'Login Page',
		'Admin Page',
		'Referrer Spam',
		'Feed',
		'404',
		'Excluded URL',
		'User Role',
		'Host name',
		'GeoIP',
	);

	/**
	 * Check to see if the user wants us to record why we're excluding hits.
	 *
	 * @return mixed
	 */
	public static function is_record_exclusion() {
		return $GLOBALS['WP_Statistics']->option->get( 'record_exclusions' );
	}

	/**
	 * Checks exclusion tracking visits and visitors.
	 */
	public static function check() {

		// Create Default Object
		$exclude = array( 'exclusion_match' => false, 'exclusion_reason' => '' );

		//TODO Remove At Last & Check has_filter return and do not go to foreach
		if ( \WP_Statistics_Rest::is_rest() ) {

			$exclude['exclusion_match']  = ( \WP_Statistics_Rest::params( 'exclude' ) == 1 ? true : false );
			$exclude['exclusion_reason'] = \WP_Statistics_Rest::params( 'exclude_reason' );
		} else {

			// Check Exclusion
			foreach ( self::$exclusion_list as $list ) {
				$method = 'exclusion_' . strtolower( str_replace( array( "-", " " ), "_", $list ) );
				$check  = self::{$method}();
				if ( $check === true ) {
					$exclude = array( 'exclusion_match' => true, 'exclusion_reason' => $list );
					break;
				}
			}

		}

		return apply_filters( 'wp_statistics_exclusion', $exclude );
	}

	/**
	 * Record Exclusion in WP-Statistics DB.
	 *
	 * @param array $exclusion
	 */
	public static function record( $exclusion = array() ) {
		global $wpdb;

		// If we're not storing exclusions, just return.
		if ( self::is_record_exclusion() != true ) {
			return;
		}

		$result = $wpdb->query( "UPDATE " . DB::table( 'exclusions' ) . " SET `count` = `count` + 1 WHERE `date` = '" . TimeZone::getCurrentDate( 'Y-m-d' ) . "' AND `reason` = '{$exclusion['exclusion_reason']}'" );
		if ( ! $result ) {

			$wpdb->insert(
				DB::table( 'exclusions' ),
				array(
					'date'   => TimeZone::getCurrentDate( 'Y-m-d' ),
					'reason' => $exclusion['exclusion_reason'],
					'count'  => 1,
				)
			);
		}
	}

	/**
	 * Detect if we're running an ajax request.
	 */
	public static function exclusion_ajax() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * Detect if we're running an WordPress CronJob.
	 */
	public static function exclusion_cron_job() {
		return ( defined( 'DOING_CRON' ) && DOING_CRON === true ) || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() === true );
	}

	/**
	 * Detect if WordPress Feed.
	 */
	public static function exclusion_feed() {
		return $GLOBALS['WP_Statistics']->option->get( 'exclude_feeds' ) and is_feed();
	}

	/**
	 * Detect if WordPress 404 Page.
	 */
	public static function exclusion_404() {
		return $GLOBALS['WP_Statistics']->option->get( 'exclude_404s' ) and is_404();
	}

	/**
	 * Detect if Exclude WordPress User role.
	 */
	public static function exclusion_user_role() {
		global $WP_Statistics;

		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			foreach ( $current_user->roles as $role ) {
				$option_name = 'exclude_' . str_replace( ' ', '_', strtolower( $role ) );
				if ( $WP_Statistics->option->get( $option_name ) == true ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Detect if Excluded URL.
	 */
	public static function exclusion_excluded_url() {
		global $WP_Statistics;

		if ( $WP_Statistics->option->get( 'excluded_urls' ) ) {
			$script    = $_SERVER['REQUEST_URI'];
			$delimiter = strpos( $script, '?' );
			if ( $delimiter > 0 ) {
				$script = substr( $script, 0, $delimiter );
			}

			$excluded_urls = explode( "\n", $WP_Statistics->option->get( 'excluded_urls' ) );
			foreach ( $excluded_urls as $url ) {
				$this_url = trim( $url );

				if ( strlen( $this_url ) > 2 ) {
					if ( stripos( $script, $this_url ) === 0 ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Detect if Referrer Spam.
	 */
	public static function exclusion_referrer_spam() {
		global $WP_Statistics;

		// Check to see if we're excluding referrer spam.
		if ( $WP_Statistics->option->get( 'referrerspam' ) ) {
			$referrer = Referred::get();

			// Pull the referrer spam list from the database.
			$referrer_spam_list = explode( "\n", $WP_Statistics->option->get( 'referrerspamlist' ) );

			// Check to see if we match any of the robots.
			foreach ( $referrer_spam_list as $item ) {
				$item = trim( $item );

				// If the match case is less than 4 characters long, it might match too much so don't execute it.
				if ( strlen( $item ) > 3 ) {
					if ( stripos( $referrer, $item ) !== false ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Detect if Crawler.
	 */
	public static function exclusion_crawler_detect() {
		$CrawlerDetect = new CrawlerDetect;
		if ( $CrawlerDetect->isCrawler() ) {
			return true;
		}

		return false;
	}

	/**
	 * Detect if Self Referral WordPress.
	 */
	public static function exclusion_self_referral() {
		return UserAgent::getHttpUserAgent() == 'WordPress/' . Helper::get_wordpress_version() . '; ' . get_home_url( null, '/' ) || UserAgent::getHttpUserAgent() == 'WordPress/' . Helper::get_wordpress_version() . '; ' . get_home_url();
	}

	/**
	 * Detect if WordPress Login Page.
	 */
	public static function exclusion_login_page() {
		global $WP_Statistics;

		if ( $WP_Statistics->option->get( 'exclude_loginpage' ) ) {
			$protocol = strpos( strtolower( $_SERVER['SERVER_PROTOCOL'] ), 'https' ) === false ? 'http' : 'https';
			$host     = $_SERVER['HTTP_HOST'];
			$script   = $_SERVER['SCRIPT_NAME'];

			$currentURL = $protocol . '://' . $host . $script;
			$loginURL   = wp_login_url();

			if ( $currentURL == $loginURL ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect if WordPress Admin Page.
	 */
	public static function exclusion_admin_page() {
		global $WP_Statistics;

		if ( $WP_Statistics->option->get( 'exclude_adminpage' ) and isset( $_SERVER['SERVER_NAME'] ) and isset( $_SERVER['REQUEST_URI'] ) ) {
			if ( stristr( $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'], "wp-admin" ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect if IP Match.
	 */
	public static function exclusion_iP_match() {
		global $WP_Statistics;

		try {
			$ip = new IP( \WP_STATISTICS\IP::getIP() );
		} catch ( \Exception $e ) {
			$ip = new IP( '127.0.0.1' );
		}

		// Pull the sub nets from the database.
		$subnets = explode( "\n", $WP_Statistics->option->get( 'exclude_ip' ) );

		// Check to see if we match any of the excluded addresses.
		foreach ( $subnets as $subnet ) {
			$subnet = trim( $subnet );

			// The shortest ip address is 1.1.1.1, anything less must be a malformed entry.
			if ( strlen( $subnet ) > 6 ) {

				try {
					$range_prased = Range::parse( $subnet )->contains( $ip );
				} catch ( \Exception $e ) {
					$range_prased = false;
				}

				if ( $range_prased ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Detect if Broken Link.
	 */
	public static function exclusion_broken_link() {
		// Check is 404
		if ( is_404() ) {

			//Check Current Page
			if ( isset( $_SERVER["HTTP_HOST"] ) and isset( $_SERVER["REQUEST_URI"] ) ) {

				//Get Full Url Page
				$page_url = ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? "https" : "http" ) . "://{$_SERVER["HTTP_HOST"]}{$_SERVER["REQUEST_URI"]}";

				//Check Link file
				$page_url = parse_url( $page_url, PHP_URL_PATH );
				$ext      = pathinfo( $page_url, PATHINFO_EXTENSION );
				if ( ! empty( $ext ) and $ext != 'php' ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Detect if Robots.
	 */
	public static function exclusion_robot() {
		global $WP_Statistics;

		// Pull the robots from the database.
		$robots = explode( "\n", $WP_Statistics->option->get( 'robotlist' ) );

		// Check to see if we match any of the robots.
		foreach ( $robots as $robot ) {
			$robot = trim( $robot );

			// If the match case is less than 4 characters long, it might match too much so don't execute it.
			if ( strlen( $robot ) > 3 ) {
				if ( stripos( UserAgent::getHttpUserAgent(), $robot ) !== false ) {
					return true;
				}
			}
		}

		// Check User IP is empty Or Not User Agent
		if ( $WP_Statistics->option->get( 'corrupt_browser_info' ) ) {
			if ( UserAgent::getHttpUserAgent() == '' || \WP_STATISTICS\IP::getIP() == '' ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Detect if GEO-IP include Or Exclude Country.
	 */
	public static function exclusion_geoip() {
		global $WP_Statistics;

		// Get User Location
		$location = GeoIP::getCountry();

		// Grab the excluded/included countries lists, force the country codes to be in upper case to match what the GeoIP code uses.
		$excluded_countries        = explode( "\n", strtoupper( str_replace( "\r\n", "\n", $WP_Statistics->option->get( 'excluded_countries' ) ) ) );
		$included_countries_string = trim( strtoupper( str_replace( "\r\n", "\n", $WP_Statistics->option->get( 'included_countries' ) ) ) );

		// We need to be really sure this isn't an empty string or explode will return an array with one entry instead of none.
		if ( $included_countries_string == '' ) {
			$included_countries = array();
		} else {
			$included_countries = explode( "\n", $included_countries_string );
		}

		// Check to see if the current location is in the excluded countries list.
		if ( in_array( $location, $excluded_countries ) ) {
			return true;
		} // Check to see if the current location is not the included countries list.
		else if ( ! in_array( $location, $included_countries ) && count( $included_countries ) > 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Detect if Exclude Host name.
	 */
	public static function exclusion_host_name() {
		global $WP_Statistics;

		// Get Host name List
		$excluded_host = explode( "\n", $WP_Statistics->option->get( 'excluded_hosts' ) );

		// If there's nothing in the excluded host list, don't do anything.
		if ( count( $excluded_host ) > 0 ) {
			$transient_name = 'wps_excluded_hostname_to_ip_cache';

			// Get the transient with the hostname cache.
			$hostname_cache = get_transient( $transient_name );

			// If the transient has expired (or has never been set), create one now.
			if ( $hostname_cache === false ) {
				// Flush the failed cache variable.
				$hostname_cache = array();

				// Loop through the list of hosts and look them up.
				foreach ( $excluded_host as $host ) {
					if ( strpos( $host, '.' ) > 0 ) {
						$hostname_cache[ $host ] = gethostbyname( $host . '.' );
					}
				}

				// Set the transient and store it for 1 hour.
				set_transient( $transient_name, $hostname_cache, 360 );
			}

			// Check if the current IP address matches one of the ones in the excluded hosts list.
			if ( in_array( $WP_Statistics->ip, $hostname_cache ) ) {
				return true;
			}
		}

		return false;
	}

}
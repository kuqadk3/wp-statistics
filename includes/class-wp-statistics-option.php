<?php

namespace WP_STATISTICS;

class Option {
	/**
	 * Get WP-Statistics Basic Option name
	 *
	 * @var string
	 */
	public static $opt_name = 'wp_statistics';

	/**
	 * Plugin options (Recorded in database)
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * User Options
	 *
	 * @var array
	 */
	public $user_options = array();

	/**
	 * is user options loaded?
	 *
	 * @var bool
	 */
	public $user_options_loaded = false;

	/**
	 * Option constructor.
	 */
	public function __construct() {
		$this->load_options();
	}

	/**
	 * WP-Statistics Default Option
	 *
	 * @param null $option_name
	 * @return array
	 */
	public static function Default_Option( $option_name = null ) {

		$options                          = array();
		$options['robotlist']             = Helper::get_robots_list();
		$options['search_converted']      = 1; //TODO Check and Remvoe it
		$options['anonymize_ips']         = false;
		$options['geoip']                 = false;
		$options['useronline']            = true;
		$options['visits']                = true;
		$options['visitors']              = true;
		$options['pages']                 = true;
		$options['check_online']          = UserOnline::$reset_user_time;
		$options['menu_bar']              = false;
		$options['coefficient']           = Visitor::$coefficient;
		$options['stats_report']          = false;
		$options['time_report']           = 'daily';
		$options['send_report']           = 'mail';
		$options['content_report']        = '';
		$options['update_geoip']          = true;
		$options['store_ua']              = false;
		$options['exclude_administrator'] = true;
		$options['disable_se_clearch']    = true;
		$options['disable_se_qwant']      = true;
		$options['disable_se_baidu']      = true;
		$options['disable_se_ask']        = true;
		$options['map_type']              = 'jqvmap';
		$options['force_robot_update']    = true;

		if ( $option_name and isset( $options[ $option_name ] ) ) {
			return $options[ $option_name ];
		}

		return $options;
	}

	/**
	 * Loads the options from WordPress
	 */
	public function load_options() {
		$this->options = get_option( self::$opt_name );

		if ( ! is_array( $this->options ) ) {
			$this->user_options = array();
		}
	}

	/**
	 * Loads the user options from WordPress.
	 * It is NOT called during the class constructor.
	 *
	 * @param bool|false $force
	 */
	public function load_user_options( $force = false ) {
		if ( $this->user_options_loaded == true && $force != true ) {
			return;
		}

		$this->user_options = get_user_meta( $GLOBALS['WP_Statistics']->user->ID, 'wp_statistics', true );
		if ( ! is_array( $this->user_options ) ) {
			$this->user_options = array();
		}

		$this->user_options_loaded = true;
	}

	/**
	 * mimics WordPress's get_option() function but uses the array instead of individual options.
	 *
	 * @param      $option
	 * @param null $default
	 *
	 * @return bool|null
	 */
	public function get( $option, $default = null ) {
		// If no options array exists, return FALSE.
		if ( ! is_array( $this->options ) ) {
			return false;
		}

		// if the option isn't set yet, return the $default if it exists, otherwise FALSE.
		if ( ! array_key_exists( $option, $this->options ) ) {
			if ( isset( $default ) ) {
				return $default;
			} else {
				return false;
			}
		}

		/**
		 * Filters a For Return WP-Statistics Option
		 *
		 * @param string $option Option name.
		 * @param string $value Option Value.
		 * @example add_filter('wp_statistics_option_coefficient', function(){ return 5; });
		 */
		return apply_filters( "wp_statistics_option_{$option}", $this->options[ $option ] );
	}


	/**
	 * mimics WordPress's get_user_meta() function
	 * But uses the array instead of individual options.
	 *
	 * @param      $option
	 * @param null $default
	 *
	 * @return bool|null
	 */
	public function user( $option, $default = null ) {
		// If the user id has not been set or no options array exists, return FALSE.
		if ( $GLOBALS['WP_Statistics']->user->ID == 0 ) {
			return false;
		}
		if ( ! is_array( $this->user_options ) ) {
			return false;
		}

		// if the option isn't set yet, return the $default if it exists, otherwise FALSE.
		if ( ! array_key_exists( $option, $this->user_options ) ) {
			if ( isset( $default ) ) {
				return $default;
			} else {
				return false;
			}
		}

		// Return the option.
		return $this->user_options[ $option ];
	}

	/**
	 * Mimics WordPress's update_option() function
	 * But uses the array instead of individual options.
	 *
	 * @param $option
	 * @param $value
	 */
	public function update( $option, $value ) {
		// Store the value in the array.
		$this->options[ $option ] = $value;

		// Write the array to the database.
		update_option( self::$opt_name, $this->options );
	}

	/**
	 * Mimics WordPress's update_user_meta() function
	 * But uses the array instead of individual options.
	 *
	 * @param $option
	 * @param $value
	 *
	 * @return bool
	 */
	public function update_user_option( $option, $value ) {
		// If the user id has not been set return FALSE.
		if ( $GLOBALS['WP_Statistics']->user->ID == 0 ) {
			return false;
		}

		// Store the value in the array.
		$this->user_options[ $option ] = $value;

		// Write the array to the database.
		update_user_meta( $GLOBALS['WP_Statistics']->user->ID, self::$opt_name, $this->user_options );
	}

	/**
	 * This function is similar to update_option,
	 * but it only stores the option in the array.
	 * This save some writing to the database if you have multiple values to update.
	 *
	 * @param $option
	 * @param $value
	 */
	public function store( $option, $value ) {
		$this->options[ $option ] = $value;
	}

	/**
	 * This function is similar to update_user_option,
	 * but it only stores the option in the array.
	 * This save some writing to the database if you have multiple values to update.
	 *
	 * @param $option
	 * @param $value
	 *
	 * @return bool
	 */
	public function store_user_option( $option, $value ) {
		// If the user id has not been set return FALSE.
		if ( $GLOBALS['WP_Statistics']->user->ID == 0 ) {
			return false;
		}

		$this->user_options[ $option ] = $value;
	}

	/**
	 * Saves the current options array to the database.
	 */
	public function save_options() {
		update_option( self::$opt_name, $this->options );
	}

	/**
	 * Saves the current user options array to the database.
	 *
	 * @return bool
	 */
	public function save_user_options() {
		if ( $GLOBALS['WP_Statistics']->user->ID == 0 ) {
			return false;
		}

		update_user_meta( $GLOBALS['WP_Statistics']->user->ID, self::$opt_name, $this->user_options );
	}

	/**
	 * Check to see if an option is currently set or not.
	 *
	 * @param $option
	 *
	 * @return bool
	 */
	public function isset_option( $option ) {
		if ( ! is_array( $this->options ) ) {
			return false;
		}

		return array_key_exists( $option, $this->options );
	}


}
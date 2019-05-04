<?php

namespace WP_STATISTICS;

class Admin_Menus {
	/**
	 * List Of Admin Page Slug WP-statistics
	 *
	 * -- Array Arg ---
	 * key   : page key for using another methods
	 * value : Admin Page Slug
	 *
	 * @var array
	 */
	public static $pages = array(
		'overview'     => 'overview',
		'browser'      => 'browsers',
		'countries'    => 'countries',
		'exclusions'   => 'exclusions',
		'hits'         => 'hits',
		'online'       => 'online',
		'pages'        => 'pages',
		'categories'   => 'categories',
		'authors'      => 'authors',
		'tags'         => 'tags',
		'referrers'    => 'referrers',
		'searches'     => 'searches',
		'words'        => 'words',
		'top-visitors' => 'top_visitors',
		'visitors'     => 'visitors',
		'optimization' => 'optimization',
		'settings'     => 'settings',
		'plugins'      => 'plugins',
		'donate'       => 'donate',
	);

	/**
	 * Admin Page Slug
	 *
	 * @var string
	 */
	public static $admin_menu_slug = 'wps_[slug]_page';

	/**
	 * Admin Page Load Action Slug
	 *
	 * @var string
	 */
	public static $load_admin_slug = 'toplevel_page_[slug]';

	/**
	 * Admin Page Load Action Slug
	 *
	 * @var string
	 */
	public static $load_admin_submenu_slug = 'statistics_page_[slug]';

	/**
	 * Wp-Statistics donate link
	 *
	 * @var string
	 */
	public static $donate = 'http://wp-statistics.com/donate';

	/**
	 * Get List Admin Pages
	 */
	public static function get_admin_page_list() {
		/**
		 * Get List Page
		 */
		foreach ( self::$pages as $page_key => $page_slug ) {
			$admin_list_page[ $page_key ] = self::get_page_slug( $page_slug );
		}
		return isset( $admin_list_page ) ? $admin_list_page : array();
	}

	/**
	 * Check in admin page
	 *
	 * @param $page | For Get List
	 * @return bool
	 */
	public static function in_page( $page ) {
		global $pagenow;
		return ( is_admin() and $pagenow == "admin.php" and isset( $_REQUEST['page'] ) and $_REQUEST['page'] == Admin_Menus::get_page_slug( $page ) );
	}

	/**
	 * Check if User in WP-Statistics Plugin Admin Page
	 */
	public static function in_plugin_page() {
		global $pagenow;
		if ( is_admin() and $pagenow == "admin.php" and isset( $_REQUEST['page'] ) ) {
			$admin_menu_slug = explode( "[slug]", self::$admin_menu_slug );
			preg_match( '/(?<=' . $admin_menu_slug[0] . ').*?(?=' . $admin_menu_slug[1] . ')/', $_REQUEST['page'], $page_name );
			if ( is_array( $page_name ) and count( $page_name ) > 0 ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get Admin Url
	 *
	 * @param null $page
	 * @param array $arg
	 * @area is_admin
	 * @return string
	 */
	public static function admin_url( $page = null, $arg = array() ) {

		//Check If Pages is in Wp-statistics
		if ( array_key_exists( $page, self::get_admin_page_list() ) ) {
			$page = self::get_page_slug( $page );
		}

		return add_query_arg( array_merge( array( 'page' => $page ), $arg ), admin_url( 'admin.php' ) );
	}

	/**
	 * Get Menu List
	 */
	public static function get_menu_list() {

		// Get the read/write capabilities required to view/manage the plugin as set by the user.
		// TODO Push To Helper Class with own Function -> wp_statistics_validate_capability
		$read_cap   = wp_statistics_validate_capability( Option::get( 'read_capability', 'manage_options' ) );
		$manage_cap = wp_statistics_validate_capability( Option::get( 'manage_capability', 'manage_options' ) );

		/**
		 * List of WP-Statistics Admin Menu
		 *
		 * --- Array Arg -----
		 * name       : Menu name
		 * title      : Page title / if not exist [title == name]
		 * cap        : min require capability @default $read_cap
		 * icon       : Wordpress DashIcon name
		 * method     : method that call in page @default log
		 * sub        : if sub menu , add main menu slug
		 * page_url   : link of Slug Url Page @see WP_Statistics::$page
		 * break      : add new line after sub menu if break key == true
		 * require    : the Condition From Wp-statistics Option if == true for show admin menu
		 *
		 */
		$list = array(
			'top'          => array(
				'title'    => __( 'Statistics', 'wp-statistics' ),
				'page_url' => 'overview',
				'method'   => 'log',
				'icon'     => 'dashicons-chart-pie',
			),
			'overview'     => array(
				'sub'      => 'overview',
				'title'    => __( 'Overview', 'wp-statistics' ),
				'page_url' => 'overview',
			),
			'hits'         => array(
				'require'  => array( 'visits' ),
				'sub'      => 'overview',
				'title'    => __( 'Hits', 'wp-statistics' ),
				'page_url' => 'hits',
			),
			'online'       => array(
				'require'  => array( 'useronline' ),
				'sub'      => 'overview',
				'title'    => __( 'Online', 'wp-statistics' ),
				'page_url' => 'online',
			),
			'referrers'    => array(
				'require'  => array( 'visitors' ),
				'sub'      => 'overview',
				'title'    => __( 'Referrers', 'wp-statistics' ),
				'page_url' => 'referrers',
			),
			'words'        => array(
				'require'  => array( 'visitors' ),
				'sub'      => 'overview',
				'title'    => __( 'Search Words', 'wp-statistics' ),
				'page_url' => 'words',
			),
			'searches'     => array(
				'require'  => array( 'visitors' ),
				'sub'      => 'overview',
				'title'    => __( 'Search Engines', 'wp-statistics' ),
				'page_url' => 'searches',
			),
			'pages'        => array(
				'require'  => array( 'pages' ),
				'sub'      => 'overview',
				'title'    => __( 'Pages', 'wp-statistics' ),
				'page_url' => 'pages',
			),
			'visitors'     => array(
				'require'  => array( 'visitors' ),
				'sub'      => 'overview',
				'title'    => __( 'Visitors', 'wp-statistics' ),
				'page_url' => 'visitors',
			),
			'countries'    => array(
				'require'  => array( 'geoip', 'visitors' ),
				'sub'      => 'overview',
				'title'    => __( 'Countries', 'wp-statistics' ),
				'page_url' => 'countries',
			),
			'categories'   => array(
				'require'  => array( 'pages' ),
				'sub'      => 'overview',
				'title'    => __( 'Categories', 'wp-statistics' ),
				'page_url' => 'categories',
			),
			'tags'         => array(
				'require'  => array( 'pages' ),
				'sub'      => 'overview',
				'title'    => __( 'Tags', 'wp-statistics' ),
				'page_url' => 'tags',
			),
			'authors'      => array(
				'require'  => array( 'pages' ),
				'sub'      => 'overview',
				'title'    => __( 'Authors', 'wp-statistics' ),
				'page_url' => 'authors',
			),
			'browsers'     => array(
				'require'  => array( 'visitors' ),
				'sub'      => 'overview',
				'title'    => __( 'Browsers', 'wp-statistics' ),
				'page_url' => 'browser',
			),
			'top.visotors' => array(
				'require'  => array( 'visitors' ),
				'sub'      => 'overview',
				'title'    => __( 'Top Visitors Today', 'wp-statistics' ),
				'page_url' => 'top-visitors',
			),
			'exclusions'   => array(
				'require'  => array( 'record_exclusions' ),
				'sub'      => 'overview',
				'title'    => __( 'Exclusions', 'wp-statistics' ),
				'page_url' => 'exclusions',
				'break'    => true,
			),
			'optimize'     => array(
				'sub'      => 'overview',
				'title'    => __( 'Optimization', 'wp-statistics' ),
				'cap'      => $manage_cap,
				'page_url' => 'optimization',
				'method'   => 'optimization'
			),
			'settings'     => array(
				'sub'      => 'overview',
				'title'    => __( 'Settings', 'wp-statistics' ),
				'cap'      => $manage_cap,
				'page_url' => 'settings',
				'method'   => 'settings'
			),
			'plugins'      => array(
				'sub'      => 'overview',
				'title'    => __( 'Add-Ons', 'wp-statistics' ),
				'name'     => '<span class="wps-text-warning">' . __( 'Add-Ons', 'wp-statistics' ) . '</span>',
				'page_url' => 'plugins',
				'method'   => 'plugins'
			),
			'donate'       => array(
				'sub'      => 'overview',
				'title'    => __( 'Donate', 'wp-statistics' ),
				'name'     => '<span class="wps-text-success">' . __( 'Donate', 'wp-statistics' ) . '</span>',
				'page_url' => 'donate',
				'method'   => 'donate'
			)
		);

		/**
		 * WP-Statistics Admin Page List
		 *
		 * @example add_filter('wp_statistics_admin_menu_list', function( $list ){ unset( $list['plugins'] ); return $list; });
		 */
		return apply_filters( 'wp_statistics_admin_menu_list', $list );
	}

	/**
	 * Get Menu Slug
	 *
	 * @param $page_slug
	 * @return mixed
	 */
	public static function get_page_slug( $page_slug ) {
		return str_ireplace( "[slug]", $page_slug, self::$admin_menu_slug );
	}

	/**
	 * Get Default Load Action in Load Any WordPress Page Slug
	 *
	 * @param $page_slug
	 * @return mixed
	 */
	public static function get_action_menu_slug( $page_slug ) {
		return str_ireplace( "[slug]", self::get_page_slug( $page_slug ), self::$load_admin_slug );
	}

	/**
	 * Menu constructor.
	 */
	public function __construct() {

		# Load WP-Statistics Admin Menu
		add_action( 'admin_menu', array( $this, 'wp_admin_menu' ) );

		# Filter Donate Link
		add_action( "load-" . str_replace( "[slug]", self::get_page_slug( 'donate' ), self::$load_admin_submenu_slug ), array( $this, 'donate' ) );

	}

	/**
	 * Load WordPress Admin Menu
	 */
	public function wp_admin_menu() {

		// Get the read/write capabilities required to view/manage the plugin as set by the user.
		$read_cap   = wp_statistics_validate_capability( Option::get( 'read_capability', 'manage_options' ) );
		$manage_cap = wp_statistics_validate_capability( Option::get( 'manage_capability', 'manage_options' ) );

		//Show Admin Menu List
		foreach ( self::get_menu_list() as $key => $menu ) {

			//Check Default variable
			$capability = $read_cap;
			$method     = 'log';
			$name       = $menu['title'];
			if ( array_key_exists( 'cap', $menu ) ) {
				$capability = $menu['cap'];
			}
			if ( array_key_exists( 'method', $menu ) ) {
				$method = $menu['method'];
			}
			if ( array_key_exists( 'name', $menu ) ) {
				$name = $menu['name'];
			}

			//Check if SubMenu or Main Menu
			if ( array_key_exists( 'sub', $menu ) ) {

				//Check Conditions For Show Menu
				if ( Option::check_option_require( $menu ) === true ) {
					add_submenu_page( self::get_page_slug( $menu['sub'] ), $menu['title'], $name, $capability, self::get_page_slug( $menu['page_url'] ), array( '\WP_STATISTICS\\' . $method . '_page', 'view' ) );
				}

				//Check if add Break Line
				if ( array_key_exists( 'break', $menu ) ) {
					add_submenu_page( self::get_page_slug( $menu['sub'] ), '', '', $capability, 'wps_break_menu', array( '\WP_STATISTICS\\' . $method . '_page', $method ) );
				}
			} else {
				add_menu_page( $menu['title'], $name, $capability, self::get_page_slug( $menu['page_url'] ), array( '\WP_STATISTICS\\' . $method . '_page', $method ), $menu['icon'] );
			}
		}

		// Add action to load the meta boxes to the overview page.
		// TODO Push to OrverView Page Class
		add_action( 'load-' . self::get_action_menu_slug( 'overview' ), array( '\WP_STATISTICS\Admin_Pages', 'overview' ) );
	}

	/**
	 * WP-Statistics Donate Page
	 */
	public function donate() {
		wp_redirect( self::$donate );
		exit;
	}

}
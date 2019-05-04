<?php

namespace WP_STATISTICS;

class Meta_Box {
	/**
	 * Meta Box Class namespace
	 *
	 * @var string
	 */
	public static $namespace = "\\WP_Statistics\\MetaBox\\";

	/**
	 * Meta Box Setup Key
	 *
	 * @param $key
	 * @return string
	 */
	public static function getMetaBoxKey( $key ) {
		return 'wp-statistics-' . $key . '-widget';
	}

	/**
	 * Load WordPress Meta Box
	 */
	public static function load() {

		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-quickstats.php';
		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-summary.php';
		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-browsers.php';
		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-countries.php';
		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-hits.php';
		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-pages.php';
		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-referring.php';
		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-search.php';
		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-words.php';
		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-top-visitors.php';
		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-recent.php';
		require_once WP_STATISTICS_DIR . 'includes/meta-box/wp-statistics-meta-box-hitsmap.php';
	}

	/**
	 * Get Admin Meta Box List
	 *
	 * @param bool $meta_box
	 * @return array|mixed
	 */
	public static function _list( $meta_box = false ) {

		/**
		 * List of WP-Statistics Admin Meta Box
		 *
		 * --- Array Arg -----
		 * page_url          : link of Widget Page @see WP_Statistics::$page
		 * name              : Name Of Widget Box
		 * require           : the Condition From Wp-statistics Option if == true
		 * show_on_dashboard : Show Meta Box in WordPress Dashboard
		 * hidden            : if set true , Default Hidden Dashboard in Wordpress Admin
		 *
		 */
		$list = array(
			'quickstats'   => array(
				'page_url'          => 'overview',
				'name'              => __( 'Quick Stats', 'wp-statistics' ),
				'show_on_dashboard' => true,
				'hidden'            => false
			),
			'summary'      => array(
				'name'              => __( 'Summary', 'wp-statistics' ),
				'hidden'            => true,
				'show_on_dashboard' => true
			),
			'browsers'     => array(
				'page_url'          => 'browser',
				'name'              => __( 'Top 10 Browsers', 'wp-statistics' ),
				'require'           => array( 'visitors' ),
				'hidden'            => true,
				'show_on_dashboard' => true
			),
			'countries'    => array(
				'page_url'          => 'countries',
				'name'              => __( 'Top 10 Countries', 'wp-statistics' ),
				'require'           => array( 'geoip', 'visitors' ),
				'hidden'            => true,
				'show_on_dashboard' => true
			),
			'hits'         => array(
				'page_url'          => 'hits',
				'name'              => __( 'Hit Statistics', 'wp-statistics' ),
				'require'           => array( 'visits' ),
				'hidden'            => true,
				'show_on_dashboard' => true
			),
			'pages'        => array(
				'page_url'          => 'pages',
				'name'              => __( 'Top 10 Pages', 'wp-statistics' ),
				'require'           => array( 'pages' ),
				'hidden'            => true,
				'show_on_dashboard' => true
			),
			'referring'    => array(
				'page_url'          => 'referrers',
				'name'              => __( 'Top Referring Sites', 'wp-statistics' ),
				'require'           => array( 'visitors' ),
				'hidden'            => true,
				'show_on_dashboard' => true
			),
			'search'       => array(
				'page_url'          => 'searches',
				'name'              => __( 'Search Engine Referrals', 'wp-statistics' ),
				'require'           => array( 'visitors' ),
				'hidden'            => true,
				'show_on_dashboard' => true
			),
			'words'        => array(
				'page_url'          => 'words',
				'name'              => __( 'Latest Search Words', 'wp-statistics' ),
				'require'           => array( 'visitors' ),
				'hidden'            => true,
				'show_on_dashboard' => true
			),
			'top-visitors' => array(
				'page_url'          => 'top-visitors',
				'name'              => __( 'Top 10 Visitors Today', 'wp-statistics' ),
				'require'           => array( 'visitors' ),
				'hidden'            => true,
				'show_on_dashboard' => true
			),
			'recent'       => array(
				'page_url'          => 'visitors',
				'name'              => __( 'Recent Visitors', 'wp-statistics' ),
				'require'           => array( 'visitors' ),
				'hidden'            => true,
				'show_on_dashboard' => true
			),
			'hitsmap'      => array(
				'name'              => __( 'Today\'s Visitors Map', 'wp-statistics' ),
				'require'           => array( 'visitors' ),
				'hidden'            => true,
				'show_on_dashboard' => true
			)
		);

		//Print List of Meta Box
		if ( $meta_box === false ) {
			return $list;
		} else {
			if ( array_key_exists( $meta_box, $list ) ) {
				return $list[ $meta_box ];
			}
		}

		return array();
	}

	/**
	 * Get Meta Box Class name
	 *
	 * @param $meta_box
	 * @return string
	 */
	public static function getMetaBoxClass( $meta_box ) {
		return self::$namespace . str_replace( "-", "_", $meta_box );
	}

	/**
	 * Check Exist Meta Box Class
	 *
	 * @param $meta_box
	 * @return bool
	 */
	public static function IsExistMetaBoxClass( $meta_box ) {
		return class_exists( self::getMetaBoxClass( $meta_box ) );
	}

}
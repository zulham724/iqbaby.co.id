<?php
/**
 * Manage install, and performs all post update operations
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Product FIlter
 * @version 4.0.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Install' ) ) {
	/**
	 * Filter Presets Handling
	 *
	 * @since 4.0.0
	 */
	class YITH_WCAN_Install {

		/**
		 * Name of Filters Lookup table
		 *
		 * @var string
		 */
		public static $filter_sessions;

		/**
		 * Stored version
		 *
		 * @var string
		 */
		private static $_stored_version;

		/**
		 * Stored DB version
		 *
		 * @var string
		 */
		private static $_stored_db_version;

		/**
		 * Default preset slug
		 *
		 * @var string
		 */
		private static $_default_preset_slug = 'default-preset';

		/**
		 * Hooks methods required to install/update plugin
		 *
		 * @return void
		 */
		public static function init() {
			global $wpdb;

			// initialize db tables.
			self::$filter_sessions = "{$wpdb->prefix}yith_wcan_filter_sessions";
			$wpdb->filter_sessions = self::$filter_sessions;

			add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
			add_action( 'init', array( __CLASS__, 'check_db_version' ), 5 );
			add_action( 'init', array( __CLASS__, 'install_endpoints' ), 10 );

			add_filter( 'yith_wcan_default_accent_color', array( __CLASS__, 'set_default_accent' ) );
		}

		/**
		 * Check current version, and trigger update procedures when needed
		 *
		 * @return void
		 */
		public static function check_version() {
			self::$_stored_version = get_option( 'yith_wcan_version' );

			if ( version_compare( self::$_stored_version, YITH_WCAN_VERSION, '<' ) ) {
				self::update();
				do_action( 'yith_wcan_updated' );
			}
		}

		/**
		 * Check current version, and trigger db update procedures when needed
		 *
		 * @return void
		 */
		public static function check_db_version() {
			self::$_stored_db_version = get_option( 'yith_wcan_db_version' );

			if ( version_compare( self::$_stored_db_version, YITH_WCAN_DB_VERSION, '<' ) ) {
				self::update_db();
				do_action( 'yith_wcan_db_updated' );
			}
		}

		/**
		 * Install required endpoints
		 *
		 * @return void
		 */
		public static function install_endpoints() {
			$session_param = YITH_WCAN_Session_Factory::get_session_query_param();

			add_rewrite_endpoint( $session_param, EP_PERMALINK | EP_PAGES | EP_CATEGORIES );

			/**
			 * Hot fix for static front pages.
			 * EP_ROOT bitmask for endpoints seems not to work well with static homepages; even if request is correctly
			 * parser, page_id isn't added to query_vars, with the result of defaulting to post archives.
			 *
			 * For this very reason we use a specific rewrite rule for static homepages, containing filter_session param
			 */
			$front_page = get_option( 'page_on_front' );

			if ( $front_page ) {
				add_rewrite_rule( '^filter_session(/(.*))?/?$', "index.php?page_id=$front_page&$session_param=\$matches[2]", 'top' );
			}

			/**
			 * Hot fix for shop page, working as product archive page.
			 * Endpoint would work for the shop page, but if you add it to the url, ^shop/?$ rewrite rule, automagically
			 * added to manage products archive, won't match any longer
			 *
			 * For this very reason we use a specific rewrite rule for shop products archive.
			 */
			$shop_page_id = wc_get_page_id( 'shop' );

			if ( current_theme_supports( 'woocommerce' ) ) {
				$has_archive = $shop_page_id && get_post( $shop_page_id ) ? urldecode( get_page_uri( $shop_page_id ) ) : 'shop';

				add_rewrite_rule( "^$has_archive/filter_session(/(.*))?/?$", "index.php?post_type=product&$session_param=\$matches[2]", 'top' );
			}
		}

		/**
		 * Update/install procedure
		 *
		 * @return void
		 */
		public static function update() {
			self::maybe_create_preset();
			self::maybe_show_upgrade_note();
			self::maybe_update_options();
			self::maybe_flush_rules();
			self::update_version();
		}

		/**
		 * DB update/install procedure
		 *
		 * @return void
		 */
		public static function update_db() {
			self::maybe_update_tables();
			self::update_db_version();
		}

		/**
		 * Create default preset, when it doesn't exists already
		 *
		 * @return void
		 */
		public static function maybe_create_preset() {
			// if preset already exists, skip.
			if ( ! self::should_create_default_preset() ) {
				return;
			}

			$new_preset = new YITH_WCAN_Preset();

			$new_preset->set_slug( self::$_default_preset_slug );
			$new_preset->set_title( _x( 'Default preset', '[ADMIN] Name of default preset that is installed with the plugin', 'yith-woocommerce-ajax-navigation' ) );
			$new_preset->set_filters( self::_get_default_filters() );
			$new_preset->save();

			update_option( 'yith_wcan_default_preset_created', true );

			do_action( 'yith_wcan_default_preset_created' );
		}

		/**
		 * Flag Upgrade Note for display, when there are widgets in the sidebar
		 *
		 * @return void
		 */
		public static function maybe_show_upgrade_note() {
			if ( ! self::should_show_upgrade_note() ) {
				return;
			}

			// set upgrade note status: 0 => hide; 1 => show.
			update_option( 'yith_wcan_upgrade_note_status', 1 );
		}

		/**
		 * Update options to latest version, when required
		 *
		 * @return void
		 */
		public static function maybe_update_options() {
			// do incremental upgrade.
			version_compare( self::$_stored_version, '4.0.0', '<' ) && self::_do_400_upgrade();

			// space for future revisions.

			do_action( 'yith_wcan_did_option_upgrade' );
		}

		/**
		 * Create or update tables for the plugin
		 *
		 * The dbDelta function will require correct operation depending on current DB structure.
		 *
		 * @return void
		 */
		public static function maybe_update_tables() {
			global $wpdb;

			$table   = self::$filter_sessions;
			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				$collate = $wpdb->get_charset_collate();
			}

			$sql = "CREATE TABLE {$table} (
							ID BIGINT( 20 ) NOT NULL AUTO_INCREMENT,
							hash CHAR( 32 ) NOT NULL,
							token CHAR( 10 ) NOT NULL,
							origin_url TEXT NOT NULL,
							query_vars TEXT NOT NULL,
							expiration timestamp NULL DEFAULT NULL,
							PRIMARY KEY  ( ID ),
							UNIQUE KEY filter_hash ( hash ),
							UNIQUE KEY filter_token ( token ),
							KEY filter_expiration ( expiration )
						) $collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}

		/**
		 * Updated version option to latest version, to avoid executing upgrade multiple times
		 *
		 * @return void
		 */
		public static function update_version() {
			update_option( 'yith_wcan_version', YITH_WCAN_VERSION );
		}

		/**
		 * Updated version option to latest db version, to avoid executing upgrade multiple times
		 *
		 * @return void
		 */
		public static function update_db_version() {
			update_option( 'yith_wcan_db_version', YITH_WCAN_DB_VERSION );
		}

		/**
		 * Get default preset, when it exists
		 *
		 * @return bool|YITH_WCAN_Preset
		 */
		public static function get_default_preset() {
			return YITH_WCAN_Presets::get_preset( self::$_default_preset_slug );
		}

		/**
		 * Checks whether we should create default preset
		 *
		 * @return bool Whether we should create default preset
		 */
		public static function should_create_default_preset() {
			return ! self::get_default_preset() && ! get_option( 'yith_wcan_default_preset_created' );
		}

		/**
		 * Checks whether we should show upgrade to preset notice
		 *
		 * @return bool Whether we should show upgrade note
		 */
		public static function should_show_upgrade_note() {
			// check if note was already dismissed.
			if ( '0' === get_option( 'yith_wcan_upgrade_note_status' ) ) {
				return false;
			}

			// check whether there is any filter in the sidebar.
			return ! ! yith_wcan_get_sidebar_with_filters();
		}

		/**
		 * Flush rewrite rules on key plugin update
		 *
		 * @return void
		 */
		public static function maybe_flush_rules() {
			version_compare( self::$_stored_version, '4.0.0', '<' ) && flush_rewrite_rules();
		}

		/**
		 * Set default accent color, when possible, matching theme's style
		 *
		 * @param string $default_accent Default color accent.
		 *
		 * @return string Filtered color code.
		 */
		public static function set_default_accent( $default_accent ) {
			if ( ! defined( 'YITH_PROTEO_VERSION' ) ) {
				return $default_accent;
			}

			return get_theme_mod( 'yith_proteo_main_color_shade', '#448a85' );
		}

		/**
		 * Upgrade options to version 4.0.0
		 *
		 * @return void.
		 */
		private static function _do_400_upgrade() {
			$old_options = get_option( 'yit_wcan_options' );

			if ( ! $old_options ) {
				return;
			}

			$options_to_export = array(
				'yith_wcan_enable_seo',
				'yith_wcan_seo_value',
				'yith_wcan_seo_rel_nofollow',
				'yith_wcan_change_browser_url',
			);

			foreach ( $options_to_export as $option ) {
				update_option( $option, yith_wcan_get_option( $option ) );
			}
		}

		/**
		 * Generates default filters for the preset created on first installation of the plugin
		 *
		 * @return array Array of filters.
		 */
		private static function _get_default_filters() {
			$filters = array();

			// set taxonomies filters.
			$filters = array_merge( $filters, self::_get_taxonomies_filters() );

			// set additional filters.
			$filters[] = self::_get_price_filter();
			$filters[] = self::_get_review_filter();
			$filters[] = self::_get_sale_stock_filter();
			$filters[] = self::_get_orederby_filter();

			return apply_filters( 'yith_wcan_default_filters', $filters );
		}

		/**
		 * Generates default Taxonomies filters for the preset created on first installation of the plugin
		 *
		 * @return array Array of filters.
		 */
		private static function _get_taxonomies_filters() {
			$filters = array();

			// start with taxonomy filters.
			$supported_taxonomies = YITH_WCAN_Query()->get_supported_taxonomies();

			foreach ( $supported_taxonomies as $taxonomy_slug => $taxonomy_object ) {
				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy_slug,
						'hide_empty' => true,
						'number'     => apply_filters( 'yith_wcan_max_default_term_count', 20 ),
					)
				);

				if ( empty( $terms ) ) {
					continue;
				}

				$filter = new YITH_WCAN_Filter_Tax();
				$terms_array = array();

				foreach ( $terms as $term ) {
					$terms_array[ $term->term_id ] = array(
						'label' => $term->name,
						'tooltip' => $term->name,
					);
				}

				// translators: 1. Taxonomy name.
				$filter->set_title( sprintf( _x( 'Filter by %s', '[ADMIN] Name of default taxonomy filter created by plugin', 'yith-woocommerce-ajax-navigation' ), $taxonomy_object->label ) );
				$filter->set_taxonomy( $taxonomy_slug );
				$filter->set_terms( $terms_array );
				$filter->set_filter_design( 'checkbox' );
				$filter->set_show_toggle( 'no' );
				$filter->set_show_count( 'no' );
				$filter->set_hierarchical( 'no' );
				$filter->set_multiple( 'yes' );
				$filter->set_relation( 'and' );
				$filter->set_adoptive( 'hide' );

				$filters[] = $filter->get_data();
			}

			return $filters;
		}

		/**
		 * Generates default Price filter for the preset created on first installation of the plugin
		 *
		 * @return array Filter options.
		 */
		private static function _get_price_filter() {
			global $wpdb;

			$filter = new YITH_WCAN_Filter_Price_Slider();

			// lookup for max product price.
			$max_price = $wpdb->get_var( "SELECT MAX(max_price) FROM {$wpdb->prefix}wc_product_meta_lookup" );
			$step      = max( (int) $max_price / 10, 1 );

			$filter->set_title( _x( 'Filter by price', '[ADMIN] Name of default price filter created by plugin', 'yith-woocommerce-ajax-navigation' ) );
			$filter->set_show_toggle( 'no' );
			$filter->set_price_slider_min( 0 );
			$filter->set_price_slider_max( ceil( $max_price ) );
			$filter->set_price_slider_step( floor( $step ) );

			return $filter->get_data();
		}

		/**
		 * Generates default Review filter for the preset created on first installation of the plugin
		 *
		 * @return array Filter options.
		 */
		private static function _get_review_filter() {
			$filter = new YITH_WCAN_Filter_Review();

			$filter->set_title( _x( 'Filter by review', '[ADMIN] Name of default review filter created by plugin', 'yith-woocommerce-ajax-navigation' ) );
			$filter->set_show_toggle( 'no' );
			$filter->set_show_count( 'no' );
			$filter->set_adoptive( 'hide' );

			return $filter->get_data();
		}

		/**
		 * Generates default Stock/Sale filter for the preset created on first installation of the plugin
		 *
		 * @return array Filter options.
		 */
		private static function _get_sale_stock_filter() {
			$filter = new YITH_WCAN_Filter_Stock_Sale();

			$filter->set_title( _x( 'Additional filters', '[ADMIN] Name of default stock/sale filter created by plugin', 'yith-woocommerce-ajax-navigation' ) );
			$filter->set_show_toggle( 'no' );
			$filter->set_show_count( 'no' );
			$filter->set_adoptive( 'hide' );

			return $filter->get_data();
		}

		/**
		 * Generates default Orderby filter for the preset created on first installation of the plugin
		 *
		 * @return array Filter options.
		 */
		private static function _get_orederby_filter() {
			$filter = new YITH_WCAN_Filter_Orderby();

			$filter->set_title( _x( 'Order by', '[ADMIN] Name of default order by filter created by plugin', 'yith-woocommerce-ajax-navigation' ) );
			$filter->set_show_toggle( 'no' );
			$filter->set_order_options( array_keys( YITH_WCAN_Filter_Factory::get_supported_orders() ) );

			return $filter->get_data();
		}

	}
}

YITH_WCAN_Install::init();

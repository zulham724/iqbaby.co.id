<?php
/**
 * Query modification to filter products
 *
 * Filters WooCommerce query, to show only products matching selection
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Product Filter
 * @version 4.0.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Query_Premium' ) ) {
	/**
	 * Query Handling
	 *
	 * @since 4.0.0
	 */
	class YITH_WCAN_Query_Premium extends YITH_WCAN_Query {

		/**
		 * Main instance
		 *
		 * @var YITH_WCAN_Query_Premium
		 * @since 4.0.0
		 */
		protected static $_instance = null;

		/**
		 * Returns an array of supported taxonomies for filtering
		 *
		 * @return WP_Taxonomy[] Array of WP_Taxonomy objects
		 */
		public function get_supported_taxonomies() {
			if ( empty( $this->_supported_taxonomies ) ) {
				$product_taxonomies   = get_object_taxonomies( 'product', 'objects' );
				$supported_taxonomies = array();
				$excluded_taxonomies  = apply_filters(
					'yith_wcan_excluded_taxonomies',
					array(
						'product_type',
						'product_visibility',
						'product_shipping_class',
					)
				);

				if ( ! empty( $product_taxonomies ) ) {
					foreach ( $product_taxonomies as $taxonomy_slug => $taxonomy ) {
						if ( in_array( $taxonomy_slug, $excluded_taxonomies ) ) {
							continue;
						}

						$supported_taxonomies[ $taxonomy_slug ] = $taxonomy;
					}
				}

				$this->_supported_taxonomies = apply_filters( 'yith_wcan_supported_taxonomies', $supported_taxonomies );
			}

			return $this->_supported_taxonomies;
		}

		/**
		 * Query class Instance
		 *
		 * @return YITH_WCAN_Query_Premium Query class instance
		 * @author Antonio La Rocca <antonio.larocca@yithemes.com>
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}
}
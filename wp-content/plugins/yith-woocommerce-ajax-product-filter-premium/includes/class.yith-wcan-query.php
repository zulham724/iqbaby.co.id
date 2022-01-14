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

if ( ! class_exists( 'YITH_WCAN_Query' ) ) {
	/**
	 * Query Handling
	 *
	 * @since 4.0.0
	 */
	class YITH_WCAN_Query {
		/**
		 * Query parameter added to any filtered page url
		 *
		 * @var string
		 */
		protected $_query_param = 'yith_wcan';

		/**
		 * List of query vars submitted for current query
		 *
		 * @var array submitted query vars.
		 */
		protected $_query_vars = array();

		/**
		 * Get taxonomies that will be used for filtering
		 *
		 * @var array
		 */
		protected $_supported_taxonomies = array();

		/**
		 * Products retrieved by by last query
		 *
		 * @var array
		 */
		protected $_products = array();

		/**
		 * An array of product ids matcing current query, per filter
		 *
		 * @var array
		 */
		protected $_products_per_filter = array();

		/**
		 * An array of currently choosen attributes
		 *
		 * @var array
		 */
		protected $_chosen_attributes;

		/**
		 * Current filtering session, if any
		 *
		 * @var YITH_WCAN_Session
		 */
		protected $_session = null;

		/**
		 * Main instance
		 *
		 * @var YITH_WCAN_Query
		 * @since 4.0.0
		 */
		protected static $_instance = null;

		/**
		 * Constructor method for the class
		 */
		public function __construct() {
			// prepare query param.
			$this->_query_param = apply_filters( 'yith_wcan_query_param', $this->_query_param );

			// do all pre-flight preparation.
			add_action( 'parse_request', array( $this, 'suppress_default_query_vars' ) );
			add_action( 'pre_get_posts', array( $this, 'prefetch_session' ), 5 );
			add_filter( 'redirect_canonical', array( $this, 'suppress_canonical_redirect' ) );

			// let's start filtering.
			add_action( 'wp', array( $this, 'start_filtering' ) );
			add_action( 'wp', array( $this, 'register_products' ) );

			// alter default wc query.
			add_action( 'woocommerce_product_query', array( $this, 'alter_product_query' ), 10, 1 );
		}

		/* === QUERY VARS METHODS === */

		/**
		 * Get single query var
		 *
		 * @param string $query_var Query var to retrieve.
		 * @param mixed  $default   Default value, to use when query var isn't set.
		 *
		 * @return mixed Query var value, or default
		 */
		public function get( $query_var, $default = '' ) {
			$query_vars = $this->get_query_vars();

			if ( isset( $query_vars[ $query_var ] ) ) {
				return $query_vars[ $query_var ];
			}

			return $default;
		}

		/**
		 * Get single query var
		 *
		 * @param string $query_var Query var to retrieve.
		 * @param mixed  $value     Value to ues for the query var.
		 */
		public function set( $query_var, $value ) {
			$this->_query_vars[ $query_var ] = $value;
		}

		/**
		 * Retrieves currently set query vars
		 *
		 * @return array Array of retrieved query vars; expected format: [
		 *     <product_taxonomy> => list of terms separated by , (OR) or by + (AND)
		 *     filter_<product_attribute> => list of terms separated by ,
		 *     meta_<meta_key> => meta value, eventually prefixed by operator (<,>, <=, >=, !=, IN, NOTIN)
		 *     query_type_<product_attribute> => and/or,
		 *     min_price => float,
		 *     max_price => float,
		 *     rating_filter => int,
		 *     orderby => string,
		 *     order => string,
		 *     onsale_filter => bool,
		 *     instock_filter => bool,
		 * ]
		 */
		public function get_query_vars() {
			if ( ! empty( $this->_query_vars ) ) {
				return $this->_query_vars;
			}

			$session = $this->maybe_retrieve_current_session();

			if ( $session ) {
				$query = $session->get_query_vars();
			} else {
				$query = array_map(
					function ( $string ) {
						$string = str_replace( ' ', '+', $string );

						return wc_clean( $string );
					},
					$_GET
				);

				// unset parameters that aren't related to filters.
				$supported_parameters = apply_filters(
					'yith_wcan_query_supported_parameters',
					array_merge(
						array(
							'min_price',
							'max_price',
							'rating_filter',
							'orderby',
							'order',
							'onsale_filter',
							'instock_filter',
						),
						array_keys( $this->get_supported_taxonomies() )
					)
				);

				// remove parameters that won't contribute to filtering.
				if ( ! empty( $query ) ) {
					foreach ( $query as $key => $value ) {
						if ( 0 === strpos( $key, 'filter_' ) ) {
							// include layered nav attributes filtering parameters.
							continue;
						} elseif ( 0 === strpos( $key, 'meta_' ) ) {
							// include meta filtering parameters.
							continue;
						} elseif ( 0 === strpos( $key, 'query_type_' ) ) {
							// include meta filtering parameters.
							continue;
						} elseif ( ! in_array( $key, $supported_parameters ) ) {
							unset( $query[ $key ] );
						}
					}
				}

				// add any parameter related to current page.
				if ( is_product_taxonomy() ) {
					global $wp_query;

					$qo = $wp_query instanceof WP_Query ? $wp_query->get_queried_object() : false;

					if ( $qo instanceof WP_Term && ! isset( $query[ $qo->taxonomy ] ) ) {
						$query[ $qo->taxonomy ] = $qo->slug;
					}
				}
			}

			$this->_query_vars = apply_filters( 'yith_wcan_query_vars', $query, $this );

			// if current query set isn't provided by a session, try to register one.
			if ( ! $session && $this->_query_vars ) {
				$this->maybe_register_current_session( $this->get_base_filter_url(), $this->_query_vars );
			}

			// return query.
			return $query;
		}

		/* === GET METHODS === */

		/**
		 * Returns an array of active filters
		 *
		 * Format of the array will change depending on context param:
		 * 'edit' : will provide an internal filters description, as provided by \YITH_WCAN_Query::get_query_vars.
		 * 'view' : will provide a formatted description, to be used to print templates; this format will be as follows:
		 * [
		 *    'filter_slug' => [                          // Each active filter will be described by an array
		 *       'label' => 'Product Categories',         // Localized label for current filter
		 *       'values' => [                            // Each of the items active for current filter (most filter will only accepts one)
		 *          [
		 *             'label' => 'Accessories'           // Label of the item
		 *             'query_vars' => [                  // Query vars that describes this item (used to remove item from filters when needed)
		 *                 'product_cat' => 'accessories,
		 *             ],
		 *          ],
		 *       ],
		 *    ],
		 * ]
		 *
		 * @param string $context Type of expected result.
		 * @return array Result set.
		 */
		public function get_active_filters( $context = 'edit' ) {
			$query_vars = $this->get_query_vars();

			if ( 'edit' === $context ) {
				return $query_vars;
			} else {
				$active_filters = array();
				$taxonomies     = $this->get_supported_taxonomies();

				$labels = apply_filters(
					'yith_wcan_query_supported_labels',
					array_merge(
						array(
							'price_range' => _x( 'Price', '[FRONTEND] Active filter labels', 'yith-woocommerce-ajax-navigation' ),
							'orderby' => _x( 'Order by', '[FRONTEND] Active filter labels', 'yith-woocommerce-ajax-navigation' ),
							'rating_filter' => _x( 'Rating', '[FRONTEND] Active filter labels', 'yith-woocommerce-ajax-navigation' ),
							'onsale_filter' => _x( 'On sale', '[FRONTEND] Active filter labels', 'yith-woocommerce-ajax-navigation' ),
							'instock_filter' => _x( 'In stock', '[FRONTEND] Active filter labels', 'yith-woocommerce-ajax-navigation' ),
						),
						wp_list_pluck( $taxonomies, 'label' )
					)
				);

				foreach ( $labels as $filter => $label ) {
					switch ( $filter ) {
						case 'price_range':
							if ( ! isset( $query_vars['min_price'] ) && ! isset( $query_vars['max_price'] ) ) {
								continue 2;
							}

							if ( isset( $query_vars['max_price'] ) ) {
								$range_label = sprintf(
								// translators: 1. Formatted min price of the range. 2. Formatted max price of the range.
									_x( '%1$s - %2$s', '[FRONTEND] Active price filter label', 'yith-woocommerce-ajax-navigation' ),
									isset( $query_vars['min_price'] ) ? wc_price( $query_vars['min_price'] ) : wc_price( 0 ),
									isset( $query_vars['max_price'] ) ? wc_price( $query_vars['max_price'] ) : '-'
								);
							} else {
								$range_label = sprintf(
								// translators: 1. Formatted min price of the range. 2. Formatted max price of the range.
									_x( '%1$s & above', '[FRONTEND] Active price filter label', 'yith-woocommerce-ajax-navigation' ),
									isset( $query_vars['min_price'] ) ? wc_price( $query_vars['min_price'] ) : wc_price( 0 )
								);
							}

							$active_filters[ $filter ] = array(
								'label'  => $label,
								'values' => array(
									array(
										'label' => $range_label,
										'query_vars' => array(
											'min_price' => isset( $query_vars['min_price'] ) ? $query_vars['min_price'] : 0,
											'max_price' => isset( $query_vars['max_price'] ) ? $query_vars['max_price'] : 0,
										),
									),
								),
							);

							break;
						case 'orderby':
							$supported_orders = YITH_WCAN_Filter_Factory::get_supported_orders();

							if ( ! isset( $query_vars['orderby'] ) || ! in_array( $query_vars['orderby'], array_keys( $supported_orders ) ) ) {
								continue 2;
							}

							$active_filters[ $filter ] = array(
								'label'  => $label,
								'values' => array(
									array(
										'label' => $supported_orders[ $query_vars['orderby'] ],
										'query_vars' => array(
											'orderby' => $query_vars['orderby'],
										),
									),
								),
							);

							break;
						case 'rating_filter':
							if ( ! isset( $query_vars['rating_filter'] ) ) {
								continue 2;
							}

							$active_filters[ $filter ] = array(
								'label'  => $label,
								'values' => array(
									array(
										'label' => wc_get_rating_html( $query_vars['rating_filter'] ),
										'query_vars' => array(
											'rating_filter' => $query_vars['rating_filter'],
										),
									),
								),
							);

							break;
						case 'onsale_filter':
							if ( ! isset( $query_vars['onsale_filter'] ) ) {
								continue 2;
							}

							$active_filters[ $filter ] = array(
								'label'  => $label,
								'values' => array(
									array(
										'label' => $label,
										'query_vars' => array(
											'onsale_filter' => 1,
										),
									),
								),
							);

							break;
						case 'instock_filter':
							if ( ! isset( $query_vars['instock_filter'] ) ) {
								continue 2;
							}

							$active_filters[ $filter ] = array(
								'label'  => $label,
								'values' => array(
									array(
										'label' => $label,
										'query_vars' => array(
											'instock_filter' => 1,
										),
									),
								),
							);

							break;
						default:
							global $wp_query;

							$qo = $wp_query instanceof WP_Query ? $wp_query->get_queried_object() : false;

							$taxonomy = $filter;
							$filter   = str_replace( 'pa_', 'filter_', $filter );

							if ( ! isset( $query_vars[ $filter ] ) ) {
								continue 2;
							}
							$terms  = yith_wcan_separate_terms( $query_vars[ $filter ] );
							$values = array();

							if ( empty( $terms ) ) {
								continue 2;
							}

							foreach ( $terms as $term_slug ) {
								$term = get_term_by( 'slug', $term_slug, $taxonomy );

								if ( ! $term || ( is_product_taxonomy() && $qo instanceof WP_Term && $qo->taxonomy === $taxonomy && $qo->slug === $term->slug ) ) {
									continue;
								}

								$values[] = array(
									'label' => $term->name,
									'query_vars' => array(
										$filter => $term_slug,
									),
								);
							}

							if ( empty( $values ) ) {
								continue 2;
							}

							$active_filters[ $filter ] = array(
								'label'  => $label,
								'values' => $values,
							);

							break;
					}
				}

				return apply_filters( 'yith_wcan_active_filter_labels', $active_filters, $query_vars );
			}
		}

		/**
		 * Returns query param
		 *
		 * @return string Query param.
		 */
		public function get_query_param() {
			return $this->_query_param;
		}

		/**
		 * Retrieves a list of product ids that matches current query vars
		 *
		 * @return array Array of products ids.
		 */
		public function get_filtered_products() {
			return $this->get_filtered_products_by_query_vars();
		}

		/**
		 * Retrieves a list of product ids that matches passed query vars
		 *
		 * @param array|null $query_vars A list of query vars to use for product filtering (for the format check @see \YITH_WCAN_Query::get_query_vars).
		 *
		 * @return array Array of products ids.
		 */
		public function get_filtered_products_by_query_vars( $query_vars = null ) {
			$query_vars = ! is_null( $query_vars ) ? $query_vars : $this->get_query_vars();

			$tmp_query_vars           = $this->_query_vars;
			$tmp_chosen_attributes    = $this->_chosen_attributes;
			$this->_query_vars        = $query_vars;
			$this->_chosen_attributes = null;

			$calculate_hash  = md5( http_build_query( $query_vars ) );
			$cache_name      = $this->get_transient_name();
			$stored_products = get_transient( $cache_name );

			if ( is_array( $stored_products ) && isset( $stored_products[ $calculate_hash ] ) ) {
				$product_ids = $stored_products[ $calculate_hash ];
			} else {
				$query = new WP_Query(
					array(
						'post_type' => 'product',
						'post_status' => 'publish',
						'posts_per_page' => '-1',
						'fields' => 'ids',
					)
				);

				// filter with current query vars.
				$this->filter( $query );

				$query->set( 'yith_wcan_prefetch_cache', true );

				// retrieve product ids for current filters.
				$product_ids = $query->get_posts();

				// save result set to stored queries.
				$stored_products[ $calculate_hash ] = $product_ids;
				set_transient( $cache_name, $stored_products, apply_filters( 'yith_wcan_queried_products_expiration', 30 * DAY_IN_SECONDS ) );
			}

			$this->_query_vars        = $tmp_query_vars;
			$this->_chosen_attributes = $tmp_chosen_attributes;

			return $product_ids;
		}

		/**
		 * Returns an array of supported taxonomies for filtering
		 *
		 * @return WP_Taxonomy[] Array of WP_Taxonomy objects
		 */
		public function get_supported_taxonomies() {
			if ( empty( $this->_supported_taxonomies ) ) {
				$product_taxonomies   = get_object_taxonomies( 'product', 'objects' );
				$supported_taxonomies = array();

				if ( ! empty( $product_taxonomies ) ) {
					foreach ( $product_taxonomies as $taxonomy_slug => $taxonomy ) {
						if ( ! in_array( $taxonomy_slug, array( 'product_cat', 'product_tag' ) ) && 0 !== strpos( $taxonomy_slug, 'pa_' ) ) {
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
		 * Checks whether filters should be applied
		 *
		 * @return bool Whether filters should be applied.
		 */
		public function should_filter() {
			$query_param = isset( $_REQUEST[ $this->get_query_param() ] ) ? intval( wp_unslash( $_REQUEST[ $this->get_query_param() ] ) ) : 0;
			$session_param = YITH_WCAN_Session_Factory::get_session_query_var();

			return apply_filters( 'yith_wcan_should_filter', ! ! $query_param || ! ! $session_param, $this );
		}

		/**
		 * Checks whether passed Query object should be processed by filter
		 *
		 * @param WP_Query $query Query object.
		 * @return bool Whether object should be filtered or not.
		 */
		public function should_process_query( $query ) {
			$result = true;

			if ( ! $query instanceof WP_Query ) {
				// skip if wrong parameter.
				$result = false;
			} elseif ( 'product' != $query->get( 'post_type' ) ) {
				// skip if we're not querying products.
				$result = false;
			} elseif ( $query->is_main_query() && ! $query->get( 'wc_query' ) ) {
				// skip if main query.
				$result = false;
			} elseif ( ! $query->is_main_query() && $query->get( 'wc_query' ) ) {
				// skip if we're already executing a special wc_query.
				$result = false;
			} elseif ( $query->get( 'yith_wcan_prefetch_cache' ) ) {
				// skip if we're prefetching products.
				$result = false;
			}

			return apply_filters( 'yith_wcan_should_process_query', $result, $query, $this );
		}

		/**
		 * Checks whether current view is applying any filter over eligible queries
		 *
		 * @return bool
		 */
		public function is_filtered() {
			return $this->should_filter() && ! empty( $this->get_query_vars() );
		}

		/* === QUERY METHODS === */

		/**
		 * Retrieve all defined query vars for current url, and set the for current query
		 *
		 * @param WP_Query $query Current query object.
		 *
		 * @return void
		 */
		public function fill_query_vars( &$query ) {
			$query_vars = $this->get_query_vars();

			if ( empty( $query_vars ) ) {
				return;
			}

			$query->query_vars = array_merge(
				$query->query_vars,
				$query_vars
			);
		}

		/**
		 * Start to filter the query
		 *
		 * @return void
		 */
		public function start_filtering() {
			// if we don't have plugin parameter, just skip.
			if ( ! $this->should_filter() ) {
				return;
			}

			// suppress conditional tags for global query, when we're executing a filter.
			$this->suppress_default_conditional_tags();

			// append handling to queries.
			add_action( 'pre_get_posts', array( $this, 'filter' ), 10, 1 );

			// append handling to product shortcodes.
			add_filter( 'woocommerce_shortcode_products_query', array( $this, 'filter_query_vars' ) );

			// during our filtering, WC blocks cannot use cached contents.
			add_filter( 'woocommerce_blocks_product_grid_is_cacheable', '__return_false' );
		}

		/**
		 * Filters query, and apply all additional query vars
		 *
		 * @param WP_Query $query Current query object.
		 *
		 * @return void
		 */
		public function filter( $query ) {
			// skip if query object shouldn't be processed.
			if ( ! $this->should_process_query( $query ) ) {
				return;
			}

			do_action( 'yith_wcan_before_query', $query, $this );

			// get tax query for current loop (even if we're on single).
			$this->fill_query_vars( $query );

			// set layered nav for current query.
			$this->set_tax_query( $query );
			$this->set_meta_query( $query );
			$this->set_orderby( $query );
			$this->set_post_in( $query );

			// set special meta for current query.
			$query->set( 'yith_wcan_query', $this->get_query_vars() );

			do_action( 'yith_wcan_after_query', $query, $this );

			add_filter( 'posts_clauses', array( $this, 'additional_post_clauses' ), 10, 2 );
			add_filter( 'the_posts', array( $this, 'do_cleanup' ), 10, 2 );
		}

		/**
		 * When we don't have a query object, we can pass query_var
		 *
		 * @param WP_Query|array $query Array of query vars, or query object.
		 * @return array Filtered array of query vars
		 */
		public function filter_query_vars( $query ) {
			if ( is_array( $query ) ) {
				$query = new WP_Query( $query );
			} elseif ( ! $query instanceof WP_Query ) {
				return $query;
			}

			// apply current filters.
			$this->filter( $query );

			// retrieve filtered query vars.
			$query_vars = $query->query_vars;

			// destroy new query object.
			unset( $query );

			return $query_vars;
		}

		/**
		 * Filters tax_query param of a query, to add parameters specified in $this->_query_vars
		 *
		 * @param array $tax_query Tax query array of current query.
		 *
		 * @return array Array describing meta query currently set in the query vars
		 */
		public function get_tax_query( $tax_query = array() ) {
			if ( ! is_array( $tax_query ) ) {
				$tax_query = array(
					'relation' => 'AND',
				);
			}

			// Layered nav filters on terms.
			foreach ( $this->get_layered_nav_chosen_attributes() as $taxonomy => $data ) {
				$tax_query[] = array(
					'taxonomy'         => $taxonomy,
					'field'            => 'slug',
					'terms'            => $data['terms'],
					'operator'         => 'and' === $data['query_type'] ? 'AND' : 'IN',
					'include_children' => false,
				);
			}

			// Filter by rating.
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$rating_filter            = $this->get( 'rating_filter' );
			$product_visibility_terms = wc_get_product_visibility_term_ids();

			if ( $rating_filter ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$rating_filter = array_filter( array_map( 'absint', explode( ',', $rating_filter ) ) );
				$rating_terms  = array();

				for ( $i = 1; $i <= 5; $i ++ ) {
					if ( in_array( $i, $rating_filter, true ) && isset( $product_visibility_terms[ 'rated-' . $i ] ) ) {
						$rating_terms[] = $product_visibility_terms[ 'rated-' . $i ];
					}
				}

				if ( ! empty( $rating_terms ) ) {
					$tax_query[] = array(
						'taxonomy'      => 'product_visibility',
						'field'         => 'term_taxonomy_id',
						'terms'         => $rating_terms,
						'operator'      => 'IN',
						'rating_filter' => true,
					);
				}
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			return array_filter( apply_filters( 'yith_wcan_product_query_tax_query', $tax_query, $this ) );
		}

		/**
		 * Get an array of attributes and terms selected with the layered nav widget.
		 *
		 * @return array
		 */
		public function get_layered_nav_chosen_attributes() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( ! is_array( $this->_chosen_attributes ) ) {
				$this->_chosen_attributes = array();

				$query_vars = $this->get_query_vars();

				if ( ! empty( $query_vars ) ) {
					foreach ( $query_vars as $key => $value ) {
						if ( 0 === strpos( $key, 'filter_' ) ) {
							$attribute    = wc_sanitize_taxonomy_name( str_replace( 'filter_', '', $key ) );
							$taxonomy     = wc_attribute_taxonomy_name( $attribute );
							$filter_terms = ! empty( $value ) ? explode( ',', wc_clean( wp_unslash( $value ) ) ) : array();

							if ( empty( $filter_terms ) || ! taxonomy_exists( $taxonomy ) || ! wc_attribute_taxonomy_id_by_name( $attribute ) ) {
								continue;
							}

							$query_type = $this->get( 'query_type_' . $attribute );

							$this->_chosen_attributes[ $taxonomy ]['terms'] = array_map( 'sanitize_title', $filter_terms ); // Ensures correct encoding.
							$this->_chosen_attributes[ $taxonomy ]['query_type'] = $query_type ? $query_type : apply_filters( 'woocommerce_layered_nav_default_query_type', 'and' );
						}
					}
				}
			}
			return $this->_chosen_attributes;
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Set tax_query parameter according to current query_vars, for the passed query object
		 *
		 * @param WP_Query $query Query object to modify.
		 * @return void
		 */
		public function set_tax_query( &$query ) {
			// get tax_query for current query.
			$tax_query = $query->get( 'tax_query' );

			// add layered nav parameters.
			$tax_query = $this->get_tax_query( $tax_query );

			// remove any default taxonomy filtering, if we've set a tax query.
			if ( ! empty( $tax_query ) ) {
				$query->set( 'taxonomy', '' );
				$query->set( 'term', '' );
			}

			// finally set tax_query parameter for the query.
			$query->set( 'tax_query', $tax_query );
		}

		/**
		 * Filters meta_query param of a query, to add parameters specified in $this->_query_vars
		 *
		 * @param array $meta_query Meta query array of current query.
		 *
		 * @return array Array describing meta query currently set in the query vars
		 */
		public function get_meta_query( $meta_query = array() ) {
			if ( ! is_array( $meta_query ) ) {
				$meta_query = array(
					'relation' => 'AND',
				);
			}

			$query_vars = $this->get_query_vars();

			if ( ! empty( $query_vars ) ) {
				foreach ( $query_vars as $key => $value ) {
					if ( 0 !== strpos( $key, 'meta_' ) ) {
						continue;
					}

					$meta_key   = str_replace( 'meta_', '', $key );

					// check if value contains operator.
					if ( 0 === strpos( $value, 'IN' ) ) {
						$operator = 'IN';
					} elseif ( 0 === strpos( $value, 'NOTIN' ) ) {
						$operator = 'NOT IN';
						$value = str_replace( $operator, 'NOTIN', $value );
					} elseif ( 0 === strpos( $value, '>=' ) ) {
						$operator = '>=';
					} elseif ( 0 === strpos( $value, '=<' ) ) {
						$operator = '=<';
					} elseif ( 0 === strpos( $value, '>' ) ) {
						$operator = '>';
					} elseif ( 0 === strpos( $value, '<' ) ) {
						$operator = '<';
					} elseif ( 0 === strpos( $value, '!=' ) ) {
						$operator = '!=';
					} else {
						$operator = '=';
					}

					$meta_query[] = array(
						'key'      => $meta_key,
						'value'    => str_replace( $operator, '', $value ),
						'operator' => $operator,
					);
				}
			}

			return array_filter( apply_filters( 'yith_wcan_product_query_meta_query', $meta_query, $this ) );
		}

		/**
		 * Set meta_query parameter according to current query_vars, for the passed query object
		 *
		 * @param WP_Query $query Query object to modify.
		 * @return void
		 */
		public function set_meta_query( &$query ) {
			// get meta_query for current query.
			$meta_query = $query->get( 'meta_query' );

			// add layered nav parameters.
			$meta_query = $this->get_meta_query( $meta_query );

			// finally set meta_query parameter for the query.
			$query->set( 'meta_query', $meta_query );
		}

		/**
		 * Returns array of parameters needed for ordering query
		 *
		 * @return array|bool Query's ordering parameters, or false when no ordering is required.
		 */
		public function get_orderby() {
			$orderby = $this->get( 'orderby' );
			$order   = $this->get( 'order' );

			if ( ! $orderby ) {
				return false;
			}

			/**
			 * This reference to WC_Query is ok, since it is one of the rare case
			 * when we can provide input, instead of relying on $_GET parameter
			 */
			return WC()->query->get_catalog_ordering_args( $orderby, $order );
		}

		/**
		 * Set order parameters according to current query_vars, for the passed query object
		 *
		 * @param WP_Query $query Query object to modify.
		 * @return void
		 */
		public function set_orderby( &$query ) {
			$orderby = $this->get( 'orderby' );

			if ( ! $orderby ) {
				return;
			}

			/**
			 * Same behaviour WC applies to main query
			 *
			 * @see \WC_Query::product_query
			 */
			$ordering = $this->get_orderby();

			if ( ! $ordering ) {
				return;
			}

			$query->set( 'orderby', $ordering['orderby'] );
			$query->set( 'order', $ordering['order'] );

			if ( isset( $ordering['meta_key'] ) ) {
				$query->set( 'meta_key', $ordering['meta_key'] );
			}
		}

		/**
		 * Set post__in parameter according to current query_vars, for the passed query object
		 *
		 * @param WP_Query $query Query object to modify.
		 * @return void
		 */
		public function set_post_in( &$query ) {
			$on_sale_only = $this->is_sale_only();
			$in_stock_only = $this->is_stock_only();

			$post_in = $query->get( 'post__in', array() );

			if ( $on_sale_only ) {
				$on_sale = $this->get_product_ids_on_sale();
				$post_in = $post_in ? array_intersect( $post_in, $on_sale ) : $on_sale;
			}

			if ( $in_stock_only || 'yes' === yith_wcan_get_option( 'yith_wcan_hide_out_of_stock_products', 'no' ) ) {
				$in_stock = $this->get_product_ids_in_stock();
				$post_in = $post_in ? array_intersect( $post_in, $in_stock ) : $in_stock;
			}

			$query->set( 'post__in', $post_in );
		}

		/**
		 * Adds additional clauses to product query, in order to apply additional filters
		 *
		 * @param array    $args     Query parts.
		 * @param WP_Query $wp_query Query object.
		 *
		 * @return array Array of filtered query parts.
		 */
		public function additional_post_clauses( $args, $wp_query ) {
			global $wpdb;

			$min_price = floatval( $this->get( 'min_price' ) );
			$max_price = floatval( $this->get( 'max_price' ) );

			if ( ! $min_price && ! $max_price ) {
				return $args;
			}

			$current_min_price = $min_price ? $min_price : 0;
			$current_max_price = $max_price ? $max_price : PHP_INT_MAX;

			/**
			 * Adjust if the store taxes are not displayed how they are stored.
			 * Kicks in when prices excluding tax are displayed including tax.
			 */
			if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
				$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' ); // Uses standard tax class.
				$tax_rates = WC_Tax::get_rates( $tax_class );

				if ( $tax_rates ) {
					$current_min_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_min_price, $tax_rates ) );
					$current_max_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_max_price, $tax_rates ) );
				}
			}

			$args['join']  .= ! strstr( $args['join'], 'wc_product_meta_lookup' ) ?
				" LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id " :
				'';
			$args['where'] .= $wpdb->prepare(
				' AND wc_product_meta_lookup.min_price >= %f AND wc_product_meta_lookup.max_price <= %f ',
				$current_min_price,
				$current_max_price
			);

			return $args;
		}

		/**
		 * Remove additional parameters from the query
		 *
		 * @param array    $posts Array of retrieved posts.
		 * @param WP_Query $query Query object.
		 * @return array Array of posts (unchanged).
		 */
		public function do_cleanup( $posts, $query ) {
			if ( ! $query->get( 'yith_wcan_query' ) ) {
				return $posts;
			}

			remove_filter( 'posts_clauses', array( $this, 'additional_post_clauses' ), 10 );
			return $posts;
		}

		/**
		 * Suppresses default query vars when filtering on home page
		 * That's done to avoid WP loading terms as queried objects, when filtering the home page
		 *
		 * @return void
		 */
		public function suppress_default_query_vars() {
			global $wp;

			if ( empty( $wp->request ) && $this->should_filter() && apply_filters( 'yith_wcan_suppress_default_query_vars', true ) ) {
				$wp->query_vars = array();
			}
		}

		/**
		 * Suppress conditional tags for current global query
		 *
		 * This should only be done when filtering (\YITH_WCAN_Query::should_filter) and is shop page.
		 * Otherwise system could set query to behave like we're on a category/tag/etc, depending on query_vars.
		 *
		 * @return void
		 */
		public function suppress_default_conditional_tags() {
			global $wp_query;

			if ( apply_filters( 'yith_wcan_suppress_default_conditional_tags', false ) ) {
				$wp_query->is_tax = false;
				$wp_query->is_tag = false;
				$wp_query->is_home = false;
				$wp_query->is_single = false;
				$wp_query->is_posts_page = false;
			}
		}

		/**
		 * Suppress canonical redirect when filtering homepage with session param
		 *
		 * @param bool $redirect Whether to redirect to canonical url.
		 * @return bool Filtered value.
		 */
		public function suppress_canonical_redirect( $redirect ) {
			if ( $this->should_filter() ) {
				$redirect = false;
			}

			return $redirect;
		}

		/**
		 * Register an array of filtered products
		 *
		 * @return void
		 */
		public function register_products() {
			if ( ! $this->is_filtered() || ! empty( $this->_products ) || ! apply_filters( 'yith_wcan_process_filters_intersection', true ) ) {
				return;
			}

			$this->_products = $this->get_filtered_products_by_query_vars();
		}

		/* === ALTER DEFAULT WC QUERY === */

		/**
		 * Set custom filtering for default WC query, for those parameters specific to our plugin
		 *
		 * @param WP_Query $query Query object.
		 * @return void
		 */
		public function alter_product_query( $query ) {
			$this->set_post_in( $query );
		}

		/* === SESSION METHODS === */

		/**
		 * Returns current filtering session
		 *
		 * @retun YITH_WCAN_Session|bool Current filtering session, or false when no session is defined
		 */
		public function get_current_session() {
			return $this->_session;
		}

		/**
		 * Returns sharing url for current filtering session
		 *
		 * @retun string|bool Sharing url, or false when no session is defined
		 */
		public function get_current_session_share_url() {
			$session = $this->_session;

			if ( ! $session ) {
				return false;
			}

			return $session->get_share_url();
		}

		/**
		 * Retrieves current filtering session, if any
		 *
		 * Used to populate query vars from session.
		 *
		 * @return YITH_WCAN_Session|bool Current filter session; false if no session is found, or is sessions are disabled.
		 */
		public function maybe_retrieve_current_session() {
			$filter_by_session = 'custom' === yith_wcan_get_option( 'yith_wcan_change_browser_url' );
			$sessions_enabled  = apply_filters( 'yith_wcan_sessions_enabled', $filter_by_session );

			if ( ! $sessions_enabled ) {
				return false;
			}

			if ( $this->_session ) {
				return $this->_session;
			}

			$session = YITH_WCAN_Session_Factory::get_current_session();

			if ( $session ) {
				$session->maybe_extend_duration() && $session->save();
				$this->_session = $session;
			}

			return $session;
		}

		/**
		 * Register current session, when needed
		 *
		 * @param string $origin_url Filtering url.
		 * @param array  $query_vars Filter parameters.
		 *
		 * @return void
		 */
		public function maybe_register_current_session( $origin_url, $query_vars ) {
			$filter_by_session = 'custom' === yith_wcan_get_option( 'yith_wcan_change_browser_url' );
			$sessions_enabled  = apply_filters( 'yith_wcan_sessions_enabled', $filter_by_session );

			if ( ! $sessions_enabled || ! $origin_url || ! $query_vars ) {
				return;
			}

			$this->_session = YITH_WCAN_Session_Factory::generate_session( $origin_url, $query_vars );
		}

		/**
		 * Retrieves current session
		 *
		 * It is important to do this early in the execution to affect also archives main query
		 * It will also modify $_GET super-global, adding query vars retrieved from the session, in order to make
		 * them available to filtering systems (including WC's layered nav) down the line.
		 *
		 * @return void
		 */
		public function prefetch_session() {
			$session = $this->maybe_retrieve_current_session();

			if ( $session ) {
				$_GET = array_merge( $_GET, $session->get_query_vars() );
			}
		}

		/* === FILTER URL METHODS === */

		/**
		 * Get url for filtering.
		 *
		 * @param array  $query_to_add    Params to add to the url (additionally to the existing ones).
		 * @param array  $query_to_remove Params to remove from the url (from the one already existing).
		 * @param string $merge_mode      Whether params should be added or removed using AND or OR method, when applicable.
		 *
		 * @return string Url for filtering.
		 */
		public function get_filter_url( $query_to_add = array(), $query_to_remove = array(), $merge_mode = 'and' ) {
			if ( ! did_action( 'wp' ) ) {
				return '';
			}

			$query_vars = $this->get_query_vars();
			$base_url   = $this->get_base_filter_url();

			if ( ! empty( $query_to_add ) ) {
				$query_vars = $this->merge_query_vars( $query_vars, $merge_mode, $query_to_add );
			}

			if ( ! empty( $query_to_remove ) ) {
				$query_vars = $this->diff_query_vars( $query_vars, $merge_mode, $query_to_remove );
			}

			$params = array_merge(
				array(
					$this->get_query_param() => 1,
				),
				$query_vars
			);

			return apply_filters( 'yith_wcan_filter_url', add_query_arg( $params, $base_url ), $query_vars, $merge_mode );
		}

		/**
		 * Returns base url for the filters (it will return current page url, or product archive url when in shop page)
		 *
		 * @return string Base filtering url.
		 */
		public function get_base_filter_url() {
			global $wp;

			if ( is_shop() || is_product_taxonomy() ) {
				$base_url = yit_get_woocommerce_layered_nav_link();
			} else {
				$base_url = home_url( $wp->request );
			}

			return apply_filters( 'yith_wcan_base_filter_url', $base_url );
		}

		/* === TEST METHODS === */

		/**
		 * Checks whether we're currently filtering for a specific term, or if we're that term page
		 *
		 * @param string  $taxonomy Taxonomy to test.
		 * @param WP_Term $term     Term to test.
		 * @return bool Whether we're filtering by that term or not.
		 */
		public function is_term( $taxonomy, $term ) {
			$taxonomies = array_keys( $this->get_supported_taxonomies() );
			$query_var  = $taxonomy;

			if ( ! in_array( $taxonomy, $taxonomies ) ) {
				return false;
			}

			if ( is_tax( $taxonomy, $term->slug ) ) {
				return true;
			}

			if ( in_array( $taxonomy, wc_get_attribute_taxonomy_names() ) ) {
				$query_var = str_replace( 'pa_', 'filter_', $taxonomy );
			}

			$terms = $this->get( $query_var, '' );
			$terms = yith_wcan_separate_terms( $terms );

			return in_array( $term->slug, $terms, true );
		}

		/**
		 * Checks whether we're currently filtering for a specific price range
		 *
		 * @param array $range Expects an array that contains min/max indexes for the range ends.
		 * @return bool Whether that range is active or not
		 */
		public function is_price_range( $range ) {
			$min_price = (float) $this->get( 'min_price', false );
			$max_price = (float) $this->get( 'max_price', false );

			return $range['min'] === $min_price && ( $range['max'] === $max_price || $range['unlimited'] );
		}

		/**
		 * Checks if we're filtering by a specific review rate
		 *
		 * @param int $rate Review rate to check.
		 * @return bool Whether that rate is active or not
		 */
		public function is_review_rate( $rate ) {
			return $rate === (int) $this->get( 'rating_filter', false );
		}

		/**
		 * Checks if we're currently sorting by a specific order
		 *
		 * @param string $order Order to check.
		 *
		 * @return bool Whether products are sorted by specified order
		 */
		public function is_ordered_by( $order ) {
			$current_order = $this->get( 'orderby' );

			return $order === $current_order || 'menu_order' === $order && ! $current_order;
		}

		/**
		 * Checks whether on sale filter is active for current query
		 *
		 * @return bool Whether on sale filter is currently active
		 */
		public function is_stock_only() {
			return 1 === (int) $this->get( 'instock_filter', 0 ) || $this->should_filter() && 'yes' === yith_wcan_get_option( 'yith_wcan_hide_out_of_stock_products', 'no' );
		}

		/**
		 * Checks whether in stock filter is active for current query
		 *
		 * @return bool Whether in stock filter is currently active
		 */
		public function is_sale_only() {
			return 1 === (int) $this->get( 'onsale_filter', 0 );
		}

		/* === RETRIEVE QUERY RELEVANT PRODUCTS === */

		/**
		 * Count how many products for the passed term match current filter
		 *
		 * @param string $taxonomy       Taxonomy to test.
		 * @param int    $term_id        Term id to test.
		 * @param bool   $auto_exclusive Whether we should exclude passed taxonomy from query_vars for filtering.
		 *
		 * @return bool|int Count of matching products, or false on failure
		 */
		public function count_query_relevant_term_objects( $taxonomy, $term_id, $auto_exclusive = true ) {
			if ( ! apply_filters( 'yith_wcan_process_filters_intersection', true ) ) {
				return false;
			}

			return count( $this->get_query_relevant_term_objects( $taxonomy, $term_id, $auto_exclusive ) );
		}

		/**
		 * Count how many on sale products match current filter
		 *
		 * @return int Count of matching products
		 */
		public function count_query_relevant_on_sale_products() {
			return count( $this->get_query_relevant_on_sale_products() );
		}

		/**
		 * Count how many in stock products match current filter
		 *
		 * @return int Count of matching products
		 */
		public function count_query_relevant_in_stock_products() {
			return count( $this->get_query_relevant_in_stock_products() );
		}

		/**
		 * Count how many products with a specific review rating match current filter
		 *
		 * @param int $rate Review rating to test.
		 *
		 * @return int Count of matching products
		 */
		public function count_query_relevant_rated_products( $rate ) {
			return count( $this->get_query_relevant_rated_products( $rate ) );
		}

		/**
		 * Count how many products in a specific price range match current filter
		 *
		 * @param array $range Array containing min and max indexes.
		 *
		 * @return int Count of matching products
		 */
		public function count_query_relevant_price_range_products( $range ) {
			return count( $this->get_query_relevant_price_range_products( $range ) );
		}

		/**
		 * Return ids for term's products matching current filter
		 *
		 * @param string $taxonomy         Taxonomy to test.
		 * @param int    $term_id          Term id to test.
		 * @param bool   $exclude_taxonomy Whether we should exclude passed taxonomy from query_vars for filtering.
		 *
		 * @return array Array of post ids that are both query-relevant and bound to term
		 */
		public function get_query_relevant_term_objects( $taxonomy, $term_id, $exclude_taxonomy = true ) {
			if ( ! apply_filters( 'yith_wcan_process_filters_intersection', true ) ) {
				return array();
			}

			if ( isset( $this->_products_per_filter[ $taxonomy ][ $term_id ] ) ) {
				return $this->_products_per_filter[ $taxonomy ][ $term_id ];
			} else {
				$posts = get_objects_in_term( $term_id, $taxonomy );

				if ( is_wp_error( $posts ) ) {
					return array();
				}

				if ( ! $exclude_taxonomy ) {
					$query_vars = $this->get_query_vars();
					$original_taxonomy = $taxonomy;

					if ( in_array( $original_taxonomy, wc_get_attribute_taxonomy_names() ) ) {
						$original_taxonomy = str_replace( 'pa_', 'filter_', $original_taxonomy );
					}

					if ( isset( $query_vars[ $original_taxonomy ] ) ) {
						unset( $query_vars[ $original_taxonomy ] );
					}

					$products = $this->get_filtered_products_by_query_vars( $query_vars );
				} else {
					$products = $this->get_filtered_products();
				}

				$match = array_intersect( $posts, $products );
				$this->_products_per_filter[ $taxonomy ][ $term_id ] = $match;

				return $match;
			}
		}

		/**
		 * Return ids for on sale  products matching current filter
		 *
		 * @return array Array of post ids that are both query-relevant and on sale
		 */
		public function get_query_relevant_on_sale_products() {
			return array_intersect( $this->get_filtered_products(), $this->get_product_ids_on_sale() );
		}

		/**
		 * Return ids for in stock  products matching current filter
		 *
		 * @return array Array of post ids that are both query-relevant and in stock
		 */
		public function get_query_relevant_in_stock_products() {
			return array_intersect( $this->get_filtered_products(), $this->get_product_ids_in_stock() );
		}

		/**
		 * Return ids for products with a specific review rating matching current filter
		 *
		 * @param int $rate Review rating to test.
		 *
		 * @return array Array of post ids that are both query-relevant and with a specific review rating
		 */
		public function get_query_relevant_rated_products( $rate ) {
			$term = get_term_by( 'slug', 'rated-' . $rate, 'product_visibility' );

			if ( ! $term ) {
				return array();
			}

			$query_vars = $this->get_query_vars();

			if ( isset( $query_vars['rating_filter'] ) ) {
				unset( $query_vars['rating_filter'] );
			}

			return array_intersect( $this->get_filtered_products_by_query_vars( $query_vars ), get_objects_in_term( $term->term_id, 'product_visibility' ) );
		}

		/**
		 * Return ids for  products in a specific price range matching current filter
		 *
		 * @param array $range Array containing min and max indexes.
		 *
		 * @return array Array of post ids that are both query-relevant and within a specific price range
		 */
		public function get_query_relevant_price_range_products( $range ) {
			global $wpdb;

			$query_vars = $this->get_query_vars();

			if ( isset( $query_vars['min_price'] ) ) {
				unset( $query_vars['min_price'] );
			}

			if ( isset( $query_vars['max_price'] ) ) {
				unset( $query_vars['max_price'] );
			}

			$products = $this->get_filtered_products_by_query_vars( $query_vars );

			if ( empty( $products ) ) {
				return $products;
			}

			$query = $wpdb->prepare(
				"SELECT product_id FROM {$wpdb->prefix}wc_product_meta_lookup WHERE min_price >= %f AND max_price <= %f AND product_id IN (" . implode( ',', $products ) . ')', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				isset( $range['min'] ) ? (float) $range['min'] : 0,
				isset( $range['max'] ) && ! $range['unlimited'] ? (float) $range['max'] : PHP_INT_MAX
			);

			return $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		/* === TRANSIENT === */

		/**
		 * Returns name of the transient used to cache queried products
		 *
		 * @return string Transient name.
		 */
		public function get_transient_name() {
			$cache_version = WC_Cache_Helper::get_transient_version( 'product' );
			$cache_name    = "yith_wcan_queried_products_{$cache_version}";

			return apply_filters( 'yith_wcan_queried_products_name', $cache_name );
		}

		/**
		 * Delete transient used to cache queried products
		 *
		 * @return void
		 */
		public function delete_transients() {
			delete_transient( $this->get_transient_name() );
			delete_transient( 'wc_products_instock' );
			delete_transient( 'yith_wcan_exclude_from_catalog_product_ids' );
		}

		/* === UTILS === */

		/**
		 * Retrieves list of ids of in-stock products
		 *
		 * @return array Array of product ids
		 */
		public function get_product_ids_in_stock() {
			// Load from cache.
			$product_ids_in_stock = get_transient( 'wc_products_instock' );

			// Valid cache found.
			if ( false !== $product_ids_in_stock ) {
				return $product_ids_in_stock;
			}

			$product_ids_in_stock = wc_get_products(
				array(
					'status' => 'publish',
					'stock_status' => 'instock',
					'limit' => -1,
					'return' => 'ids',
				)
			);

			set_transient( 'wc_products_instock', $product_ids_in_stock, DAY_IN_SECONDS * 30 );

			return $product_ids_in_stock;
		}

		/**
		 * Retrieves list of ids of in-stock products
		 *
		 * @return array Array of product ids
		 */
		public function get_product_ids_on_sale() {
			return wc_get_product_ids_on_sale();
		}

		/**
		 * Merge sets of query vars together; when applicable, uses merge mode to merge parameters together
		 *
		 * @param array  $query_vars     Initial array of parameters.
		 * @param string $merge_mode     Merge mode (AND/OR).
		 * @param array  ...$vars_to_add Additional sets of params to merge.
		 *
		 * @return array Merged parameters.
		 */
		public function merge_query_vars( $query_vars, $merge_mode, ...$vars_to_add ) {
			$supported_taxonomies = $this->get_supported_taxonomies();

			if ( ! empty( $vars_to_add ) ) {
				foreach ( $vars_to_add as $vars ) {
					foreach ( $vars as $key => $value ) {
						if ( in_array( $key, array_keys( $supported_taxonomies ) ) ) {
							if ( ! isset( $query_vars[ $key ] ) ) {
								$query_vars[ $key ] = $value;
							} else {
								$glue = 'and' === $merge_mode ? '+' : ',';
								$existing = explode( $glue, $query_vars[ $key ] );
								$new = explode( $glue, $value );

								$query_vars[ $key ] = implode( $glue, array_unique( array_merge( $existing, $new ) ) );
							}
						} elseif ( 0 === strpos( $key, 'filter_' ) ) {
							$attribute = str_replace( 'filter_', '', $key );

							$query_vars[ "query_type_{$attribute}" ] = $merge_mode;

							if ( ! isset( $query_vars[ $key ] ) ) {
								$query_vars[ $key ] = $value;
							} else {
								$existing = explode( ',', $query_vars[ $key ] );
								$new = explode( ',', $value );

								$query_vars[ $key ] = implode( ',', array_unique( array_merge( $existing, $new ) ) );
							}
						} else {
							$query_vars[ $key ] = $value;
						}
					}
				}
			}

			return $query_vars;
		}

		/**
		 * Diff sets of query vars together; when applicable, uses merge mode to diff parameters apart
		 *
		 * @param array  $query_vars        Initial array of parameters.
		 * @param string $merge_mode        Merge mode (AND/OR).
		 * @param array  ...$vars_to_remove Additional sets of params to diff.
		 *
		 * @return array Merged parameters.
		 */
		public function diff_query_vars( $query_vars, $merge_mode, ...$vars_to_remove ) {
			$supported_taxonomies = $this->get_supported_taxonomies();

			if ( ! empty( $vars_to_remove ) ) {
				foreach ( $vars_to_remove as $vars ) {
					foreach ( $vars as $key => $value ) {
						if ( in_array( $key, array_keys( $supported_taxonomies ) ) ) {
							if ( isset( $query_vars[ $key ] ) ) {
								$glue = 'and' === $merge_mode ? '+' : ',';
								$existing = explode( $glue, $query_vars[ $key ] );
								$new = explode( $glue, $value );

								$query_vars[ $key ] = implode( $glue, array_unique( array_diff( $existing, $new ) ) );
							}

							if ( empty( $query_vars[ $key ] ) ) {
								unset( $query_vars[ $key ] );
							}
						} elseif ( 0 === strpos( $key, 'filter_' ) ) {
							$attribute = str_replace( 'filter_', '', $key );

							$query_vars[ "query_type_{$attribute}" ] = $merge_mode;

							if ( isset( $query_vars[ $key ] ) ) {
								$existing = explode( ',', $query_vars[ $key ] );
								$new = explode( ',', $value );

								$query_vars[ $key ] = implode( ',', array_unique( array_diff( $existing, $new ) ) );
							}

							if ( empty( $query_vars[ $key ] ) ) {
								unset( $query_vars[ $key ] );
								unset( $query_vars[ "query_type_{$attribute}" ] );
							}
						} else {
							unset( $query_vars[ $key ] );
						}
					}
				}
			}

			return $query_vars;
		}

		/**
		 * Query class Instance
		 *
		 * @return YITH_WCAN_Query Query class instance
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

if ( ! function_exists( 'YITH_WCAN_Query' ) ) {
	/**
	 * Returns single instance of YITH_WCAN_Query class
	 *
	 * @return YITH_WCAN_Query
	 */
	function YITH_WCAN_Query() {
		if ( defined( 'YITH_WCAN_PREMIUM' ) ) {
			return YITH_WCAN_Query_Premium::instance();
		}

		return YITH_WCAN_Query::instance();
	}
}

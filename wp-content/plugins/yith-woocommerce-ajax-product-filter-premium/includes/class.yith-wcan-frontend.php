<?php
/**
 * Frontend class
 *
 * @author  Your Inspiration Themes
 * @package YITH WooCommerce Ajax Navigation
 * @version 1.3.2
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Frontend' ) ) {
	/**
	 * Frontend class.
	 * The class manage all the frontend behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAN_Frontend {

		/**
		 * Array of product ids filtered for taxonomy
		 *
		 * @var array
		 * @deprecated
		 * @since version 3.0
		 */
		public $filtered_product_ids_for_taxonomy = array();

		/**
		 * Array of product ids filtered for current layered nav selection
		 *
		 * @var array
		 * @deprecated
		 * @since version 3.0
		 */
		public $layered_nav_product_ids = array();

		/**
		 * Array of unfiltered product ids for current shop page
		 *
		 * @var array
		 * @deprecated
		 * @since version 3.0
		 */
		public $unfiltered_product_ids = array();

		/**
		 * Array of product ids for current filters selection
		 *
		 * @var array
		 * @deprecated
		 * @since version 3.0
		 */
		public $filtered_product_ids = array();

		/**
		 * Array of product ids to include in current main query
		 *
		 * @var array
		 * @deprecated
		 * @since version 3.0
		 */
		public $layered_nav_post__in = array();

		/**
		 * Query object
		 *
		 * @var YITH_WCAN_Query
		 */
		protected $_query = null;

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {
			// new query object.
			$this->_query = YITH_WCAN_Query();

			// Legacy query methods.
			add_filter( 'the_posts', array( $this, 'the_posts' ), 15, 2 );
			add_filter( 'woocommerce_layered_nav_link', 'yit_plus_character_hack', 99 );
			add_filter( 'woocommerce_is_filtered', 'yit_is_filtered_uri', 20 );

			if ( is_active_widget( false, false, 'yith-woo-ajax-navigation' ) ) {
				add_filter( 'woocommerce_is_layered_nav_active', '__return_true' );
			}

			// Frontend methods.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
			add_action( 'body_class', array( $this, 'body_class' ) );
			add_action( 'wp_head', array( $this, 'add_meta' ) );

			// Template methods.
			add_action( 'init', array( $this, 'add_active_filters_list' ) );
			add_action( 'init', array( $this, 'add_reset_button' ) );
			add_action( 'init', array( $this, 'add_mobile_modal_opener' ) );

			add_action( 'yith_wcan_before_preset_filters', array( $this, 'filters_title' ), 10, 1 );
			add_action( 'yith_wcan_after_preset_filters', array( $this, 'apply_filters_button' ), 10, 1 );

			// YITH WCAN Loaded.
			do_action( 'yith_wcan_loaded' );
		}

		/* === LEGACY QUERY METHODS === */

		/**
		 * Returns main query object
		 *
		 * @return YITH_WCAN_Query
		 */
		public function get_query() {
			return $this->_query;
		}

		/**
		 * Select the correct query object
		 *
		 * @param WP_Query|bool $current_wp_query Fallback query object.
		 *
		 * @access public
		 * @return array The query params
		 */
		public function select_query_object( $current_wp_query ) {
			/**
			 * For WordPress 4.7 Must use WP_Query object
			 */
			global $wp_the_query;

			return apply_filters( 'yith_wcan_use_wp_the_query_object', true ) ? $wp_the_query->query : $current_wp_query->query;
		}

		/**
		 * Hook into the_posts to do the main product query if needed.
		 *
		 * @access public
		 *
		 * @param WP_Post[]     $posts Retrieved posts.
		 * @param WP_Query|bool $query Query object, when relevant.
		 *
		 * @return array
		 */
		public function the_posts( $posts, $query = false ) {
			global $wp_query;
			$queried_object = $wp_query instanceof WP_Query ? $wp_query->get_queried_object() : false;

			if ( ! empty( $queried_object ) && ( is_shop() || is_product_taxonomy() || ! apply_filters( 'yith_wcan_is_search', is_search() ) ) ) {
				$filtered_posts   = array();
				$queried_post_ids = array();

				$problematic_theme = array(
					'basel',
					'ux-shop',
					'aardvark',
				);

				$wp_theme      = wp_get_theme();
				$template_name = $wp_theme->get_template();

				/**
				 * Support for Flatsome Theme lower then 3.6.0
				 */
				if ( 'flatsome' === $template_name && version_compare( '3.6.0', $wp_theme->Version, '<' ) ) {
					$problematic_theme[] = 'flatsome';
				}

				$is_qTranslateX_and_yit_core_1_0_0 = class_exists( 'QTX_Translator' ) && defined( 'YIT_CORE_VERSION' ) && '1.0.0' === YIT_CORE_VERSION;
				$is_problematic_theme              = in_array( $template_name, $problematic_theme );

				if ( $is_qTranslateX_and_yit_core_1_0_0 || $is_problematic_theme || class_exists( 'SiteOrigin_Panels' ) ) {
					add_filter( 'yith_wcan_skip_layered_nav_query', '__return_true' );
				}

				$query_filtered_posts = $this->layered_nav_query();

				foreach ( $posts as $post ) {

					if ( in_array( $post->ID, $query_filtered_posts ) ) {
						$filtered_posts[]   = $post;
						$queried_post_ids[] = $post->ID;
					}
				}

				$query->posts      = $filtered_posts;
				$query->post_count = count( $filtered_posts );

				// Get main query.
				$current_wp_query = $this->select_query_object( $query );

				if ( is_array( $current_wp_query ) ) {
					// Get WP Query for current page (without 'paged').
					unset( $current_wp_query['paged'] );
				} else {
					$current_wp_query = array();
				}

				// Ensure filters are set.
				$unfiltered_args = array_merge(
					$current_wp_query,
					array(
						'post_type'              => 'product',
						'numberposts'            => - 1,
						'post_status'            => 'publish',
						'meta_query'             => is_object( $current_wp_query ) ? $current_wp_query->meta_query : array(),
						'fields'                 => 'ids',
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'pagename'               => '',
						'wc_query'               => 'get_products_in_view', // Only for WC <= 2.6.x.
						'suppress_filters'       => true,
					)
				);

				$hide_out_of_stock_items = apply_filters( 'yith_wcan_hide_out_of_stock_items', 'yes' == get_option( 'woocommerce_hide_out_of_stock_items' ) ? true : false );

				if ( $hide_out_of_stock_items ) {
					$unfiltered_args['meta_query'][] = array(
						'key'     => '_stock_status',
						'value'   => 'instock',
						'compare' => 'AND',
					);
				}

				$unfiltered_args              = apply_filters( 'yith_wcan_unfiltered_args', $unfiltered_args );
				$this->unfiltered_product_ids = apply_filters( 'yith_wcan_unfiltered_product_ids', get_posts( $unfiltered_args ), $query, $current_wp_query );
				$this->filtered_product_ids   = $queried_post_ids;

				// Also store filtered posts ids...
				if ( count( $queried_post_ids ) > 0 ) {
					$this->filtered_product_ids = array_intersect( $this->unfiltered_product_ids, $queried_post_ids );
				} else {
					$this->filtered_product_ids = $this->unfiltered_product_ids;
				}

				if ( count( $this->layered_nav_post__in ) > 0 ) {
					$this->layered_nav_product_ids = array_intersect( $this->unfiltered_product_ids, $this->layered_nav_post__in );
				} else {
					$this->layered_nav_product_ids = $this->unfiltered_product_ids;
				}
			}

			return $posts;
		}

		/**
		 * Layered Nav post filter.
		 *
		 * @param array $filtered_posts Optional array of filtered post ids.
		 *
		 * @return array
		 */
		public function layered_nav_query( $filtered_posts = array() ) {
			global $wp_query;
			if ( apply_filters( 'yith_wcan_skip_layered_nav_query', false ) ) {
				return $filtered_posts;
			}

			$_chosen_attributes  = YITH_WCAN()->get_layered_nav_chosen_attributes();
			$is_product_taxonomy = false;
			if ( is_product_taxonomy() ) {
				global $wp_query;
				$queried_object      = $wp_query instanceof WP_Query ? $wp_query->get_queried_object() : false;
				$is_product_taxonomy = false;

				if( $queried_object ){
					$is_product_taxonomy = array(
						'taxonomy' => $queried_object->taxonomy,
						'terms'    => $queried_object->slug,
						'field'    => YITH_WCAN()->filter_term_field,
					);
				}
			}

			if ( count( $_chosen_attributes ) > 0 ) {

				$matched_products   = array(
					'and' => array(),
					'or'  => array(),
				);
				$filtered_attribute = array(
					'and' => false,
					'or'  => false,
				);

				foreach ( $_chosen_attributes as $attribute => $data ) {
					$matched_products_from_attribute = array();
					$filtered                        = false;

					if ( count( $data['terms'] ) > 0 ) {
						foreach ( $data['terms'] as $value ) {

							$args = array(
								'post_type'        => 'product',
								'numberposts'      => - 1,
								'post_status'      => 'publish',
								'fields'           => 'ids',
								'no_found_rows'    => true,
								'suppress_filters' => true,
								'tax_query'        => array(
									array(
										'taxonomy' => $attribute,
										'terms'    => $value,
										'field'    => YITH_WCAN()->filter_term_field,
									),
								),
							);

							$args = yit_product_visibility_meta( $args );

							if ( $is_product_taxonomy ) {
								$args['tax_query'][] = $is_product_taxonomy;
							}

							// TODO: Increase performance for get_posts().
							$post_ids = apply_filters( 'woocommerce_layered_nav_query_post_ids', get_posts( $args ), $args, $attribute, $value );

							if ( ! is_wp_error( $post_ids ) ) {

								if ( count( $matched_products_from_attribute ) > 0 || $filtered ) {
									$matched_products_from_attribute = 'or' === $data['query_type'] ? array_merge( $post_ids, $matched_products_from_attribute ) : array_intersect( $post_ids, $matched_products_from_attribute );
								} else {
									$matched_products_from_attribute = $post_ids;
								}

								$filtered = true;
							}
						}
					}

					if ( count( $matched_products[ $data['query_type'] ] ) > 0 || true === $filtered_attribute[ $data['query_type'] ] ) {
						$matched_products[ $data['query_type'] ] = 'or' === $data['query_type'] ? array_merge( $matched_products_from_attribute, $matched_products[ $data['query_type'] ] ) : array_intersect( $matched_products_from_attribute, $matched_products[ $data['query_type'] ] );
					} else {
						$matched_products[ $data['query_type'] ] = $matched_products_from_attribute;
					}

					$filtered_attribute[ $data['query_type'] ] = true;

					$this->filtered_product_ids_for_taxonomy[ $attribute ] = $matched_products_from_attribute;
				}

				// Combine our AND and OR result sets.
				if ( $filtered_attribute['and'] && $filtered_attribute['or'] ) {
					$results = array_intersect( $matched_products['and'], $matched_products['or'] );
				} else {
					$results = array_merge( $matched_products['and'], $matched_products['or'] );
				}

				if ( $filtered ) {

					$this->layered_nav_post__in   = $results;
					$this->layered_nav_post__in[] = 0;

					if ( count( $filtered_posts ) == 0 ) {
						$filtered_posts   = $results;
						$filtered_posts[] = 0;
					} else {
						$filtered_posts   = array_intersect( $filtered_posts, $results );
						$filtered_posts[] = 0;
					}
				}
			} else {

				$args = array(
					'post_type'        => 'product',
					'numberposts'      => - 1,
					'post_status'      => 'publish',
					'fields'           => 'ids',
					'no_found_rows'    => true,
					'suppress_filters' => true,
					'tax_query'        => array(),
					'meta_query'       => array(),
				);

				if ( $is_product_taxonomy ) {
					$args['tax_query'][] = $is_product_taxonomy;
				}

				if ( isset( $_GET['min_price'] ) && isset( $_GET['max_price'] ) ) {
					$min_price            = (float) $_GET['min_price'];
					$max_price            = (float) $_GET['max_price'];
					$args['meta_query'][] = array(
						'key'     => '_price',
						'value'   => array( $min_price, $max_price ),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					);
				}

				$args           = yit_product_visibility_meta( $args );
				$queried_object = $wp_query instanceof WP_Query ? $wp_query->get_queried_object() : false;
				$taxonomy       = false;
				$slug           = false;

				if ( $queried_object instanceof WP_Term ) {
					$taxonomy = $queried_object->taxonomy;
					$slug     = $queried_object->slug;
				}

				// TODO: Increase performance for get_posts().
				$post_ids = apply_filters( 'woocommerce_layered_nav_query_post_ids', get_posts( $args ), $args, $taxonomy, $slug );

				if ( ! is_wp_error( $post_ids ) ) {
					$this->layered_nav_post__in   = $post_ids;
					$this->layered_nav_post__in[] = 0;

					if ( count( $filtered_posts ) == 0 ) {
						$filtered_posts   = $post_ids;
						$filtered_posts[] = 0;
					} else {
						$filtered_posts   = array_intersect( $filtered_posts, $post_ids );
						$filtered_posts[] = 0;
					}
				}
			}

			return (array) $filtered_posts;
		}

		/* === ASSETS METHODS === */

		/**
		 * Enqueue frontend styles and scripts
		 *
		 * @access public
		 * @return void
		 * @since  1.0.0
		 */
		public function enqueue_styles_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			if ( yith_wcan_can_be_displayed() ) {
				wp_register_script( 'jseldom', YITH_WCAN_URL . 'assets/js/jquery.jseldom' . $suffix . '.js', array( 'jquery' ), '0.0.2', true );
				wp_enqueue_style( 'yith-wcan-frontend', YITH_WCAN_URL . 'assets/css/frontend.css', false, YITH_WCAN_VERSION );
				wp_enqueue_script( 'yith-wcan-script', YITH_WCAN_URL . 'assets/js/yith-wcan-frontend' . $suffix . '.js', array( 'jquery', 'jseldom' ), YITH_WCAN_VERSION, true );

				$custom_style     = yith_wcan_get_option( 'yith_wcan_custom_style', '' );
				$current_theme    = function_exists( 'wp_get_theme' ) ? wp_get_theme() : null;
				$current_template = $current_theme instanceof WP_Theme ? $current_theme->get_template() : '';

				! empty( $custom_style ) && wp_add_inline_style( 'yith-wcan-frontend', sanitize_text_field( $custom_style ) );

				$args = apply_filters(
					'yith_wcan_ajax_frontend_classes',
					array(
						'container'          => yith_wcan_get_option( 'yith_wcan_ajax_shop_container', '.products' ),
						'pagination'         => yith_wcan_get_option( 'yith_wcan_ajax_shop_pagination', 'nav.woocommerce-pagination' ),
						'result_count'       => yith_wcan_get_option( 'yith_wcan_ajax_shop_result_container', '.woocommerce-result-count' ),
						'wc_price_slider'    => array(
							'wrapper'   => '.price_slider',
							'min_price' => '.price_slider_amount #min_price',
							'max_price' => '.price_slider_amount #max_price',
						),
						'is_mobile'          => wp_is_mobile(),
						'scroll_top'         => yith_wcan_get_option( 'yith_wcan_ajax_scroll_top_class', '.yit-wcan-container' ),
						'scroll_top_mode'    => yith_wcan_get_option( 'yith_wcan_scroll_top_mode', 'mobile' ),
						'change_browser_url' => 'yes' == yith_wcan_get_option( 'yith_wcan_change_browser_url', 'yes' ) ? true : false,
						/* === Avada Theme Support === */
						'avada'              => array(
							'is_enabled' => class_exists( 'Avada' ),
							'sort_count' => 'ul.sort-count.order-dropdown',
						),
						/* Flatsome Theme Support */
						'flatsome'           => array(
							'is_enabled'        => function_exists( 'flatsome_option' ),
							'lazy_load_enabled' => get_theme_mod( 'lazy_load_images' ),
						),
						/* === YooThemes Theme Support === */
						'yootheme'           => array(
							'is_enabled' => 'yootheme' === $current_template,
						),
					)
				);

				wp_localize_script( 'yith-wcan-script', 'yith_wcan', apply_filters( 'yith-wcan-frontend-args', $args ) );
			}

			$shortcode_args = apply_filters(
				'yith_wcan_shortcodes_script_args',
				array(
					'query_param'        => YITH_WCAN_Query()->get_query_param(),
					'content'            => apply_filters( 'yith_wcan_content_selector', '#content' ),
					'change_browser_url' => in_array( yith_wcan_get_option( 'yith_wcan_change_browser_url', 'yes' ), array( 'yes', 'custom' ) ),
					'instant_filters'    => 'yes' === yith_wcan_get_option( 'yith_wcan_instant_filters', 'yes' ),
					'ajax_filters'       => 'yes' === yith_wcan_get_option( 'yith_wcan_ajax_filters', 'yes' ),
					'show_clear_filter'  => 'yes' === yith_wcan_get_option( 'yith_wcan_show_clear_filter', 'no' ),
					'scroll_top'         => 'yes' === yith_wcan_get_option( 'yith_wcan_scroll_top', 'no' ),
					'modal_on_mobile'    => 'yes' === yith_wcan_get_option( 'yith_wcan_modal_on_mobile', 'yes' ),
					'session_param'      => YITH_WCAN_Session_Factory::get_session_query_param(),
					'is_shop_on_front'   => is_shop(),
					'shop_url'           => trailingslashit( yit_get_woocommerce_layered_nav_link() ),
					'terms_per_page'     => apply_filters( 'yith_wcan_dropdown_terms_per_page', 10 ),
					'loader'             => 'custom' === yith_wcan_get_option( 'yith_wcan_ajax_loader_style', 'default' ) ? yith_wcan_get_option( 'yith_wcan_ajax_loader_custom_icon', '' ) : false,
					'mobile_media_query' => 991,
					'currency_format'    => apply_filters(
						'yith_wcan_shortcodes_script_currency_format',
						array(
							'symbol'    => get_woocommerce_currency_symbol(),
							'decimal'   => esc_attr( wc_get_price_decimal_separator() ),
							'thousand'  => esc_attr( wc_get_price_thousand_separator() ),
							'precision' => wc_get_price_decimals(),
							'format'    => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ),
						)
					),
					'labels'             => apply_filters(
						'yith_wcan_shortcodes_script_labels',
						array(
							'empty_option' => _x( 'All', '[FRONTEND] "All" label shown when no term is selected', 'yith-woocommerce-ajax-navigation' ),
							'search_placeholder' => _x( 'Search...', '[FRONTEND] Search placeholder shown in terms dropdown', 'yith-woocommerce-ajax-navigation' ),
							'no_items' => _x( 'No item found', '[FRONTEND] Empty items list in the dropdown', 'yith-woocommerce-ajax-navigation' ),
							// translators: 1. Number of items to show.
							'show_more' => _x( 'Show %d more', '[FRONTEND] Show more link on terms dropdown', 'yith-woocommerce-ajax-navigation' ),
							'close' => _x( 'Close', '[FRONTEND] Alt text for modal close button on mobile', 'yith-woocommerce-ajax-navigation' ),
							'show_results' => _x( 'Show results', '[FRONTEND] Label for filter button, on mobile modal', 'yith-woocommerce-ajax-navigation' ),
							'clear_selection' => _x( 'Clear', '[FRONTEND] Label for clear selection link, that appears above filter after selection', 'yith-woocommerce-ajax-navigation' ),
							'clear_all_selections' => _x( 'Clear All', '[FRONTEND] Label for clear selection link, that appears above filter after selection', 'yith-woocommerce-ajax-navigation' ),
						)
					),
				)
			);

			wp_enqueue_style( 'yith-wcan-shortcodes' );
			wp_localize_script( 'yith-wcan-shortcodes', 'yith_wcan_shortcodes', $shortcode_args );

			$custom_css = $this->_build_custom_css();

			if ( ! empty( $custom_css ) ) {
				wp_add_inline_style( 'yith-wcan-shortcodes', $custom_css );
			}
		}

		/**
		 * Build custom CSS template, to be used in page header
		 *
		 * @return bool|string Custom CSS template, ro false when no content should be output.
		 */
		protected function _build_custom_css() {
			$default_accent_color = apply_filters( 'yith_wcan_default_accent_color', '#A7144C' );

			$variables = array();
			$options   = array(
				'filters_colors' => array(
					'default' => array(
						'titles'     => '#434343',
						'background' => '#FFFFFF',
						'accent'     => $default_accent_color,
					),
					'callback' => function( $raw_value ) {
						// register accent color as rgb component, to be used in rgba() function.
						$accent = $raw_value['accent'];

						list( $accent_r, $accent_g, $accent_b ) = yith_wcan_hex2rgb( $accent );

						$raw_value['accent_r'] = $accent_r;
						$raw_value['accent_g'] = $accent_g;
						$raw_value['accent_b'] = $accent_b;

						return $raw_value;
					},
				),
				'color_swatches_style' => array(
					'default' => 'round',
					'variable' => 'color_swatches_border_radius',
					'callback' => function( $raw_value ) {
						return 'round' === $raw_value ? '100%' : '5px';
					},
				),
				'color_swatches_size' => array(
					'default' => '30',
					'callback' => function( $raw_value ) {
						return $raw_value . 'px';
					},
				),
				'labels_style' => array(
					'default' => array(
						'background'        => '#FFFFFF',
						'background_hover'  => $default_accent_color,
						'background_active' => $default_accent_color,
						'text'              => '#434343',
						'text_hover'        => '#FFFFFF',
						'text_active'       => '#FFFFFF',
					),
				),
				'anchors_style' => array(
					'default' => array(
						'text' => '#434343',
						'text_hover' => $default_accent_color,
						'text_active' => $default_accent_color,
					),
				),
			);

			// cycles through options.
			foreach ( $options as $variable => $settings ) {
				$option   = "yith_wcan_{$variable}";
				$variable = '--yith-wcan-' . ( isset( $settings['variable'] ) ? $settings['variable'] : $variable );
				$value    = yith_wcan_get_option( $option, $settings['default'] );

				if ( isset( $settings['callback'] ) && is_callable( $settings['callback'] ) ) {
					$value = $settings['callback']( $value );
				}

				if ( empty( $value ) ) {
					continue;
				}

				if ( is_array( $value ) ) {
					foreach ( $value as $sub_variable => $sub_value ) {
						$variables[ "{$variable}_{$sub_variable}" ] = $sub_value;
					}
				} else {
					$variables[ $variable ] = $value;
				}
			}

			if ( empty( $variables ) ) {
				return false;
			}

			// start CSS snippet.
			$template = ":root{\n";

			// cycles through variables.
			foreach ( $variables as $variable => $value ) {
				$template .= "\t{$variable}: {$value};\n";
			}

			// close :root directive.
			$template .= '}';

			return $template;
		}

		/**
		 * Add a body class(es)
		 *
		 * @param array $classes The classes array.
		 *
		 * @return array
		 * @since  1.0
		 * @author Andrea Grillo <andrea.grillo@yithemes.com>
		 */
		public function body_class( $classes ) {
			$classes[] = apply_filters( 'yith_wcan_body_class', 'yith-wcan-free' );

			return $classes;
		}

		/**
		 * Add custom meta to filtered page
		 *
		 * @return void
		 */
		public function add_meta() {
			$session_share_url = $this->_query->get_current_session_share_url();

			if ( ! $session_share_url ) {
				return;
			}

			?>
				<meta name="yith_wcan:sharing_url" content="<?php echo esc_attr( $session_share_url ); ?>">
			<?php
		}

		/* === TEMPLATE METHODS === */

		/**
		 * Print Apply Filters button template
		 *
		 * @param YITH_WCAN_Preset|bool $preset Current preset, when applicable; false otherwise.
		 *
		 * @return void
		 */
		public function apply_filters_button( $preset = false ) {
			$instant_filters = 'yes' === yith_wcan_get_option( 'yith_wcan_instant_filters', 'yes' );

			if ( $instant_filters ) {
				return;
			}

			yith_wcan_get_template( 'filters/global/apply-filters.php', compact( 'preset' ) );
		}

		/**
		 * Print preset title template
		 *
		 * @param YITH_WCAN_Preset|bool $preset Current preset, when applicable; false otherwise.
		 *
		 * @return void
		 */
		public function filters_title( $preset = false ) {
			$title = yith_wcan_get_option( 'yith_wcan_filters_title', '' );

			/**
			 * Print title template when:
			 * 1. Admin set a title
			 * 2. Filters will be shown as modal on mobile (title will be shown on mobile only, default will apply if no filter is configured).
			 */
			if ( empty( $title ) && 'yes' !== yith_wcan_get_option( 'yith_wcan_modal_on_mobile' ) ) {
				return;
			}

			$additional_classes_array = array();

			// apply default title when required.
			if ( empty( $title ) ) {
				$title = apply_filters( 'yith_wcan_default_modal_title', _x( 'Filter products', '[FRONTEND] Default modal title - mobile only', 'yith-woocommerce-ajax-navigation' ) );
				$additional_classes_array[] = 'mobile-only';
			}

			$title_tag = apply_filters( 'yith_wcan_preset_title_tag', 'h3' );
			$additional_classes = implode( ' ', apply_filters( 'yith_wcan_preset_title_classes', $additional_classes_array, $this ) );

			echo wp_kses_post( sprintf( '<%1$s class="%3$s">%2$s</%1$s>', esc_html( $title_tag ), esc_html( $title ), esc_attr( $additional_classes ) ) );
		}

		/**
		 * Hooks callback that will print list fo active filters
		 *
		 * @return void
		 */
		public function add_active_filters_list() {
			$show_active_filters = 'yes' === yith_wcan_get_option( 'yith_wcan_show_active_labels', 'yes' );
			$active_filters_position = yith_wcan_get_option( 'yith_wcan_active_labels_position', 'before_filters' );

			if ( ! $show_active_filters ) {
				return;
			}

			switch ( $active_filters_position ) {
				case 'before_filters':
					add_action( 'yith_wcan_before_preset_filters', array( $this, 'active_filters_list' ) );
					break;
				case 'after_filters':
					add_action( 'yith_wcan_after_preset_filters', array( $this, 'active_filters_list' ) );
					break;
				case 'before_products':
					$locations = $this->get_before_product_locations();

					if ( ! $locations ) {
						return;
					}

					foreach ( $locations as $location ) {
						add_action( $location['hook'], array( $this, 'active_filters_list' ), $location['priority'] );
					}
					break;
			}
		}

		/**
		 * Print list of active filters
		 *
		 * @param YITH_WCAN_Preset|bool $preset Current preset, when applicable; false otherwise.
		 *
		 * @return void
		 */
		public function active_filters_list( $preset = false ) {
			$show_active_filters = 'yes' === yith_wcan_get_option( 'yith_wcan_show_active_labels', 'yes' );

			if ( ! $show_active_filters ) {
				return;
			}

			$active_filters = $this->_query->get_active_filters( 'view' );
			$show_titles    = 'yes' === yith_wcan_get_option( 'yith_wcan_active_labels_with_titles', 'yes' );
			$labels_heading = apply_filters( 'yith_wcan_active_filters_title', _x( 'Active filters', '[FRONTEND] Active filters title', 'yith-woocommerce-ajax-navigation' ) );

			yith_wcan_get_template( 'filters/global/active-filters.php', compact( 'preset', 'active_filters', 'show_titles', 'labels_heading' ) );
		}

		/**
		 * Hooks callback that will print list fo active filters
		 *
		 * @return void
		 */
		public function add_reset_button() {
			$show_reset_button = 'yes' === yith_wcan_get_option( 'yith_wcan_show_reset', 'yes' );
			$reset_button_position = yith_wcan_get_option( 'yith_wcan_reset_button_position', 'after_filters' );

			if ( ! $show_reset_button ) {
				return;
			}

			switch ( $reset_button_position ) {
				case 'before_filters':
					add_action( 'yith_wcan_before_preset_filters', array( $this, 'reset_button' ) );
					break;
				case 'after_filters':
					add_action( 'yith_wcan_after_preset_filters', array( $this, 'reset_button' ) );
					break;
				case 'before_products':
					$locations = $this->get_before_product_locations( 2 );

					if ( ! $locations ) {
						return;
					}

					foreach ( $locations as $location ) {
						add_action( $location['hook'], array( $this, 'reset_button' ), $location['priority'] );
					}
					break;
				case 'after_active_labels':
					add_action( 'yith_wcan_after_active_filters', array( $this, 'reset_button' ) );
					break;
			}
		}

		/**
		 * Print list of active filters
		 *
		 * @param YITH_WCAN_Preset|bool $preset Current preset, when applicable; false otherwise.
		 *
		 * @return void
		 */
		public function reset_button( $preset = false ) {
			$show_reset_button = 'yes' === yith_wcan_get_option( 'yith_wcan_show_reset', 'yes' );

			if ( ! $show_reset_button || ! YITH_WCAN_Query()->is_filtered() ) {
				return;
			}

			yith_wcan_get_template( 'filters/global/reset-filters.php', compact( 'preset' ) );
		}

		/**
		 * Adds Mobile Modal Opener button, before product sections when possible
		 *
		 * @return void
		 */
		public function add_mobile_modal_opener() {
			$modal_on_mobile = 'yes' === yith_wcan_get_option( 'yith_wcan_modal_on_mobile', 'no' );

			if ( ! $modal_on_mobile ) {
				return;
			}

			$locations = $this->get_before_product_locations( -2 );

			if ( ! $locations ) {
				return;
			}

			foreach ( $locations as $location ) {
				add_action( $location['hook'], array( $this, 'mobile_modal_opener' ), $location['priority'] );
			}
		}

		/**
		 * Print Mobile Modal Opener button
		 *
		 * @param YITH_WCAN_Preset|bool $preset Current preset, when applicable; false otherwise.
		 *
		 * @return void
		 */
		public function mobile_modal_opener( $preset = false ) {
			$preset = $preset instanceof YITH_WCAN_Preset ? $preset : false;
			$label = apply_filters( 'yith_wcan_mobile_modal_opener_label', _x( 'Filters', '[FRONTEND] Label for the Filters button on mobile', 'yith-woocommerce-ajax-navigation' ) );

			yith_wcan_get_template( 'filters/global/mobile-filters.php', compact( 'label', 'preset' ) );
		}

		/* === UTILS METHODS === */

		/**
		 * Returns an array of locations where items shown "Before products" should be hooked
		 *
		 * @param int $offset Integer used to offset hook priority.
		 *                    It is used when multiple templates are hooked to the same location, and you want to define a clear order.
		 *
		 * @return array Array of locations.
		 */
		public function get_before_product_locations( $offset = 0 ) {
			return apply_filters(
				'yith_wcab_before_product_locations',
				array(
					// before shop.
					array(
						'hook' => 'woocommerce_before_shop_loop',
						'priority' => 10 + $offset,
					),
					// before products shortcode.
					array(
						'hook' => 'woocommerce_shortcode_before_products_loop',
						'priority' => 10 + $offset,
					),
					// before no_products template.
					array(
						'hook' => 'woocommerce_no_products_found',
						'priority' => 5 + $offset,
					),
				)
			);
		}
	}
}

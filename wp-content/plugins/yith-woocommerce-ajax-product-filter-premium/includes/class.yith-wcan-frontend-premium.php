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

if ( ! class_exists( 'YITH_WCAN_Frontend_Premium' ) ) {
	/**
	 * Frontend class.
	 * The class manage all the frontend behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAN_Frontend_Premium extends YITH_WCAN_Frontend {

		/**
		 * Constructor method
		 *
		 * @return void
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'wp_head', array( $this, 'meta_robot_generator' ) );

			add_filter( 'yith_wcan_body_class', array( $this, 'premium_body_class' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'add_dropdown_styles' ), 20 );
		}

		/**
		 * Enqueue Script for Premium version
		 *
		 * @return void
		 * @author Andrea Grillo <andrea.grillo@yithemes.com
		 * @since  2.0
		 */
		public function enqueue_styles_scripts() {
			parent::enqueue_styles_scripts();

			if ( yith_wcan_can_be_displayed() ) {
				$suffix     = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				$loader_url = YITH_WCAN_URL . 'assets/images/ajax-loader.gif';

				$options = array(
					'ajax_wc_price_filter'                  => yith_wcan_get_option( 'yith_wcan_enable_ajax_price_filter' ),
					'wc_price_filter_slider'                => yith_wcan_get_option( 'yith_wcan_enable_ajax_price_filter_slider' ),
					'wc_price_filter_slider_in_ajax'        => yith_wcan_get_option( 'yith_wcan_enable_slider_in_ajax' ),
					'wc_price_filter_dropdown'              => yith_wcan_get_option( 'yith_wcan_enable_dropdown_price_filter' ),
					'wc_price_filter_dropdown_style'        => apply_filters( 'yith_wcan_dropdown_type', yith_wcan_get_option( 'yith_wcan_dropdown_style' ) ),
					'wc_price_filter_dropdown_widget_class' => yith_wcan_get_option( 'yith_wcan_ajax_widget_title_class', 'h3.widget-title' ),
					'widget_wrapper_class'                  => yith_wcan_get_option( 'yith_wcan_ajax_widget_wrapper_class', '.widget' ),
					'price_filter_dropdown_class'           => apply_filters( 'yith_wcan_dropdown_class', 'widget-dropdown' ),
					'ajax_pagination_enabled'               => yith_wcan_get_option( 'yith_wcan_enable_ajax_shop_pagination', 'no' ),
					'pagination_anchor'                     => yith_wcan_get_option( 'yith_wcan_ajax_shop_pagination', 'nav.woocommerce-pagination' ) . ' ' . yith_wcan_get_option( 'yith_wcan_ajax_shop_pagination_anchor_class', 'a.page-numbers' ),
					'force_widget_init'                     => apply_filters( 'yith_wcan_force_widget_init', false ),
				);

				wp_enqueue_script( 'yith_wcan_frontend-premium', YITH_WCAN_URL . 'assets/js/yith-wcan-frontend-premium' . $suffix . '.js', array( 'jquery' ), YITH_WCAN_VERSION, true );
				wp_localize_script( 'yith-wcan-script', 'yith_wcan_frontend', array( 'loader_url' => yith_wcan_get_option( 'yith_wcan_ajax_loader', $loader_url ) ) );
				wp_localize_script( 'yith_wcan_frontend-premium', 'yith_wcan_frontend_premium', $options );
			}
		}

		/**
		 * Enqueue Script for Widget Dropdown
		 *
		 * @return void
		 * @author Andrea Grillo <andrea.grillo@yithemes.com
		 * @since  2.0
		 */
		public function add_dropdown_styles() {
			// Dropdown Options.
			$widget_title   = yith_wcan_get_option( 'yith_wcan_ajax_widget_title_class', 'h3.widget-title' );
			$widget_wrapper = yith_wcan_get_option( 'yith_wcan_ajax_widget_wrapper_class', '.widget' );
			$background_url = YITH_WCAN_URL . 'assets/images/select-arrow.png';

			$css = "{$widget_wrapper} {$widget_title}.with-dropdown {position: relative; cursor: pointer;}
                    {$widget_wrapper} {$widget_title}.with-dropdown .widget-dropdown { border-width: 0; width: 22px; height: 22px; background: url({$background_url}) top 0px right no-repeat; background-size: 95% !important; position: absolute; top: 0; right: 0;}
                    {$widget_wrapper} {$widget_title}.with-dropdown.open .widget-dropdown {background-position: bottom 15px right;}";

			wp_add_inline_style( 'yith-wcan-frontend', $css );
		}

		/**
		 * Add Meta Robots in the <head> section
		 *
		 * @return void
		 * @author Andrea Grillo <andrea.grillo@yithemes.com>
		 * @author Antonio La Rocca <antonio.larocca@yithemes.com>
		 * @since  2.4.1
		 */
		public function meta_robot_generator() {
			$enable_seo      = 'yes' == yith_wcan_get_option( 'yith_wcan_enable_seo' );
			$meta_options    = yith_wcan_get_option( 'yith_wcan_seo_value', 'noindex-follow' );

			if ( $enable_seo && 'disabled' !== $meta_options && ( YITH_WCAN_Query()->is_filtered() || yith_wcan_can_be_displayed() && yit_is_filtered_uri() ) ) {
				$content = str_replace( '-', ', ', $meta_options );
				?>
				<meta name="robots" content="<?php echo esc_attr( $content ); ?>">
				<?php
			}
		}

		/**
		 * Add a body class(es)
		 *
		 * @param string $classes Body classes added by the plugin.
		 *
		 * @return string Filtered list of classes added by the plugin to the body.
		 * @since  1.0
		 * @author Andrea Grillo <andrea.grillo@yithemes.com>
		 */
		public function premium_body_class( $classes ) {
			return 'yith-wcan-pro';
		}
	}
}

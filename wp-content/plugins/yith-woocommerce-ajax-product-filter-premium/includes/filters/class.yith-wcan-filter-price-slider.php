<?php
/**
 * Price Slider filter class
 *
 * Offers method specific to Price Range filter
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Product FIlter
 * @version 4.0.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Filter_Price_Slider' ) ) {
	/**
	 * Price Slider Filter Handling
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAN_Filter_Price_Slider extends YITH_WCAN_Filter {

		/**
		 * Filter type
		 *
		 * @var string
		 */
		protected $type = 'price_slider';

		/**
		 * Method that will output content of the filter on frontend
		 *
		 * @return string Template for current filter
		 */
		public function render() {
			$atts = array(
				'filter' => $this,
				'preset' => $this->get_preset(),
			);

			return yith_wcan_get_template( 'filters/filter-price-slider.php', $atts, false );
		}

		/**
		 * Returns current minimum value of the price range
		 *
		 * @return float Current minimum value of the price range.
		 */
		public function get_current_min() {
			return (float) YITH_WCAN_Query()->get( 'min_price', $this->get_price_slider_min() );
		}

		/**
		 * Returns current maximum value of the price range
		 *
		 * @return float Current maximum value of the price range.
		 */
		public function get_current_max() {
			return (float) YITH_WCAN_Query()->get( 'max_price', $this->get_price_slider_max() );
		}
	}
}

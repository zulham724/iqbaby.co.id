<?php
/**
 * Review filter class
 *
 * Offers method specific to Review filter
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Product FIlter
 * @version 4.0.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Filter_Review' ) ) {
	/**
	 * OrderBy Filter Handling
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAN_Filter_Review extends YITH_WCAN_Filter {

		/**
		 * Filter type
		 *
		 * @var string
		 */
		protected $type = 'review';

		/**
		 * List of formatted review ratings for current view
		 *
		 * @var array
		 */
		protected $_formatted_rates;

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

			return yith_wcan_get_template( 'filters/filter-review.php', $atts, false );
		}

		/**
		 * Render count for them each review rating
		 *
		 * @param int $rate Review rating to count.
		 * @return string Count template
		 */
		public function render_review_rate_count( $rate ) {
			$count = is_int( $rate ) ? $this->get_review_rate_count( $rate ) : $rate['count'];

			return $this->render_count( $count );
		}

		/**
		 * Checks whether there are review rates relevant to current query
		 *
		 * @return bool Result of the test.
		 */
		public function has_relevant_rates() {
			return ! ! $this->get_formatted_review_rates();
		}

		/**
		 * Retrieve formatted rates
		 *
		 * @return array Array of formatted ranges.
		 */
		public function get_formatted_review_rates() {
			if ( ! empty( $this->_formatted_rates ) ) {
				return $this->_formatted_rates;
			}

			$result = array();

			for ( $i = 5; $i > 0; $i-- ) {
				$rating = array(
					'rate' => $i,
				);

				$rating['count'] = $this->get_review_rate_count( $i );

				// hidden item.
				if ( ! $rating['count'] && 'hide' == $this->get_adoptive() ) {
					continue;
				}

				// set additional classes.
				$rating['additional_classes'] = array();

				if ( $this->is_review_rate_active( $i ) ) {
					$rating['additional_classes'][] = 'active';
				}

				if ( ! $rating['count'] ) {
					$rating['additional_classes'][] = 'disabled';
				}

				$rating['additional_classes'] = implode( ' ', $rating['additional_classes'] );

				$result[] = $rating;
			}

			$this->_formatted_rates = $result;

			return $result;
		}

		/**
		 * Retrieves url to filter by the passed review rate
		 *
		 * @param int $rate Review rate to check.
		 * @return string Url to filter by specified parameter.
		 */
		public function get_filter_url( $rate ) {
			$param = array( 'rating_filter' => $rate );

			if ( $this->is_review_rate_active( $rate ) ) {
				$url = YITH_WCAN_Query()->get_filter_url( array(), $param );
			} else {
				$url = YITH_WCAN_Query()->get_filter_url( $param );
			}

			return $url;
		}

		/**
		 * Checks if we're filtering by a specific review rate
		 *
		 * @param int $rate Review rate to check.
		 * @return bool Whether that rate is active or not
		 */
		public function is_review_rate_active( $rate ) {
			return YITH_WCAN_Query()->is_review_rate( $rate );
		}

		/**
		 * Returns count of products with a specific review rating for current query
		 *
		 * @param int $rate Review rating to test.
		 *
		 * @return int Items count
		 */
		public function get_review_rate_count( $rate ) {
			return YITH_WCAN_Query()->count_query_relevant_rated_products( $rate );
		}
	}
}

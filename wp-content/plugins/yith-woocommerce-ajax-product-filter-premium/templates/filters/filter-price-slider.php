<?php
/**
 * Price Slider template
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Product Filter
 * @version 4.0.0
 */

/**
 * Variables available for this template:
 *
 * @var $preset YITH_WCAN_Preset
 * @var $filter YITH_WCAN_Filter_Price_Slider
 * @var $term WP_Term
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly
?>

<?php if ( $filter->get_order_options() ) : ?>
	<div class="yith-wcan-filter <?php echo esc_attr( $filter->get_additional_classes() ); ?>" id="filter_<?php echo esc_attr( $preset->get_id() ); ?>_<?php echo esc_attr( $filter->get_id() ); ?>" data-filter-type="<?php echo esc_attr( $filter->get_type() ); ?>" data-filter-id="<?php echo esc_attr( $filter->get_id() ); ?>">
		<?php echo $filter->render_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="filter-content">
			<div class="price-slider" data-min="<?php echo esc_attr( $filter->get_price_slider_min() ); ?>" data-max="<?php echo esc_attr( $filter->get_price_slider_max() ); ?>" data-step="<?php echo esc_attr( $filter->get_price_slider_step() ); ?>">
				<div class="price-slider-ui"></div>
				<input type="hidden" class="price-slider-min" name="filter[<?php echo esc_attr( $preset->get_id() ); ?>][<?php echo esc_attr( $filter->get_id() ); ?>][min]" id="filter_<?php echo esc_attr( $preset->get_id() ); ?>_<?php echo esc_attr( $filter->get_id() ); ?>_min" value="<?php echo esc_attr( $filter->get_current_min() ); ?>" />
				<input type="hidden" class="price-slider-max" name="filter[<?php echo esc_attr( $preset->get_id() ); ?>][<?php echo esc_attr( $filter->get_id() ); ?>][max]" id="filter_<?php echo esc_attr( $preset->get_id() ); ?>_<?php echo esc_attr( $filter->get_id() ); ?>_max" value="<?php echo esc_attr( $filter->get_current_max() ); ?>" />
			</div>
		</div>
	</div>
<?php endif; ?>

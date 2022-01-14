<?php
/**
 * Price Range template
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Product Filter
 * @version 4.0.0
 */

/**
 * Variables available for this template:
 *
 * @var $preset YITH_WCAN_Preset
 * @var $filter YITH_WCAN_Filter_Price_Range
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly
?>

<?php if ( $filter->has_relevant_ranges() ) : ?>
	<div class="yith-wcan-filter <?php echo esc_attr( $filter->get_additional_classes() ); ?>" id="filter_<?php echo esc_attr( $preset->get_id() ); ?>_<?php echo esc_attr( $filter->get_id() ); ?>" data-filter-type="<?php echo esc_attr( $filter->get_type() ); ?>" data-filter-id="<?php echo esc_attr( $filter->get_id() ); ?>">
		<?php echo $filter->render_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="filter-content">
			<ul class="filter-items <?php echo esc_attr( $filter->get_items_container_classes() ); ?>">
				<?php foreach ( $filter->get_formatted_ranges() as $range ) : ?>
					<li class="filter-item text <?php echo esc_attr( $range['additional_classes'] ); ?>">
						<a href="<?php echo esc_url( $filter->get_filter_url( $range ) ); ?>" <?php yith_wcan_add_rel_nofollow_to_url( true, true ); ?> role="button" data-range-min="<?php echo esc_attr( $range['min'] ); ?>" data-range-max="<?php echo $range['unlimited'] ? '' : esc_attr( $range['max'] ); ?>" class="price-range">
							<?php echo $filter->render_formatted_range( $range ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo $filter->render_range_count( $range ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
<?php endif; ?>

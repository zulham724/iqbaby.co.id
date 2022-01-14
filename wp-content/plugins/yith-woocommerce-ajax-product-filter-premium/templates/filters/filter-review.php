<?php
/**
 * Filter by review template
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Product Filter
 * @version 4.0.0
 */

/**
 * Variables available for this template:
 *
 * @var $preset YITH_WCAN_Preset
 * @var $filter YITH_WCAN_Filter_Review
 * @var $term WP_Term
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly
?>

<?php if ( $filter->has_relevant_rates() ) : ?>
	<div class="yith-wcan-filter <?php echo esc_attr( $filter->get_additional_classes() ); ?>" id="filter_<?php echo esc_attr( $preset->get_id() ); ?>_<?php echo esc_attr( $filter->get_id() ); ?>" data-filter-type="<?php echo esc_attr( $filter->get_type() ); ?>" data-filter-id="<?php echo esc_attr( $filter->get_id() ); ?>">
		<?php echo $filter->render_title(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="filter-content">
			<select class="filter-items filter-dropdown <?php echo esc_attr( $filter->get_items_container_classes() ); ?>" name="filter[<?php echo esc_attr( $preset->get_id() ); ?>][<?php echo esc_attr( $filter->get_id() ); ?>]" id="filter_<?php echo esc_attr( $preset->get_id() ); ?>_<?php echo esc_attr( $filter->get_id() ); ?>" data-order="DESC">
				<option class="filter-item select" value=""><?php echo esc_html_x( 'Any rating', '[FRONTEND] General option for reviews dropdown', 'yith-woocommerce-ajax-navigation' ); ?></option>
				<?php foreach ( $filter->get_formatted_review_rates() as $rate ) : ?>
					<option class="filter-item select <?php echo esc_attr( $rate['additional_classes'] ); ?>" value="<?php echo esc_attr( $rate['rate'] ); ?>" <?php selected( $filter->is_review_rate_active( $rate['rate'] ) ); ?> data-template="<?php echo esc_attr( yith_wcan_get_rating_html( $rate['rate'] ) ); ?>" data-count="<?php echo esc_attr( $filter->render_review_rate_count( $rate ) ); ?>" >
						<?php echo esc_html( yith_wcan_get_rating_label( $rate['rate'] ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
<?php endif; ?>

<?php
/**
 * General options
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Product Filter
 * @version 4.0.0
 */

return apply_filters(
	'yith_wcan_panel_general_options',
	array(

		'general' => array(
			'general_section_start' => array(
				'name' => _x( 'General settings', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'yith_wcan_general_settings',
			),

			'instant_filter' => array(
				'name'      => _x( 'Filter mode', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Choose to apply filters in real time using AJAX or whether to show a button to apply all filters', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_instant_filters',
				'type'      => 'yith-field',
				'yith-type' => 'radio',
				'default'   => 'yes',
				'options'   => array(
					'yes' => _x( 'Instant result', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
					'no'  => _x( 'By clicking "Apply filters" button', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				),
			),

			'ajax_filter' => array(
				'name'      => _x( 'Show results', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Choose whether to load the results on the same page using AJAX or load the results on a new page ', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_ajax_filters',
				'type'      => 'yith-field',
				'default'   => 'yes',
				'yith-type' => 'radio',
				'options'   => array(
					'yes' => _x( 'In same page using AJAX', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
					'no'  => _x( 'Reload on a new page', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				),
			),

			'hide_empty_terms' => array(
				'name'      => _x( 'Hide empty terms', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable to hide empty terms from filters section', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_hide_empty_terms',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'hide_out_of_stock' => array(
				'name'      => _x( 'Hide out of stock products', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable to hide "out of stock" products from the results.', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_hide_out_of_stock_products',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'show_reset' => array(
				'name'      => _x( 'Show reset button', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable to show the "Reset filter" button to allow the user to cancel the filter selection in one click', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_show_reset',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'reset_button_positon' => array(
				'name'      => _x( 'Reset button position', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Choose the default position for reset button', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_reset_button_position',
				'type'      => 'yith-field',
				'yith-type' => 'radio',
				'default'   => 'after_filters',
				'options'   => array(
					'before_filters'      => _x( 'Before filters', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
					'after_filters'       => _x( 'After filters', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
					'before_products'     => _x( 'Above products list<small>When using WooCommerce\'s Gutenberg product blocks, this may not work as expected; in these cases you can place Reset Button anywhere in the page using <code>[yith_wcan_reset_button]</code> shortcode or <code>YITH Filters Reset Button</code> block</small>', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
					'after_active_labels' => _x( 'Inline with active filters', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				),
				'deps'      => array(
					'ids'    => 'yith_wcan_show_reset',
					'values' => 'yes',
				),
			),

			'show_clear_filter' => array(
				'name'      => _x( 'Show "Clear" above each filter', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable to show the "Clear" link above each filter of the preset', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_show_clear_filter',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'show_active_labels' => array(
				'name'      => _x( 'Show active filters as labels', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable to show the active filters as labels. Labels show the current filters selection, and can be used to remove any active filter.', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_show_active_labels',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'active_labels_position' => array(
				'name'      => _x( 'Active filters labels position', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Choose the default position for Active Filters labels', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_active_labels_position',
				'type'      => 'yith-field',
				'yith-type' => 'radio',
				'default'   => 'before_filters',
				'options'   => array(
					'before_filters'  => _x( 'Before filters', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
					'after_filters'   => _x( 'After filters', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
					'before_products' => _x( 'Above products list<small>When using WooCommerce\'s Gutenberg product blocks, this may not work as expected; in these cases you can place Reset Button anywhere in the page using <code>[yith_wcan_active_filters_labels]</code> shortcode or <code>YITH Mobile Filters Modal Opener</code> block</small>', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				),
				'deps'      => array(
					'ids'    => 'yith_wcan_show_active_labels',
					'values' => 'yes',
				),
			),

			'active_labels_with_titles' => array(
				'name'      => _x( 'Show titles for active filter labels', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable to show labels subdivided by filter, and to show a title for each group', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_active_labels_with_titles',
				'type'      => 'yith-field',
				'default'   => 'yes',
				'yith-type' => 'onoff',
				'deps'      => array(
					'ids'    => 'yith_wcan_show_active_labels',
					'values' => 'yes',
				),
			),

			'scroll_top' => array(
				'name'      => _x( 'Scroll top after filtering', '[ADMIN] Customization settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable this option if you want to scroll to top after filtering.', '[ADMIN] Customization settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_scroll_top',
				'default'   => 'no',
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
			),

			'modal_on_mobile' => array(
				'name'      => _x( 'Show as modal on mobile', '[ADMIN] Customization settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable this option if you want to show filter section as a modal on mobile devices.<small>The modal opener will appear before products. When using WooCommerce\'s Gutenberg product blocks, this may not work as expected. If this is the case, you can place the Modal opener button anywhere in the page using <code>[yith_wcan_mobile_modal_opener]</code> shortcode or <code>YITH Mobile Filters Modal Opener</code> block</small>', '[ADMIN] Customization settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yith_wcan_modal_on_mobile',
				'default'   => 'no',
				'type'      => 'yith-field',
				'yith-type' => 'onoff',
			),

			'general_section_end' => array(
				'type' => 'sectionend',
				'id' => 'yith_wcan_general_settings',
			),

		),
	)
);

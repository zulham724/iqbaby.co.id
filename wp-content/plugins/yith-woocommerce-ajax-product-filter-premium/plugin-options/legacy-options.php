<?php
/**
 * Legacy options
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Product Filter
 * @version 4.0.0
 */

return apply_filters(
	'yith_wcan_panel_legacy_options',
	array(

		'legacy' => array(
			'legacy_frontend_start' => array(
				'name' => _x( 'Frontend options', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'yith_wcan_legacy_frontend_settings',
			),

			'product_container' => array(
				'name'      => _x( 'Product container', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enter here the CSS selector (class or ID) of the product container', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_ajax_shop_container]',
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'default'   => '.products',
			),

			'pagination_container' => array(
				'name'      => _x( 'Shop pagination container', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enter here the CSS selector (class or ID) of the shop pagination container', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_ajax_shop_pagination]',
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'default'   => 'nav.woocommerce-pagination',
			),

			'count_container' => array(
				'name'      => _x( 'Result count container', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enter here the CSS selector (class or ID) of the results count container', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_ajax_shop_result_container]',
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'default'   => 'nav.woocommerce-pagination',
			),

			'scroll_top_selector' => array(
				'name'      => _x( '"Scroll to top" anchor selector', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enter here the CSS selector (class or ID) of the "Scroll to to top" anchor', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_ajax_scroll_top_class]',
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'default'   => 'nav.woocommerce-pagination',
			),

			'order_by' => array(
				'name'      => _x( 'Terms sorting', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Choose how to sort terms inside filters', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_ajax_shop_terms_order]',
				'type'      => 'yith-field',
				'default'   => 'menu_order',
				'yith-type' => 'radio',
				'options'   => array(
					'product'      => _x( 'Product count', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
					'alphabetical' => _x( 'Alphabetical', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
					'menu_order'   => _x( 'Default', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				),
			),

			'scroll_to_top' => array(
				'name'      => _x( 'Scroll to top after filtering', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Choose whether you want to enable the "Scroll to top" option on Desktop, Mobile, or on both of them', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_scroll_top_mode]',
				'type'      => 'yith-field',
				'default'   => 'menu_order',
				'yith-type' => 'radio',
				'options'   => array(
					'disabled' => _x( 'Disabled', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
					'mobile'   => _x( 'Mobile', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
					'desktop'  => _x( 'Desktop', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
					'both'     => _x( 'Mobile and Desktop', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				),
			),

			'widget_title_selector' => array(
				'name'      => _x( 'Widget title selector', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enter here the CSS selector (class or ID) of the widget title', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_ajax_widget_title_class]',
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'default'   => 'h3.widget-title',
			),

			'widget_container' => array(
				'name'      => _x( 'Widget container', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enter here the CSS selector (class or ID) of the widget container', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_ajax_widget_wrapper_class]',
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'default'   => '.widget',
			),

			'filter_style' => array(
				'name'      => _x( 'Filter style', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Choose the style of the filter inside widgets', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_ajax_shop_filter_style]',
				'type'      => 'yith-field',
				'default'   => 'standard',
				'yith-type' => 'radio',
				'options'   => array(
					'standard'   => _x( '"x" icon before active filters', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
					'checkboxes' => _x( 'Checkboxes', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				),
			),

			'legacy_frontend_end' => array(
				'type' => 'sectionend',
				'id' => 'yith_wcan_legacy_frontend_settings',
			),

			'legacy_general_start' => array(
				'name' => _x( 'General options', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'yith_wcan_legacy_general_settings',
			),

			'ajax_loader' => array(
				'name'      => _x( 'Ajax Loader', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Choose loading icon you want to use for your widget filters', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_ajax_loader]',
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'default'   => YITH_WCAN_URL . 'assets/images/ajax-loader.gif',
			),

			'ajax_price_filter' => array(
				'name'      => _x( 'Filter by price using AJAX', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Filter products via AJAX when using WooCommerce price filter widget', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_enable_ajax_price_filter]',
				'type'      => 'yith-field',
				'default'   => 'yes',
				'yith-type' => 'onoff',
			),

			'price_slider' => array(
				'name'      => _x( 'Use slider for price filtering', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Transform default WooCommerce price filter into a slider', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_enable_ajax_price_filter]',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'ajax_price_slider' => array(
				'name'      => _x( 'Filter by price using AJAX slider', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Filter products via AJAX when using WooCommerce price filter widget', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_enable_ajax_price_filter_slider]',
				'type'      => 'yith-field',
				'default'   => 'yes',
				'yith-type' => 'onoff',
			),

			'price_dropdown' => array(
				'name'      => _x( 'Add toggle for price filter widget', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Show price filtering widget as a toggle', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_enable_dropdown_price_filter]',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'price_dropdown_style' => array(
				'name'      => _x( 'Chose how to show price filter toggle', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Choose whether to show price filtering widget as an open or closed toggle', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_dropdown_style]',
				'type'      => 'yith-field',
				'default'   => 'open',
				'yith-type' => 'radio',
				'options'   => array(
					'open' => _x( 'Opened', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
					'close' => _x( 'Closed', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				),
			),

			'ajax_shop_pagination' => array(
				'name'      => _x( 'Enable ajax pagination', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Make shop pagination anchors load new page via ajax', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_enable_ajax_shop_pagination]',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'shop_pagination_selector' => array(
				'name'      => _x( 'Shop pagination selector', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enter here the CSS selector (class or ID) of the shop pagination', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_ajax_shop_pagination_anchor_class]',
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'default'   => 'a.page-numbers',
			),

			'show_current_categories' => array(
				'name'      => _x( 'Show current categories', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable if you want to show link to current category in the filter, when visiting category page', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_show_current_categories_link]',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'show_all_categories' => array(
				'name'      => _x( 'Show "All categories" anchor', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable if you want to show a link to retrieve products from all categories, after a category filter is applied', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_enable_see_all_categories_link]',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'all_categories_label' => array(
				'name'      => _x( '"All categories" anchor label', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enter here the text you want to use for "All categories" anchor', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_enable_see_all_categories_link_text]',
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'default'   => _x( 'See all categories', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
			),

			'show_all_tags' => array(
				'name'      => _x( 'Show "All tags" anchor', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enable if you want to show a link to retrieve products from all tags, after a category filter is applied', '[ADMIN] General settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_enable_see_all_tags_link]',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'all_tags_label' => array(
				'name'      => _x( '"All tags" anchor label', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Enter here the text you want to use for "All tags" anchor', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_enable_see_all_tags_link_text]',
				'type'      => 'yith-field',
				'yith-type' => 'text',
				'default'   => _x( 'See all tags', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
			),


			'hierarchical_tags' => array(
				'name'      => _x( 'Hierarchical tags', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'desc'      => _x( 'Make product tag taxonomy hierarchical', '[ADMIN] Legacy settings page', 'yith-woocommerce-ajax-navigation' ),
				'id'        => 'yit_wcan_options[yith_wcan_enable_hierarchical_tags_link]',
				'type'      => 'yith-field',
				'default'   => 'no',
				'yith-type' => 'onoff',
			),

			'legacy_general_end' => array(
				'type' => 'sectionend',
				'id' => 'yith_wcan_legacy_general_settings',
			),

		),
	)
);

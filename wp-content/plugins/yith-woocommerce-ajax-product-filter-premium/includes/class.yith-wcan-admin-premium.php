<?php
/**
 * Admin class
 *
 * @author  YITH
 * @package YITH WooCommerce Ajax Navigation
 * @version 4.0.0
 */

if ( ! defined( 'YITH_WCAN' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAN_Admin_Premium' ) ) {
	/**
	 * Admin class.
	 * This class manage all the admin features.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAN_Admin_Premium extends YITH_WCAN_Admin {

		/**
		 * Construct
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Andrea Grillo <andrea.grillo@yithemes.com>
		 */
		public function __construct() {
			parent::__construct();

			// updates available tabs.
			add_filter( 'yith_wcan_settings_tabs', array( $this, 'settings_tabs' ) );

			// Add premium options.
			add_filter( 'yith_wcan_panel_filter_options', array( $this, 'add_filter_options' ) );
		}

		/* === PANEL METHODS === */

		/**
		 * Add premium filter options
		 *
		 * @param array $settings List of filter options.
		 * @return array Filtered list of filter options.
		 */
		public function add_filter_options( $settings ) {
			// add premium settings.
			$additional_options_batch_1 = array(
				'column_number' => array(
					'label'   => _x( 'Columns number', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'number',
					'min'     => 1,
					'step'    => 1,
					'max'     => 8,
					'desc'  => _x( 'Set the number of items per row you want to show for this design', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),
			);
			$settings      = yith_wcan_merge_in_array( $settings, $additional_options_batch_1, 'filter_design' );

			$additional_options_batch_2 = array(
				'show_search' => array(
					'label'   => _x( 'Show search field', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'onoff',
					'desc'  => _x( 'Enable if you want to show search field inside dropdown', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),

				'price_slider_min' => array(
					'label'   => _x( 'Slider min value', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'number',
					'min'     => 0,
					'step'    => 0.01,
					'desc'  => _x( 'Set the minimum value for the price slider', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),

				'price_slider_max' => array(
					'label'   => _x( 'Slider max value', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'number',
					'min'     => 0,
					'step'    => 0.01,
					'desc'  => _x( 'Set the maximum value for the price slider', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),

				'price_slider_step' => array(
					'label'   => _x( 'Slider step', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'number',
					'min'     => 0.01,
					'step'    => 0.01,
					'desc'  => _x( 'Set the value for each increment of the price slider', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),

				'order_options'  => array(
					'label'   => _x( 'Order options', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'select-buttons',
					'multiple' => true,
					'class'   => 'wc-enhanced-select',
					'options' => YITH_WCAN_Filter_Factory::get_supported_orders(),
					'desc'  => _x( 'Select sorting options to show', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),

				'price_ranges' => array(
					'label'   => _x( 'Customize price ranges', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'custom',
					'action'  => 'yith_wcan_price_ranges',
				),

				'show_stock_filter' => array(
					'label'   => _x( 'Show stock filter', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'onoff',
					'desc'  => _x( 'Enable if you want to show "In Stock" filter', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),

				'show_sale_filter' => array(
					'label'   => _x( 'Show sale filter', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'onoff',
					'desc'  => _x( 'Enable if you want to show "On Sale" filter', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),

				'show_toggle' => array(
					'label'   => _x( 'Show as toggle', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'onoff',
					'desc'  => _x( 'Enable if you want to show this filter as a toggle', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),

				'toggle_style' => array(
					'label'   => _x( 'Toggle style', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'radio',
					'options' => array(
						'closed' => _x( 'Closed by default', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
						'opened' => _x( 'Opened by default', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					),
					'desc'  => _x( 'Choose if toggle has to closed or opened by default', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),

				'order_by' => array(
					'label'   => _x( 'Order by', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'select',
					'options' => array(
						'name' => _x( 'Name', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
						'slug' => _x( 'Slug', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
						'count' => _x( 'Term count', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
						'term_order' => _x( 'Term order', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					),
					'desc'  => _x( 'Select the default order for terms of this filter', 'yith-woocommerce-ajax-navigation' ),
				),

				'order' => array(
					'label'   => _x( 'Order type', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'select',
					'options' => array(
						'asc' => _x( 'ASC', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
						'desc' => _x( 'DESC', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					),
					'desc'  => _x( 'Select the default order for terms of this filter', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),

				'show_count' => array(
					'label'   => _x( 'Show count of items', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'onoff',
					'desc'  => _x( 'Enable if you want to show how many items are available for each term', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),
			);
			$settings = yith_wcan_merge_in_array( $settings, $additional_options_batch_2, 'terms_options' );

			$additional_options_batch_3 = array(
				'adoptive' => array(
					'label'   => _x( 'Adoptive filtering', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'type'    => 'radio',
					'options' => array(
						'hide' => _x( 'Terms will be hidden', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
						'or'  => _x( 'Terms will be visible, but not clickable', 'yith-woocommerce-ajax-navigation' ),
					),
					'desc' => _x( 'Decide how to manage filter options that show no results when applying filters. Choose to hide them or make them visible (this will show them in lighter grey and not clickable)', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),
			);
			$settings = yith_wcan_merge_in_array( $settings, $additional_options_batch_3, 'relation' );

			// add premium options to existing settings.
			$settings['hierarchical']['options'] = yith_wcan_merge_in_array(
				$settings['hierarchical']['options'],
				array(
					'collapsed' => _x( 'Yes, with terms collapsed', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
					'expanded' => _x( 'Yes, with terms expanded', '[Admin] Filter edit form', 'yith-woocommerce-ajax-navigation' ),
				),
				'parents_only'
			);

			return $settings;
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @param array $tabs Array of available tabs.
		 *
		 * @return   array Filtered array of tabs
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use      /Yit_Plugin_Panel class
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function settings_tabs( $tabs ) {
			unset( $tabs['premium'] );

			$tabs['seo'] = __( 'SEO', 'yith-woocommerce-ajax-navigation' );

			return $tabs;
		}

		/**
		 * Prints single item of "Term edit" template
		 *
		 * @param int    $id Current row id.
		 * @param int    $term_id Current term id.
		 * @param string $term_name Current term name.
		 * @param string $term_options Options for current term (it may include label, tooltip, colors, and image).
		 *
		 * @return void
		 * @author Antonio La Rocca <antonio.larocca@yithemes.com>
		 */
		public function filter_term_field( $id, $term_id, $term_name, $term_options = array() ) {
			// just include template, and provide passed terms.
			include( YITH_WCAN_DIR . 'templates/admin/preset-filter-term-advanced.php' );
		}

		/* === PLUGIN META === */

		/**
		 * Adds action links to plugin row in plugins.php admin page
		 *
		 * @param array  $new_row_meta_args Array of data to filter.
		 * @param array  $plugin_meta       Array of plugin meta.
		 * @param string $plugin_file       Path to init file.
		 * @param array  $plugin_data       Array of plugin data.
		 * @param string $status            Not used.
		 * @param string $init_file         Constant containing plugin int path.
		 *
		 * @return   array
		 * @since    1.0
		 * @author   Andrea Grillo <andrea.grillo@yithemes.com>
		 * @use      plugin_row_meta
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_WCAN_INIT' ) {
			$new_row_meta_args = parent::plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file );

			if ( defined( $init_file ) && constant( $init_file ) == $plugin_file ) {
				$new_row_meta_args['is_premium'] = true;
			}

			return $new_row_meta_args;
		}

	}
}

<?php

add_action( 'woocommerce_settings_start', 'tm_wc_ajax_register_settings' );
add_action( 'woocommerce_settings_tm_wc_ajax', 'tm_wc_ajax_render_settings_page' );
add_action( 'woocommerce_update_options_tm_wc_ajax', 'tm_wc_ajax_update_options' );

// register filter hooks
add_filter( 'woocommerce_settings_tabs_array', 'tm_wc_ajax_register_settings_tab', PHP_INT_MAX );

function tm_wc_ajax_get_settings() {

	return array(
		array(
			'id'    => 'general-options',
			'type'  => 'title',
			'title' => __( 'General Options', 'tm-woocommerce-ajax-filters' ),
		),
		array(
			'type'    => 'checkbox',
			'id'      => 'tm_wc_ajax_filters_grid_list_enable',
			'title'   => __( 'Enable grid-list', 'tm-woocommerce-ajax-filters' ),
			'desc'    => __( 'Enable grid-list layout toggle button', 'tm-woocommerce-ajax-filters' ),
			'default' => 'yes'
		),
		array(
			'type'    => 'checkbox',
			'id'      => 'tm_wc_ajax_filters_ordering_enable',
			'title'   => __( 'Enable AJAX ordering', 'tm-woocommerce-ajax-filters' ),
			'desc'    => __( 'Enable AJAX functionality on ordering', 'tm-woocommerce-ajax-filters' ),
			'default' => 'yes'
		),
		array(
			'type'    => 'checkbox',
			'id'      => 'tm_wc_ajax_filters_pagination_enable',
			'title'   => __( 'Enable AJAX pagination', 'tm-woocommerce-ajax-filters' ),
			'desc'    => __( 'Enable AJAX functionality on pagination', 'tm-woocommerce-ajax-filters' ),
			'default' => 'yes'
		),
		array(
			'type'    => 'checkbox',
			'id'      => 'tm_wc_ajax_filters_loadmore_enable',
			'title'   => __( 'Enable AJAX Load More button', 'tm-woocommerce-ajax-filters' ),
			'desc'    => __( 'Enable AJAX Load More button', 'tm-woocommerce-ajax-filters' ),
			'default' => 'yes'
		),
		array(
			'type'    => 'text',
			'id'      => 'tm_wc_ajax_filters_loadmore_label',
			'title'   => __( 'Load More button label', 'tm-woocommerce-ajax-filters' ),
			'default' => __( 'Load more', 'tm-woocommerce-ajax-filters' ),
		),
		array(
			'type'    => 'number',
			'id'      => 'tm_wc_ajax_filters_loadmore_treshold',
			'title'   => __( 'Load more threshold on desktop', 'tm-woocommerce-ajax-filters' ),
			'default' => 20
		),
		array(
			'type'    => 'number',
			'id'      => 'tm_wc_ajax_filters_loadmore_treshold_mobile',
			'title'   => __( 'Load more threshold on mobile', 'tm-woocommerce-ajax-filters' ),
			'default' => 20
		),
		array( 'type' => 'sectionend', 'id' => 'general-options' )
	);
}


/**
 * Registers plugin settings in the WooCommerce settings array.
 *
 * @since 1.0.0
 * @action woocommerce_settings_start
 *
 * @global array $woocommerce_settings WooCommerce settings array.
 */
function tm_wc_ajax_register_settings() {

	global $woocommerce_settings;

	$woocommerce_settings['tm_wc_ajax'] = tm_wc_ajax_get_settings();
}

/**
 * Registers WooCommerce settings tab which will display the plugin settings.
 *
 * @since 1.0.0
 * @filter woocommerce_settings_tabs_array PHP_INT_MAX
 *
 * @param array $tabs The array of already registered tabs.
 * @return array The extended array with the plugin tab.
 */
function tm_wc_ajax_register_settings_tab( $tabs ) {

	$tabs['tm_wc_ajax'] = esc_html__( 'TM Ajax', 'tm-woocommerce-ajax-filters' );

	return $tabs;
}

/**
 * Renders plugin settings tab.
 *
 * @since 1.0.0
 * @action woocommerce_settings_tm_woocompare_list
 *
 * @global array $woocommerce_settings The aggregate array of WooCommerce settings.
 * @global string $current_tab The current WooCommerce settings tab.
 */
function tm_wc_ajax_render_settings_page() {

	global $woocommerce_settings, $current_tab;

	if ( function_exists( 'woocommerce_admin_fields' ) ) {

		woocommerce_admin_fields( $woocommerce_settings[$current_tab] );
	}
}

/**
 * Updates plugin settings after submission.
 *
 * @since 1.0.0
 * @action woocommerce_update_options_tm_woocompare_list
 */
function tm_wc_ajax_update_options() {

	if ( function_exists( 'woocommerce_update_options' ) ) {

		woocommerce_update_options( tm_wc_ajax_get_settings() );
	}
}
<?php
/**
 * Uninstall plugin
 *
 * @author TemplateMonster
 * @package TM Woocommerce Package
 * @version 1.0.0
 */

// If uninstall not called from WordPress exit
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {

	exit;
}

function tm_wc_ajax_filters_uninstall(){

	global $wpdb;

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'tm_wc_ajax_filters%' ) );
}



if ( ! is_multisite() ) {

	tm_wc_ajax_filters_uninstall();
}
else {

	global $wpdb;

	$blog_ids         = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
	$original_blog_id = get_current_blog_id();

	foreach ( $blog_ids as $blog_id ) {

		switch_to_blog( $blog_id );

		tm_wc_ajax_filters_uninstall();
	}
	switch_to_blog( $original_blog_id );
}
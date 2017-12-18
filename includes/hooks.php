<?php

add_action( 'woocommerce_after_subcategory', 'tm_wc_ajax_filters_after_subcategory', 11, 2 );

remove_action( 'woocommerce_shop_loop_subcategory_title', 'woocommerce_template_loop_category_title', 10 );

add_action( 'woocommerce_shop_loop_subcategory_title', 'tm_wc_ajax_filters_shop_loop_subcategory_title', 10, 2 );

?>
<?php

if ( class_exists( 'WC_Widget_Layered_Nav' ) ) {

	class TM_Woo_Ajax_Filters_Widget extends WC_Widget_Layered_Nav {

		public $price;

		/**
		 * Sets up a new TM WooCommerce Ajax Filters widget instance.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			parent::__construct();

			$this->widget_cssclass    = 'woocommerce widget_price_filter widget_layered_nav widget_tm_woo_ajax_filters';
			$this->widget_description = __( 'Shows an ajax filters in a widget which lets you narrow down the list of shown products when viewing product categories.', 'tm-woocommerce-ajax-filters' );
			$this->widget_id          = 'tm_woo_ajax_filters';
			$this->widget_name        = __( 'TM WooCommerce Ajax Filters', 'tm-woocommerce-ajax-filters' );

			WC_Widget::__construct();

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

			add_action( 'wp_enqueue_scripts' , array( $this, 'enqueue_assets' ) );

			add_action( 'woocommerce_before_shop_loop', array( $this, 'woocommerce_before_shop_loop' ), -998 );

			add_action( 'tm_wc_ajax_before_shop_loop_no_products', array( $this, 'woocommerce_before_shop_loop' ) );
		}

		public function detach_select_handler() {

				wc_enqueue_js( "
					jQuery( '.widget_tm_woo_ajax_filters select[class*=\"dropdown_layered_nav\"]' ).off( 'change' );
					$.tmWcAjaxFiltersSelectsInit();
				" );
		}

		public function enqueue_admin_assets() {

			wp_enqueue_script( 'tm-wc-ajax-products-widget-admin' );
		}

		/**
		 * Init settings after post types are registered.
		 */
		public function init_settings() {
			parent::init_settings();

			$this->settings['title']['std'] = __( 'Filter', 'tm-woocommerce-ajax-filters' );

			$display_type_options = array(
				'slider' => __( 'Slider', 'tm-woocommerce-ajax-filters' ),
				'inputs' => __( 'Inputs', 'tm-woocommerce-ajax-filters' )
			);

			$this->settings['display_type']['options'] = array_merge( $this->settings['display_type']['options'], $display_type_options );

			$this->settings['attribute']['options'] = array_merge( array( 'price' => 'price' ), $this->settings['attribute']['options'] );

		}

		public function woocommerce_before_shop_loop() {

			$dismiss_icon       = apply_filters( 'tw_wc_filters_dismiss_icon', '<span class="dashicons dashicons-dismiss"></span>' );
			$_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes();
			$min                = isset( $_GET['min_price'] );
			$max                = isset( $_GET['max_price'] );
			$chosen_atts        = ! empty( $_chosen_attributes );

			if( $min || $max || $chosen_atts ) {

				$page_url = $this->get_page_base_url();

				echo apply_filters( 'tw_wc_price_filters__wrapper_open', '<div class="tm-wc-ajax-filters-wrapper">' );

				if( $min || $max ) {

					$url = remove_query_arg( array( 'min_price', 'max_price' ), $page_url );


					echo apply_filters( 'tw_wc_price_filters_open', '<span class="tm-wc-ajax-filters-price">' );

					if( $min ) {

						$min_price = wc_price( esc_attr( $_GET['min_price'] ) );
						$from_text = __( 'from', 'tm-woocommerce-ajax-filters' );
						$html      = sprintf( '%s <span class="tm-wc-ajax-filters-price-from">%s</span>', $from_text, $min_price );

						echo apply_filters( 'tw_wc_price_filters_from', $html, $from_text, $min_price );
					}
					if( $max ) {

						$max_price = wc_price( esc_attr( $_GET['max_price'] ) );
						$to_text   = __( 'to', 'tm-woocommerce-ajax-filters' );
						$html      = sprintf( '%s <span class="tm-wc-ajax-filters-price-to">%s</span>', $to_text, $max_price );

						echo ' ' . apply_filters( 'tw_wc_price_filters_to', $html, $to_text, $max_price );
					}
					echo ' <a class="tm-wc-ajax-filters-dismiss" href="' . $url . '">';
					echo $dismiss_icon;
					echo '</a>';
					echo apply_filters( 'tw_wc_price_filters_close', '</span>' );
				}
				if( $chosen_atts ) {

					foreach ( $_chosen_attributes as $taxonomy => $attributes ) {

						echo apply_filters( 'tw_wc_attribute_filters_open', '<span class="tm-wc-ajax-filters-attribute">' );

						$get_terms_args = array( 'hide_empty' => '1' );

						$orderby = wc_attribute_orderby( $taxonomy );

						switch ( $orderby ) {

							case 'name' :

								$get_terms_args['orderby']    = 'name';
								$get_terms_args['menu_order'] = false;

							break;

							case 'id' :

								$get_terms_args['orderby']    = 'id';
								$get_terms_args['order']      = 'ASC';
								$get_terms_args['menu_order'] = false;

							break;

							case 'menu_order' :

								$get_terms_args['menu_order'] = 'ASC';

							break;
						}

						$terms = get_terms( $taxonomy, $get_terms_args );

						echo '<span class="tm-wc-ajax-filters-attribute-label">' . wc_attribute_label( $taxonomy ) . ':</span>';

						foreach ( $terms as $term ) {

							$current_values = isset( $_chosen_attributes[ $taxonomy ]['terms'] ) ? $_chosen_attributes[ $taxonomy ]['terms'] : array();
							$option_is_set  = in_array( $term->slug, $current_values );

							if( $option_is_set ) {

								$filter_name    = 'filter_' . sanitize_title( str_replace( 'pa_', '', $taxonomy ) );
								$query_name     = 'query_type_' . sanitize_title( str_replace( 'pa_', '', $taxonomy ) );
								$current_filter = isset( $_GET[ $filter_name ] ) ? explode( ',', wc_clean( $_GET[ $filter_name ] ) ) : array();
								$query_type     = isset( $_GET[ $query_name ] ) ? wc_clean( $_GET[ $query_name ] ) : '';
								$current_filter = array_map( 'sanitize_title', $current_filter );

								if ( ! in_array( $term->slug, $current_filter ) ) {

									$current_filter[] = $term->slug;
								}

								// Add current filters to URL.
								foreach ( $current_filter as $key => $value ) {

									// Exclude query arg for current term archive term
									if ( $value === $this->get_current_term_slug() ) {

										unset( $current_filter[ $key ] );
									}

									// Exclude self so filter can be unset on click.
									if ( $value === $term->slug ) {

										unset( $current_filter[ $key ] );
									}
								}
								$link = remove_query_arg( $filter_name, $this->get_page_base_url() );

								if ( ! empty( $current_filter ) ) {

									$link = add_query_arg( $filter_name, implode( ',', $current_filter ), $link );

									// Add Query type Arg to URL
									if ( $query_type === 'or' && ! ( 1 === sizeof( $current_filter ) ) ) {

										$link = add_query_arg( 'query_type_' . sanitize_title( str_replace( 'pa_', '', $taxonomy ) ), 'or', $link );
									}
								}
								echo ' <span class="tm-wc-ajax-filters-attribute-value">' . $term->name . '</span>';
								echo ' <a class="tm-wc-ajax-filters-dismiss" href="' . $link . '">';
								echo $dismiss_icon;
								echo '</a>';
							}
						}

						echo apply_filters( 'tw_wc_attribute_filters_close', '</span>' );
					}
				}
				$reset_link = remove_query_arg( array( 'min_price', 'max_price', 'orderby', 'min_rating' ), $page_url );

				if ( $_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes() ) {

					foreach ( $_chosen_attributes as $name => $data ) {

						$filter_name = sanitize_title( str_replace( 'pa_', '', $name ) );

						if ( ! empty( $data['terms'] ) ) {

							$reset_link = remove_query_arg( 'filter_' . $filter_name, $reset_link );
						}
						if ( 'or' == $data['query_type'] ) {

							$reset_link = remove_query_arg( 'query_type_' . $filter_name, $reset_link );
						}
					}
				}

				$reset_text = __( 'Clear all', 'tm-woocommerce-ajax-filters' );
				$html       = sprintf( ' <a class="tm-wc-ajax-filters-reset button" href="%s">%s</a>', $reset_link, $reset_text );

				echo apply_filters( 'tw_wc_price_filters__reset_link', $html, $reset_link, $reset_text );
				echo apply_filters( 'tw_wc_price_filters__wrapper_close', '</div>' );
			}
		}

		public function enqueue_assets() {

			wp_enqueue_style( 'tm-wc-ajax-filters-widget' );

			if ( is_active_widget( false, false, $this->id_base, true ) ) {

				wp_enqueue_script( 'tm-wc-ajax-product-filters' );
			}
		}

		public function form( $instance ) {

			$this->init_settings();

			WC_Widget::form( $instance );
		}

		public function update( $new_instance, $old_instance ) {

			$this->init_settings();

			return WC_Widget::update( $new_instance, $old_instance );
		}

		/**
		 * Outputs the content for the current TM About Store widget instance.
		 *
		 * @since 2.8.0
		 * @access public
		 *
		 * @param array $args     Display arguments including 'before_title', 'after_title',
		 *                        'before_widget', and 'after_widget'.
		 * @param array $instance Settings for the current TM About Store widget instance.
		 */
		public function widget( $args, $instance ) {

			$taxonomy     = isset( $instance['attribute'] )    ? $instance['attribute']    : '';
			$display_type = isset( $instance['display_type'] ) ? $instance['display_type'] : $this->settings['display_type']['std'];

			if( 'price' === $taxonomy ) {

				$this->price = new TM_Woo_Extended_Price_Widget;

				$this->price->widget( $args, $instance );

				return;
			}
			parent::widget( $args, $instance );

			if( 'dropdown' === $display_type ){

				add_action( 'wp_footer', array( $this, 'detach_select_handler' ) );
			}
		}

		/**
		 * Show dropdown layered nav.
		 * @param  array $terms
		 * @param  string $taxonomy
		 * @param  string $query_type
		 * @return bool Will nav display?
		 */
		protected function layered_nav_dropdown( $terms, $taxonomy, $query_type ) {
			$found = false;

			if ( $taxonomy !== $this->get_current_taxonomy() || ( defined('DOING_AJAX') && DOING_AJAX ) ) {

				$term_counts          = $this->get_filtered_term_product_counts( wp_list_pluck( $terms, 'term_id' ), $taxonomy, $query_type );
				$_chosen_attributes   = WC_Query::get_layered_nav_chosen_attributes();
				$taxonomy_filter_name = str_replace( 'pa_', '', $taxonomy );

				$url = $this->get_page_base_url( $taxonomy );

				echo '<select class="dropdown_layered_nav_' . esc_attr( $taxonomy_filter_name ) . '">';
				echo '<option value="' . esc_url( $url ) . '">' . sprintf( __( 'Any %s', 'tm-wc-ajax-filters' ), wc_attribute_label( $taxonomy ) ) . '</option>';

				foreach ( $terms as $term ) {

					// If on a term page, skip that term in widget list
					if ( $term->term_id === $this->get_current_term_id() ) {

						continue;
					}
					// Get count based on current view
					$current_values    = isset( $_chosen_attributes[ $taxonomy ]['terms'] ) ? $_chosen_attributes[ $taxonomy ]['terms'] : array();
					$option_is_set     = in_array( $term->slug, $current_values );
					$count             = isset( $term_counts[ $term->term_id ] ) ? $term_counts[ $term->term_id ] : 0;

					// Only show options with count > 0
					if ( 0 < $count ) {

						$found = true;

					} elseif ( 'and' === $query_type && 0 === $count && ! $option_is_set ) {

						continue;
					}
					echo '<option value="' . esc_url( add_query_arg( array( "filter_". esc_js( $taxonomy_filter_name ) => esc_attr( $term->slug ) ), $url ) ) . '" ' . selected( $option_is_set, true, false ) . '>' . esc_html( $term->name ) . '</option>';
				}
				echo '</select>';
			}
			return $found;
		}


		public function get_page_base_url( $taxonomy = '' ) {
			if ( tm_ajax_wc_version_check( '3.3' ) ){
				return parent::get_current_page_url( $taxonomy );
			}
			return parent::get_page_base_url( $taxonomy );
		}


	}
}

class TM_Woo_Extended_Price_Widget extends WC_Widget_Price_Filter {

	public function __construct() {

		wp_register_script( 'tm-wc-price-slider', tm_wc_ajax_filters()->plugin_url() . '/assets/js/price-slider.js', array( 'wc-price-slider' ), TM_WC_AJAX_FILTERS_VERISON, true );

		wp_localize_script( 'tm-wc-price-slider', 'tm_wc_price_slider_params', array(
			'currency_pos'       => get_option( 'woocommerce_currency_pos' ),
			'price_format'       => sprintf( get_woocommerce_price_format(), get_woocommerce_currency_symbol(), '{0}' ),
			'decimal_separator'  => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'decimals'           => wc_get_price_decimals(),
		) );

		wp_register_script( 'tm-wc-price-input', tm_wc_ajax_filters()->plugin_url() . '/assets/js/price-inputs.js', array( 'wc-price-slider' ), TM_WC_AJAX_FILTERS_VERISON, true );
	}

	public function widget( $args, $instance ) {

		$display_type = isset( $instance['display_type'] ) ? $instance['display_type'] : $this->settings['display_type']['std'];

		global $wp, $wp_the_query;

		if ( ! is_post_type_archive( 'product' ) && ! is_tax( get_object_taxonomies( 'product' ) ) ) {

			return;
		}
		if( 'slider' === $display_type ) {

			wp_enqueue_script( 'tm-wc-price-slider' );

		} else {

			wp_enqueue_script( 'tm-wc-price-input' );
		}
		// Remember current filters/search
		$fields = '';

		if ( get_search_query() ) {

			$fields .= '<input type="hidden" name="s" value="' . get_search_query() . '" />';
		}
		if ( ! empty( $_GET['post_type'] ) ) {

			$fields .= '<input type="hidden" name="post_type" value="' . esc_attr( $_GET['post_type'] ) . '" />';
		}
		if ( ! empty ( $_GET['product_cat'] ) ) {

			$fields .= '<input type="hidden" name="product_cat" value="' . esc_attr( $_GET['product_cat'] ) . '" />';
		}
		if ( ! empty( $_GET['product_tag'] ) ) {

			$fields .= '<input type="hidden" name="product_tag" value="' . esc_attr( $_GET['product_tag'] ) . '" />';
		}
		if ( ! empty( $_GET['orderby'] ) ) {

			$fields .= '<input type="hidden" name="orderby" value="' . esc_attr( $_GET['orderby'] ) . '" />';
		}
		if ( ! empty( $_GET['min_rating'] ) ) {

			$fields .= '<input type="hidden" name="min_rating" value="' . esc_attr( $_GET['min_rating'] ) . '" />';
		}
		if ( $_chosen_attributes = WC_Query::get_layered_nav_chosen_attributes() ) {

			foreach ( $_chosen_attributes as $attribute => $data ) {

				$taxonomy_filter = 'filter_' . str_replace( 'pa_', '', $attribute );

				$fields .= '<input type="hidden" name="' . esc_attr( $taxonomy_filter ) . '" value="' . esc_attr( implode( ',', $data['terms'] ) ) . '" />';

				if ( 'or' == $data['query_type'] ) {
					$fields .= '<input type="hidden" name="' . esc_attr( str_replace( 'pa_', 'query_type_', $attribute ) ) . '" value="or" />';
				}
			}
		}
		// Find min and max price in current result set
		$prices = $this->get_filtered_price();

		if ( ! $prices ) {

			return;
		}
		$min       = floor( $prices->min_price );
		$max       = ceil( $prices->max_price );
		$min_price = isset( $_GET['min_price'] ) ? esc_attr( $_GET['min_price'] ) : $min;
		$max_price = isset( $_GET['max_price'] ) ? esc_attr( $_GET['max_price'] ) : $max;

		if ( $min_price < $min ) {

			$min = $min_price;
		}
		if ( $max_price > $max ) {

			$max = $max_price;
		}
		$this->widget_start( $args, $instance );

		if ( '' === get_option( 'permalink_structure' ) ) {

			$form_action = remove_query_arg( array( 'page', 'paged' ), add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );

		} else {

			$form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( trailingslashit( $wp->request ) ) );
		}
		/*
		 * Adjust max if the store taxes are not displayed how they are stored.
		 * Min is left alone because the product may not be taxable.
		 * Kicks in when prices excluding tax are displayed including tax.
		*/
		if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {

			$tax_classes = array_merge( array( '' ), WC_Tax::get_tax_classes() );
			$class_max   = $max;

			foreach ( $tax_classes as $tax_class ) {

				if ( $tax_rates = WC_Tax::get_rates( $tax_class ) ) {

					$class_max = $max + WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $max, $tax_rates ) );
				}
			}
			$max = $class_max;
		}
		echo '<form method="get" action="' . esc_url( $form_action ) . '">' . $fields;

		if( 'slider' === $display_type ) {

			echo '<div class="price_slider_wrapper">
					<div class="tm_wc_price_slider"></div>
					<div class="price_slider_amount">
						<input type="text" id="min_price" name="min_price" value="' . esc_attr( $min_price ) . '" data-min="' . esc_attr( apply_filters( 'woocommerce_price_filter_widget_min_amount', $min ) ) . '" placeholder="' . esc_attr__( 'Min price', 'tm-woocommerce-ajax-filters' ) . '">
						<input type="text" id="max_price" name="max_price" value="' . esc_attr( $max_price ) . '" data-max="' . esc_attr( apply_filters( 'woocommerce_price_filter_widget_max_amount', $max ) ) . '" placeholder="' . esc_attr__( 'Max price', 'tm-woocommerce-ajax-filters' ) . '">
						<div class="price_label">
							' . __( 'Price:', 'tm-woocommerce-ajax-filters' ) . ' <span class="from">' . wc_price( esc_attr( $min_price ) ) . '</span> &mdash; <span class="to">' . wc_price( esc_attr( $max_price ) ) . '</span>
						</div>
						<div class="clear"></div>
					</div>
				</div>';

		} else {

			echo '<div class="tm_wc_price_filter_inputs_wrapper">
				<div class="tm_wc_price_filter_inputs">
					<input type="number" class="input-text" step="1" min="' . esc_attr( apply_filters( 'woocommerce_price_filter_widget_min_amount', $min ) ) . '" max="' . esc_attr( apply_filters( 'woocommerce_price_filter_widget_max_amount', $max ) ) . '" name="min_price" value="' . esc_attr( $min_price ) . '" data-min="' . esc_attr( apply_filters( 'woocommerce_price_filter_widget_min_amount', $min ) ) . '" placeholder="' . esc_attr__( 'Min price', 'tm-woocommerce-ajax-filters' ) . '">
					<input type="number" class="input-text" step="1" min="' . esc_attr( apply_filters( 'woocommerce_price_filter_widget_min_amount', $min ) ) . '" max="' . esc_attr( apply_filters( 'woocommerce_price_filter_widget_max_amount', $max ) ) . '" name="max_price" value="' . esc_attr( $max_price ) . '" data-max="' . esc_attr( apply_filters( 'woocommerce_price_filter_widget_max_amount', $max ) ) . '" placeholder="' . esc_attr__( 'Max price', 'tm-woocommerce-ajax-filters' ) . '">
				</div>
				<button type="button" class="button btn btn-default">' . __( 'Filter', 'tm-woocommerce-ajax-filters' ) . '</button>
			</div>';
		}
		echo '</form>';

		$this->widget_end( $args );
	}

}
<?php
/**
 * @package   TM WooCommerce Ajax Filters
 * @author    TemplateMonster
 * @license   GPL-2.0+
 * @link      http://www.templatemonster.com/
 */

/**
 * Class for including page templates.
 *
 * @since 1.0.0
 */
class TM_WooCommerce_Ajax {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	public function __construct() {

		add_filter( 'woocommerce_pagination_args', array( $this, 'pagination_args' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 11 );

		add_action( 'wp_ajax_nopriv_tm_wc_rebuild_products', array( $this, 'process_ajax' ) );
		add_action( 'wp_ajax_tm_wc_rebuild_products', array( $this, 'process_ajax' ) );

		add_action( 'wp_ajax_nopriv_tm_wc_load_more', array( $this, 'process_load_more_ajax' ) );
		add_action( 'wp_ajax_tm_wc_load_more', array( $this, 'process_load_more_ajax' ) );

		add_action( 'dynamic_sidebar_before', array( $this, 'dynamic_sidebar_before' ) );

		add_action( 'woocommerce_before_shop_loop', array( $this, 'products_wrapper_start' ), -999 );

		add_action( 'woocommerce_after_shop_loop', array( $this, 'products_wrapper_end' ), 999 );

		add_action( 'woocommerce_before_template_part', array( $this, 'woocommerce_before_template_part' ), 10, 4 );

		add_action( 'woocommerce_after_template_part', array( $this, 'woocommerce_after_template_part' ), 10, 4 );

		add_action( 'woocommerce_after_shop_loop', array( $this, 'load_more_button' ), 9 );

		add_filter( 'loop_end', array( $this, 'loop_end' ), 9 );
	}

	public static function instance() {

		if ( is_null( self::$_instance ) ) {

			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function woocommerce_before_template_part( $template_name, $template_path, $located, $args ) {
		if ( tm_ajax_wc_prop_is_shortcode() ) {
			return;
		}

		if ( 'loop/no-products-found.php' === $template_name ) {

			$this->products_wrapper_start();

			do_action( 'tm_wc_ajax_before_shop_loop_no_products' );
		}
	}

	public function woocommerce_after_template_part( $template_name, $template_path, $located, $args ) {
		if ( 'loop/no-products-found.php' === $template_name ) {

			$this->products_wrapper_end();

			do_action( 'tm_wc_ajax_after_shop_loop_no_products' );
		}
	}

	public function products_wrapper_start() {
		if ( tm_ajax_wc_prop_is_shortcode() ) {
			return;
		}

		echo '<div class="tm-wc-ajax-products-wrapper">' . "\n";
	}

	public function products_wrapper_end() {
		if ( tm_ajax_wc_prop_is_shortcode() ) {
			return;
		}

		$tm_wc_ajax_filters = tm_wc_ajax_filters();

		echo $tm_wc_ajax_filters->get_loader();

		echo '</div>' . "\n";
	}

	public function dynamic_sidebar_before( $id ) {

		global $wp_registered_sidebars, $sidebars_widgets, $wp_registered_widgets;

		$in_sidebar = false;
		$id         = apply_filters( 'cherry_sidebars_custom_id', $id );

		if ( isset( $sidebars_widgets[ $id ] ) && ! empty( $sidebars_widgets[ $id ] ) ) {

			foreach ( $sidebars_widgets[ $id ] as $key => $widget_id ) {

				if ( 0 === strpos( $widget_id, 'tm_woo_ajax_filters' ) ) {

					$in_sidebar = true;
				}
			}
		}

		if ( $in_sidebar && ! is_admin() ) {

			echo apply_filters( 'tm_wc_ajax_sidebar_before', '<div data-sidebar="' . $id . '">', $id );

			add_action( 'dynamic_sidebar_after', array( $this, 'dynamic_sidebar_after' ) );
		}
	}

	public function dynamic_sidebar_after( $id ) {
		echo apply_filters( 'tm_wc_ajax_sidebar_after', '</div>', $id );
		remove_action( 'dynamic_sidebar_after', array( $this, 'dynamic_sidebar_after' ) );
	}

	public function process_load_more_ajax() {

		ob_start();

		$page_url       = $_POST['pageUrl'];
		$products_count = $_POST['productsCount'];

		$_SERVER['REQUEST_URI'] = parse_url( $page_url, PHP_URL_PATH );
		$_SERVER['PHP_SELF']    = str_replace( 'wp-admin/admin-ajax', 'index', $_SERVER['PHP_SELF'] );

		global $wp;

		$wp->parse_request();

		$args                = $wp->query_vars;
		$args                = array_merge( $args, $this->maybe_get_args_from_url( $page_url ) );
		$args['post_status'] = 'publish';

		parse_str( parse_url( $page_url, PHP_URL_QUERY ), $_GET );

		if ( get_option( 'page_on_front' ) == wc_get_page_id( 'shop' ) ) {
			$args['post_type'] = 'product';
		}

		$wcquery = new TM_WC_Query();
		$posts   = new WP_Query( $args );

		$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'] = $posts;

		$this->process_ajax_get_products();

		$content = ob_get_clean();

		ob_start();

		$this->load_more_button();

		$button = ob_get_clean();

		$json = array(
			'products' => $content,
			'button'   => $button
		);

		wp_reset_query();

		$json = apply_filters( 'tm_wc_ajax_json', $json );

		wp_send_json_success( $json );

	}

	public function maybe_get_args_from_url( $url ) {

		$query = parse_url( $url, PHP_URL_QUERY );

		if ( ! $query ) {
			return array();
		}

		parse_str( $query, $args );

		return $args;

	}

	public function process_ajax() {
		$page_url      = $_POST['pageUrl'];
		$wcbreadcrumbs = isset( $_POST['wcbreadcrumbs'] ) ? ( bool ) json_decode( $_POST['wcbreadcrumbs'] ) : false;
		$task          = isset( $_POST['task'] ) ? $_POST['task'] : '';

		$request_path  = parse_url( $page_url, PHP_URL_PATH );
		$request_query = parse_url( $page_url, PHP_URL_QUERY );

		if ( ! empty( $request_query ) ) {
			$request_path .= '?' . $request_query;
		}

		$_SERVER['REQUEST_URI'] = $request_path;
		$_SERVER['PHP_SELF']    = str_replace( 'wp-admin/admin-ajax', 'index', $_SERVER['PHP_SELF'] );

		global $wp;

		$wp->parse_request();

		$args = $wp->query_vars;

		parse_str( parse_url( $page_url, PHP_URL_QUERY ), $_GET );

		$args                = array_merge( $args, $this->maybe_get_args_from_url( $page_url ) );
		$args['post_status'] = 'publish';

		if (  get_option( 'page_on_front' ) == wc_get_page_id( 'shop' ) ) {
			$args['post_type'] = 'product';
		}

		$wcquery = new TM_WC_Query();
		$posts   = new WP_Query( $args );

		$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'] = $posts;

		ob_start();

		do_action( 'woocommerce_before_shop_loop' );

		woocommerce_product_loop_start();

		$this->process_ajax_get_products();

		woocommerce_product_loop_end();
		do_action( 'woocommerce_after_shop_loop' );

		$content               = ob_get_clean();
		$filters_content       = array();
		$wcbreadcrumbs_content = false;

		if ( 'filter' === $task || 'ordering' === $task ) {

			$widget   = new TM_Woo_Ajax_Filters_Widget();
			$settings = $widget->get_settings();

			global $wp_registered_sidebars, $sidebars_widgets, $wp_registered_widgets;

			unset( $sidebars_widgets['wp_inactive_widgets'] );

			foreach ( $sidebars_widgets as $sidebar => $sidebar_widgets ) {

				$in_sidebar = false;

				foreach ( $sidebar_widgets as $key => $widget_id ) {

					if ( 0 === strpos( $widget_id, 'tm_woo_ajax_filters' ) ) {

						$in_sidebar = true;
					}
				}
				if ( ! $in_sidebar ) {

					unset( $sidebars_widgets[ $sidebar ] );
				}
			}
			foreach ( $sidebars_widgets as $sidebar => $sidebar_widgets ) {

				$sidebar_args = $wp_registered_sidebars[ $sidebar ];

				foreach ( $sidebar_widgets as $key => $widget_id ) {

					$sidebar_args['before_widget'] = $wp_registered_sidebars[ $sidebar ]['before_widget'];

					$filters_content[ $sidebar ][ $key ]['id'] = $widget_id;

					if ( 0 === strpos( $widget_id, 'tm_woo_ajax_filters' ) ) {

						$classname_ = '';

						foreach ( ( array ) $wp_registered_widgets[ $widget_id ]['classname'] as $cn ) {

							if ( is_string( $cn ) ) {
								$classname_ .= '_' . $cn;
							} elseif ( is_object( $cn ) ) {
								$classname_ .= '_' . get_class( $cn );
							}
						}
						$classname_ = ltrim( $classname_, '_' );

						$sidebar_args['before_widget'] = sprintf( $sidebar_args['before_widget'], $widget_id, $classname_ );

						$id = str_replace( 'tm_woo_ajax_filters-', '', $widget_id );

						ob_start();

						the_widget( 'TM_Woo_Ajax_Filters_Widget', $settings[ $id ], $sidebar_args );

						$filters_content[ $sidebar ][ $key ]['content'] = ob_get_clean();

					} else {

						$filters_content[ $sidebar ][ $key ]['content'] = false;
					}
				}
			}
		}

		if ( $wcbreadcrumbs ) {

			ob_start();

			woocommerce_breadcrumb();

			$wcbreadcrumbs_content = ob_get_clean();
		}

		$json = array(
			'products'      => $content,
			'filters'       => $filters_content,
			'wcbreadcrumbs' => $wcbreadcrumbs_content
		);

		wp_reset_query();

		$json = apply_filters( 'tm_wc_ajax_json', $json );

		wp_send_json_success( $json );
	}

	/**
	 * Display products in ajax.
	 *
	 * @since 1.0.6
	 *
	 */
	public function process_ajax_get_products() {

		if ( tm_ajax_wc_version_check( '3.3' ) ) {

			wc_setup_loop();

			if ( have_posts() ) :

				while ( have_posts() ) : the_post();

					wc_get_template_part( 'content', 'product' );

				endwhile;

			endif;
		} else {
			if ( have_posts() ) :

				woocommerce_product_subcategories( array( 'force_display' => true ) );

				while ( have_posts() ) : the_post();
					wc_get_template_part( 'content', 'product' );
				endwhile;

			elseif ( ! woocommerce_product_subcategories( array(
				'before' => woocommerce_product_loop_start( false ),
				'after'  => woocommerce_product_loop_end( false )
			) ) ) :
				wc_get_template( 'loop/no-products-found.php' );
			endif;
		}
	}

	public function get_widget_id_base( $value ) {

		return str_replace( 'tm_woo_ajax_filters-', '', $value );
	}

	public function enqueue_assets() {
		if ( is_product_taxonomy() || is_post_type_archive( 'product' ) || is_page( wc_get_page_id( 'shop' ) ) ) {

			wp_enqueue_style( 'tm-wc-ajax' );

			wp_enqueue_script( 'tm-wc-ajax-products' );

			wp_localize_script( 'tm-wc-ajax-products', 'tmWooAjaxProducts', array(
				'ajaxurl'        => admin_url( 'admin-ajax.php', is_ssl() ? 'https' : 'http' ),
				'ajaxOrderby'    => 'yes' === get_option( 'tm_wc_ajax_filters_ordering_enable' ) ? true : false,
				'ajaxPagination' => 'yes' === get_option( 'tm_wc_ajax_filters_pagination_enable' ) ? true : false
			) );
		}
	}

	public function pagination_args( $args = array() ) {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

			$args['base'] = esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', $this->get_pagenum_link( 999999999, false ) ) ) );
		}

		return $args;
	}

	public static function get_pagenum_link( $pagenum = 1, $escape = true ) {

		global $wp_rewrite;

		$pagenum   = (int) $pagenum;
		$parse_url = parse_url( $_POST['pageUrl'] );
		$page_url  = str_replace( $parse_url['scheme'] . '://' . $parse_url['host'], '', $_POST['pageUrl'] );
		$request   = remove_query_arg( 'paged', $page_url );
		$home_root = parse_url( home_url() );
		$home_root = ( isset( $home_root['path'] ) ) ? $home_root['path'] : '';
		$home_root = preg_quote( $home_root, '|' );
		$request   = preg_replace( '|^' . $home_root . '|i', '', $request );
		$request   = preg_replace( '|^/+|', '', $request );

		if ( ! $wp_rewrite->using_permalinks() ) {

			$base = trailingslashit( get_bloginfo( 'url' ) );

			if ( $pagenum > 1 ) {

				$result = add_query_arg( 'paged', $pagenum, $base . $request );

			} else {

				$result = $base . $request;
			}
		} else {

			$qs_regex = '|\?.*?$|';

			preg_match( $qs_regex, $request, $qs_match );

			if ( ! empty( $qs_match[0] ) ) {

				$query_string = $qs_match[0];
				$request      = preg_replace( $qs_regex, '', $request );

			} else {

				$query_string = '';
			}
			$request = preg_replace( "|$wp_rewrite->pagination_base/\d+/?$|", '', $request );
			$request = preg_replace( '|^' . preg_quote( $wp_rewrite->index, '|' ) . '|i', '', $request );
			$request = ltrim( $request, '/' );
			$base    = trailingslashit( get_bloginfo( 'url' ) );

			if ( $wp_rewrite->using_index_permalinks() && ( $pagenum > 1 || '' != $request ) ) {
				$base .= $wp_rewrite->index . '/';
			}

			if ( $pagenum > 1 ) {

				$request = ( ( ! empty( $request ) ) ? trailingslashit( $request ) : $request ) . user_trailingslashit( $wp_rewrite->pagination_base . "/" . $pagenum, 'paged' );
			}
			$result = $base . $request . $query_string;
		}

		/**
		 * Filter the page number link for the current request.
		 *
		 * @since 1.0.0
		 *
		 * @param string $result The page number link.
		 */
		$result = apply_filters( 'get_pagenum_link', $result );


		if ( $escape ) {
			return esc_url( $result );
		} else {
			return esc_url_raw( $result );
		}
	}

	public function load_more_button() {
		if ( tm_ajax_wc_prop_is_shortcode() ) {
			return;
		}

		if ( 'yes' !== get_option( 'tm_wc_ajax_filters_loadmore_enable', 'yes' ) ) {

			return;
		}

		if ( ! empty( $_REQUEST['s'] ) && ! empty( $_REQUEST['post_type'] ) ) {
			// Hide load more from search results page
			return;
		}

		global $wp_query;

		$args = apply_filters( 'woocommerce_pagination_args', array(
			'base'         => esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) ),
			'format'       => '',
			'add_args'     => false,
			'current'      => max( 1, get_query_var( 'paged' ) ),
			'total'        => $wp_query->max_num_pages,
			'add_fragment' => ''
		) );

		$pagenum_link = html_entity_decode( get_pagenum_link() );
		$url_parts    = explode( '?', $pagenum_link );

		if ( $args['current'] && ( $args['current'] < $args['total'] || - 1 == $args['total'] ) ) {

			$link = str_replace( '%_%', $args['format'], $args['base'] );
			$link = str_replace( '%#%', $args['current'] + 1, $link );

			if ( ! is_array( $args['add_args'] ) ) {
				$args['add_args'] = array();
			}

			if ( isset( $url_parts[1] ) ) {
				$format       = explode( '?', str_replace( '%_%', $args['format'], $args['base'] ) );
				$format_query = isset( $format[1] ) ? $format[1] : '';
				wp_parse_str( $format_query, $format_args );
				wp_parse_str( $url_parts[1], $url_query_args );
				foreach ( $format_args as $format_arg => $format_arg_value ) {

					unset( $url_query_args[ $format_arg ] );
				}
				$args['add_args'] = array_merge( $args['add_args'], urlencode_deep( $url_query_args ) );
			}

			$add_args = $args['add_args'];

			if ( $add_args ) {
				$link = add_query_arg( $add_args, $link );
			}
			$link .= $args['add_fragment'];

			if ( $this->wc_last_page_loaded() ) {
				return;
			}

			$classes   = array( 'button', 'tm-wc-ajax-load-more-button', 'btn', 'btn-default' );
			$text      = get_option( 'tm_wc_ajax_filters_loadmore_label', __( 'Load more', 'tm-woocommerce-ajax-filters' ) );
			$preloader = apply_filters( 'tm_wc_ajax_filters_button_preloader', '' );
			$html      = sprintf( '<button  data-href="%s" type="button" class="%s">%s</button>', $link, implode( ' ', $classes ), $text . $preloader );
			echo apply_filters( 'tm_wc_ajax_filters_loadmore_button', $html, $link, $classes, $text, $preloader );
		}
	}

	/**
	 * Check if last page was loaded on archive product page.
	 *
	 * @since 1.0.6
	 *
	 * @return bool.
	 */
	public function wc_last_page_loaded() {
		if ( tm_ajax_wc_version_check( '3.3' ) ) {
			$current = wc_get_loop_prop( 'current_page' );
			$total   = wc_get_loop_prop( 'total_pages' );

			if ( $current == $total ) {
				return true;
			}

			return false;
		} else {
			$treshhold = wp_is_mobile() ? ( int ) get_option( 'tm_wc_ajax_filters_loadmore_treshold_mobile', 20 ) : ( int ) get_option( 'tm_wc_ajax_filters_loadmore_treshold', 20 );

			if ( $treshhold <= $this->loop ) {
				return true;
			}

			return false;
		}
	}

	public function loop_end() {

		if ( is_main_query() ) {

			global $woocommerce_loop;

			$this->loop = isset( $woocommerce_loop['loop'] ) ? $woocommerce_loop['loop'] : false;
		}
	}
}

function tm_wc_ajax() {

	return TM_WooCommerce_Ajax::instance();
}

tm_wc_ajax();

if ( ! class_exists( 'WC_Query' ) ) {

	require_once ABSPATH . 'wp-content/plugins/woocommerce/includes/class-wc-query.php';
}

class TM_WC_Query extends WC_Query {

	public function __construct() {

		add_action( 'init', array( $this, 'add_endpoints' ) );
		add_action( 'wp_loaded', array( $this, 'get_errors' ), 20 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'parse_request' ), 0 );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
		add_action( 'wp', array( $this, 'remove_product_query' ) );
		add_action( 'wp', array( $this, 'remove_ordering_args' ) );

		$this->init_query_vars();
	}

	public function pre_get_posts( $q ) {

		$GLOBALS['wp_the_query'] = $q;

		parent::pre_get_posts( $q );
	}
}
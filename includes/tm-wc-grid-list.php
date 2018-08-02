<?php

class TM_WC_Grid_List {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	public $condition;
	public $query_vars;
	public $toggle_enabled = false;
	public $is_shop_loop = false;

	public function __construct() {
		$this->set_condition();

		add_action( 'woocommerce_before_shop_loop', array( $this, 'add_toggle_button' ), 40 );

		add_filter( 'wc_get_template_part', array( $this, 'list_product_template_loader' ), 10, 3 );

		add_filter( 'wc_get_template', array( $this, 'list_category_template_loader' ), 10, 5 );

		add_filter( 'post_class', array( $this, 'add_product_class' ), 30, 3 );

		add_filter( 'product_cat_class', array( $this, 'add_category_class' ), 10, 3 );

		add_action( 'woocommerce_before_shop_loop', array( $this, 'set_gl_trigger' ) );
		add_action( 'woocommerce_after_shop_loop', array( $this, 'unset_gl_trigger' ) );
	}

	/**
	 * Set grid/list condition
	 */
	public function set_condition() {

		$gl = array( 'grid', 'list' );

		// Directly passed in request value has much priority
		if ( isset( $_REQUEST['glCondition'] ) && in_array( $_REQUEST['glCondition'], $gl ) ) {
			$this->condition = $_REQUEST['glCondition'];

			return;
		}

		$this->condition = isset( $_COOKIE['tm-woo-grid-list'] ) ? $_COOKIE['tm-woo-grid-list'] : 'grid';

	}

	/**
	 * Set main shop loop trigger
	 */
	public function set_gl_trigger() {
		$this->is_shop_loop = true;
	}

	/**
	 * Set main shop loop trigger
	 */
	public function unset_gl_trigger() {
		$this->is_shop_loop = false;
	}

	public static function instance() {

		if ( is_null( self::$_instance ) ) {

			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function list_product_template_loader( $template, $slug, $name ) {

		if ( ! $this->is_layout_option_active() ) {
			return $template;
		}

		if ( 'content' === $slug && 'product' === $name && 'list' === $this->condition ) {

			$name .= '-list';

			if ( $name && ! WC_TEMPLATE_DEBUG_MODE ) {

				$template = locate_template( array(
					"{$slug}-{$name}.php",
					WC()->template_path() . "{$slug}-{$name}.php"
				) );
			}

			if ( ! $template && $name && file_exists( tm_wc_ajax_filters()->plugin_dir( "/templates/{$slug}-{$name}.php" ) ) ) {

				$template = tm_wc_ajax_filters()->plugin_dir( "/templates/{$slug}-{$name}.php" );
			}

			if ( ! $template && ! WC_TEMPLATE_DEBUG_MODE ) {

				$template = locate_template( array( "{$slug}.php", WC()->template_path() . "{$slug}.php" ) );
			}
		}

		return $template;
	}

	public function list_category_template_loader( $located, $template_name, $args, $template_path, $default_path ) {

		if ( 'content-product_cat.php' === $template_name && 'list' === $this->condition ) {

			$template_name = 'content-product_cat-list.php';
			$default_path  = tm_wc_ajax_filters()->plugin_dir( '/templates/' );
			$located       = wc_locate_template( $template_name, $template_path, $default_path );

			if ( ! file_exists( $located ) ) {

				_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );

				return;
			}

		}

		return $located;
	}

	public function is_layout_option_active() {

		$is_active = false;

		if ( is_shop() || is_product_taxonomy() ) {
			$is_active = true;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$this->toggle_enabled = true;
			$is_active            = true;
		} else {
			$is_active = $this->is_shop_loop;
		}

		return $is_active;
	}

	public function add_product_class( $classes, $class = '', $post_id = '' ) {

		if ( ! $post_id || 'product' !== get_post_type( $post_id ) ) {

			return $classes;
		}

		if ( ! $this->is_layout_option_active() ) {
			return $classes;
		}

		$product = wc_get_product( $post_id );

		if ( $product && is_main_query() ) {

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

				$classes[] = 'product';
			}

			if ( 'list' === $this->condition && $this->toggle_enabled ) {
				$classes[] = 'product-list';
			}
		}

		return array_unique( $classes );
	}

	public function add_category_class( $classes, $class, $category ) {
		if ( 'list' === $this->condition && $this->toggle_enabled ) {
			$classes[] = 'product-list';
		}

		return array_unique( $classes );
	}

	public function add_toggle_button() {
		if ( tm_ajax_wc_prop_is_shortcode() ) {
			return;
		}

		if ( is_product_taxonomy() ) {
			$id = wc_get_page_id( 'shop' );
		} else {
			$id = get_the_id();
		}

		if ( ! $id && ! empty( $_REQUEST['pageRef'] ) ) {
			$id = (int) $_REQUEST['pageRef'];
		}

		$html     = array();
		$html[]   = '<div data-page-referrer="' . $id . '" class="tm-woo-grid-list-toggle-button-wrapper">';
		$html[]   = '<span class="tm-woo-grid-list-toggle-button">';
		$disabled = 'list' === $this->condition ? ' disabled' : '';
		$html[]   = '<span class="tm-woo-grid-list-toggler tm-woo-grid-list-toggle-button-list' . $disabled . '" data-condition="list">';
		$html[]   = '<span class="dashicons dashicons-exerpt-view"></span>';
		$html[]   = '</span>';
		$disabled = 'grid' === $this->condition ? ' disabled' : '';
		$html[]   = '<span class="tm-woo-grid-list-toggler tm-woo-grid-list-toggle-button-grid' . $disabled . '" data-condition="grid">';
		$html[]   = '<span class="dashicons dashicons-grid-view"></span>';
		$html[]   = '</span>';
		$html[]   = '</span>';
		$html[]   = '</div>';

		$this->toggle_enabled = true;

		echo implode( "\n", $html );
	}
}

function tm_wc_grid_list() {

	return TM_WC_Grid_List::instance();
}

tm_wc_grid_list();

?>
<?php
/**
 * Plugin Name: TM WooCommerce Ajax Filters
 * Description: Smart Ajax Filter for WooCommerce
 * Version:     1.0.9
 * Author:      TemplateMonster
 * Author URL:  http://www.templatemonster.com/wordpress-themes.php
 * Text Domain: tm-woocommerce-ajax-filters
 * License:     GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path: /languages
 */

/*  This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {

	header( 'HTTP/1.0 404 Not Found', true, 404 );

	exit;
}

class TM_WC_Ajax_Filters {

	/**
	 * The single instance of the class.
	 *
	 * @var TM_Woocommerce
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Trigger checks is woocoomerce active or not
	 *
	 * @since 1.0.0
	 * @var   bool
	 */
	public $has_woocommerce = null;

	/**
	 * Holder for plugin folder path
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $plugin_dir = null;

	/**
	 * Holder for plugin scripts suffix
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $suffix;

	/**
	 * Main TM_Woocommerce Instance.
	 *
	 * Ensures only one instance of TM_Woocommerce is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
	 * @see tm_wc()
	 * @return TM_Woocommerce - Main instance.
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {

			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Sets up needed actions/filters for the theme to initialize.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! $this->has_woocommerce() || defined( 'TM_WC_AJAX_FILTERS_VERISON' ) ) {

			add_action( 'admin_notices', array( $this, 'admin_notice_disabled_woocommerce' ) );

			return false;
		}
		define( 'TM_WC_AJAX_FILTERS_VERISON', '1.0.9' );

		// Register admin assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_assets' ), 9 );

		// Load public assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 9 );

		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		// Internationalize the text strings used.
		add_action( 'plugins_loaded', array( $this, 'lang' ), 1 );

		add_action( 'plugins_loaded', array( $this, 'init' ) );

		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ) );

		$this->includes();
		$this->filter_qureied_object();

		register_activation_hook( __FILE__, array( $this, 'tm_wc_ajax_filters_install' ) );

		$this->set_suffix();
	}

	/**
	 * Add filter for active theme to set correct queried object ID.
	 *
	 * @return void
	 */
	public function filter_qureied_object() {

		$stylesheet = get_stylesheet();
		$stylesheet = str_replace( '-', '_', $stylesheet );

		add_filter( $stylesheet . '_queried_object_id', array( $this, 'maybe_set_queried_object' ) );

	}

	/**
	 * Try to set apropriate queried object ID
	 *
	 * @param  mixed $id Current object ID
	 *
	 * @return mixed
	 */
	public function maybe_set_queried_object( $id ) {

		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $id;
		}

		if ( ! isset( $_REQUEST['pageRef'] ) ) {
			return $id;
		}

		return (int) $_REQUEST['pageRef'];
	}

	public function set_suffix() {

		$this->suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}

	public function admin_notice_disabled_woocommerce() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$details_link = self_admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=woocommerce&amp;TB_iframe=true&amp;width=600&amp;height=550' );
		?>
			<div class="notice notice-warning is-dismissible">
				<p><?php printf( __( 'TM WooCommerce Ajax Filters is enabled but not effective. It requires %s in order to work. Please install and activate it.', 'tm-woocommerce-ajax-filters' ), '<a href="' . esc_url( $details_link ) . '" class="thickbox open-plugin-details-modal">' . __( 'WooCommerce', 'tm-woocommerce-ajax-filters' ) . '</a>' ); ?></p>
			</div>
		<?php
	}

	/**
	 * Loads the translation files.
	 *
	 * @since 1.0.0
	 */
	function lang() {

		load_plugin_textdomain( 'tm-woocommerce-ajax-filters', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Include core files.
	 *
	 * @since 1.0.0
	 */
	public function init() {
		include_once 'includes/hooks.php';
	}

	/**
	 * Include core files.
	 *
	 * @since 1.0.0
	 */
	public function include_template_functions() {
		include_once 'includes/tm-wc-ajax.php';
		include_once 'includes/functions.php';
	}


	public function includes() {

		include_once 'includes/settings.php';

		if ( 'yes' === get_option( 'tm_wc_ajax_filters_grid_list_enable', 'yes' ) ) {

			include_once 'includes/tm-wc-grid-list.php';
		}
	}

	public function register_widgets() {

		include_once 'includes/widgets/class-tm-ajax-filter-widget.php';

		register_widget( 'TM_Woo_Ajax_Filters_Widget' );
	}

	/**
	 * Check if WooCommerce is active
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function has_woocommerce() {

		if ( null == $this->has_woocommerce ) {

			$this->has_woocommerce = in_array(
				'woocommerce/woocommerce.php',
				apply_filters( 'active_plugins', get_option( 'active_plugins' ) )
			);
		}

		return $this->has_woocommerce;
	}


	public function register_admin_assets() {

		wp_register_script( 'tm-wc-ajax-products-widget-admin', $this->plugin_url() . '/assets/js/tm-wc-ajax-products-widget-admin' . $this->suffix . '.js', array( 'jquery' ), TM_WC_AJAX_FILTERS_VERISON, true );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_assets() {

		// TM Woo Grid-List
		wp_register_style( 'tm-wc-ajax', $this->plugin_url() . '/assets/css/tm-wc-ajax.css', array( 'dashicons' ) );
		wp_register_script( 'tm-wc-ajax-products', $this->plugin_url() . '/assets/js/tm-wc-ajax-products' . $this->suffix . '.js', array( 'jquery-cookie' ), TM_WC_AJAX_FILTERS_VERISON, true );

		wp_localize_script( 'tm-wc-ajax-products', 'tmWooAjaxProducts', array(
			'ajaxurl'        => admin_url( 'admin-ajax.php', is_ssl() ? 'https' : 'http' ),
			'ajaxOrderby'    => 'yes' === get_option( 'tm_wc_ajax_filters_ordering_enable', 'yes' ) ? true : false,
			'ajaxPagination' => 'yes' === get_option( 'tm_wc_ajax_filters_pagination_enable', 'yes' ) ? true : false
		) );

		wp_register_script( 'tm-wc-ajax-product-filters', $this->plugin_url() . '/assets/js/tm-wc-ajax-product-filters' . $this->suffix . '.js', array( 'tm-wc-ajax-products' ), TM_WC_AJAX_FILTERS_VERISON, true );

		wp_register_style( 'tm-wc-ajax-filters-widget', $this->plugin_url() . '/assets/css/tm-wc-ajax-filters-widget.css', array( 'dashicons' ) );
	}

	public function plugin_url() {

		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_dir( $path = null ) {

		if ( ! $this->plugin_dir ) {

			$this->plugin_dir = trailingslashit( plugin_dir_path( __FILE__ ) );
		}

		return $this->plugin_dir . $path;
	}

	public function tm_wc_ajax_filters_install() {

		require_once 'includes/install.php';

		TM_WC_Ajax_Filters_Install()->init();
	}

	public function get_loader() {

		$loader = '<svg width="60px" height="60px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="uil-ring-alt"><rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect><circle cx="50" cy="50" r="40" stroke="#afafb7" fill="none" stroke-width="10" stroke-linecap="round"></circle><circle cx="50" cy="50" r="40" stroke="#5cffd6" fill="none" stroke-width="6" stroke-linecap="round"><animate attributeName="stroke-dashoffset" dur="2s" repeatCount="indefinite" from="0" to="502"></animate><animate attributeName="stroke-dasharray" dur="2s" repeatCount="indefinite" values="150.6 100.4;1 250;150.6 100.4"></animate></circle></svg>';

		return '<div class="tm-wc-ajax-filters-loader">' . apply_filters( 'tm_wc_ahax_filters_loader', $loader ) . '</div>';
	}

	public function get_original_product_id( $id ) {

		global $sitepress;

		if ( isset( $sitepress ) ) {

			$id = icl_object_id( $id, 'product', true, $sitepress->get_default_language() );
		}

		return $id;
	}
}

function tm_wc_ajax_filters() {

	return TM_WC_Ajax_Filters::instance();
}

tm_wc_ajax_filters();

?>
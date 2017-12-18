<?php

if ( !defined( 'ABSPATH' ) ) {

	exit;
} // Exit if accessed directly

if ( !class_exists( 'TM_WC_Ajax_Filters_Install' ) ) {

	/**
	 * Install plugin table and create the wishlist page
	 *
	 * @since 1.0.0
	 */
	class TM_WC_Ajax_Filters_Install {

		/**
		 * Single instance of the class
		 *
		 * @var \TM_WC_Ajax_Filters_Install
		 * @since 2.0.0
		 */
		protected static $instance;

		/**
		 * Plugin options
		 *
		 * @var array
		 * @since 1.0.0
		 */
		public $options;

		/**
		 * Returns single instance of the class
		 *
		 * @return \TM_WC_Ajax_Filters_Install
		 * @since 2.0.0
		 */
		public static function get_instance(){

			if( is_null( self::$instance ) ){

				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Initiator. Replace the constructor.
		 *
		 * @since 1.0.0
		 */
		public function init() {

			require_once 'settings.php';

			$this->options = tm_wc_ajax_get_settings();

			$this->default_options( $this->options );
		}

		/**
		 * Set options to their default value.
		 *
		 * @param array $options
		 * @return bool
		 * @since 1.0.0
		 */
		public function default_options( $options ) {

			foreach( $options as $value ) {

				if ( isset( $value['default'] ) && isset( $value['id'] ) && ! get_option( $value['id'] ) ) {

					add_option( $value['id'], $value['default'] );
				}
			}
		}
	}
}

/**
 * Unique access to instance of TM_WC_Ajax_Filters_Install class
 *
 * @return \TM_WC_Ajax_Filters_Install
 * @since 2.0.0
 */
function TM_WC_Ajax_Filters_Install(){
	return TM_WC_Ajax_Filters_Install::get_instance();
}
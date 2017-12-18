jQuery( function( $ ) {

	function tmWcPriceInputsInit() {

		$( '.widget_tm_woo_ajax_filters' ).each( function() {

			var self = $( this );

			$( 'button', self ).on( 'click', function( event ) {

				$.tmWcOrderingHandler( event );
			} );
		} );
	}
	tmWcPriceInputsInit();

	$( document ).on( 'tm_wc_products_changed', function( event ) {

		if( event.widgets_updated ) {

			tmWcPriceInputsInit();
		}
	} );
} );
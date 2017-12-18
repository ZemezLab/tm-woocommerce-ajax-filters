/* global tm_wc_price_slider_params */

if (!String.prototype.format) {
	String.prototype.format = function() {
		var args = arguments;
		return this.replace(/{(\d+)}/g, function(match, number) {
			return typeof args[number] != 'undefined' ? args[number] : match;
		});
	};
}

function number_format(number, decimals, dec_point, thousands_sep) {
	number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
	var n = !isFinite(+number) ? 0 : +number,
		prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
		sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
		dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
		s = '',
		toFixedFix = function(n, prec) {
			var k = Math.pow(10, prec);
			return '' + (Math.round(n * k) / k)
				.toFixed(prec);
		};
	// Fix for IE parseFloat(0.55).toFixed(0) = 0;
	s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
		.split('.');
	if (s[0].length > 3) {
		s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
	}
	if ((s[1] || '')
		.length < prec) {
		s[1] = s[1] || '';
		s[1] += new Array(prec - s[1].length + 1)
			.join('0');
	}
	return s.join(dec);
}

jQuery( function( $ ) {

	// tm_wc_price_slider_params is required to continue, ensure the object exists
	if ( typeof tm_wc_price_slider_params === 'undefined' ) {
		return false;
	}

	function tmWcPriceFilterInit() {

		$( '.widget_tm_woo_ajax_filters' ).each( function() {

			var self = $( this );

			// Get markup ready for slider
			$( 'input#min_price, input#max_price', self ).hide();

			// Price slider uses jquery ui
			var min_price         = $( '#min_price', self ).data( 'min' ),
				max_price         = $( '#max_price', self ).data( 'max' ),
				current_min_price = parseInt( $( '#min_price', self ).val() ),
				current_max_price = parseInt( $( '#max_price', self ).val() );

			$( document.body ).on( 'tm_wc_price_slider_create tm_wc_price_slider_slide', function( event, min, max ) {


				$( '.price_slider_amount span.from', self ).html( tm_wc_price_slider_params.price_format.format( number_format( min, 2, '.', ',' ) ) );
				$( '.price_slider_amount span.to', self ).html( tm_wc_price_slider_params.price_format.format( number_format( max, 2, '.', ',' ) ) );

				$( document.body ).trigger( 'price_slider_updated', [ min, max ] );
			} );

			$( '.tm_wc_price_slider', self ).slider({
				range:   true,
				animate: true,
				min:     min_price,
				max:     max_price,
				values:  [ current_min_price, current_max_price ],
				create:  function() {

					$( document.body ).trigger( 'tm_wc_price_slider_create', [ current_min_price, current_max_price ] );
				},
				slide:   function( event, ui ) {

					$( document.body ).trigger( 'tm_wc_price_slider_slide', [ ui.values[0], ui.values[1] ] );
				},
				change:  function( event, ui ) {

					var changed = false;

					if( parseInt( $( 'input#min_price', self ).val() ) !== ui.values[0] ) {

						$( 'input#min_price', self ).val( ui.values[0] );

						changed = true;
					}
					if( parseInt( $( 'input#max_price', self ).val() ) !== ui.values[1] ) {

						$( 'input#max_price', self ).val( ui.values[1] );

						changed = true;
					}

					if( changed ) {

						event.currentTarget = event.target;

						$( document.body ).trigger( 'tm_wc_price_slider_change', [ ui.values[0], ui.values[1] ] );

						$.tmWcOrderingHandler( event );
					}
				}
			} );
		} );
	}
	tmWcPriceFilterInit();

	$( document ).on( 'tm_wc_products_changed', function( event ) {

		if( event.widgets_updated ) {

			tmWcPriceFilterInit();
		}
	} );
});

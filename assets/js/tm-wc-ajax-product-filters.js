( function( $ ) {
	'use strict';

	function tmWcWidgetAjaxFiltersHandler( event ) {

		event.preventDefault();

		var data = {
				pageUrl:    event.currentTarget.href,
				task:       'filter'
			};
		$.tmWcProductsAjax( data );
	}

	function tmWcWidgetAjaxFiltersSelectHandler( event ) {

		var data = {
				pageUrl:    $( event.currentTarget ).val(),
				task:       'filter'
			};
		$.tmWcProductsAjax( data );
	}

	function tmWcAjaxFiltersInit() {

		$( '.widget_tm_woo_ajax_filters .wc-layered-nav-term > a, .tm-wc-ajax-filters-dismiss, .tm-wc-ajax-filters-reset' ).on( 'click', function( event ) {

			tmWcWidgetAjaxFiltersHandler( event );
		} );
	}
	$.tmWcAjaxFiltersSelectsInit = function() {

		$( '.widget_tm_woo_ajax_filters select[class*="dropdown_layered_nav"]' ).on( 'change', function( event ) {

			tmWcWidgetAjaxFiltersSelectHandler( event );
		} );
	};
	tmWcAjaxFiltersInit();

	$( document ).on( 'tm_wc_products_changed', function( event ) {

		if( event.widgets_updated ) {

			tmWcAjaxFiltersInit();

			$.tmWcAjaxFiltersSelectsInit();
		}
	} );
} ( jQuery ) );
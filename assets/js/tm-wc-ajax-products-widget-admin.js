( function( $ ) {
	'use strict';

	function tmWcAjaxFiltersAdmin( event, widget ) {

		var idBase = widget.find( 'input[name="multi_number"]' ).val() ? widget.find( 'input[name="multi_number"]' ).val() : widget.find( 'input[name="widget_number"]' ).val(),
			attrSelect = $( 'select[name="widget-tm_woo_ajax_filters[' + idBase + '][attribute]"]' );

		attributesHandler( idBase );

		attrSelect.on( 'change', function() {

			attributesHandler( idBase );
		} );
	}

	function attributesHandler( idBase ) {

		var attrSelect    = $( 'select[name="widget-tm_woo_ajax_filters[' + idBase + '][attribute]"]' ),
			displaySelect = $( 'select[name="widget-tm_woo_ajax_filters[' + idBase + '][display_type]"]' ),
			priceOptions  = 'option[value="slider"], option[value="inputs"]',
			attrOptions   = 'option[value="list"], option[value="dropdown"]',
			querySelect   = $( 'select[name="widget-tm_woo_ajax_filters[' + idBase + '][query_type]"]' );

		if( 'price' === attrSelect.val() ) {

			displaySelect.find( attrOptions ).css({ display: 'none' });
			displaySelect.find( priceOptions ).removeAttr( 'style' );

			if( 'list' === displaySelect.val() || 'dropdown' === displaySelect.val() ) {

				displaySelect.val( 'slider' );
			}

			querySelect.closest( 'p' ).hide();

		} else {

			displaySelect.find( attrOptions ).removeAttr( 'style' );
			displaySelect.find( priceOptions ).css({ display: 'none' });

			if( 'slider' === displaySelect.val() || 'inputs' === displaySelect.val() ) {

				displaySelect.val( 'list' );
			}
			querySelect.closest( 'p' ).show();
		}
	}

	$( '#widgets-right' ).find( 'div.widget[id*=tm_woo_ajax_filters]' ).each( function () {

		tmWcAjaxFiltersAdmin( 'init', $( this ) );
	} );

	$( document ).on( 'widget-updated widget-added', function( event, widget ){

		if ( widget.is( '[id*=_tm_woo_ajax_filters]' ) ) {

			tmWcAjaxFiltersAdmin( event, widget );
		}
	} );

} ( jQuery ) );
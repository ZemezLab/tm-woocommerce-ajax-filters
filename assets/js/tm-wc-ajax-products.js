( function( $ ) {
	'use strict';

	var orderingClass   = '.woocommerce-ordering',
		btnClass        = '.tm-woo-grid-list-toggle-button',
		togglerClass    = '.tm-woo-grid-list-toggler',
		wrapperClass    = '.tm-wc-ajax-products-wrapper',
		btn             = $( btnClass ),
		toggler         = btn.find( togglerClass ),
		disabledClass   = 'disabled',
		cookie          = 'tm-woo-grid-list',
		wrapper         = $( wrapperClass ),
		wcbreadcrumbs   = $( '.woocommerce-breadcrumb' ),
		paginationClass = '.woocommerce-pagination a.page-numbers',
		loadMoreClass   = 'button.tm-wc-ajax-load-more-button';

	$.tmWcProductsAjax = function( data ) {

		var wrapper = $( wrapperClass );

		wrapper.addClass( 'loading' );

		data.action        = 'tm_wc_rebuild_products';
		data.wcbreadcrumbs = wcbreadcrumbs.length;

		$.post(
			tmWooAjaxProducts.ajaxurl,
			data,
			function( response ) {

				wrapper.removeClass( 'loading' );

				if( response.success ) {

					wrapper.replaceWith( response.data.products );

					var event = $.Event( 'tm_wc_products_changed' );

					for ( var sidebar in response.data.filters ) {

						var $sidebar        = $( 'div[data-sidebar=' + sidebar + ']' ),
							existingWidgets = $sidebar.children(),
							n               = 0;

						response.data.filters[sidebar].forEach( function( item, i, arr ) {

							if( false !== item.content ) {

								if( 'undefined' !== typeof existingWidgets[n] ) {

									if( existingWidgets[n].id === item.id ) {

										$( existingWidgets[n] ).replaceWith( item.content );

										event.widgets_updated = true;

										n++;

									} else if( '' !== item.content ) {

										$( existingWidgets[n] ).before( item.content );

										event.widgets_updated = true;

										existingWidgets = $sidebar.children();

										n++;
									}
								} else {

									$( 'div[data-sidebar=' + sidebar + ']' ).append( item.content );

									event.widgets_updated = true;

									existingWidgets = $sidebar.children();
								}
							} else {

								n++;
							}
						} );
					}

					if( response.data.wcbreadcrumbs ) {

						wcbreadcrumbs.replaceWith( response.data.wcbreadcrumbs );
					}

					if ( history.pushState && ( location.href !== data.pageUrl ) ) {

						history.pushState( null, null, data.pageUrl );
					}
					$( document ).trigger( event );

					tmWcAjaxInit();
				}
			}
		);
	};

	function tmWcGridListTogglerHandler( event ) {

		var self = $( event.currentTarget );

		if( self.hasClass( disabledClass ) ) {

			return;
		}
		toggler.removeClass( disabledClass );

		self.addClass( disabledClass );

		var condition = event.currentTarget.dataset.condition,
			data      = {
				pageUrl: location.href,
				pageRef: toggler.closest( '.tm-woo-grid-list-toggle-button-wrapper' ).data( 'page-referrer' ),
				glCondition: condition
			};

		$.cookie( cookie, condition, { expires: 365, path: '/' } );

		$.tmWcProductsAjax( data );
	}

	$.tmWcOrderingHandler = function( event ) {

		var self = $( event.currentTarget );

		if ( ! tmWooAjaxProducts.ajaxOrderby && self.closest( 'form' ).hasClass( 'woocommerce-ordering' ) ) {

			self.closest( 'form' ).submit();

			return;
		}
		var currentUrl   = location.href,
			form         = self.closest( 'form' ),
			formData     = form.serialize(),
			urlQuery     = location.search,
			pageUrl;

		if( urlQuery.length ) {

			pageUrl = currentUrl.replace( urlQuery, '?' + formData );

		} else {

			pageUrl = currentUrl + '?' + formData;
		}
		var data = {
			pageUrl:    pageUrl,
			task:       'ordering'
		};

		$.tmWcProductsAjax( data );
	};

	function tmWcPaginationHandler( event ) {

		if( tmWooAjaxProducts.ajaxPagination ) {

			event.preventDefault();

			var data = {
				pageUrl:    event.currentTarget.href,
			};
			$.tmWcProductsAjax( data );
		}
	}

	function tmWcLoadMoreHandler( event ) {

		event.preventDefault();

		var wrapper       = $( wrapperClass ),
			productsCount = wrapper.find('.product').length,
			button = $( event.currentTarget );

		var data = {
			pageUrl:       event.currentTarget.dataset.href,
			action:        'tm_wc_load_more',
			productsCount: productsCount,
		};

		button.addClass( 'loading' );

		$.post(
			tmWooAjaxProducts.ajaxurl,
			data,
			function( response ) {

				button.removeClass( 'loading' );

				if( response.success ) {

					var wrapper = $( wrapperClass );

					wrapper.find('.product').last().after( response.data.products );

					button.replaceWith( response.data.button );

					var event = $.Event( 'tm_wc_products_changed' );

					$( document ).trigger( event );

					tmWcAjaxInit();
				}
			}
		);
	}

	function tmWcAjaxInit() {

		$( orderingClass ).off( 'change', 'select.orderby' );

		btn     = $( btnClass );
		toggler = btn.find( togglerClass );

		toggler.off( 'click' );
		toggler.on( 'click', function( event ) {

			tmWcGridListTogglerHandler( event );
		} );
		$( orderingClass ).on( 'change', 'select.orderby', function( event ) {

			$.tmWcOrderingHandler( event );
		} );

		$( paginationClass ).off( 'click' );
		$( paginationClass ).on( 'click', function( event ) {

			tmWcPaginationHandler( event );
		} );

		$( loadMoreClass ).off( 'click' );
		$( loadMoreClass ).on( 'click', function( event ) {

			tmWcLoadMoreHandler( event );
		} );
	}
	$( document ).ready( function() {

		tmWcAjaxInit();
	} );
} ( jQuery ) );
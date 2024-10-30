/************************************************************************
 * LimeLight CRM - Wordpress Plugin
 * Copyright (C) 2017 Lime Light CRM, Inc.

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Public js
if ( location.href.indexOf( "wp-admin" ) === -1 ) {
	UtmCookie = new UtmCookie();
}

jQuery( "button" ).click( function() {
   buttonId = this.id;
} );

jQuery( function() {
	wpllMain.init();
} );

var wpllMain = ( function() {

	var init = function() {

		jQuery( '.wpll-ajax' ).on( 'submit', function( e ) {
			e.preventDefault();
			jQuery( '#js-response' ).addClass( 'limelight-loading' );
			jQuery( '#js-response' ).html( '<div class="limelight-spinner"></div>' );

			var cc = jQuery( '#js-cc-num' );

			if ( cc ) {
				jQuery( '#js-cc-num' ).val( sessionStorage.limelightCC );
				sessionStorage.limelightCC = '';
			}

			if ( typeof wpllParams !== 'undefined' ) {
				if ( ( typeof buttonId !== 'undefined' ) && ( buttonId.indexOf( 'product-qty-' ) !== -1 ) ) {
					var product_qty_x = buttonId.replace( '-button', '' ),
					    formData      = jQuery( '.' + product_qty_x + ' :visible, .' + product_qty_x + ' input[type=hidden]' ).serialize();
				} else {
					var formData = jQuery( '#' + wpllParams.formId + ' :visible, #' + wpllParams.formId + ' input[type=hidden]' ).serialize();
				}

				var data = {
					'action'          : 'wpll_process_form',
					'formId'          : wpllParams.formId,
					'formData'        : formData,
					'prospectId'      : sessionStorage.limelightProspectId || '',
					'productId'       : sessionStorage.limelightProductId || '',
					'cart'            : sessionStorage.limelightCart || '',
					'products'        : sessionStorage.limelightProducts || '',
					'utm_medium'      : UtmCookie.utm_medium(),
					'utm_source'      : UtmCookie.utm_source(),
					'utm_campaign'    : UtmCookie.utm_campaign(),
					'utm_term'        : UtmCookie.utm_term(),
					'utm_content'     : UtmCookie.utm_content(),
					'device_category' : UtmCookie.device_category(),
					'currentPage'     : window.location.href
				};

				jQuery.post(
					wpllParams.ajaxUrl,
					data,
					function( response ) {
						var response = JSON.parse( response ),
						    storages = response.storage,
						    html     = response.html;

						jQuery.each( storages, function( storage, value ) {
							sessionStorage.setItem( storage, value );
							} );

						if ( html ) {
							if ( response.javascript ) {
								html = html + response.javascript;
							}

							jQuery( '#js-response' ).removeClass( 'limelight-loading' );
							jQuery( '#js-response' ).html( '' );
							jQuery( '#js-response-container' ).addClass( 'limelight-response-modal' );
							jQuery( '#js-response' ).addClass( 'limelight-response-body' );
							jQuery( '#js-response' ).html( html );

							if ( html.indexOf( "get3d" ) !== -1 ) {
								document.get3d.submit();
							}
						} else {
							jQuery( '#js-response' ).removeClass( 'limelight-loading' )
							jQuery( '#js-response' ).html( '' );
							jQuery( '#js-response' ).html( response.javascript );

							if ( response.redirect_to ) {
								location.href = response.redirect_to;
							}
						}
					}
				);
			}
		} );

		if ( typeof wpllParams !== 'undefined' ) {

			var aff_data = {
				'action' : 'get_affiliates',
				'url'    : window.location.href
			};

			jQuery.post(
				wpllParams.ajaxUrl,
				aff_data,
				function( response ) {
					if ( response !== '0' ) {
						jQuery( '#js-response' ).html( response );
					}
				}
			);
		}
	}

	return {
		init: init
	};

} )();

jQuery( document ).ready( function() {

	jQuery( '#js-shipping-country' ).change( function() {
		jQuery( '#js-loading' ).addClass( 'limelight-loading' );
		jQuery( '#js-loading' ).html( '<div class="limelight-spinner"></div>' );
			jQuery.ajax( {
				type : 'POST',
				url  : wpllParams.ajaxUrl,
				data : {
					'action'  : 'update_states',
					'country' : jQuery( '#js-shipping-country' ).val()
				},
				success : function( response ) {
					jQuery( '#js-shipping-state' ).html( response );
					jQuery( '#js-loading' ).html( '' );
					jQuery( '#js-loading' ).removeClass( 'limelight-loading' );
				}
			} );
	} );

	jQuery( '#js-country' ).change( function() {
		jQuery( '#js-loading' ).addClass( 'limelight-loading' );
		jQuery( '#js-loading' ).html( '<div class="limelight-spinner"></div>' );
		jQuery.ajax( {
			type : 'POST',
			url  : wpllParams.ajaxUrl,
			data : {
				'action'  : 'update_states',
				'country' : jQuery( '#js-country' ).val()
			},
			success : function( response ) {
				jQuery( '#js-state' ).html( response );
				jQuery( '#js-loading' ).html( '' );
				jQuery( '#js-loading' ).removeClass( 'limelight-loading' );
			}
		} );
	} );

	jQuery( '#js-cc-num' ).focusin( function( e ) {
		jQuery( this ).val( '' );
		jQuery( this ).val( sessionStorage.limelightCC );
	} );

	jQuery( '#js-cc-num' ).focusout( function( e ) {
		sessionStorage.limelightCC = e.target.value;
		jQuery( this ).val( sessionStorage.limelightCC.replace( /.(?=.{4})/g, '*' ) );
	} );

	jQuery( '#js-cc-num' ).change( function( e ) {
		var data = {
			'3' : 'amex',
			'4' : 'visa',
			'5' : 'master',
			'6' : 'discover',
		};

		jQuery.each( data, function( key, cctype ) {
			if ( jQuery( '#js-cc-num' ).val().match( '^' + key ) && jQuery( "#js-cc-type option[value='" + cctype + "']" ).length > 0 ) {
				jQuery( '#js-cc-type' ).val( cctype );
			}
		} );

	} );

	jQuery( '#js-cc-num' ).keypress( function() {
		return event.charCode >= 48 && event.charCode <= 57
	} );
	
	jQuery( '#js-cvv' ).keypress( function() {
		return event.charCode >= 48 && event.charCode <= 57
	} );
	
	jQuery( '#js-phone' ).keypress( function() {
		return event.charCode >= 48 && event.charCode <= 57
	} );
	
	jQuery( '#js-response-container' ).click( function() {
		jQuery( '#js-response-container' ).removeClass( 'limelight-response-modal' );
		jQuery( '#js-response' ).removeClass( 'limelight-response-body' );		
		jQuery( '#js-response' ).html( '' );
	} );

	jQuery( '#confirm-delete' ).click( function() {
		jQuery( '#delete-account' ).prop( 'disabled', function( i, v ) { return !v; } );
	} );

	jQuery( '#delete-account' ).click( function() {
		window.location.replace( window.location.host + "./?action=delete_my_account&user_id=" + jQuery( '#delete-account' ).val() );
	} );

	var d = new Date();
		jQuery( 'select[name=cardMonth]' ).val( ( '00' + ( 1 + d.getMonth() ) ).slice( -2 ) );
} );

window.addEventListener( 'load', function() {

	var upsellNoThanks             = document.getElementById( 'js-upsell-no-thanks-button' ),
	    singlePageFirstName         = document.getElementById( 'js-single-page-first-name' ),
	    singlePageProduct           = document.getElementById( 'js-single-page-product-id' ),
	    singlePageShipping          = document.getElementById( 'js-single-page-shipping-id' ),
	    billingSameAsShipping       = document.getElementById( 'js-billing-same-as-shipping' ),
	    singleBillingSameAsShipping = document.getElementById( 'js-shipping-same-as-billing' ),
	    xAmount                     = document.getElementById( 'js-x-amount' ),
	    productId                   = sessionStorage.limelightProductId,
	    products                    = sessionStorage.limelightProducts;


	if ( upsellNoThanks ) {
		upsellNoThanks.addEventListener( 'click', function() {
			document.getElementById( 'js-upsell-no-thanks' ).value = 1;
		} );
	}

	if ( xAmount ) {
		if ( xAmount.value == 0 ) {
			if ( productId || products ) {
				window.location.reload();
			}
		}
	}

	if ( singlePageFirstName ) {
		singlePageFirstName.addEventListener( 'click', limelightSinglePage );
	}

	if ( singlePageProduct ) {
		singlePageProduct.addEventListener( 'click', limelightSinglePage );
	}

	if ( singlePageShipping ) {
		singlePageShipping.addEventListener( 'click', limelightSinglePage );
	}

	if ( singlePageShipping ) {
		singlePageShipping.addEventListener( 'click', limelightSinglePage );
	}

	if ( singleBillingSameAsShipping ) {
		singleBillingSameAsShipping.addEventListener( 'click', function() {
			jQuery('#js-billing-section').toggle();
		} );
	}

	if ( billingSameAsShipping ) {
		billingSameAsShipping.addEventListener( 'click', function() {

			if ( document.getElementById( 'js-billing-same-as-shipping' ).checked ) {
				document.getElementById( 'js-billing-section' ).innerHTML = '';
			} else {
				document.getElementById( 'js-billing-section' ).innerHTML = document.getElementById( 'js-billing-section' ).innerHTML + '<h2>Billing Information:</h2>';
				document.getElementById( 'js-billing-section' ).innerHTML = document.getElementById( 'js-billing-section' ).innerHTML + '<label>First Name</label><input type="text" name="billingFirstName" value="" required>';
				document.getElementById( 'js-billing-section' ).innerHTML = document.getElementById( 'js-billing-section' ).innerHTML + '<label>Last Name</label><input type="text" name="billingLastName" value="" required>';
				document.getElementById( 'js-billing-section' ).innerHTML = document.getElementById( 'js-billing-section' ).innerHTML + '<label>Address</label><input type="text" name="billingAddress1" value="" required>';
				document.getElementById( 'js-billing-section' ).innerHTML = document.getElementById( 'js-billing-section' ).innerHTML + '<label>Address 2</label><input type="text" name="billingAddress2" value="">';
				document.getElementById( 'js-billing-section' ).innerHTML = document.getElementById( 'js-billing-section' ).innerHTML + '<label>City</label><input type="text" name="billingCity" value="" required>';
				document.getElementById( 'js-billing-section' ).innerHTML = document.getElementById( 'js-billing-section' ).innerHTML + '<label>State</label><select name="billingState" id="js-billing-state" required>' + document.getElementById( 'js-shipping-state' ).innerHTML + '</select>';
				document.getElementById( 'js-billing-section' ).innerHTML = document.getElementById( 'js-billing-section' ).innerHTML + '<label>Zip</label><input type="text" name="billingZip" value="" required>';
				document.getElementById( 'js-billing-section' ).innerHTML = document.getElementById( 'js-billing-section' ).innerHTML + '<label>Country</label><select name="billingCountry" id="js-billing-country" required>' + document.getElementById( 'js-shipping-country' ).innerHTML + '</select>';

				jQuery( document ).ready( function() {

					jQuery( '#js-billing-country' ).change( function() {
						jQuery( '#js-loading' ).addClass( 'limelight-loading' );
						jQuery.ajax( {
							type : 'POST',
							url  : wpllParams.ajaxUrl,
							data : {
								'action'  : 'update_states',
								'country' : jQuery( '#js-billing-country' ).val()
							},
							success : function( response ) {
								jQuery( '#js-billing-state' ).html( response );
								jQuery( '#js-loading' ).removeClass( 'limelight-loading' );
							}
						} );
					} );
				} );
			}
		} );
	}

} );

// Admin js
window.addEventListener( 'load', function() {

	var adminForm         = document.getElementById( 'js-limelight-admin-form' ),
	    eDigitalEnabled    = document.getElementById( 'js-edigital-enabled' ),
	    threeDEnabled      = document.getElementById( 'js-3d-enabled'),
	    threeDCampaign     = document.getElementById( 'js-3d-gateway-id' );

	if ( adminForm ) {
		adminForm.className = 'limelight-admin-form';
		adminForm.addEventListener( 'submit', function( evt ) {
			var required = [ 'js-api-username', 'js-api-password', 'js-app-key' ]

			if ( threeDEnabled ) {
				if ( threeDEnabled.checked ) {
					required.push( 'js-3d-gateway-id' );
				}
			}

			if ( eDigitalEnabled ) {
				if ( eDigitalEnabled.checked ) {
					required.push( 'js-edigital-campaign-id' );
					required.push( 'js-edigital-product-id' );
				}
			}

			for ( var i in required ) {
				var name = required[ i ];

				if ( ! limelightAdminFormValidate( name ) ) {
					evt.preventDefault();
				}
			}

		} );
	}

	if ( eDigitalEnabled ) {
		eDigitalEnabled.addEventListener( 'click', function() {

			if ( eDigitalEnabled.checked ) {
				document.getElementById( 'js-edgitial-product-select' ).className  = 'limelight-edigital-is-active';
				document.getElementById( 'js-edigital-campaign-select' ).className = 'limelight-edigital-is-active';
			} else {
				document.getElementById( 'js-edigital-enabled-message' ).innerHTML = '';
				document.getElementById( 'js-edgitial-product-select' ).className  = 'limelight-edigital';
				document.getElementById( 'js-edigital-campaign-select' ).className = 'limelight-edigital';
			}
		} );
	}

	if ( threeDEnabled ) {
		threeDEnabled.addEventListener( 'click', function() {

			if ( threeDEnabled.checked ) {
				document.getElementById( 'js-3d-verify-enabled-message' ).innerHTML = '<br><br>Please select a <strong>3D Verify Enabled</strong> Gateway.';
				document.getElementById( 'js-3d-gateway-select' ).className        += '-is-active';
				threeDCampaign.addEventListener( 'change', function() {
					document.getElementById( 'js-3d-verify-enabled-message' ).innerHTML = '';
					document.getElementById( 'js-3d-gateway-id-error' ).className       = 'limelight-form-error';
				} );
			} else {
				document.getElementById( 'js-3d-verify-enabled-message' ).innerHTML = '';
				document.getElementById( 'js-3d-gateway-select' ).className         = 'limelight-3d';
				document.getElementById( 'js-3d-gateway-id-error' ).className       = 'limelight-form-error';
			}
		} );
	}

} );

function limelightAdminFormValidate( name ) {

	var input     = document.getElementById( name ),
	    errorSpan = document.getElementById( name + '-error' ),
	    value     = input.value,
	    isSet     = true;

	if ( value == '' ) {
		errorSpan.className    += '-is-active';
		input.style.borderColor = '#ff0000';
		isSet                   = false;
		input.focus();
	} else {
		input.style.borderColor = '#dddddd';
	}

	return isSet;
}

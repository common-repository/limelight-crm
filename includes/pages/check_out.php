<?php
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

echo $this->echo_javascript();


$cookies    = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
$vars_to_js = array(
	'ajaxUrl' => admin_url( 'admin-ajax.php', ( isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) ),
	'formId'  => 'js-check-out-form'
);

wp_localize_script( 'limelight-main-js', 'wpllParams', $vars_to_js );

if ( isset( $cookies->thankYou ) && $cookies->thankYou == 1 ) {
	echo $this->echo_cookie_reset_script();
}

function update_check_out() {
	echo '
	<script type="text/javascript">
		jQuery( document ).ready( function( $ ) {
			var updateCheckOut = $.ajax( {
				type    : "POST",
				url     : "",
				success : function( response ) {
					 var data   = $( "#js-price-summary", response );
					 var update = data.html();
					 $( "#js-price-summary" ).html( update );
				}
			} );
			updateCheckOut.abort();
			updateCheckOut.abort();
		} );
	</script>
	';
}

add_action( 'wp_footer', 'update_check_out' );

if ( ! empty( $_POST ) ) {

	unset( $_POST['username'] );
	unset( $_POST['password'] );

	if ( $this->campaign_data->any_upsells == 1 ) {
		$redirect_to = $this->limelight_get_url( $this->campaign_data->web_pages->upsell_page_1->page_id );
	} else {
		$redirect_to = $this->limelight_get_url( $this->campaign_data->web_pages->thank_you->page_id );
	}

	$params = array();

	if ( ! empty( $cookies->affiliates ) ) {
		foreach ( json_decode( stripslashes( $cookies->affiliates ) ) as $affiliate => $value ) {
			$params[$affiliate] = $value;
		}
	}

	if ( ! empty( $_POST['cart'] ) ) {

		if ( $this->options['3d_verify_enabled'] == 1 && ! empty( $_POST['verify_3d_temp_id'] ) ) {

			$params = array_merge( $params, array(
				'firstName'         => sanitize_text_field( $_POST['first_name'] ),
				'lastName'          => sanitize_text_field( $_POST['last_name'] ),
				'billingFirstName'  => sanitize_text_field( $_POST['first_name'] ),
				'billingLastName'   => sanitize_text_field( $_POST['last_name'] ),
				'creditCardNumber'  => sanitize_text_field( $_POST['x_card_num'] ),
				'CVV'               => sanitize_text_field( $_POST['x_cvv'] ),
				'expirationDate'    => sanitize_text_field( $_POST['x_exp_month'] . $_POST['x_exp_year'] ),
				'auth_amount'       => sanitize_text_field( $_POST['x_amount'] ),
				'forceGatewayId'    => $this->options['3d_gateway_id'],
				'verify_3d_temp_id' => sanitize_text_field( $_POST['verify_3d_temp_id'] ),
				'method'            => 'NewOrder',
				'tranType'          => 'Sale',
				'campaignId'        => $this->options['campaign_id'],
			) );

			if ( ! empty( $_POST['eci'] ) ) {
				$params = array_merge( $params, array(
					'cavv' => sanitize_text_field( $_POST['cavv'] ),
					'eci'  => sanitize_text_field( $_POST['eci'] ),
					'xid'  => sanitize_text_field( $_POST['xid'] )
				) );
			}
			
			foreach ( $_POST as $k => $v ) {
				$params[$k] = sanitize_text_field( $v );
			}

		}
	}

	if ( ! empty( $_POST['prospectId'] ) ) {

		if ( $this->options['3d_verify_enabled'] == 1 && ! empty( $_POST['verify_3d_temp_id'] ) ) {

			$params = array_merge( $params, array(
				'sessionId'         => ( isset( $_POST['sessionId'] ) ? sanitize_text_field( $_POST['sessionId'] ) : '' ),
				'edigital'          => ( isset( $_POST['edigital'] ) ? sanitize_text_field( $_POST['edigital'] ) : '' ),
				'creditCardType'    => sanitize_text_field( $_POST['creditCardType'] ),
				'ipAddress'         => $_SERVER['REMOTE_ADDR'],
				'prospectId'        => sanitize_text_field( $_POST['prospectId'] ),
				'shippingId'        => sanitize_text_field( $_POST['shippingId'] ),
				'productId'         => sanitize_text_field( $_POST['productId'] ),
				'method'            => 'NewOrderWithProspect',
				'tranType'          => 'Sale',
				'forceGatewayId'    => $this->options['3d_gateway_id'],
				'billingFirstName'  => sanitize_text_field($_POST['first_name'] ),
				'billingLastName'   => sanitize_text_field($_POST['last_name'] ),
				'creditCardNumber'  => sanitize_text_field($_POST['x_card_num'] ),
				'CVV'               => sanitize_text_field($_POST['x_cvv'] ),
				'expirationDate'    => sanitize_text_field( $_POST['x_exp_month'] ). sanitize_text_field( $_POST['x_exp_year'] ),
				'auth_amount'       => sanitize_text_field($_POST['x_amount'] ),
				'verify_3d_temp_id' => sanitize_text_field( $_POST['verify_3d_temp_id'] )
			) );

			if ( ! empty( $_POST['eci'] ) ) {
				$params = array_merge( $params, array(
					'cavv' => sanitize_text_field( $_POST['cavv'] ),
					'eci'  => sanitize_text_field( $_POST['eci'] ),
					'xid'  => sanitize_text_field( $_POST['xid'] )
				) );
			}
		}
		
	}

	$unset_me = array(
		'error_message',
		'currentPage',
		'x_relay_url',
		'first_name',
		'last_name',
		'x_exp_month',
		'x_exp_year',
		'x_amount',
	);

	foreach ( $unset_me as $unset ) {
		unset( $params[$unset] );
	}

	$new_order = $this->api_transact( $params, $redirect_to );
	$response  = '<script>';

	foreach ( $new_order['storage'] as $storage => $value ) {
		$response .= "sessionStorage.setItem( '$storage', '$value' );
		";
	}
	
	$response .= '</script>' . $new_order['javascript'] . $this->echo_javascript();

	if ( $new_order['html'] ) {
		$response .= $new_order['html'];
	} else {
		$response .= "<script>location.href = '{$redirect_to}';</script>";
	}

	echo $response;
}

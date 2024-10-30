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

$cookies    = ( isset( $_COOKIE['limelight-data'] ) ? json_decode( stripslashes( $_COOKIE['limelight-data'] ) ) : [] );
$vars_to_js = array(
	'ajaxUrl' => admin_url( 'admin-ajax.php', ( isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) ),
	'formId'  => 'js-one-click-check-out-form'
);

wp_localize_script( 'limelight-main-js', 'wpllParams', $vars_to_js );

if ( isset( $cookies->thankYou ) && $cookies->thankYou == 1 ) {
	echo $this->echo_cookie_reset_script();
}

echo $this->echo_javascript();

$redirect_to = ( $this->campaign_data->any_upsells == 1 ? $this->limelight_get_url( $this->campaign_data->web_pages->upsell_page_1->page_id ) :  $this->limelight_get_url( $this->campaign_data->web_pages->thank_you->page_id ) );

if ( ! empty( $_POST ) ) {
	if ( $this->options[ '3d_verify_enabled' ] == 1 && ! empty( $_POST[ 'verify_3d_temp_id' ] ) ) {
		$params = array(
			'firstName'             => sanitize_text_field( $_POST[ 'first_name' ] ),
			'lastName'              => sanitize_text_field( $_POST[ 'last_name' ] ),
			'billingFirstName'      => sanitize_text_field( $_POST[ 'first_name' ] ),
			'billingLastName'       => sanitize_text_field( $_POST[ 'last_name' ] ),
			'creditCardNumber'      => sanitize_text_field( $_POST[ 'x_card_num' ] ),
			'CVV'                   => sanitize_text_field( $_POST[ 'x_cvv' ] ),
			'expirationDate'        => sanitize_text_field( $_POST[ 'x_exp_month' ] ) . sanitize_text_field( $_POST[ 'x_exp_year' ] ),
			'auth_amount'           => sanitize_text_field( $_POST[ 'x_amount' ] ),
			'productId'             => sanitize_text_field( $_POST[ 'productId' ] ),
			'shippingId'            => sanitize_text_field( $_POST[ 'shippingId' ] ),
			'creditCardType'        => sanitize_text_field( $_POST[ 'creditCardType' ] ),
			'email'                 => sanitize_email( $_POST[ 'email' ] ),
			'ipAddress'             => $_SERVER[ 'REMOTE_ADDR' ],
			'edigital'              => ( isset( $_POST[ 'edigital' ] ) ? sanitize_text_field( $_POST[ 'edigital' ] ) : '' ),
			'sessionId'             => ( isset( $_POST[ 'sessionId' ] ) ? sanitize_text_field( $_POST[ 'sessionId' ] ) : '' ),
			'shippingAddress1'      => sanitize_text_field( $_POST[ 'shippingAddress1' ] ),
			'shippingAddress2'      => sanitize_text_field( $_POST[ 'shippingAddress2' ] ),
			'shippingCity'          => sanitize_text_field( $_POST[ 'shippingCity' ] ),
			'shippingState'         => sanitize_text_field( $_POST[ 'shippingState' ] ),
			'shippingZip'           => sanitize_text_field( $_POST[ 'shippingZip' ] ),
			'shippingCountry'       => sanitize_text_field( $_POST[ 'shippingCountry' ] ),
			'phone'                 => sanitize_text_field( $_POST[ 'phone' ] ),
			'billingSameAsShipping' => sanitize_text_field( $_POST[ 'billingSameAsShipping' ] ),
			'billingAddress1'       => sanitize_text_field( $_POST[ 'billingAddress1' ] ),
			'billingAddress2'       => sanitize_text_field( $_POST[ 'billingAddress2' ] ),
			'billingCity'           => sanitize_text_field( $_POST[ 'billingCity' ] ),
			'billingState'          => sanitize_text_field( $_POST[ 'billingState' ] ),
			'billingZip'            => sanitize_text_field( $_POST[ 'billingZip' ] ),
			'billingCountry'        => sanitize_text_field( $_POST[ 'billingCountry' ] ),
			'method'                => 'NewOrder',
			'tranType'              => 'Sale',
			'forceGatewayId'        => $this->options[ '3d_gateway_id' ],
			'campaignId'            => $this->options[ 'campaign_id' ],
			'verify_3d_temp_id'     => $_POST[ 'verify_3d_temp_id' ],
		);

		if ( ! empty( $_POST[ 'eci' ] ) ) {
			$params = array_merge( $params, array(
				'cavv' => sanitize_text_field( $_POST[ 'cavv' ] ),
				'eci'  => sanitize_text_field( $_POST[ 'eci' ] ),
				'xid'  => sanitize_text_field( $_POST[ 'xid' ] )
			) );
		}

		$new_order = $this->api_transact( $params, $redirect_to );
		$response = '<script>';

		foreach ( $new_order[ 'storage' ] as $storage => $value ) {
			$response .= "sessionStorage.setItem( '$storage', '$value' );
			";
		}

		$response .= '</script>' . $new_order[ 'javascript' ] . $this->echo_javascript();

		if ( $new_order[ 'html' ] ) {
			$response .= $new_order[ 'html' ];
		} else {
			$response .= "<script>location.href = '{$redirect_to}';</script>";
		}

		echo $response;
	}
}
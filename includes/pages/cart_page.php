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


$cookies    = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
$vars_to_js = array(
	'ajaxUrl' => admin_url( 'admin-ajax.php', ( isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) ),
	'formId'  => 'js-cart-form'
);

wp_localize_script( 'limelight-main-js', 'wpllParams', $vars_to_js );

echo $this->echo_javascript();

if ( isset( $cookies->thankYou ) && $cookies->thankYou == 1 ) {
	echo $this->echo_cookie_reset_script();
}

function update_cart() {
	echo '
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				var cartUpdate = $.ajax( {
					type    : "POST",
					url     : "",
					success : function( response ) {
						 $( "#js-cart-products" ).html( "" );
						 var data = $( "#js-cart-products", response );
						 $( "#js-cart-products" ).html( data.html() );
					}
				} );
				cartUpdate.abort();
				cartUpdate.abort();
			} );
			
		</script>
	';
}

add_action('wp_footer', 'update_cart');

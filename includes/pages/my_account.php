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
	'formId'  => 'js-my-account-form'
);

wp_localize_script( 'limelight-main-js', 'wpllParams', $vars_to_js );

if ( isset( $cookies->thankYou ) && $cookies->thankYou == 1 ) {
	echo $this->echo_cookie_reset_script();
}

echo $this->echo_javascript();

<?php

/**
 * @link       https://limelightcrm.com
 * @since      1.1.0
 * @package    Limelight
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$option_names = array(
	'limelight_options',
	'limelight_store_options',
	'limelight_error_options',
);

foreach ( $option_names as $o ) {
	delete_option( $o );
	delete_site_option( $o ); //multisite
}

<?php
// Kill heartbeat for dev
// add_action( 'init', 'stop_heartbeat', 1 );
// function stop_heartbeat() {
// wp_deregister_script( 'heartbeat' );
// }

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

/************************************************************************
 *
 * @link              https://limelightcrm.com
 * @since             1.1.1
 * @package           Limelight
 *
 * @wordpress-plugin
 * Plugin Name:       LimeLight CRM
 * Plugin URI:        http://help.limelightcrm.com/hc/en-us/articles/115003634306-Wordpress-Plugin
 * Description:       A Plugin to easily integrate LimeLight.
 * Version:           1.1.1
 * Author:            Lime Light CRM, Inc.
 * Author URI:        https://limelightcrm.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

defined( 'ABSPATH' ) or die( 'Plugin file cannot be accessed directly.' );

require plugin_dir_path( __FILE__ ) . 'includes/class-limelight.php';
include plugin_dir_path( __FILE__ ) . 'includes/class-limelight-remove-category-base.php';
include plugin_dir_path( __FILE__ ) . 'includes/class-limelight-products-widget.php';

$plugin = new Limelight();

function limelight_products() {

	$labels = array(
		'name'                => _x( 'Products', 'Post Type General Name', '' ),
		'singular_name'       => _x( 'Product', 'Post Type Singular Name', '' ),
		'menu_name'           => __( 'Products', '' ),
		'parent_item_colon'   => __( 'Parent Product', '' ),
		'all_items'           => __( 'All Products', '' ),
		'view_item'           => __( 'View Product', '' ),
		'add_new_item'        => __( 'Add New Product', '' ),
		'add_new'             => __( 'Add New', '' ),
		'edit_item'           => __( 'Edit Product', '' ),
		'update_item'         => __( 'Update Product', '' ),
		'search_items'        => __( 'Search Product', '' ),
		'not_found'           => __( 'Not Found', '' ),
		'not_found_in_trash'  => __( 'Not found in Trash', '' ),

	);

	$args = array(
		'label'               => __( 'products', '' ),
		'description'         => __( 'Product news and reviews', '' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields', ),
		'taxonomies'          => array( 'limelight_products' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 5,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'capability_type'     => 'page',
		'menu_icon'           => 'dashicons-cart',
	);
	register_post_type( 'products', $args );
}
add_action( 'init',        'limelight_products', 0 );
add_filter( 'widget_text', 'do_shortcode' );

function limelight_add_custom_types( $query ) {

	if( is_category() || is_tag() && empty( $query->query_vars['suppress_filters'] ) ) {

		$query->set( 'post_type', array( 'post', 'products' ));
		return $query;

	}

}

add_filter( 'pre_get_posts', 'limelight_add_custom_types' );

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


class Limelight_Products_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			'Limelight_Products_Widget',
			__('LimeLight Products', 'llcrm_products_domain'), 
			array( 'description' => __( 'List Your LimeLight Products', 'llcrm_products_domain' ), )
		);
	}

	public function widget( $args, $instance ) {
		
		$title        = apply_filters( 'widget_title', $instance['title'] );
		$num_products = $instance['num_products'];
		
		echo $args['before_widget'];
		if ( ! empty( $title ) ) { echo $args['before_title'] . $title . $args['after_title']; }

		$this->limelight_widget_products( $num_products );
		echo $args['after_widget'];
	}
		 
	public function form( $instance ) {

		if ( isset( $instance[ 'title' ] ) ) {
			$title        = esc_attr( $instance[ 'title' ] );
			$num_products = esc_attr( $instance['num_products'] );
		} else {
			$title        = __( 'Products', 'llcrm_products_domain' );
			$num_products = __( '5',        'llcrm_products_domain' );
		}

		$title_id           = $this->get_field_id( 'title' );
		$title_name         = $this->get_field_name( 'title' );
		$title_translate    = _e( 'Title:' );
		$title_esc          = esc_attr( $title );
		$products_id        = $this->get_field_id( 'num_products' );
		$products_name      = $this->get_field_name( 'num_products' );
		$products_translate = _e( 'Number of posts to show:' );
		$products_esc       = esc_attr( $num_products );

		echo <<<HTML
			<p>
				<label for="{$title_id}">{$title_translate}</label> 
				<input class="widefat" id="{$title_id}" name="{$title_name}" type="text" value="{$title_esc}" />
			</p>
			<p>
				<label for="{$products_id}">{$products_translate}</label> 
				<input class="tiny-text" id="{$products_id}" name="{$products_name}" type="text" value="{$products_esc}" />
			</p>
HTML;
	}
		 
	public function update( $new_instance, $old_instance ) {
		
		$instance                 = array();
		$instance['title']        = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['num_products'] = ( ( ! empty( $new_instance['num_products'] ) ) && is_numeric( $new_instance['num_products'] ) ) ? strip_tags( $new_instance['num_products'] ) : '5';
		
		return $instance;
	}

	function limelight_widget_products( $num_products ) {
		
		global
			$post;

		add_image_size( 'product_widget_size', 45, 45, false );
		
		wp_reset_query();

		$products = new WP_Query();
		$args     = array(
			'post_type'      => 'products',
			'orderby'        => 'rand',
			'order'          => 'ASC',
			'posts_per_page' => $num_products,
		);
		
		$products->query( $args );
		
		if ( $products->found_posts > 0 ) {
			
			echo '<ul class="products_widget">';
			while ( $products->have_posts() ) {
				$products->the_post();
				$image = ( has_post_thumbnail( $post->ID ) ) ? get_the_post_thumbnail( $post->ID, 'product_widget_size' ) : '<div class="noThumb"></div>';
				$li    = '<li>' . $image . '<a href="' . get_permalink() . '">';
				$li   .= get_the_title() . '</a></li>';

				echo $li;
			}
			echo '</ul>';
			wp_reset_postdata();

		} else {

			echo '<p>No Products Found</p>';

		}

	}

}

function limelight_prods_widget() {
	register_widget('Limelight_Products_Widget');
}

add_action( 'widgets_init', 'limelight_prods_widget' );

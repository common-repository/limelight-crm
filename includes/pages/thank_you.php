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

$echo_java = $this->echo_javascript();
$products  = $this->campaign_data->products;
$shippings = $this->campaign_data->shipping_info;
$cookies   = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );

if ( isset( $cookies->eDigitalOrderTotal ) ) {
	$edigital_products = array(
		'Skin'   => 'https://cdn.limelightcrm.com/SkinCover.jpg',
		'Brain'  => 'https://cdn.limelightcrm.com/BrainCover.jpg',
		'Muscle' => 'https://cdn.limelightcrm.com/MuscleCover.jpg',
		'Diet'   => 'https://cdn.limelightcrm.com/DietCover.jpg'
	);
	if ( strpos( $this->options['edigital_product_name'], 'Skin' ) ) {
		$edigital_product = 'Skin';
	} elseif ( strpos( $this->options['edigital_product_name'], 'Brain' ) ) {
		$edigital_product = 'Brain';
	} elseif ( strpos( $this->options['edigital_product_name'], 'Muscle' ) ) {
		$edigital_product = 'Muscle';
	} elseif ( strpos( $this->options['edigital_product_name'], 'Diet' ) ) {
		$edigital_product = 'Diet';
	}

	if ( array_key_exists( $edigital_product, $edigital_products ) ) {
		$edigital_img = $edigital_products[$edigital_product];
	}
}

if ( empty( $cookies->cart ) ) {
	$order_total_1  = $cookies->orderTotal;
	$order_total_2  = ( isset( $cookies->upsellOrderTotal1 )  ? $cookies->upsellOrderTotal1  : '' );
	$order_total_3  = ( isset( $cookies->upsellOrderTotal2 )  ? $cookies->upsellOrderTotal2  : '' );
	$order_total_4  = ( isset( $cookies->upsellOrderTotal3 )  ? $cookies->upsellOrderTotal3  : '' );
	$order_total_5  = ( isset( $cookies->edigitalOrderTotal ) ? $cookies->edigitalOrderTotal : '' );
	$combined_total = number_format( $order_total_1 + $order_total_2 + $order_total_3 + $order_total_4 + $order_total_5, 2);
	$product_1      = $cookies->productId;
	$product_2      = ( isset( $cookies->upsellProductId1 )  ? $cookies->upsellProductId1  : '' );
	$product_3      = ( isset( $cookies->upsellProductId2 )  ? $cookies->upsellProductId2  : '' );
	$product_4      = ( isset( $cookies->upsellProductId3 )  ? $cookies->upsellProductId3  : '' );
	$product_5      = ( isset( $cookies->edigitalProductId ) ? $cookies->edigitalProductId : '' );
	$shipping_1     = $cookies->shippingId;
	$shipping_2     = ( isset( $cookies->upsellShippingId1 )  ? $cookies->upsellShippingId1  : '' );
	$shipping_3     = ( isset( $cookies->upsellShippingId2 )  ? $cookies->upsellShippingId2  : '' );
	$shipping_4     = ( isset( $cookies->upsellShippingId3 )  ? $cookies->upsellShippingId3  : '' );

	foreach ( $products as $product ) {
		if ( $product->product_id == $product_1 ) {
			$product_1_name  = $product->product_name;
			$product_1_price = $product->product_price;
		}

		if ( $product->product_id == $product_2 ) {
			$product_2_name  = $product->product_name;
			$product_2_price = $product->product_price;
		}

		if ( $product->product_id == $product_3 ) {
			$product_3_name  = $product->product_name;
			$product_3_price = $product->product_price;
		}

		if ( $product->product_id == $product_4 ) {
			$product_4_name  = $product->product_name;
			$product_4_price = $product->product_price;
		}
	}

	foreach ( $shippings as $shipping ) {
		if ( $shipping->shipping_id == $shipping_1 ) {
			$shipping_1_price = $shipping->shipping_initial_price;
			$shipping_1_name  = $shipping->shipping_name;
		}
		if ( $shipping->shipping_id == $shipping_2 ) {
			$shipping_2_price = $shipping->shipping_initial_price;
			$shipping_2_name  = $shipping->shipping_name;
		}
		if ( $shipping->shipping_id == $shipping_3 ) {
			$shipping_3_price = $shipping->shipping_initial_price;
			$shipping_3_name  = $shipping->shipping_name;
		}
		if ( $shipping->shipping_id == $shipping_4 ) {
			$shipping_4_price = $shipping->shipping_initial_price;
			$shipping_4_name  = $shipping->shipping_name;
		}		

	}

	$html = <<<HTML
		<div name="order-info-summary" class="order-info-summary" id="js-order-info-summary">
		{$echo_java}
			<table>
				<th>Description</th>
				<th>Shipping</th>
				<th>Total</th>
				<tr>
					<td>
						{$product_1_name} ({$product_1_price})
					</td>
					<td>
						{$shipping_1_name} ({$shipping_1_price})
					</td>
					<td>
						$ {$order_total_1}
					</td>
				</tr>
HTML;

	if ( ! empty( $cookies->upsellOrderTotal1 ) ) {
		$html .= <<<HTML
			<tr>
				<td>
					{$product_2_name} ({$product_2_price})
				</td>
				<td>
					{$shipping_2_name} ({$shipping_2_price})
				</td>
				<td>
					$ {$order_total_2}
				</td>
			</tr>
HTML;
	}

	if ( ! empty( $cookies->upsellOrderTotal2 ) ) {
		$html .= <<<HTML
			<tr>
				<td>
					{$product_3_name} ({$product_3_price})
				</td>
				<td>
					{$shipping_3_name} ({$shipping_3_price})
				</td>
				<td>
					$ {$order_total_3}
				</td>
			</tr>
HTML;
	}

	if ( ! empty( $cookies->upsellOrderTotal3 ) ) {
		$html .= <<<HTML
			<tr>
				<td>
					{$product_4_name} ({$product_4_price})
				</td>
				<td>
					{$shipping_4_name} ({$shipping_4_price})
				</td>
				<td>
					$ {$order_total_4}
				</td>
			</tr>
HTML;
	}

	if ( ! empty( $cookies->edigitalOrderTotal ) ) {
		$html .= <<<HTML
			<tr>
				<td>
					<span class="limelight-span-product-photo"><img src="{$edigital_img}" /></span>
					Health & Fitness Ebook<br>
					Delivered to the email address you provided.<br>
					Please note the $1.97 charge is billed separately.
				</td>
				<td>
					$ {$cookies->edigitalOrderTotal}
				</td>
			</tr>
HTML;
	}

	$html .= <<<HTML
			<tr>
				<td>
					Grand Total:
				</td>
				<td>
				</td>
				<td>
					$ {$combined_total}
				</td>
			</tr>
			</table>
HTML;

} else {
	$order_info_products  = json_decode( $cookies->products, true );
	$main_order_total     = ( ! empty( $cookies->orderTotal ) ? $cookies->orderTotal : 0 );
	$edigital_order_total = ( ! empty( $cookies->edigitalOrderTotal ) ? $cookies->edigitalOrderTotal : 0 );
	$upsell_order_total_1 = ( ! empty( $cookies->upsellOrderTotal1 ) ? $cookies->upsellOrderTotal1 : 0 );
	$upsell_order_total_2 = ( ! empty( $cookies->upsellOrderTotal2 ) ? $cookies->upsellOrderTotal2 : 0 );
	$upsell_order_total_3 = ( ! empty( $cookies->upsellOrderTotal3 ) ? $cookies->upsellOrderTotal3 : 0 );

	$grand_total = ( isset( $upsell_order_total_1 ) ? number_format( $main_order_total + $edigital_order_total + $upsell_order_total_1 + $upsell_order_total_2 + $upsell_order_total_3, 2 ) : number_format( $main_order_total, 2 ) );

	$html        = <<<HTML
		<div name="order-info-summary" class="order-info-summary" id="js-order-info-summary">
		{$echo_java}
			<table>
				<th>Description</th>
				<th>Total</th>
HTML;

	foreach ( $order_info_products as $order_info_product ) {
		$order_product_total = number_format( $order_info_product['product_price'] * $order_info_product['product_quantity'], 2 );
		$html               .= <<<HTML
			<tr>
				<td>
					<!-- <span class="limelight-span-product-photo"><img src="" /></span> -->
					{$order_info_product['product_name']} ({$order_info_product['product_quantity']})
				</td>
				<td>
					$ {$order_product_total}
				</td>
			</tr>
HTML;
	}

	if ( ! empty( $cookies->upsellOrderTotal1 ) ) {
		$product_id = $cookies->upsellProductId1;

		foreach ( $products as $product ) {

			if ( $product_id == $product->product_id ) {
				$product_name = $product->product_name;
			}
		}

		$html .= <<<HTML
			<tr>
				<td>
					{$product_name}
				</td>
				<td>
					$ {$cookies->upsellOrderTotal1}
				</td>
			</tr>
HTML;
	}

	if ( ! empty( $cookies->upsellOrderTotal2 ) ) {
		$product_id = $cookies->upsellProductId2;

		foreach ( $products as $product ) {

			if ( $product_id == $product->product_id ) {
				$product_name = $product->product_name;
			}
		}

		$html .= <<<HTML
			<tr>
				<td>
					{$product_name}
				</td>
				<td>
					$ {$cookies->upsellOrderTotal2}
				</td>
			</tr>
HTML;
	}

	if ( ! empty( $cookies->upsellOrderTotal3 ) ) {
		$product_id = $cookies->upsellProductId3;

		foreach ( $products as $product ) {

			if ( $product_id == $product->product_id ) {
				$product_name = $product->product_name;
			}
		}

		$html .= <<<HTML
			<tr>
				<td>
					{$product_name}
				</td>
				<td>
					$ {$cookies->upsellOrderTotal3}
				</td>
			</tr>
HTML;
	}

	if ( ! empty( $cookies->edigitalOrderTotal ) ) {
		$html .= <<<HTML
			<tr>
				<td>
					<span class="limelight-span-product-photo"><img src="{$edigital_img}" /></span>
					Health & Fitness Ebook<br>
					Delivered to the email address you provided.<br>
					Please note the $1.97 charge is billed separately.
				</td>
				<td>
					$ {$cookies->edigitalOrderTotal}
				</td>
			</tr>
HTML;
	}

	foreach ( $shippings as $shipping ) {

		if ( $shipping->shipping_id == $cookies->shippingId ) {
			$shipping_price = $shipping->shipping_initial_price;
			$shipping_name  = $shipping->shipping_name;
		}
	}
		$html .= <<<HTML
			<tr>
				<td>
					Shipping & Handling
				</td>
				<td>
					$ {$shipping_price}
				</td>
			</tr>
			<tr>
				<td>
					Grand Total:
				</td>
				<td>
					$ {$grand_total}
				</td>
			</tr>
			</table>

HTML;

}

$html .= $this->get_google_analytic_script();
$html .= $this->get_google_ecommerce_script() . '</div>';

echo $html;

function update_thank_you() {
	echo '
		<script type="text/javascript">

			sessionStorage.limelightThankYou = 1;

			jQuery( document ).ready( function( $ ) {
				$.ajax( {
					type : "POST",
					url : "",
					success : function( response ) {
						 var data   = $( "#js-order-info-summary", response ),
						 	update = data.html();

						 $( "#js-order-info-summary" ).html( "" );
						 $( "#js-order-info-summary" ).html( update );
					}
				} );
			} );
		</script>
	';
}

add_action( 'wp_footer', 'update_thank_you' );

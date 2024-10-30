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

class Limelight_Admin extends Limelight {

	public
		$upsell_products = array(),
		$error_codes     = array();

	private
		$admin_tpl;

	public function __construct() {

		parent::__construct();

		$this->admin_build_tpl();

		$this->error_codes = $this->create_error_codes_array();

		if ( ! empty( $this->options['campaign_id'] ) ) {
			$this->upsell_products = $this->api_get_campaign_products( $this->options['campaign_id'] );
		}

		add_action( 'admin_menu', array( $this, 'limelight_add_menu' ) );
		add_action( 'admin_init', array( $this, 'limelight_init' ) );
		add_action( 'admin_init', array( $this, 'limelight_store' ) );
		add_action( 'admin_init', array( $this, 'limelight_errors' ) );
	}

	public function limelight_add_menu() {

		add_menu_page(
			__( 'LimeLight CRM' ),
			__( 'LimeLight CRM' ),
			__( 'manage_options' ),
			__( 'LimeLight CRM' ),
			array( $this, 'display' ),
			plugin_dir_url( __FILE__ ) . 'assets/icon.png'
		);

		add_submenu_page(
			__( 'LimeLight CRM' ),
			__( 'Error Responses' ),
			__( 'Error Responses' ),
			__( 'manage_options' ),
			__( 'Error Responses' ),
			array( $this, 'display' )
		);

		add_submenu_page(
			__( 'LimeLight CRM' ),
			__( 'Store Settings' ),
			__( 'Store Settings' ),
			__( 'manage_options' ),
			__( 'Store Settings' ),
			array( $this, 'display' )
		);
	}

	public function display() {

		include( 'admin/index.php' );

	}

	public function limelight_store() {
		add_settings_section(
			'limelight_store_info',
			'Store Info',
			'',
			'limelight_store_settings'
		);

		add_settings_field(
			'store_name',
			'Shop Name',
			array( $this, 'display_tpl' ),
			'limelight_store_settings',
			'limelight_store_info',
			array(
				$this->admin_tpl['field_store_name'],
				array(
					'<!--SHOPNAME-->' => $this->store_info['store_name'],
				)
			)
		);

		add_settings_field(
			'store_toll_free',
			'Toll Free',
			array( $this, 'display_tpl' ),
			'limelight_store_settings',
			'limelight_store_info',
			array(
				$this->admin_tpl['field_store_toll_free'],
				array(
					'<!--SHOPTOLLFREE-->' => $this->store_info['store_toll_free'],
				)
			)
		);

		add_settings_field(
			'store_address1',
			'Address 1',
			array( $this, 'display_tpl' ),
			'limelight_store_settings',
			'limelight_store_info',
			array(
				$this->admin_tpl['field_store_address1'],
				array(
					'<!--SHOPADDRESS1-->' => $this->store_info['store_address1'],
				)
			)
		);

		add_settings_field(
			'store_address2',
			'Address 2',
			array( $this, 'display_tpl' ),
			'limelight_store_settings',
			'limelight_store_info',
			array(
				$this->admin_tpl['field_store_address2'],
				array(
					'<!--SHOPADDRESS2-->' => $this->store_info['store_address2'],
				)
			)
		);

		add_settings_field(
			'store_city',
			'City',
			array( $this, 'display_tpl' ),
			'limelight_store_settings',
			'limelight_store_info',
			array(
				$this->admin_tpl['field_store_city'],
				array(
					'<!--SHOPCITY-->' => $this->store_info['store_city'],
				)
			)
		);

		add_settings_field(
			'store_state',
			'State',
			array( $this, 'display_tpl' ),
			'limelight_store_settings',
			'limelight_store_info',
			array(
				$this->admin_tpl['field_store_state'],
				array(
					'<!--SHOPSTATE-->' => $this->store_info['store_state'],
				)
			)
		);

		add_settings_field(
			'store_zip',
			'Zip',
			array( $this, 'display_tpl' ),
			'limelight_store_settings',
			'limelight_store_info',
			array(
				$this->admin_tpl['field_store_zip'],
				array(
					'<!--SHOPZIP-->' => $this->store_info['store_zip'],
				)
			)
		);

		register_setting(
			'limelight_store_group',
			'limelight_store_options',
			array( $this, 'sanitize' )
		);

	}

	public function limelight_errors() {

		add_settings_section(
			'error_responses_config',
			'Error Response Config',
			'',
			'limelight_error_responses'
		);

		$errors = array(
			101, 123, 200, 201, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316, 317, 318, 319, 320, 321, 322, 323, 324, 325, 326, 327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340, 341, 342, 400, 411, 412, 413, 414, 415, 600, 666, 667, 668, 669, 700, 705, 800, 900, 901, 902, 1000, 1001, 1002,
		);

		foreach ( $errors as $error )
		{
			$eid   = 'error_' . $error;
			$title = 'Error Code ' . $error;

			add_settings_field(
				$eid,
				$title,
				array( $this, 'display_tpl' ),
				'limelight_error_responses',
				'error_responses_config',
				array(
					$this->admin_tpl['field_error_code'],
					array(
						'<!--VALUE-->'      => $this->error_codes[$eid],
						'<!--ERROR_CODE-->' => $eid
					)
				)
			);
		}

		register_setting(
			'limelight_error_group',
			'limelight_error_options',
			array( $this, 'sanitize' )
		);

	}

	public function limelight_init() {

		add_settings_section(
			'limelight_settings',
			'Credentials',
			'',
			'Limelight_Admin'
		);

		add_settings_field(
			'api_user_name',
			'API User Name',
			array( $this, 'display_tpl' ),
			'Limelight_Admin',
			'limelight_settings',
			array(
				$this->admin_tpl['field_username'],
				array(
					'<!--USERNAME-->' => $this->options['api_user_name'],
				)
			)
		);

		add_settings_field(
			'api_password',
			'API Password',
			array( $this, 'display_tpl' ),
			'Limelight_Admin',
			'limelight_settings',
			array(
				$this->admin_tpl['field_password'],
				array(
					'<!--PASSWORD-->' => $this->options['api_password'],
				)
			)
		);

		add_settings_field(
			'app_key',
			'App Key',
			array( $this, 'display_tpl' ),
			'Limelight_Admin',
			'limelight_settings',
			array(
				$this->admin_tpl['field_url'],
				array(
					'<!--APP_KEY-->' => $this->options['app_key'],
				)
			)
		);

		add_settings_field(
			'google_tracking_id',
			'Google Tracking ID',
			array( $this, 'display_tpl' ),
			'Limelight_Admin',
			'limelight_settings',
			array(
				$this->admin_tpl['field_google'],
				array(
					'<!--GOOGLE-->' => $this->options['google_tracking_id'],
				)
			)
		);

		add_settings_section(
			'limelight_campaigns',
			'Campaign Info',
			array( $this, 'check_creds_set' ),
			'Limelight_Admin'
		);

		add_settings_section(
			'limelight_value_adds',
			'Value Added Services',
			'',
			'Limelight_Admin'
		);

		register_setting(
			'limelight_admin_group',
			'limelight_options',
			array( $this, 'sanitize' )
		);
	}

	public function sanitize( $input ) {

		$edigital_product_id = ( isset( $input['edigital_product_id'] ) ) ? sanitize_text_field( $input['edigital_product_id'] ) : '';

		if ( ! empty( $edigital_product_id ) ) {
			$data = $this->api_get_product_info( $edigital_product_id );

			$input['edigital_product_sku']  = $data['product_sku'];
			$input['edigital_shipping_id']  = 1;
			$input['edigital_product_name'] = $data['product_name'];
		}

		return array_map( 'sanitize_text_field', $input );
	}

	public function check_creds_set() {

		if ( ! empty( $this->options['api_password'] ) && ! empty( $this->options['api_user_name'] ) && ! empty( $this->options['app_key'] ) ) {

			if ( $check_creds = $this->api_check_credentials() ) {
				echo $check_creds;
			} else {

				add_settings_field(
					'campaign_id',
					'Available Campaign(s)',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_campaigns',
					array(
						$this->admin_tpl['field_campaigns'],
						array(
							'<!--CAMPAIGNS-->' => $this->api_get_campaigns( 'main' ),
						)
					)
				);

				add_settings_field(
					'active_campaign_id',
					'Active Campaign: ',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_campaigns',
					array(
						$this->admin_tpl['field_campaign'],
						array(
							'<!--CAMPAIGN-->' => $this->options['campaign_id'],
						)
					)
				);

				add_settings_field(
					'upsell_product_id_1',
					'Upsell 1: ',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_campaigns',
					array(
						$this->admin_tpl['field_upsell1'],
						array(
							'<!--UPSELL1-->' => $this->get_upsell_products( '1' ),
						)
					)
				);

				add_settings_field(
					'upsell_product_id_2',
					'Upsell 2: ',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_campaigns',
					array(
						$this->admin_tpl['field_upsell2'],
						array(
							'<!--UPSELL2-->' => $this->get_upsell_products( '2' ),
						)
					)
				);

				add_settings_field(
					'upsell_product_id_3',
					'Upsell 3: ',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_campaigns',
					array(
						$this->admin_tpl['field_upsell3'],
						array(
							'<!--UPSELL3-->' => $this->get_upsell_products( '3' ),
						)
					)
				);

				add_settings_field(
					'group_upsells',
					'Group Upsells',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_campaigns',
					array(
						$this->admin_tpl['field_group_upsells'],
						array(
							'<!--GROUP_UPSELLS-->' => ( $this->options['group_upsells'] ? 'checked' : '' ),
						)
					)
				);				

				add_settings_field(
					'prospect_product',
					'New Prospect Product: ',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_campaigns',
					array(
						$this->admin_tpl['field_prospect_product'],
						array(
							'<!--PROSPECTPRODUCT-->' => $this->get_prospect_product(),
						)
					)
				);

				add_settings_field(
					'https_enabled',
					'Force Https',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_campaigns',
					array(
						$this->admin_tpl['field_https'],
						array(
							'<!--HTTPS-->' => ( $this->options['https_enabled'] ? 'checked' : '' ),
						)
					)
				);

				add_settings_field(
					'use_limelight_css',
					'Use LimeLight Styles',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_campaigns',
					array(
						$this->admin_tpl['field_css'],
						array(
							'<!--CSS-->' => ( $this->options['use_limelight_css'] ? 'checked' : '' ),
						)
					)
				);

				add_settings_field(
					'3d_verify_enabled',
					'3D Verify Enabled',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_value_adds',
					array(
						$this->admin_tpl['field_3dverify'],
						array(
							'<!--3DVERIFY-->' => ( $this->options['3d_verify_enabled'] ? 'checked' : '' )
						)
					)
				);

				add_settings_field(
					'3d_gateway_id',
					'3D Verify Gateway',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_value_adds',
					array(
						$this->admin_tpl['field_3dgateway'],
						array(
							'<!--3DGATEWAY-->' => $this->get_gateways(),
							'<!--CLASS3D-->'   => ( $this->options['3d_verify_enabled'] ? 'limelight-3d-is-active' : 'limelight-3d' ),
						)
					)
				);

				add_settings_field(
					'kount_enabled',
					'Kount Enabled',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_value_adds',
					array(
						$this->admin_tpl['field_kount'],
						array(
							'<!--KOUNT-->' => ( $this->options['kount_enabled'] ? 'checked' : '' )
						)
					)
				);

				add_settings_field(
					'edigital_enabled',
					'eDigital Enabled',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_value_adds',
					array(
						$this->admin_tpl['field_edigital'],
						array(
							'<!--EDIGITAL-->' => ( $this->options['edigital_enabled'] ? 'checked' : '' )
						)
					)
				);

				add_settings_field(
					'edigital_campaign_id',
					'eDigital Campaign',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_value_adds',
					array(
						$this->admin_tpl['field_edigital_campaign'],
						array(
							'<!--EDIGITALCAMPAIGN-->' => $this->api_get_campaigns( 'edigital' ),
							'<!--CLASSED-->'          => ( $this->options['edigital_enabled'] ? 'limelight-edgitial-is-active' : 'limelight-edigital' ),
						)
					)
				);

				add_settings_field(
					'edigital_product_id',
					'eDigital Product',
					array( $this, 'display_tpl' ),
					'Limelight_Admin',
					'limelight_value_adds',
					array(
						$this->admin_tpl['field_edigital_product'],
						array(
							'<!--EDIGITALPRODUCT-->' => $this->get_edigital_products(),
							'<!--CLASSED-->'         => ( $this->options['edigital_enabled'] ? 'limelight-edgitial-is-active' : 'limelight-edigital' ),
						)
					)
				);
			}
		}
	}

	private function build_get_gateways_params( $range_start, $range_end ) {
		$params = array(
			'method'     => 'gateway_view',
			'gateway_id' => implode(',', range( $range_start, $range_end ) ),
		);

		return $params;
	}

	public function get_gateways() {

		$html          = '';
		$gateways_data = array();
		$gateways      = array();
		$ranges        = array(
			'1'   => '300',
			'301' => '600',
			'601' => '900',
			'901' => '1000',
		);

		foreach ( $ranges as $range_start => $range_end ) {
			$params          = $this->build_get_gateways_params( $range_start, $range_end );
			$api_url         = $this->membership_api_url . http_build_query( $params );
			$gateway_data    = wp_remote_retrieve_body( wp_remote_get( $api_url ) );

			parse_str( $gateway_data, $output );

			if ( ! empty( $output['data'] ) ) {
				array_push( $gateways_data, $output['data'] );
			}
		}

		foreach ( $gateways_data as $data ) {
			$gateways = array_merge( $gateways, ( array ) json_decode( $data ) );
		}

		rsort( $gateways );

		$gateways_3d  = array(
			'Lime Light',
			'SALT Payments 2.0',
			'Network Merchant Inc',
			'Chargeback Guardian (Previous Version)',
			'Group ISO',
			'Maverick',
			'Maxx Merchants',
			'Durango Direct',
			'PayScout',
			'Inovio'
		); // These could change if having any issues here, verify that the names match.

		if ( $gateways ) {
			foreach ( $gateways as $gateway ) {

				if ( isset( $gateway->gateway_active ) && $gateway->gateway_active && in_array( $gateway->gateway_provider, $gateways_3d ) ) {
					$is_selected = ( ! empty( $this->options['3d_gateway_id'] ) && $gateway->gateway_id == $this->options['3d_gateway_id'] ? 'selected' : '' );
					$html       .= <<<HTML
						<option value = "{$gateway->gateway_id}" {$is_selected}>{$gateway->gateway_alias} ({$gateway->gateway_id})</option>
HTML;
				}
			}
		}
		return $html;
	}


	public function get_upsell_products( $upsell ) {

		$html     = '';
		$upsell_x = 'upsell_product_id_' . $upsell;

		foreach ( $this->upsell_products as $product ) {
			$is_selected  = ( isset( $this->options[$upsell_x] ) && $this->options[$upsell_x] == $product['product_id'] ? 'selected' : '' );
			$product_id   = $product['product_id'];
			$product_name = $product['product_name'];
			$html .= <<<HTML
				<option value="{$product_id}" $is_selected>{$product_name}</option>
HTML;
		}

		return $html;
	}

	public function get_prospect_product() {

		$html     = '';
		$products = $this->api_get_campaign_products( $this->options['campaign_id'] );

		foreach ( $products as $product ) {
			$is_selected  = ( isset( $this->options['prospect_product'] ) && $this->options['prospect_product'] == $product['product_id'] ? 'selected' : '' );
			$product_id   = $product['product_id'];
			$product_name = $product['product_name'];
			$html .= <<<HTML
				<option value="{$product_id}" $is_selected>{$product_name}</option>
HTML;
		}

		return $html;

	}

	public function api_get_campaigns( $type ) {

		$params = array(
			'method' => 'campaign_find_active'
		);

		$api_url       = $this->membership_api_url . http_build_query( $params );
		$api_response  = wp_remote_retrieve_body( wp_remote_get( $api_url ) );
		$raw_datas     = explode( '&', $api_response );
		$campaign_info = array();
		$campaigns     = array();
		$html          = '';

		foreach ( $raw_datas as $raw_data ) {
			$data  = explode( '=', $raw_data );
			$key   = $data[0];
			$value = $data[1];

			$campaign_info[$key] = $value;
		}

		if ( ! empty( $campaign_info ) && $campaign_info['response'] == '100' ) {

			$campaign_names = explode( ',' , $campaign_info['campaign_name'] );

			foreach ( $campaign_names as $campaign_name => $value ) {
				$campaign_names[$campaign_name] = urldecode( $value );
			}

			$campaign_ids = explode( ',', $campaign_info['campaign_id'] );
			$campaigns    = array_reverse( array_combine( $campaign_names, $campaign_ids ) );

		}

		foreach ( $campaigns as $campaign => $key ) {

			$is_selected = '';

			if ( $type == 'edigital' ) {
				$is_selected = ( $this->options['edigital_enabled'] && $key == $this->options['edigital_campaign_id'] ? 'selected' : '' );
			} elseif ( $type = 'main' ) {
				$is_selected = ( ! empty( $this->options['campaign_id'] ) && $key == $this->options['campaign_id'] ? 'selected' : '' );
			}

			$html .= <<<HTML
				<option value="{$key}" {$is_selected} >{$campaign} ({$key})</option>
HTML;
		}

			return $html;
	}

	public function get_edigital_products() {

		$html        = '';
		$campaign_id = ( $this->options['edigital_enabled'] ? $this->options['edigital_campaign_id'] : '' );

		if ( ! empty( $campaign_id ) ) {
			$params = array(
				'campaign_id' => $campaign_id,
				'method'      => 'campaign_view'
			);

			$api_url       = $this->membership_api_url . http_build_query( $params );
			$edigital_data = wp_remote_retrieve_body( wp_remote_get( $api_url ) );

			parse_str( $edigital_data, $output );

			if ( ! empty( $output['product_id'] ) ) {
				$products    = array();
				$product_ids = explode( ',', $output['product_id'] );

				foreach ( $product_ids as $product_id ) {
					$product               = $this->api_get_product_info( $product_id );
					$product['product_id'] = $product_id;

					array_push( $products, $product );
				}

				foreach ( $products as $product ) {
					$is_selected = ( ! empty( $this->options['edigital_product_id'] ) && $product['product_id'] == $this->options['edigital_product_id'] ? 'selected' : '' );
					$html       .= <<<HTML
						<option value="{$product['product_id']}" {$is_selected}>{$product['product_name']}</option>
HTML;
				}
			}
		}

		return $html;
	}

	public function api_check_credentials() {
		$html   = '';
		$params = array(
			'method' => 'validate_credentials'
		);

		$api_url           = $this->membership_api_url . http_build_query( $params );
		$check_credentials = wp_remote_retrieve_body( wp_remote_get( $api_url ) );

		if ( $check_credentials == '200' ) {
			$html = <<<HTML
			<br>
			<p class="limelight-form-error-is-active">ERROR getting campaigns. Please check your API User Name, Password and URL and try again.</p>
HTML;
		}

		return $html;
	}

	public function api_get_campaign_data() {

		if ( ! empty( $this->options['campaign_id'] ) ) {
			$params = array(
				'campaign_id' => $this->options['campaign_id'],
				'method'      => 'campaign_view'
			);

			$api_url       = $this->membership_api_url . http_build_query( $params );
			$campaign_data = wp_remote_retrieve_body( wp_remote_get( $api_url ) );

			parse_str( $campaign_data, $output );

			if ( $output['response_code'] == '100' ) {
				$config        = $output;
				$product_ids   = explode( ',', $config['product_id'] );
				$product_infos = array();

				foreach ( $product_ids as $product_id ) {
					$product_info = $this->api_get_product_info( $product_id );
					array_push( $product_infos, $product_info );
				}

				$product_names = explode( ',', $config['product_name'] );
				$products      = array();
				$upsell_ids    = array( $this->options['upsell_product_id_1'], $this->options['upsell_product_id_2'], $this->options['upsell_product_id_3'] );

				foreach ( $product_ids as $product_id => $product ) {
					$products[$product_id] = array(
						'product_id'            => $product,
						'product_name'          => $product_names[$product_id],
						'product_price'         => $product_infos[$product_id]['product_price'],
						'product_sku'           => $product_infos[$product_id]['product_sku'],
						'product_max_quantity'  => $product_infos[$product_id]['product_max_quantity'],
						'product_has_rebill'    => $product_infos[$product_id]['product_has_rebill'],
						'product_rebill_price'  => $product_infos[$product_id]['product_rebill_price'],
						'product_category_name' => $product_infos[$product_id]['product_category_name'],
						'product_description'   => $product_infos[$product_id]['product_description'],
					);

					if ( in_array( $product, $upsell_ids ) ) {
						$products[$product_id]['is_upsell'] = 1;
					} else {
						$products[$product_id]['is_upsell'] = 0;
					}
				}

				$web_pages = array(
					'home_page',
					'blog_page',
					'new_prospect',
					'check_out',
					'thank_you',
					'single_page',
					'cart_page',
					'new_prospect_advanced',
					'single_page_advanced',
					'terms',
					'privacy',
					'contact',
					'my_account',
					'order_history',
					'order_details',
				);

				if ( ! empty( $this->options['upsell_product_id_1'] ) ) {
					$config['any_upsells'] = 1;
					$page_type             = 'upsell_page_1';
					array_push( $web_pages, $page_type );
				} else {
					$config['any_upsells'] = 0;
				}

				if ( ! empty( $this->options['upsell_product_id_2'] ) ) {
					$page_type = 'upsell_page_2';
					array_push( $web_pages, $page_type );
				}

				if ( ! empty( $this->options['upsell_product_id_3'] ) ) {
					$page_type = 'upsell_page_3';
					array_push( $web_pages, $page_type );
				}

				$config['web_pages'] = $web_pages;

				$shipping_ids              = explode( ',', $config['shipping_id'] );
				$shipping_names            = explode( ',', $config['shipping_name'] );
				$shipping_descriptions     = explode( ',', $config['shipping_description'] );
				$shipping_initial_prices   = explode( ',', $config['shipping_initial_price'] );
				$shipping_recurring_prices = explode( ',', $config['shipping_recurring_price'] );
				$shipping_info             = array();

				foreach ( $shipping_ids as $shipping_id => $shipping_data ) {
					$shipping_info[$shipping_id] = array(
						'shipping_id'               => $shipping_data,
						'shipping_name'             => $shipping_names[$shipping_id],
						'shipping_description'      => $shipping_descriptions[$shipping_id],
						'shipping_initial_price'    => $shipping_initial_prices[$shipping_id],
						'shipping_recurring_prices' => $shipping_recurring_prices [$shipping_id],
					);
				}

				$config['shipping_info'] = $shipping_info;

				if ( $current_pages = json_decode( get_option( 'limelight_campaign_data' ), true ) ) {

					foreach ( $current_pages['web_pages'] as $current_page ) {
						wp_delete_post( $current_page['page_id'] );
					}
				}

				$pages = array();

				foreach ( ( array ) $config['web_pages'] as $page_type => $page ) {
					$pages[$page] = array( 'page_type' => $page );
				}

				foreach ( $products as $product ) {
					$page_type    = 'product_details_page';
					$page         = 'product_' . $product['product_id'];
					$pages[$page] = array( 'page_type' => $page_type );
				}

				foreach ( $pages as $page => $page_type ) {
					$pages[$page]['page_id'] = $this->add_page( $page );
					$pages[$page]['url']     = $this->limelight_get_url( $pages[$page]['page_id'] );
				}

				$config['web_pages'] = $pages;
				$get_product_pages   = array();

				foreach ( $products as $product ) {
					$product_page = 'product_' . $product['product_id'];

					foreach ( $config['web_pages'] as $web_page => $page ) {

						if ( $web_page == $product_page ) {
							$url                    = $page['url'];
							$product['product_url'] = $url;

							array_push( $get_product_pages, $product );
						}
					}
				}

				$config['products'] = $get_product_pages;

				$file = realpath( dirname(__FILE__) ) . '/assets/limelight_country_state_code_map.csv';
				$csv  = array_map( 'str_getcsv', file( $file ) );

				array_walk( $csv, function( &$a ) use ( $csv ) {
					$a = array_combine( $csv[0], $a );
				} );
				array_shift( $csv );

				$states              = array();
				$config['countries'] = explode( ',', $config['countries'] );
				$country_count       = count( $config['countries'] );

				if ( $country_count === 1 ) {

					foreach ( $config['countries'] as $config_country => $code ) {

						foreach ( $csv as $country ) {

							if ( $country['Abbreviation'] == $code ) {

								$state = array(
									'country_code' => $country['Abbreviation'],
									'state_name'   => $country['State'],
									'state_code'   => $country['State Code/Id']
								);

								array_push( $states, $state );
							}
						}
					}
				} else {

					$merica = array_search( 'US', $config->countries );

					if ( ! empty( $merica ) ) {

						unset( $config['countries'][$merica] );
						array_unshift( $config['countries'], 'US' );
					}

					foreach ( $config['countries'] as $config_country => $code ) {

						foreach ( $csv as $country ) {

							if ( $country['Abbreviation'] == $code ) {

								$state = array(
									'country_code' => $country['Abbreviation'],
									'state_name'   => $country['State'],
									'state_code'   => $country['State Code/Id']
								);

								array_push( $states, $state );
							}
						}
					}
				}

				$config['states'] = $states;

				update_option( 'limelight_campaign_data', json_encode( $config ) );
			}
		}
	}

	public function api_get_campaign_products( $campaign_id = 0 ) {

		$products = array();

		if ( $campaign_id ) {
			$params = array(
				'campaign_id' => $campaign_id,
				'method'      => 'campaign_view'
			);

			$api_url       = $this->membership_api_url . http_build_query( $params );
			$campaign_data = wp_remote_retrieve_body( wp_remote_get( $api_url ) );

			parse_str( $campaign_data, $output );

			$config        = ( object ) $output;
			$product_ids   = explode( ',', ( isset( $config->product_id ) ? $config->product_id : '' ) );
			$product_infos = array();

			foreach ( $product_ids as $product_id ) {
				$product_info = $this->api_get_product_info( $product_id );
				array_push( $product_infos, $product_info );
			}

			$product_prices         = array();
			$product_skus           = array();
			$product_max_quantities = array();
			$product_category_names = array();
			$product_descriptions   = array();

			foreach ( $product_infos as $product_info ) {
				$product_price         = ( isset( $product_info['product_price'] ) ? $product_info['product_price'] : '' );
				$product_sku           = ( isset( $product_info['product_sku'] ) ? $product_info['product_sku'] : '' );
				$product_max_quantity  = ( isset( $product_info['product_max_quantity'] ) ? $product_info['product_max_quantity'] : '' );
				$product_category_name = ( isset( $product_info['product_category_name'] ) ? $product_info['product_category_name'] : '' );
				$product_description   = ( isset( $product_info['product_description'] ) ? $product_info['product_description'] : '' );

				array_push( $product_prices, $product_price );
				array_push( $product_skus, $product_sku );
				array_push( $product_max_quantities, $product_max_quantity );
				array_push( $product_category_names, $product_category_name );
				array_push( $product_descriptions, $product_description );
			}

			$product_names = explode( ',', ( isset( $config->product_name ) ? $config->product_name : '' ) );

			foreach ( $product_ids as $product_id => $product ) {
				$products[$product_id] = array(
					'product_id'            => $product,
					'product_name'          => $product_names[$product_id],
					'product_price'         => $product_prices[$product_id],
					'product_sku'           => $product_skus[$product_id],
					'product_max_quantity'  => $product_max_quantities[$product_id],
					'product_category_name' => $product_category_names[$product_id],
					'product_description'   => $product_descriptions[$product_id],
				);
			}

			asort( $products );
		}

		return $products;
	}

	public function api_get_product_info( $product_id ) {

		$params = array(
			'product_id' => $product_id,
			'method'     => 'product_index'
		);

		$product_info = array();
		$api_url      = $this->membership_api_url . http_build_query( $params );
		$product_data = wp_remote_retrieve_body( wp_remote_get( $api_url ) );

		parse_str( $product_data, $output );

		if ( ! empty( $output ) ) {

			$has_rebill   = ( $output['product_rebill_product'] == 0 ? '0' : '1' );
			$rebill_price = '';

			if ( $has_rebill === 1 ) {

				$rebill_product = $output['product_rebill_product'];

				$rebill_params = array(
					'product_id' => $rebill_product,
					'method'     => 'product_index'
				);
				$rebill_api_url = $this->membership_api_url . http_build_query( $rebill_params );
				$rebill_data    = wp_remote_retrieve_body( wp_remote_get( $rebill_api_url ) );

				parse_str( $rebill_data, $response );

				$rebill_price = $response['product_price'];
			}

			$product_info = array(
				'product_price'         => $output['product_price'],
				'product_sku'           => $output['product_sku'],
				'product_max_quantity'  => $output['product_max_quantity'],
				'product_name'          => $output['product_name'],
				'product_category_name' => $output['product_category_name'],
				'product_description'   => $output['product_description'],
				'product_has_rebill'    => $has_rebill,
				'product_rebill_price'  => $rebill_price,
			);
		} else {
			wp_die( 'Something went wrong, please try again.' );
		}

		return $product_info;
	}

	public function add_page( $page_type ) {

		$pages     = $this->create_pages_array();
		$post_type = 'page';

		if ( ! array_key_exists( $page_type, $pages ) ) {
			$product_id   = str_replace( 'product_', '', $page_type );
			$products     = $this->api_get_campaign_products( $this->options['campaign_id'] );
			$product_name = '';

			foreach ( $products as $product ) {
				if ( $product['product_id'] == $product_id ) {
					$product_name          = $product['product_name'];
					$product_id            = $product['product_id'];
					$product_sku           = $product['product_sku'];
					$product_price         = $product['product_price'];
					$product_category_name = $product['product_category_name'];
					$product_description   = $product['product_description'];
				}
			}

			$title = $product_name;

			if( ! empty( $product_description ) ) {
				$content = $product_description;
			} else {
				$content = "This is an auto-generated Wordpress / Lime Light CRM product (Product ID: <u>{$product_id}</u> / SKU: <u>{$product_sku}</u>) <br><br><b>{$product_name}</b> was imported from your specified LL campaign #<b>{$this->options['campaign_id']}</b>. Edit this product's description to change this text. Add product image, styling, etc. The price of this product is <b>{$product_price}</b>. Enjoy! <br><br>";
			}
			$content .= '[product_details_page product_id="' . $product_id . '" text="Add to Cart"]';
			$post_type = 'products';

		} else {
			$page    = $pages[$page_type];
			$title   = $page['title'];
			$content = $page['content'];
		}

		if ( $page_type == 'upsell_page_1' ) {
			$page    = $pages[$page_type];
			$title   = $page['title'];
			$content = $page['content'];
			$content = str_replace('[upsell_products]', '[upsell_products product_id="' . $this->options['upsell_product_id_1'] . '"]', $content);
		} elseif ( $page_type == 'upsell_page_2' ) {
			$page    = $pages[$page_type];
			$title   = $page['title'];
			$content = $page['content'];
			$content = str_replace('[upsell_products]', '[upsell_products product_id="' . $this->options['upsell_product_id_2'] . '"]', $content);
		} elseif ( $page_type == 'upsell_page_3' ) {
			$page    = $pages[$page_type];
			$title   = $page['title'];
			$content = $page['content'];
			$content = str_replace('[upsell_products]', '[upsell_products product_id="' . $this->options['upsell_product_id_3'] . '"]', $content);
		}

		if ( ! empty( $this->options['edigital_enabled'] ) && $this->options['edigital_enabled'] && $page_type == 'check_out' ) {
			$content = '[check_out_page]<br>[return_to_cart text="Return to Cart"]<br>[check_out_summary]<br>[check_out_customer_info]<br>[check_out_billing_info]<br>[edigital_info]<br>[check_out_submit_button text="Continue"]<br>[/check_out_page]';
		}

		if ( ! empty( $this->options['edigital_enabled'] ) && $this->options['edigital_enabled'] && $page_type == 'single_page' ) {
			$content = '[single_page]<br>[single_page_products]<br>[single_page_shipping]<br>[single_page_customer_info]<br>[single_page_billing_info]<br>[edigital_info]<br>[single_page_button text="Continue"]<br>[/single_page]';
		}

		$_p = array(
			'post_title'     => $title,
			'post_content'   => $content,
			'post_status'    => 'publish',
			'post_type'      => $post_type,
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_category'  => array( 1 ),

		);

		$new_product = wp_insert_post( $_p );

		if ( $post_type == 'products' ) {

			add_post_meta( $new_product, 'product_id', $product_id );
			add_post_meta( $new_product, 'sku',        $product_sku );
			add_post_meta( $new_product, 'price',      $product_price );

			$cat_exist = get_cat_ID( 'Shop' );
			$cat_ids   = array();

			if ( $cat_exist == 0 ) {

				$shop_category = array(
				  'cat_name' => 'Shop',
				  'taxonomy' => 'category',
				);

				$cat_ids[] = wp_insert_category( $shop_category );

			} else {

				$cat_ids[] = $cat_exist;

			}

			$shop_category = array(
			  'cat_name'        => $product_category_name,
			  'taxonomy'        => 'category',
			  'category_parent' => $cat_ids[0],
			);

			$cat_ids[]          = wp_insert_category( $shop_category );
			$exist_sub_category = get_cat_ID( $product_category_name );

			if ( ! $exist_sub_category ) {

				$shop_category = array(
				  'cat_name'        => $product_category_name,
				  'taxonomy'        => 'category',
				  'category_parent' => $cat_ids[0],
				);

				$cat_ids[] = wp_insert_category( $shop_category );

			} else {

				$cat_ids[] = $exist_sub_category;
			}

			wp_set_object_terms( $new_product, $cat_ids, 'category' );

		}

		return $new_product;
	}


	protected function create_error_codes_array() {

		$return = array();
		$errors = array(
			101, 123, 200, 201, 303, 304, 305, 306, 307, 308, 309, 310, 311, 312, 313, 314, 315, 316, 317, 318, 319, 320, 321, 322, 323, 324, 325, 326, 327, 328, 329, 330, 331, 332, 333, 334, 335, 336, 337, 338, 339, 340, 341, 342, 400, 411, 412, 413, 414, 415, 600, 666, 667, 668, 669, 700, 705, 800, 900, 901, 902, 1000, 1001, 1002
		);

		foreach ( $errors as $error ) {
			$error_message = '';
			$key           = 'error_' . $error;

			if ( $current_errors = get_option( 'limelight_error_options' ) ) {
				foreach ( $current_errors as $current_error => $message ) {
					if ( $current_error == $key ) {
						$error_message = $message;
					}
				}
			}

			$return[$key] = $error_message;
		}

		return $return;
	}

	protected function create_pages_array() {

		return array(
			'home_page' => array(
				'content'   => '',
				'page_type' => 'home_page',
				'title'     => 'Home',
			),
			'blog_page' => array(
				'content'   => '',
				'page_type' => 'blog_page',
				'title'     => 'Blog',
			),
			'new_prospect' => array(
				'content'   => $this->admin_tpl['new_prospect_page'],
				'page_type' => 'new_prospect',
				'title'     => 'Product Details',
			),
			'upsell_page_1' => array(
				'content'   => $this->admin_tpl['upsell_page'],
				'page_type' => 'upsell_page',
				'title'     => 'Additional Offer 1',
			),
			'upsell_page_2' => array(
				'content'   =>  $this->admin_tpl['upsell_page'],
				'page_type' => 'upsell_page',
				'title'     => 'Additional Offer 2',
			),
			'upsell_page_3' => array(
				'content'   => $this->admin_tpl['upsell_page'],
				'page_type' => 'upsell_page',
				'title'     => 'Additional Offer 3',
			),
			'check_out' => array(
				'content'   => $this->admin_tpl['check_out_page'],
				'page_type' => 'check_out',
				'title'     => 'Check Out',
			),
			'thank_you' => array(
				'content'   => '[thank_you_page]',
				'page_type' => 'thank_you',
				'title'     => 'Thank You',
			),
			'single_page' => array(
				'content'   => $this->admin_tpl['single_page'],
				'page_type' => 'single_page',
				'title'     => 'One Click Checkout',
			),
			'cart_page' => array(
				'content'   => $this->admin_tpl['cart_page'],
				'page_type' => 'cart_page',
				'title'     => 'Cart',
			),
			'product_details_page' => array(
				'content'   => '[product_details_page]',
				'page_type' => 'product_details_page',
				'title'     => 'Product Details',
			),
			'single_page_advanced' => array(
				'content'   => $this->admin_tpl['single_page_advanced'],
				'title'     => 'Advanced One Click Checkout',
				'page_type' => 'single_page_advanced',
			),
			'new_prospect_advanced' => array(
				'content'   => $this->admin_tpl['new_prospect_advanced'],
				'page_type' => 'new_prospect_advanced',
				'title'     => 'Advanced Product Details',
			),
			'terms' => array(
				'content'   => $this->admin_tpl['terms_conditions'],
				'page_type' => 'terms',
				'title'     => 'Terms and Conditions',
			),
			'privacy' => array(
				'content'   => $this->admin_tpl['privacy_policy'],
				'page_type' => 'privacy',
				'title'     => 'Privacy Statement',
			),
			'contact' => array(
				'content'   => $this->admin_tpl['contact_info'],
				'page_type' => 'contact',
				'title'     => 'Contact Us',
			),
			'my_account' => array(
				'content'   => $this->admin_tpl['my_account'],
				'page_type' => 'my_account',
				'title'     => 'My Account',
			),
			'order_history' => array(
				'content'   => $this->admin_tpl['order_history'],
				'page_type' => 'order_history',
				'title'     => 'Order History',
			),
			'order_details' => array(
				'content'   => $this->admin_tpl['order_details'],
				'page_type' => 'order_details',
				'title'     => 'Order Details',
			),
		);
	}

	private function admin_build_tpl() {

		$this->admin_tpl['admin_index'] = <<<TPL
<div class="wrap">
	<h1 class="limelight-admin-header">LimeLight CRM Settings</h1>
		<p>Enter your credentials below and select the <span class="limelight-strong">Update Settings</span>.</p>
		<p>Settings will not be saved unless <span class="limelight-strong">Update Settings</span> is selected</p>
		<p>After doing so, you will be given the option to select the campaign that you would like to generate pages for.</p>
		<!--ERROR-->
		<form method="post" action="options.php" id="js-limelight-admin-form">
			<!--ADMIN_FIELDS-->
		</form>

		<div class="limelight-update-warning">
			<p>*Updating the settings will move any current pages attached to the campaign to the trash*</p>
			<p>*You can copy any styling from the pages in the trash to the new pages if needed.*</p>
			<p></p>
		</div>
		<!--WORKFLOW_PAGES-->
		<p></p><p>Copyright &copy; <!--COPYRIGHT_DATE--> Lime Light CRM, Inc.</p>
TPL;
		$this->admin_tpl['workflow_pages'] = <<<TPL
		<div class="wrap">
			<h1>Workflow Pages</h1>
			<p>Below you will see a list of starting points/pages for the different workflows.</p>
			<ul>
				<li><a href="<!--PRODUCTS_PAGE_URL-->" target="_blank">Products Page</a></li>
				<li><a href="<!--NEW_PROSPECT_URL-->" target="_blank">New Prospect Page</a></li>
				<li><a href="<!--SINGLE_PAGE_URL-->" target="_blank">One Click Checkout</a></li>
				<li><a href="<!--ADVANCED_NEW_PROSPECT_URL-->" target="_blank">Advanced New Prospect Page</a></li>
				<li><a href="<!--ADVANCED_SINGLE_PAGE_URL-->" target="_blank">Advanced One Click Checkout</a></li>
				<li><a href="<!--TERMS_URL-->" target="_blank">Terms & Conditions</a></li>
				<li><a href="<!--PRIVACY_URL-->" target="_blank">Privacy Statment</a></li>
				<li><a href="<!--CONTACT_URL-->" target="_blank">Contact Us</a></li>
				<li><a href="<!--MY_ACCOUNT-->" target="_blank">My Account</a></li>
				<li><a href="<!--ORDER_HISTORY-->" target="_blank">Order History</a></li>
				<li><a href="<!--ORDER_DETAILS-->" target="_blank">Order Details</a></li>
			</ul>
		</div>
TPL;

		$this->admin_tpl['admin_responses'] = <<<TPL
<div class="wrap">
	<h1 class="limelight-admin-header">Error Responses</h1>
		<p>Override your default error messages for various API response codes.</p>
		<p>Remember to hit <span class="limelight-strong">Update Settings</span> when you're finished. If these aren't set the default LimeLight responses will be used.</p>
		<p>Reference can be found on the <a target="_blank" href="https://help.limelightcrm.com/hc/en-us/articles/212809566-Transaction-API-Documentation">LimeLight Transaction API</a> documentation under the section <u>Appendix A – Response Codes and Meanings</u>.</p>
		<!--ERROR-->
</div>
<form method="post" action="options.php" id="js-limelight-admin-form">
	<!--ADMIN_FIELDS-->
</form>
TPL;
		$this->admin_tpl['admin_store'] = <<<TPL
<div class="wrap">
	<h1 class="limelight-admin-header">Store Settings</h1>
		<p>Enter you store information here to be shown throughout your site.</p>
		<p>These values will be used on pages like your <span class="limelight-strong">Terms & Conditions</span> or <span class="limelight-strong">Privacy Policy</span>.</p>
		<!--ERROR-->
</div>
<form method="post" action="options.php" id="js-limelight-admin-form">
	<!--ADMIN_FIELDS-->
</form>
TPL;
		$this->admin_tpl['field_username'] = <<<TPL
<input type='text' id='js-api-username' name='limelight_options[api_user_name]' value="<!--USERNAME-->" autocomplete="off">
<span id='js-api-username-error' class='limelight-form-error'>Please enter a valid User Name</span>
TPL;
		$this->admin_tpl['field_password'] = <<<TPL
<input type='password' id='js-api-password' name='limelight_options[api_password]' value="<!--PASSWORD-->" autocomplete="off">
<span id='js-api-password-error' class='limelight-form-error'>Please enter a valid password</span>
TPL;
		$this->admin_tpl['field_url'] = <<<TPL
<input type='text' id='js-app-key' name='limelight_options[app_key]' value="<!--APP_KEY-->">
<span id='js-app-key-error' class='limelight-form-error'>Please enter a valid App Key</span>
TPL;
		$this->admin_tpl['field_store_name'] = <<<TPL
<input type='text' id='js-api-shop-name' name='limelight_store_options[store_name]' value="<!--SHOPNAME-->">
<span id='js-api-shop-name-error' class='limelight-form-error'>Please enter a valid Shop Name</span>
TPL;
		$this->admin_tpl['field_store_toll_free'] = <<<TPL
<input type='text' id='js-api-shop-toll-free' name='limelight_store_options[store_toll_free]' value="<!--SHOPTOLLFREE-->">
<span id='js-api-shop-toll-free-error' class='limelight-form-error'>Please enter a valid Shop Name</span>
TPL;
		$this->admin_tpl['field_store_address1'] = <<<TPL
<input type='text' id='js-api-shop-address-1' name='limelight_store_options[store_address1]' value="<!--SHOPADDRESS1-->">
<span id='js-api-shop-address-1-error' class='limelight-form-error'>Please enter a valid Shop Name</span>
TPL;
		$this->admin_tpl['field_store_address2'] = <<<TPL
<input type='text' name='limelight_store_options[store_address2]' value="<!--SHOPADDRESS2-->">
TPL;
		$this->admin_tpl['field_store_city'] = <<<TPL
<input type='text' id='js-api-shop-city' name='limelight_store_options[store_city]' value="<!--SHOPCITY-->">
<span id='js-api-shop-city-error' class='limelight-form-error'>Please enter a valid City</span>
TPL;
		$this->admin_tpl['field_store_state'] = <<<TPL
<input type='text' id='js-api-shop-state' name='limelight_store_options[store_state]' value="<!--SHOPSTATE-->">
<span id='js-api-shop-state-error' class='limelight-form-error'>Please enter a valid State</span>
TPL;
		$this->admin_tpl['field_store_zip'] = <<<TPL
<input type='text' id='js-api-shop-zip' name='limelight_store_options[store_zip]' value="<!--SHOPZIP-->">
<span id='js-api-shop-zip-error' class='limelight-form-error'>Please enter a valid Zip</span>
TPL;
		$this->admin_tpl['field_google'] = <<<TPL
<input type='text' name='limelight_options[google_tracking_id]' value="<!--GOOGLE-->">
TPL;
		$this->admin_tpl['field_campaigns'] = <<<TPL
<select name="limelight_options[campaign_id]" id="js-campaign-id">
	<option value="">Please Select a Campaign</option>
	<!--CAMPAIGNS-->
</select>
<div id="js-campaign-loading" ></div>
TPL;
		$this->admin_tpl['field_campaign'] = <<<TPL
<p class="limelight-strong"><!--CAMPAIGN--></p>
TPL;
		$this->admin_tpl['field_upsell1'] = <<<TPL
<select name="limelight_options[upsell_product_id_1]" id="upsell_product_id_1">
	<option value="">Please Select a Product</option>
	<!--UPSELL1-->
</select>
TPL;
		$this->admin_tpl['field_upsell2'] = <<<TPL
<select name="limelight_options[upsell_product_id_2]" id="upsell_product_id_2">
	<option value="">Please Select a Product</option>
	<!--UPSELL2-->
</select>
TPL;
		$this->admin_tpl['field_upsell3'] = <<<TPL
<select name="limelight_options[upsell_product_id_3]" id="upsell_product_id_3">
	<option value="">Please Select a Product</option>
	<!--UPSELL3-->
</select>
TPL;
		$this->admin_tpl['field_group_upsells'] = <<<TPL
<input type="checkbox" name="limelight_options[group_upsells]" value="1" <!--GROUP_UPSELLS--> >
TPL;
		$this->admin_tpl['field_prospect_product'] = <<<TPL
<select name="limelight_options[prospect_product]" id="prospect_product">
	<option value="">Select New Prospect Product</option>
	<!--PROSPECTPRODUCT-->
</select>
TPL;
		$this->admin_tpl['field_https'] = <<<TPL
<input type="checkbox" name="limelight_options[https_enabled]" value="1" <!--HTTPS--> >
TPL;
		$this->admin_tpl['field_css'] = <<<TPL
<input type="checkbox" name="limelight_options[use_limelight_css]" value="1" <!--CSS--> >
TPL;
		$this->admin_tpl['field_3dverify'] = <<<TPL
<input type="checkbox" id="js-3d-enabled" name="limelight_options[3d_verify_enabled]" value="1" <!--3DVERIFY--> >
<span id="js-3d-verify-enabled-message"></span>
TPL;
		$this->admin_tpl['field_3dgateway'] = <<<TPL
<span id="js-3d-gateway-select" class="<!--CLASS3D-->">
<select name="limelight_options[3d_gateway_id]" id="js-3d-gateway-id">
	<option value="">Please Select a 3D Verify Enabled Gateway</option>
	<!--3DGATEWAY-->
</select>
</span>
<p id='js-3d-gateway-id-error' class='limelight-form-error'>Please select a 3D Verify Enabled Gateway or Disable 3D Verify</p>
TPL;
		$this->admin_tpl['field_kount'] = <<<TPL
<input type="checkbox" name="limelight_options[kount_enabled]" value="1" <!--KOUNT--> >
TPL;
		$this->admin_tpl['field_edigital'] = <<<TPL
<input type="checkbox" id="js-edigital-enabled" name="limelight_options[edigital_enabled]" value="1" <!--EDIGITAL--> >
<span id="js-edigital-enabled-message"></span>
TPL;
		$this->admin_tpl['field_edigital_campaign'] = <<<TPL
<span id="js-edigital-campaign-select" class="<!--CLASSED-->">
<select name="limelight_options[edigital_campaign_id]" id="js-edigital-campaign-id">
	<option value="">Please Select Your eDigital Campaign</option>
	<!--EDIGITALCAMPAIGN-->
</select>
</span>
<p id='js-edigital-campaign-id-error' class='limelight-form-error'>Please select a eDigital Campaign or Disable eDigital</p>
TPL;
		$this->admin_tpl['field_edigital_product'] = <<<TPL
<span id="js-edgitial-product-select" class="<!--CLASSED-->">
<select name="limelight_options[edigital_product_id]" id="js-edigital-product-id">
	<!--EDIGITALPRODUCT-->
</select>
</span>
<p id='js-edigital-product-id-error' class='limelight-form-error'>Please select a eDigital Product or Disable eDigital</p>
TPL;
		$this->admin_tpl['new_prospect_page'] = <<<TPL
[new_prospect_page]
[new_prospect_info_form]
[new_prospect_products]
[new_prospect_shipping]
[new_prospect_opt_in]
[new_prospect_button text="Continue"]
[/new_prospect_page]
TPL;
		$this->admin_tpl['upsell_page'] = <<<TPL
[upsell_page]
[upsell_products]
[upsell_no_thanks text="No Thanks"]
[upsell_submit_button text="Add to Order"]
[/upsell_page]
TPL;
		$this->admin_tpl['check_out_page'] = <<<TPL
[check_out_page]
[return_to_cart text="Return to Cart"]
[check_out_summary]
[check_out_customer_info]
[check_out_billing_info]
[check_out_opt_in]
[check_out_submit_button text="Check Out"]
[/check_out_page]
TPL;
		$this->admin_tpl['single_page'] = <<<TPL
[single_page]
[single_page_products]
[single_page_shipping]
[single_page_customer_info]
[single_page_billing_info]
[single_page_opt_in]
[single_page_button text="Check Out"]
[/single_page]
TPL;
		$this->admin_tpl['cart_page'] = <<<TPL
[cart_page]
[view_all_products text="View all Products"]
[cart_page_cart]
[cart_page_shipping]
[cart_page_button text="Check Out"]
[/cart_page]
TPL;
		$this->admin_tpl['single_page_advanced'] = <<<TPL
[single_page]
<div>
	Products: [single_page_products style="float: right; width: 75%"]
</div>
<br>
<div>
	Shipping: [single_page_shipping style="float: right; width: 75%"]
</div>
<hr>
<label>First Name</label>
[single_page_shipping_first_name class="bigInput"]
<br>
<label>Last Name</label>
[single_page_shipping_last_name class="bigInput"]
<br>
<label>Address</label>
[single_page_shipping_address1 class="bigInput"]
<br>
<label>Address2</label>
[single_page_shipping_address2 class="bigInput"]
<br>
<label>City</label>
[single_page_shipping_city class="bigInput"]
<br>
<label>State</label>
[single_page_shipping_state class="bigInput"]
<br>
<label>Zip</label>
[single_page_shipping_zip class="bigInput"]
<br>
<label>Country</label>
[single_page_shipping_country class="bigInput"]
<br>
<label>Phone</label>
[single_page_phone class="bigInput"]
<br>
<label>Email</label>
[single_page_email class="bigInput"]
<br>
<label>Shipping same as billing</label>
[shipping_same_as_billing class="form-check-input"]
<hr>
[single_page_billing_section]
<label>First Name</label>
[single_page_billing_first_name class="bigInput"]
<br>
<label>Last Name</label>
[single_page_billing_last_name class="bigInput"]
<br>
<label>Address</label>
[single_page_billing_address1 class="bigInput"]
<br>
<label>Address2</label>
[single_page_billing_address2 class="bigInput"]
<br>
<label>City</label>
[single_page_billing_city class="bigInput"]
<br>
<label>State</label>
[single_page_billing_state class="bigInput"]
<br>
<label>Zip</label>
[single_page_billing_zip class="bigInput"]
<br>
<label>Country</label>
[single_page_billing_country class="bigInput"]
<hr>
[/single_page_billing_section]
<br>
<label>Card Type</label>
[single_page_credit_card_type class="bigInput"]
<br>
<label>Card Number</label>
[single_page_credit_card_number class="bigInput"]
<br>
<label>CVV</label>
[single_page_credit_card_cvv class="bigInput"]
<br>
<label>Expiration Month</label>
[single_page_credit_card_month class="bigInput"]
<br>
<label>Expiration Year</label>
[single_page_credit_card_year class="bigInput"]
<br>
[single_page_opt_in]
[single_page_button text="Check Out"]
<br>
[/single_page]
<style>
	.bigInput{
		width: 100%;
	}
</style>
TPL;
		$this->admin_tpl['new_prospect_advanced'] = <<<TPL
[new_prospect_page]
<label> First Name: [new_prospect_first_name class="highlight-field"] </label>
<label> Last Name: [new_prospect_last_name class="highlight-field"] </label>
<label> Email: [new_prospect_email class="highlight-field"] </label>
<label class="float-left"> Phone: [new_prospect_phone class="highlight-field"] </label>
<label> Address: [new_prospect_address1 class="highlight-field"] </label>
<label> Address2: [new_prospect_address2 class="highlight-field"] </label>
<label> Zip: [new_prospect_zip class="highlight-field"] </label>
<label> City: [new_prospect_city class="highlight-field"] </label>
<label> State: [new_prospect_state class="highlight-field"] </label>
<label> Country: [new_prospect_country class="highlight-field"] </label>
[new_prospect_products]
[new_prospect_shipping]
[new_prospect_opt_in]
[new_prospect_button text="Continue"]
[/new_prospect_page]
<style>
	.highlight-field {
		background-color: lightyellow;
	}
	input, select {
		box-shadow: 1px 1px 4px #000; width:100%;
	}
</style>
TPL;
		$this->admin_tpl['terms_conditions'] = <<<TPL
<h3>1. Terms</h3>
<p>
	By accessing this web site, you are agreeing to be bound by these web site Terms and Conditions of Use, all applicable laws and regulations, and agree that you are responsible for compliance with any applicable local laws. If you do not agree with any of these terms, you are prohibited from using or accessing this site. The materials contained in this web site are protected by applicable copyright and trade mark law.
</p>
<h3>2. Use License</h3>
<p>
	Permission is granted to temporarily download one copy of the materials (information or software) on [store_name]\'s web site for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:<ol type="i"><li>modify or copy the materials;
</p>
<ol type="a">
	<li>
		use the materials for any commercial purpose, or for any public display (commercial or non-commercial);
	</li>
	<li>
		attempt to decompile or reverse engineer any software contained on [store_name]\'s web site;
	</li>
	<li>
		remove any copyright or other proprietary notations from the materials; or
	</li>
	<li>
		transfer the materials to another person or "mirror" the materials on any other server.
	</li>
	<li>This license shall automatically terminate if you violate any of these restrictions and may be terminated by [store_name] at any time. Upon terminating your viewing of these materials or upon the termination of this license, you must destroy any downloaded materials in your possession whether in electronic or printed format.
	</li>
</ol>
<h3>3. Disclaimer</h3>
<p>The materials on [store_name]\'s web site are provided "as is". [store_name] makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties, including without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights. Further, [store_name] does not warrant or make any representations concerning the accuracy, likely results, or reliability of the use of the materials on its Internet web site or otherwise relating to such materials or on any sites linked to this site.
</p>
<h3>4. Limitations</h3>
<p>
	In no event shall [store_name] or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption,) arising out of the use or inability to use the materials on [store_name]\'s Internet site, even if [store_name] or a [store_name] authorized representative has been notified orally or in writing of the possibility of such damage. Because some jurisdictions do not allow limitations on implied warranties, or limitations of liability for consequential or incidental damages, these limitations may not apply to you.
</p>
<h3>5. Revisions and Errata</h3>
<p>
	The materials appearing on [store_name]\'s web site could include technical, typographical, or photographic errors. [store_name] does not warrant that any of the materials on its web site are accurate, complete, or current. [store_name] may make changes to the materials contained on its web site at any time without notice. [store_name] does not, however, make any commitment to update the materials.
</p>
<h3>6. Links</h3>
<p>
	[store_name] has not reviewed all of the sites linked to its Internet web site and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by [store_name] of the site. Use of any such linked web site is at the user\'s own risk.
</p>
<h3>7. Site Terms of Use Modifications</h3>
<p>
	[store_name] may revise these terms of use for its web site at any time without notice. By using this web site you are agreeing to be bound by the then current version of these Terms and Conditions of Use.
</p>
<h3>8. Governing Law</h3>
<p>
	Any claim relating to [store_name]\'s web site shall be governed by the laws of the State of Florida without regard to its conflict of law provisions.
</p>
<p>
	General Terms and Conditions applicable to Use of a Web Site.
</p>
TPL;
		$this->admin_tpl['privacy_policy'] = <<<TPL
<p>
	Your privacy is very important to us. Accordingly, we have developed this Policy in order for you to understand how we collect, use, communicate and disclose and make use of personal information. The following outlines our privacy policy.
</p>
<ul>
	<li>
		Before or at the time of collecting personal information, we will identify the purposes for which information is being collected.
	</li>
	<li>
		We will collect and use of personal information solely with the objective of fulfilling those purposes specified by us and for other compatible purposes, unless we obtain the consent of the individual concerned or as required by law.
	</li>
	<li>
		We will only retain personal information as long as necessary for the fulfillment of those purposes.
	</li>
	<li>
		We will collect personal information by lawful and fair means and, where appropriate, with the knowledge or consent of the individual concerned.
	</li>
	<li>
		Personal data should be relevant to the purposes for which it is to be used, and, to the extent necessary for those purposes, should be accurate, complete, and up-to-date.
	</li>
	<li>
		We will protect personal information by reasonable security safeguards against loss or theft, as well as unauthorized access, disclosure, copying, use or modification.
	</li>
	<li>
		We will make readily available to customers information about our policies and practices relating to the management of personal information.
	</li>
</ul>
<p>
	We are committed to conducting our business in accordance with these principles in order to ensure that the confidentiality of personal information is protected and maintained.
</p>
TPL;
		$this->admin_tpl['contact_info'] = <<<TPL
<h2>Mailing Address</h2><h3>[store_name]</h3>[store_address1] [store_address2]<br/>[store_city], [store_state] [store_zip]<h2>Toll-Free</h2>[store_toll_free]
TPL;
		$this->admin_tpl['account_info'] = <<<TPL
<h2>Account Information Page Coming Soon</h2>
TPL;
		$this->admin_tpl['field_error_code'] = <<<TPL
<input type='text' name='limelight_error_options[<!--ERROR_CODE-->]' value="<!--VALUE-->">
TPL;
		$this->admin_tpl['my_account'] = <<<TPL
[my_account_page]
[my_account_form]
[my_account_button text="Update"]
[my_account_delete text="Delete My Account"]
[/my_account_page]
<br>
TPL;
		$this->admin_tpl['order_history'] = <<<TPL
[order_history_page]
[order_history_form]
[/order_history_page]
TPL;
		$this->admin_tpl['order_details'] = <<<TPL
[order_details_page]
[order_details_form]
[order_details_button text="Update Order"]
[/order_details_page]
TPL;
	}

}
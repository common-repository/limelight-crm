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

/**
 * @link       https://limelightcrm.com
 * @since      1.1.0
 * @package    Limelight
 * @subpackage Limelight/includes
 * @author     Lime Light CRM <admin@limelightcrm.com>
 */

require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-limelight-admin.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-limelight-error-response.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

class Limelight {

	const
		API_URL_TOKEN       = 'https://<APPKEY>.limelightcrm.com/admin/<API_TYPE>.php?username=<USERNAME>&password=<PASSWORD>&';

	public
		$version = '';

	protected
		$client_tpl,
		$membership_api_url = '',
		$transact_api_url   = '',
		$years              = array(),
		$months             = array(),
		$cards_types        = array(),
		$campaign_data      = array(),
		$error_codes        = array(),
		$options            = array(
			'api_user_name'         => '',
			'api_password'          => '',
			'app_key'               => '',
			'store_name'            => '',
			'store_toll_free'       => '',
			'store_address1'        => '',
			'store_address2'        => '',
			'store_city'            => '',
			'store_state'           => '',
			'store_zip'             => '',
			'google_tracking_id'    => '',
			'group_upsells'         => '',
			'campaign_id'           => '',
			'upsell_product_id_1'   => '',
			'upsell_product_id_2'   => '',
			'upsell_product_id_3'   => '',
			'https_enabled'         => '0',
			'use_limelight_css'     => '0',
			'3d_verify_enabled'     => '0',
			'3d_gateway_id'         => '',
			'kount_enabled'         => '0',
			'edigital_enabled'      => '0',
			'edigital_campaign_id'  => '',
			'edigital_product_id'   => '',
			'edigital_product_name' => '',
			'edigital_product_sku'  => '',
			'edigital_shipping_id'  => '',
		),
		$store_info         = array(
			'store_name'            => '',
			'store_toll_free'       => '',
			'store_address1'        => '',
			'store_address2'        => '',
			'store_city'            => '',
			'store_state'           => '',
			'store_zip'             => '',
		);


	public function __construct() {

		$this->client_build_tpl();
		$this->define_hooks();

		$this->build_properties();

		if ( ! $this instanceof Limelight_Admin ) {
			if ( is_admin() ) {
				// uncomment ini_set line when debugging
				// ini_set( 'display_errors', 1 );
				new Limelight_Admin;
			}


			add_action('wp_enqueue_scripts',               array( $this, 'limelight_scripts_and_styles' ) );
			add_action('admin_enqueue_scripts',            array( $this, 'limelight_scripts_and_styles' ) );
			add_action('admin_footer',                     array( $this, 'echo_admin_ajax' ) );

			add_action('wp_ajax_campaign_change',          array( $this, 'campaign_change' ) );
			add_action('wp_ajax_update_states',            array( $this, 'update_states' ));
			add_action('wp_ajax_nopriv_update_states',     array( $this, 'update_states' ) );

			add_action('wp_ajax_wpll_process_form',        array( $this, 'wpll_process_form' ) );
			add_action('wp_ajax_nopriv_wpll_process_form', array( $this, 'wpll_process_form' ) );

			add_action('wp_ajax_get_affiliates',           array( $this, 'get_affiliates' ) );
			add_action('wp_ajax_nopriv_get_affiliates',    array( $this, 'get_affiliates' ) );

			add_filter( 'login_redirect',                  array( $this, 'limelight_login_redirect') );
			add_action( 'init',                            array( $this, 'limelight_user_handling') );

			//define form options for Year
			$current_year = date( 'y' );

			for ( $x_count = 0; $x_count <= 20; $x_count++ ) {
				$year_index = $current_year + $x_count;

				$this->years[$year_index] = "20{$year_index}";
			}

			//define form options for Credit Card Type
			$credit_card_types = ( ! empty( $this->campaign_data ) ? explode( ',', $this->campaign_data->payment_name ) : [] );

			foreach ( $credit_card_types as $credit_card_type ) {

				switch ( $credit_card_type ) {
					case 'amex' :
						$this->cards_types[$credit_card_type] = 'American Express';
					break;

					case 'visa' :
						$this->cards_types[$credit_card_type] = 'Visa';
					break;

					case 'master' :
						$this->cards_types[$credit_card_type] = 'Master Card';
					break;

					case 'discover' :
						$this->cards_types[$credit_card_type] = 'Discover Card';
					break;
				}
			}

			//define form options for Month
			for ( $x_month = 1; $x_month <= 12; $x_month++ ) {
				$this->months[str_pad( $x_month, 2, "0", STR_PAD_LEFT )] = date( 'M', mktime( 0, 0, 0, $x_month, 10 ) );
			}
		}

		if ( ! $this->version ) {
			$this->set_version();
		}
	}

	public function set_version() {
		$callers = debug_backtrace();

		foreach ( $callers as $caller ) {
			if ( $caller['function'] == 'include_once' )
			{
				$plugin_data   = get_plugin_data( $caller['args'][0] );
				$this->version = $plugin_data['Version'];
				break;
			}
		}
	}

	private function define_hooks() {

		$this->register_shortcodes();
	}

	protected function get_api_url ( $type = '' ) {

		$tokens = array(
			'<API_TYPE>' => $type,
			'<APPKEY>'   => $this->options['app_key'],
			'<USERNAME>' => $this->options['api_user_name'],
			'<PASSWORD>' => $this->options['api_password'],
		);

		return strtr( self::API_URL_TOKEN, $tokens );
	}

	public function build_properties() {

		if ( ! empty( get_option( 'limelight_options' ) ) ) {
			$this->campaign_data      = ( get_option( 'limelight_campaign_data' ) ? json_decode( get_option( 'limelight_campaign_data' ) ) : array() );
			$this->options            = array_merge( $this->options, get_option( 'limelight_options' ) );
			$this->transact_api_url   = $this->get_api_url( 'transact' );
			$this->membership_api_url = $this->get_api_url( 'membership' );
		}

		if ( ! empty( get_option( 'limelight_store_options' ) ) ) {
			$this->store_info = get_option( 'limelight_store_options' );
		}

		if ( ! empty( get_option( 'limelight_error_options' ) ) ) {
			$this->error_codes = get_option( 'limelight_error_options' );
		}
	}

	public function limelight_login_redirect() {
		$redirect     = site_url();
		$current_user = wp_get_current_user();

		if ( in_array( 'administrator', (array) $current_user->roles ) ) {
			$redirect = admin_url();
		} else {
			if ( ! empty( $this->campaign_data ) ) {
				$redirect = $this->limelight_get_url($this->campaign_data->web_pages->my_account->page_id);
			}
		}
		
		return $redirect;
	}

	public function limelight_scripts_and_styles() {

		wp_register_script( 'limelight-traffic-attribution-js', plugin_dir_url( __FILE__ ) . 'assets/limelight-traffic-attribution.min.js', array(), $this->version, true );
		wp_enqueue_script( 'limelight-traffic-attribution-js' );

		wp_register_script( 'limelight-main-js', plugin_dir_url( __FILE__ ) . 'assets/main.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'limelight-main-js' );

		wp_register_style( 'limelight-main-css', plugin_dir_url( __FILE__ ) . 'assets/main.css', array(), $this->version, false );
		wp_enqueue_style( 'limelight-main-css' );

	}

	function require_file( $filename ) {

		require realpath( dirname(__FILE__) ) . '/' . $filename;
	}

	protected function limelight_get_url ( $page_id ) {

		return ( $this->options['https_enabled'] == 1 ? str_replace( 'http://', 'https://', get_permalink( $page_id ) ) : get_permalink( $page_id ) );

	}

	protected function register_shortcodes() {

		add_shortcode( 'new_prospect_page',               array( $this, 'sc_new_prospect_page' ) );
		add_shortcode( 'new_prospect_info_form',          array( $this, 'sc_new_prospect_info_form' ) );
		add_shortcode( 'new_prospect_products',           array( $this, 'sc_new_prospect_products' ) );
		add_shortcode( 'new_prospect_shipping',           array( $this, 'sc_shipping' ) );
		add_shortcode( 'new_prospect_opt_in',             array( $this, 'sc_user_opt_in' ) );
		add_shortcode( 'new_prospect_button',             array( $this, 'sc_new_prospect_button' ) );

		//individual prospect input fields
		add_shortcode( 'new_prospect_first_name',         array( $this, 'build_input_from_shortcode') );
		add_shortcode( 'new_prospect_last_name',          array( $this, 'build_input_from_shortcode') );
		add_shortcode( 'new_prospect_address1',           array( $this, 'build_input_from_shortcode') );
		add_shortcode( 'new_prospect_address2',           array( $this, 'build_input_from_shortcode') );
		add_shortcode( 'new_prospect_city',               array( $this, 'build_input_from_shortcode') );
		add_shortcode( 'new_prospect_state',              array( $this, 'sc_new_prospect_state') );
		add_shortcode( 'new_prospect_zip',                array( $this, 'build_input_from_shortcode') );
		add_shortcode( 'new_prospect_country',            array( $this, 'sc_new_prospect_country') );
		add_shortcode( 'new_prospect_phone',              array( $this, 'build_input_from_shortcode') );
		add_shortcode( 'new_prospect_email',              array( $this, 'build_input_from_shortcode') );
		add_shortcode( 'new_selected_product',            array( $this, 'sc_new_prospect_product') );

		add_shortcode( 'upsell_page',                     array( $this, 'sc_upsell_page' ) );
		add_shortcode( 'upsell_products',                 array( $this, 'sc_upsell_products' ) );
		add_shortcode( 'upsell_no_thanks',                array( $this, 'sc_upsell_no_thanks_button' ) );
		add_shortcode( 'upsell_submit_button',            array( $this, 'sc_upsell_submit_button' ) );

		add_shortcode( 'check_out_page',                  array( $this, 'sc_check_out_page' ) );
		add_shortcode( 'check_out_summary',               array( $this, 'sc_check_out_summary' ) );
		add_shortcode( 'check_out_customer_info',         array( $this, 'sc_check_out_customer_info' ) );
		add_shortcode( 'check_out_billing_info',          array( $this, 'sc_check_out_billing_info' ) );
		add_shortcode( 'return_to_cart',                  array( $this, 'sc_check_out_return_to_cart' ) );
		add_shortcode( 'check_out_opt_in',                array( $this, 'sc_user_opt_in' ) );
		add_shortcode( 'check_out_submit_button',         array( $this, 'sc_check_out_submit_button' ) );

		add_shortcode( 'single_page',                     array( $this, 'sc_single_page' ) );
		add_shortcode( 'single_page_products',            array( $this, 'sc_single_page_products' ) );
		add_shortcode( 'single_page_shipping',            array( $this, 'sc_single_page_shipping' ) );
		add_shortcode( 'single_page_customer_info',       array( $this, 'sc_single_page_customer_info' ) );
		add_shortcode( 'single_page_billing_info',        array( $this, 'sc_single_page_billing_info' ) );
		add_shortcode( 'single_page_opt_in',              array( $this, 'sc_user_opt_in' ) );
		add_shortcode( 'single_page_button',              array( $this, 'sc_single_page_button' ) );
		add_shortcode( 'shipping_same_as_billing',        array( $this, 'sc_single_page_shipping_same_as_billing' ) );
		add_shortcode( 'single_page_billing_section',     array( $this, 'sc_single_page_billing_section' ) );

		//individual shipping fields
		add_shortcode( 'single_page_shipping_first_name', array( $this, 'sc_single_page_shipping_first_name' ) );
		add_shortcode( 'single_page_shipping_last_name',  array( $this, 'sc_single_page_shipping_last_name' ) );
		add_shortcode( 'single_page_shipping_address1',   array( $this, 'build_input_from_shortcode' ) );
		add_shortcode( 'single_page_shipping_address2',   array( $this, 'build_input_from_shortcode' ) );
		add_shortcode( 'single_page_shipping_city',       array( $this, 'build_input_from_shortcode' ) );
		add_shortcode( 'single_page_shipping_state',      array( $this, 'sc_single_page_shipping_state' ) );
		add_shortcode( 'single_page_shipping_zip',        array( $this, 'build_input_from_shortcode' ) );
		add_shortcode( 'single_page_shipping_country',    array( $this, 'sc_single_page_shipping_country' ) );
		add_shortcode( 'single_page_phone',               array( $this, 'build_input_from_shortcode' ) );
		add_shortcode( 'single_page_email',               array( $this, 'build_input_from_shortcode' ) );

		//individual billing fields
		add_shortcode( 'single_page_billing_first_name',  array( $this, 'build_input_from_shortcode' ) );
		add_shortcode( 'single_page_billing_last_name',   array( $this, 'build_input_from_shortcode' ) );
		add_shortcode( 'single_page_billing_address1',    array( $this, 'build_input_from_shortcode' ) );
		add_shortcode( 'single_page_billing_address2',    array( $this, 'build_input_from_shortcode' ) );
		add_shortcode( 'single_page_billing_city',        array( $this, 'build_input_from_shortcode' ) );
		add_shortcode( 'single_page_billing_state',       array( $this, 'sc_single_page_billing_state' ) );
		add_shortcode( 'single_page_billing_zip',         array( $this, 'build_input_from_shortcode' ) );
		add_shortcode( 'single_page_billing_country',     array( $this, 'sc_single_page_billing_country' ) );
		add_shortcode( 'single_page_credit_card_number',  array( $this, 'sc_single_page_credit_card_number' ) );
		add_shortcode( 'single_page_credit_card_type',    array( $this, 'sc_single_page_credit_card_type' ) );
		add_shortcode( 'single_page_credit_card_cvv',     array( $this, 'sc_single_page_credit_card_cvv' ) );
		add_shortcode( 'single_page_credit_card_month',   array( $this, 'sc_single_page_credit_card_month' ) );
		add_shortcode( 'single_page_credit_card_year',    array( $this, 'sc_single_page_credit_card_year' ) );

		add_shortcode( 'cart_page',                       array( $this, 'sc_cart_page' ) );
		add_shortcode( 'view_all_products',               array( $this, 'sc_cart_page_view_all' ) );
		add_shortcode( 'cart_page_cart',                  array( $this, 'sc_cart_page_cart' ) );
		add_shortcode( 'cart_page_shipping',              array( $this, 'sc_shipping' ) );
		add_shortcode( 'cart_page_button',                array( $this, 'sc_cart_page_button' ) );

		add_shortcode( 'thank_you_page',                  array( $this, 'sc_thank_you_page' ) );
		add_shortcode( 'product_details_page',            array( $this, 'sc_product_details_page' ) );
		add_shortcode( 'edigital_info',                   array( $this, 'sc_edigital' ) );

		add_shortcode( 'store_name',                      array( $this, 'sc_store_name' ) );
		add_shortcode( 'store_address1',                  array( $this, 'sc_store_address1' ) );
		add_shortcode( 'store_address2',                  array( $this, 'sc_store_address2' ) );
		add_shortcode( 'store_city',                      array( $this, 'sc_store_city' ) );
		add_shortcode( 'store_state',                     array( $this, 'sc_store_state' ) );
		add_shortcode( 'store_zip',                       array( $this, 'sc_store_zip' ) );
		add_shortcode( 'store_toll_free',                 array( $this, 'sc_store_toll_free' ) );

		add_shortcode( 'my_account_page',                 array( $this, 'sc_my_account_page' ) );
		add_shortcode( 'my_account_button',               array( $this, 'sc_my_account_button' ) );
		add_shortcode( 'my_account_delete',               array( $this, 'sc_my_account_delete' ) );
		add_shortcode( 'my_account_form',                 array( $this, 'sc_my_account_form' ) );

		add_shortcode( 'order_history_page',              array( $this, 'sc_order_history_page' ) );
		add_shortcode( 'order_history_button',            array( $this, 'sc_order_history_button' ) );
		add_shortcode( 'order_history_form',              array( $this, 'sc_order_history_form' ) );

		add_shortcode( 'order_details_page',              array( $this, 'sc_order_details_page' ) );
		add_shortcode( 'order_details_button',            array( $this, 'sc_order_details_button' ) );
		add_shortcode( 'order_details_form',              array( $this, 'sc_order_details_form' ) );
	}

	public function sc_edigital() {

		return <<<HTML
			<span>
				<input type="checkbox" value="1" name="edigital" checked>
				Yes!  Sign me up for the Health and Fitness program, only $1.97 monthly.
			</span>
HTML;
	}

	public function sc_new_prospect_page( $atts, $content = null ) {

		$this->require_file( 'pages/new_prospect.php' );

		$html = $this->fill_tpl(
			$this->client_tpl['limelight_forms'],
			array(
				'<!--CLASS-->' => ( $this->options['use_limelight_css'] == 1 ? 'limelight-form wpll-ajax' : 'new-prospect-form wpll-ajax' ),
				'<!--JS_ID-->' => 'js-new-prospect-form',
				)
			);

		$html .= do_shortcode( $content ) . '</form><div id="js-response-container"><div id="js-response"></div></div><div id="js-loading"></div>' . $this->get_google_analytic_script();

		return $html;

	}

	public function sc_my_account_page( $atts, $content = null ) {

		$user = wp_get_current_user();

		if ( ! in_array( 'administrator', (array) $user->roles ) ) {

			$this->require_file( 'pages/my_account.php' );

			$html = $this->fill_tpl(
				$this->client_tpl['limelight_forms'],
				array(
					'<!--CLASS-->' => ( $this->options['use_limelight_css'] == 1 ? 'limelight-form wpll-ajax' : 'my-account-form wpll-ajax' ),
					'<!--JS_ID-->' => 'js-my-account-form',
					)
				);

			$html .= do_shortcode( $content ) . '</form><div id="js-response-container"><div id="js-response"></div></div><div id="js-loading"></div>' . $this->get_google_analytic_script();

		} else {

			$html = 'You are logged in as an administrator. Try a subscriber account to view this page.';

		}


		return $html;

	}	

	public function sc_my_account_button( $atts ) {

		$class = ( $this->options['use_limelight_css'] == 1 ? 'limelight-button' : 'limelight-custom-button' );

		if ( isset( $atts['class'] ) ) {
			$atts['class'] .= " {$class}";
		} else {
			$atts['class'] = $class;
		}

		$html_attributes = $this->html_attributes_from_shortcode( $atts );
		$atts            = shortcode_atts( array(
			'text' => 'Continue',
			),
			$atts, 'my_account_page'
		);

		return <<<HTML
			<button type="submit" {$html_attributes}>{$atts['text']}</button>
HTML;

	}

	public function limelight_user_handling() {

		if ( isset( $_REQUEST['action'] ) &&  $_REQUEST['action'] == 'delete_my_account' ) {

			include( './wp-admin/includes/user.php' );

			wp_delete_user( intval( sanitize_text_field( $_REQUEST['user_id'] ) ) );
			wp_logout();
			wp_redirect( home_url() );

		}

	}

	public function sc_my_account_delete( $atts ) {

		$class   = ( $this->options['use_limelight_css'] == 1 ? 'limelight-button' : 'limelight-custom-button' );
		$user_id = get_current_user_id();

		if ( isset( $atts['class'] ) ) {
			$atts['class'] .= " {$class}";
		}
			$atts['class'] = $class;

		return <<<HTML
			<br>
			<input type="checkbox" id="confirm-delete">Delete My Account <button id="delete-account" value="{$user_id}" disabled>Delete</button>
HTML;

	}

	public function sc_order_history_page( $atts, $content = null ) {

		$user = wp_get_current_user();

		if ( ! in_array( 'administrator', (array) $user->roles ) ) {

			$this->require_file( 'pages/order_history.php' );

			$html = $this->fill_tpl(
				$this->client_tpl['limelight_forms'],
				array(
					'<!--CLASS-->' => ( $this->options['use_limelight_css'] == 1 ? 'limelight-form wpll-ajax' : 'order-history-form wpll-ajax' ),
					'<!--JS_ID-->' => 'js-order-history-form',
					)
				);

			$html .= do_shortcode( $content ) . '</form><div id="js-response-container"><div id="js-response"></div></div><div id="js-loading"></div>' . $this->get_google_analytic_script();

		} else {

			$html = 'You are logged in as an administrator. Try a subscriber account to view this page.';

		}

		return $html;

	}

	public function sc_order_history_button( $atts ) {

		$class = ( $this->options['use_limelight_css'] == 1 ? 'limelight-button' : 'limelight-custom-button' );

		if ( isset( $atts['class'] ) ) {
			$atts['class'] .= " {$class}";
		} else {
			$atts['class'] = $class;
		}

		$html_attributes = $this->html_attributes_from_shortcode( $atts );
		$atts            = shortcode_atts( array(
			'text' => 'Continue',
			),
			$atts, 'order_history_page'
		);

		return <<<HTML
			<button type="submit" {$html_attributes}>{$atts['text']}</button>
HTML;

	}

	public function sc_order_details_page( $atts, $content = null ) {

		$user = wp_get_current_user();

		if ( ! in_array( 'administrator', (array) $user->roles ) ) {

			$this->require_file( 'pages/order_details.php' );

			$html = $this->fill_tpl(
				$this->client_tpl['limelight_forms'],
				array(
					'<!--CLASS-->' => ( $this->options['use_limelight_css'] == 1 ? 'limelight-form wpll-ajax' : 'order-details-form wpll-ajax' ),
					'<!--JS_ID-->' => 'js-order-details-form',
					)
				);

			$html .= do_shortcode( $content ) . '</form><div id="js-response-container"><div id="js-response"></div></div><div id="js-loading"></div>' . $this->get_google_analytic_script();

		} else {

			$html = 'You are logged in as an administrator. Try a subscriber account to view this page.';

		}

		return $html;

	}

	public function sc_order_details_button( $atts ) {

		$class = ( $this->options['use_limelight_css'] == 1 ? 'limelight-button' : 'limelight-custom-button' );

		if ( isset( $atts['class'] ) ) {
			$atts['class'] .= " {$class}";
		} else {
			$atts['class'] = $class;
		}

		$html_attributes = $this->html_attributes_from_shortcode( $atts );
		$atts            = shortcode_atts( array(
			'text' => 'Continue',
			),
			$atts, 'order_details_page'
		);

		return <<<HTML
			<button type="submit" {$html_attributes}>{$atts['text']}</button>
HTML;

	}

	private function html_attributes_from_shortcode( $atts ) {

		$html           = "";
		$keys_to_remove = array(
			'text',
			'productId'
		);

		if ( is_array( $atts ) ) {

			foreach ( $keys_to_remove as $att_key ) {

				if ( isset( $atts[$att_key] ) ) {

					unset( $atts[$att_key] );

				}

			}

			foreach ( $atts as $att_name => $att_value ) {

				$html .= " {$att_name}='{$att_value}'";

			}

		} elseif ( is_string( $atts ) ) {

			$html = $atts;

		}

		return $html;

	}

	private function build_input( $field_name, $attributes ) {

		if ( ! isset( $attributes['type'] ) ) {
			$attributes['type'] = 'text';
		}

		$input_attributes = $this->html_attributes_from_shortcode( $attributes );

		return <<<HTML
			<input name='{$field_name}' {$input_attributes}>
HTML;

	}

	private function camelize_field_name_from_shortcode( $shortcode, $capitalize_first_char = false ) {

		$clean_prefixes = array(
			'new_prospect_',
			'single_page_',
			'check_out_',
			'upsell',
			'product_details_page_'
		);

		foreach ( $clean_prefixes as $clean_prefix ) {
			$shortcode = str_ireplace( $clean_prefix, '', $shortcode );
		}

		$shortcode = str_replace( ' ', '', ucwords( str_replace( '_', ' ', $shortcode ) ) );

		if ( ! $capitalize_first_char ) {
			$shortcode[0] = strtolower( $shortcode[0] );
		}

		return $shortcode;
	}

	public function build_input_from_shortcode( $atts, $content=null, $tag ) {

		$field_name = $this->camelize_field_name_from_shortcode( $tag );

		return $this->build_input( $field_name, $atts );

	}

	public function sc_new_prospect_state( $atts ) {

		$html            = array();
		$html_attributes = $this->html_attributes_from_shortcode( $atts );

		if ( count( $this->campaign_data->countries ) > 1 ) {

			$html[] = $this->fill_tpl( $this->client_tpl['new_prospect_states_multiple_countries'],
				array(
					'<!--ATTRIBUTES-->' => $html_attributes,
				)
			);
		} elseif ( count( $this->campaign_data->states ) > 1 && count( $this->campaign_data->countries ) == 1 ) {

			$states = array();

			foreach ( $this->campaign_data->states as $state ) {
				$states[] = $this->fill_tpl( $this->client_tpl['get_new_prospect_states'],
					array(
						'<!--VALUE-->' => $state->state_code,
						'<!--NAME-->'  => $state->state_name,
					)
				);
			}

			$html[] = $this->fill_tpl( $this->client_tpl['new_prospect_states_single_country'],
				array(
					'<!--ATTRIBUTES-->' => $html_attributes,
					'<!--STATES-->'     => implode( "\n", $states ),
				)
			);

		} elseif ( count( $this->campaign_data->states ) == 1 && count( $this->campaign_data->countries ) == 1 ) {

			$html[] = $this->fill_tpl( $this->client_tpl['new_prospect_state'],
				array(
					'<!--ATTRIBUTES-->' => $html_attributes,
				)
			);
		}

		return implode( "\n", $html );
	}

	public function sc_new_prospect_country( $atts ) {

		$html_attributes = $this->html_attributes_from_shortcode( $atts );
		$html[]          = "<select {$html_attributes} name='country' id='js-country'>";

		if ( count( $this->campaign_data->countries ) > 1 ) {
			$html[] = '<option>Select Your Country</option>';
		}

		foreach ( $this->campaign_data->countries as $country ) {
			$html[] = $this->fill_tpl( $this->client_tpl['get_new_prospect_countries'],
				array(
					'<!--COUNTRY-->' => $country,
				)
			);
		}

		$html[] = "</select>\n<div id='js-loading'></div>";

		return implode( "\n", $html );
	}

	public function sc_new_prospect_product( $atts ) {

		if ( ! isset( $atts['id'] ) ) {

			$html = $this->sc_new_prospect_products( $atts );

		} else {

			$html = $this->build_input( 'productId', array_merge( $atts, array( 'type' => 'hidden' ) ) );

		}

		return $html;
	}

	public function sc_new_prospect_info_form() {

		$states    = array();
		$countries = array();

		if ( count( $this->campaign_data->countries ) > 1 ) {

			$states[] = $this->fill_tpl( $this->client_tpl['new_prospect_states_multiple_countries'],
				array(
					'<!--ATTRIBUTES-->' => '',
				)
			);
		} elseif ( count( $this->campaign_data->states ) > 1 && count( $this->campaign_data->countries ) == 1 ) {

			$multiple_states = array();

			foreach ( $this->campaign_data->states as $state ) {
				$multiple_states[] = $this->fill_tpl( $this->client_tpl['get_new_prospect_states'],
					array(
						'<!--VALUE-->' => $state->state_code,
						'<!--NAME-->'  => $state->state_name,
					)
				);
			}

			$states[] = $this->fill_tpl( $this->client_tpl['new_prospect_states_single_country'],
				array(
					'<!--ATTRIBUTES-->' => '',
					'<!--STATES-->'     => implode( "\n", $multiple_states ),
				)
			);

		} elseif ( count( $this->campaign_data->states ) == 1 && count( $this->campaign_data->countries ) == 1 ) {

			$states[] = $this->fill_tpl( $this->client_tpl['new_prospect_state'],
				array(
					'<!--ATTRIBUTES-->' => '',
				)
			);
		}

		if ( count( $this->campaign_data->countries ) > 1 ) {
			$countries[] = '<option>Select Your Country</option>';
		}

		foreach ( $this->campaign_data->countries as $country ) {

			$countries[] = $this->fill_tpl( $this->client_tpl['get_new_prospect_countries'],
				array(
					'<!--COUNTRY-->' => $country,
				)
			);
		}

		$this->display_tpl( array(
			$this->client_tpl['new_prospect_info_form'],
				array(
					'<!--STATES-->'    => implode( "\n", $states ),
					'<!--COUNTRIES-->' => implode( "\n", $countries ),
				)
		) );

	}

	public function sc_my_account_form() {

		$order_history = ( ! empty ( $this->campaign_data ) ? $this->limelight_get_url( $this->campaign_data->web_pages->order_history->page_id ) : '' );
		$order_ids     = $this->get_subscriber_orders();
		$tokens        = array(
			'<!--ORDER_ID-->'         => '',
			'<!--ORDER_HISTORY-->'    => '',
			'<!--SHIPPING_FIRST-->'   => '',
			'<!--SHIPPING_LAST-->'    => '',
			'<!--CUSTOMER_EMAIL-->'   => '',
			'<!--CUSTOMER_PHONE-->'   => '',
			'<!--SHIPPING_ADDR1-->'   => '',
			'<!--SHIPPING_ADDR2-->'   => '',
			'<!--SHIPPING_CITY-->'    => '',
			'<!--SHIPPING_STATE-->'   => '',
			'<!--SHIPPING_ZIP-->'     => '',
			'<!--SHIPPING_COUNTRY-->' => '',
			'<!--BILLING_FIRST-->'    => '',
			'<!--BILLING_LAST-->'     => '',
			'<!--BILLING_ADDR1-->'    => '',
			'<!--BILLING_ADDR2-->'    => '',
			'<!--BILLING_CITY-->'     => '',
			'<!--BILLING_STATE-->'    => '',
			'<!--BILLING_ZIP-->'      => '',
			'<!--BILLING_COUNTRY-->'  => '',
		);

		if ( ! empty( $order_ids ) ) {
			$params     = array(
				'method'   => 'order_view',
				'order_id' => $order_ids[ 0 ],
			);

			$my_account = $this->api_membership( $params );
			$tokens     = array_merge( $tokens, array(
				'<!--ORDER_ID-->'         => $order_ids[ 0 ],
				'<!--ORDER_HISTORY-->'    => $order_history,
				'<!--SHIPPING_FIRST-->'   => $my_account[ 'shipping_first_name' ],
				'<!--SHIPPING_LAST-->'    => $my_account[ 'shipping_last_name' ],
				'<!--CUSTOMER_EMAIL-->'   => $my_account[ 'email_address' ],
				'<!--CUSTOMER_PHONE-->'   => $my_account[ 'customers_telephone' ],
				'<!--SHIPPING_ADDR1-->'   => $my_account[ 'shipping_street_address' ],
				'<!--SHIPPING_ADDR2-->'   => $my_account[ 'shipping_street_address2' ],
				'<!--SHIPPING_CITY-->'    => $my_account[ 'shipping_city' ],
				'<!--SHIPPING_STATE-->'   => $my_account[ 'shipping_state' ],
				'<!--SHIPPING_ZIP-->'     => $my_account[ 'shipping_postcode' ],
				'<!--SHIPPING_COUNTRY-->' => $my_account[ 'shipping_country' ],
				'<!--BILLING_FIRST-->'    => $my_account[ 'billing_first_name' ],
				'<!--BILLING_LAST-->'     => $my_account[ 'billing_last_name' ],
				'<!--BILLING_ADDR1-->'    => $my_account[ 'billing_street_address' ],
				'<!--BILLING_ADDR2-->'    => $my_account[ 'billing_street_address2' ],
				'<!--BILLING_CITY-->'     => $my_account[ 'billing_city' ],
				'<!--BILLING_STATE-->'    => $my_account[ 'billing_state' ],
				'<!--BILLING_ZIP-->'      => $my_account[ 'billing_postcode' ],
				'<!--BILLING_COUNTRY-->'  => $my_account[ 'billing_country' ],
			) );
		}

		$this->display_tpl( $this->client_tpl['my_account_form'], $tokens );
	}

	public function sc_order_history_form() {

		$this->display_tpl( array(
			$this->client_tpl['order_history_form'],
				array(
					'<!--ORDER_HISTORY-->' => $this->get_order_history_page(),
				)
			)
		);
	}
	
	public function sc_order_details_form() {

		$cookies         = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
		$details_page_id = $cookies->orderDetailsPage;
		$params          = array(
			'method'   => 'order_view',
			'order_id' => $details_page_id,
		);

		$order_details = $this->api_membership( $params );
		$line_items    = $this->get_line_items( $order_details['products'] );

		$this->display_tpl( array(
			$this->client_tpl['order_details_form'],
				array(
					'<!--ORDER_ID-->'         => $details_page_id,
					'<!--ORDER_DATE-->'       => $order_details['acquisition_date'],
					'<!--CUSTOMER_EMAIL-->'   => $order_details['email_address'],
					'<!--CUSTOMER_PHONE-->'   => $order_details['customers_telephone'],
					'<!--SHIPPING_FIRST-->'   => $order_details['shipping_first_name'],
					'<!--SHIPPING_LAST-->'    => $order_details['shipping_last_name'],
					'<!--SHIPPING_ADDR1-->'   => $order_details['shipping_street_address'],
					'<!--SHIPPING_ADDR2-->'   => $order_details['shipping_street_address2'],
					'<!--SHIPPING_CITY-->'    => $order_details['shipping_city'],
					'<!--SHIPPING_STATE-->'   => $order_details['shipping_state'],
					'<!--SHIPPING_ZIP-->'     => $order_details['shipping_postcode'],
					'<!--SHIPPING_COUNTRY-->' => $order_details['shipping_country'],
					'<!--BILLING_FIRST-->'    => $order_details['billing_first_name'],
					'<!--BILLING_LAST-->'     => $order_details['billing_last_name'],
					'<!--BILLING_ADDR1-->'    => $order_details['billing_street_address'],
					'<!--BILLING_ADDR2-->'    => $order_details['billing_street_address2'],		
					'<!--BILLING_CITY-->'     => $order_details['billing_city'],
					'<!--BILLING_STATE-->'    => $order_details['billing_state'],
					'<!--BILLING_ZIP-->'      => $order_details['billing_postcode'],
					'<!--BILLING_COUNTRY-->'  => $order_details['billing_country'],
					'<!--LINE_ITEMS-->'       => $line_items,
					'<!--ORDER_TOTAL-->'      => $order_details['order_total'],
				)
			) 
		);
	}

	public function get_line_items( $products ) {

		$html = '<tr class="limelight-list-item-title"><td colspan="2">Item</td><td colspan="2">Status</td><td>Qty</td><td>Price</td></tr>';

		foreach ( $products as $product ) {

			$html .= '<tr><td colspan="2">' . $product['name'] . ' <br><span class="limelight-order-sku">(' . $product['sku'] . ')</span>';

			if ( $product['is_recurring'] && ! $product['on_hold'] ) {
				$html .= '<td colspan="2"><span class="limelight-order-status"><b>ACTIVE</b><br>Rebills <u>' . $product['next_subscription_product'] . '</u> on <u>' . $product['recurring_date'] . '</u></span></td>';
			} else {
				$html .= '<td colspan="2"></td>';
			}

			$html .= '<td>' . $product['product_qty'] . '</td><td>$' . $product['price'] . '</td></tr>';

		}

		return $html;

	}

	public function get_subscriber_orders() {

		$order_ids = array();
		$params    = array(
			'method' => 'order_find',
				);

		$result    = $this->api_membership( $params );

		if ( isset( $result['order_ids'] ) ) {
			$order_ids = ( strpos( $result['order_ids'], ',' ) ? explode( ',', $result['order_ids'] ) : array( $result['order_ids'] ) );
		}

		return $order_ids;

	}

	public function get_order_history_pagination( $order_ids, $current_page, $per_page ) {

		$total_orders = count( $order_ids );
		$total_pages  = intval( $total_orders / $per_page );
		$html         = array();

		if ( $total_pages > 0 ) {

			$html[] = 'Page <select id="page_num" name="page_num" onclick="jQuery(\'#order_details_page\').val(0)">';

			for ( $i = 1; $i <= $total_pages; $i++ ) {
				if ( $current_page == $i ) {
					$selected = 'selected';
				} else {
					$selected = '';
				}

				$html[] = "<option value='{$i}' {$selected}>{$i}</option>";
			}

			$html[] = "</select> of {$total_pages} (showing {$per_page} orders per page)" . do_shortcode('[order_history_button text="go"]');

		}

		return implode( "\n", $html );

	}
	
	public function get_order_history_page() {
		
		$order_ids    = array_reverse( $this->get_subscriber_orders() );
		$cookies      = json_decode( stripslashes( $_COOKIE['limelight-data'] ), true );
		$per_page     = 3;
		$current_page = 1;

		if ( ! empty ( $cookies ) ) {
			$current_page = ( isset( $cookies['orderHistoryPage'] ) ? $cookies['orderHistoryPage'] : 1 );
		}

		$start = $per_page * ( $current_page - 1 );
		$html  = array();

		foreach ( array_slice( $order_ids, $start, $per_page ) as $order_id ) {

			$params = array(
				'method'   => 'order_view',
				'order_id' => $order_id,
			);

			$order = $this->api_membership( $params );

			$html[] = "<table><td colspan=\"3\" class=\"limelight-order-id\">#{$order_id}</td><td colspan=\"3\" class=\"limelight-order-date\">{$order['acquisition_date']}</td>" . $this->get_line_items( $order['products'] ) . "<tr><td colspan=\"6\" class=\"limelight-order-total\">Total: $ {$order['order_total']}</td></tr></table>";

		}

		$html[] = $this->get_order_history_pagination( $order_ids, $current_page, $per_page );

		return implode( "\n", $html );

	}

	public function sc_new_prospect_products( $atts ) {

		$products          = $this->campaign_data->products;
		$prospect_selected = ( isset( $this->options['prospect_product'] ) ? 'class="hidden-input-field"' : '' );
		$html_attributes   = $this->html_attributes_from_shortcode( $atts );
		$html              = "<label {$prospect_selected}> Products: <select name='productId' {$html_attributes}>";

		foreach ( $products as $product ) {
			if ( isset( $this->options['prospect_product'] ) && $this->options['prospect_product'] == $product->product_id ) {

				$html .= "<option value='{$product->product_id}' selected>{$product->product_name}</option>";

			} elseif ( $product->is_upsell == 0 && ! isset( $this->options['prospect_product'] ) ) {

				$html .= "<option value='{$product->product_id}'>{$product->product_name}</option>";

			}
		}

		$html .= '</select></label>';

		return $html;
	}

	public function sc_shipping( $atts ) {
		
		$shippings       = $this->campaign_data->shipping_info;
		$shipping_count  = ( count( $shippings ) == 1 ? 'class="hidden-input-field"' : '' );
		$html_attributes = $this->html_attributes_from_shortcode( $atts );
		$html            = "<label {$shipping_count}> Shipping: <select name='shippingId' {$html_attributes} >";	

		foreach ( $shippings as $shipping ) {
			$html .= <<<HTML
				<option value="{$shipping->shipping_id}"> {$shipping->shipping_name} ({$shipping->shipping_initial_price})</option>
HTML;
		}

		$html .= '</select></label>';

		return $html;
	}

	public function sc_new_prospect_button( $atts ) {

		$class = ( $this->options['use_limelight_css'] == 1 ? 'limelight-button' : 'limelight-custom-button' );

		if ( isset( $atts['class'] ) ) {
			$atts['class'] .= " {$class}";
		} else {
			$atts['class'] = $class;
		}

		$html_attributes = $this->html_attributes_from_shortcode( $atts );
		$atts            = shortcode_atts( array(
			'text' => 'Continue',
			),
			$atts, 'new_prospect_button'
		);

		return <<<HTML
			<button type="submit" {$html_attributes}>{$atts['text']}</button>
HTML;

	}

	public function sc_upsell_page( $atts, $content = null ) {

		$this->require_file( 'pages/upsell_page.php' );

		$html = $this->fill_tpl(
			$this->client_tpl['limelight_forms'],
			array(
				'<!--CLASS-->' => ( $this->options['use_limelight_css'] == 1 ? 'limelight-form wpll-ajax' : 'upsell-form wpll-ajax' ),
				'<!--JS_ID-->' => 'js-upsell-form',
				)
		);

		$html .= do_shortcode( $content ) . '</form><div id="js-response-container"><div id="js-response"></div></div>' . $this->get_google_analytic_script();

		return $html;

	}

	public function sc_upsell_products( $atts ) {

		$html_attributes = $this->html_attributes_from_shortcode( $atts );

		$atts = shortcode_atts( array(
			'product_id' => '',
			),
			$atts, 'upsell_product'
		);

		return <<<HTML
			<input type="hidden" name="productId" value="{$atts['product_id']}" {$html_attributes}>
HTML;

	}

	public function sc_upsell_no_thanks_button( $atts ) {

		$class = ( $this->options['use_limelight_css'] == 1 ? 'limelight-button' : 'limelight-custom-button' );

		if ( isset( $atts['class'] ) ) {
			$atts['class'] .= " {$class}";
		} else {
			$atts['class'] = $class;
		}

		$html_attributes = $this->html_attributes_from_shortcode( $atts );

		$atts = shortcode_atts( array(
			'text' => 'No Thanks',
			),
			$atts, 'upsell_no_thanks'
		);

		return <<<HTML
			<input type="hidden" value="" name="upsell-no-thanks" id="js-upsell-no-thanks">
			<button type="submit" id="js-upsell-no-thanks-button" {$html_attributes}>{$atts['text']}</button>
HTML;

	}

	public function sc_upsell_submit_button( $atts ) {

		$class = ( $this->options['use_limelight_css'] == 1 ? 'limelight-button' : 'limelight-custom-button' );

		if ( isset( $atts['class'] ) ) {
			$atts['class'] .= " {$class}";
		} else {
			$atts['class'] = $class;
		}

		$html_attributes = $this->html_attributes_from_shortcode( $atts );

		$atts = shortcode_atts( array(
			'text' => 'Add to Order',
			),
			$atts, 'upsell_submit_button'
		);

		return <<<HTML
			<button type="submit" {$html_attributes}>{$atts['text']}</button>
HTML;

	}

	public function sc_check_out_page( $atts, $content = null ) {

		$this->require_file( 'pages/check_out.php' );

		$html = $this->fill_tpl(
			$this->client_tpl['limelight_forms'],
			array(
				'<!--CLASS-->' => ( $this->options['use_limelight_css'] == 1 ? 'limelight-form wpll-ajax' : 'check-out-form wpll-ajax' ),
				'<!--JS_ID-->' => 'js-check-out-form',
				)
			);

		$html .= do_shortcode( $content ) . '</form><div id="js-response-container"><div id="js-response"></div></div><div id="js-loading"></div>' . $this->get_google_analytic_script();

		return $html;

	}

	public function sc_check_out_summary() {

		$html           = '';
		$shippings      = $this->campaign_data->shipping_info;
		$cookies        = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
		$shipping_price = 0;

		foreach ( $shippings as $shipping ) {

			if ( $shipping->shipping_id == $cookies->shippingId ) {
				$shipping_price = $shipping->shipping_initial_price;
				$html          .= '<script type="text/javascript">sessionStorage.limelightShippingPrice =  "' . $shipping_price . '";</script><input type="hidden" name="shippingId" value="' . $cookies->shippingId . '">';
			}
		}

		if ( isset( $cookies->cart ) && $cookies->cart == 1 ) {

			$temp_products = array();
			$products      = $this->campaign_data->products;

			foreach ( $products as $product ) {

				$product_qty_x = 'product_qty_' . $product->product_id;

				if ( ! empty( $cookies->$product_qty_x ) ) {
					$product_quantity    = $cookies->$product_qty_x;
					$product_total_price = $product->product_price * $cookies->$product_qty_x;
					$temp_product        = array(
						'product_id'           => $product->product_id,
						'product_price'        => $product->product_price,
						'product_quantity'     => $product_quantity,
						'product_total_price'  => $product_total_price,
						'product_name'         => $product->product_name,
						'product_sku'          => $product->product_sku,
						'product_has_rebill'   => $product->product_has_rebill,
						'product_rebill_price' => $product->product_rebill_price,
					);
					array_push( $temp_products, $temp_product );
				}

			}

			$java_products = json_encode( $temp_products );

			$html .= <<<EOT
				<script type="text/javascript">
					sessionStorage.limelightProducts = JSON.stringify($java_products);
				</script>

EOT;

			$cart_total = 0;
			$html      .= <<<HTML
				<div name="price_summary" id="js-price-summary">
					<table>
						<tr>
							<td><strong>Product</strong></td>
							<td><strong>Price</strong></td>
							<td><strong>Quantity</strong></td>
							<td><strong>Total</strong></td>
						</tr>
HTML;

			foreach ( $temp_products as $temp_product ) {
				$product_total_price = number_format( $temp_product['product_total_price'], 2 );
				$html               .= <<<HTML
					<tr>
						<td>{$temp_product['product_name']}</td>
						<td>{$temp_product['product_price']}</td>
						<td>{$temp_product['product_quantity']}</td>
						<td>$ {$product_total_price}</td>
					</tr>
HTML;
				$cart_total = $temp_product['product_total_price'] + $cart_total;
			}

			$cart_total = number_format( $shipping_price + $cart_total, 2 );
			$html      .= <<<HTML
				<tr>
					<td>Shipping: </td>
					<td></td>
					<td></td>
					<td>$ {$shipping_price}</td>
				</tr>
				<tr>
					<td><strong>Grand Total:</strong></td>
					<td></td>
					<td></td>
					<td><label>$ {$cart_total}</label></td>
				</tr>
				</table>
				</div>
HTML;
		} else {
			$products      = $this->campaign_data->products;
			$product_price = 0;

			foreach ( $products as $product ) {

				if ( $product->product_id == $cookies->productId ) {
					$product_price = $product->product_price;
				}
			}

			$total_price = number_format( $shipping_price + $product_price, 2 );
			$html       .= <<<HTML
				<div name="price_summary" id="js-price-summary">
					<table>
						<tr>
							<td>Price: </td>
							<td>$ {$product_price}</td>
						</tr>
						<tr>
							<td>Shipping: </td>
							<td>$ {$shipping_price}</td>
						</tr>
						<tr>
							<td>Total: </td>
							<td><strong>$ {$total_price}</strong></td>
						</tr>
					</table>
				</div>
HTML;
		}

		$html .= $this->echo_javascript();

		return $html;
	}

	public function sc_check_out_customer_info() {

		$countries = $this->campaign_data->countries;
		$states    = $this->campaign_data->states;
		$cookies   = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );

		$html    = ( $this->options['kount_enabled'] == 1 ? $this->echo_kount_data() : '' );

		if ( $this->options['3d_verify_enabled'] == 1 ) {
			$products  = $this->campaign_data->products;
			$shippings = $this->campaign_data->shipping_info;

			foreach ( $products as $product ) {

				if ( isset( $cookies->productId ) && $cookies->productId == $product->product_id ) {
					$product_amount = ( ! empty( $product->product_rebill_price ) ? $product->product_rebill_price : $product->product_price );
				}
			}

			foreach ( $shippings as $shipping ) {

				if ( $cookies->shippingId == $shipping->shipping_id ) {
					$shipping_amount = $shipping->shipping_initial_price;
				}
			}

			if ( isset( $cookies->cart ) && $cookies->cart == 1 ) {

				$amount = 0;

				foreach ( json_decode( stripslashes( $cookies->products ) ) as $product ) {
					$price  = ( ! empty( $product->product_rebill_price ) ? $product->product_rebill_price : $product->product_price );

					$amount = number_format( $price + $amount, 2 );
				}

			} else {
				$amount = $product_amount + $shipping_amount;
			}

			$x_relay_url = $this->limelight_get_url( $this->campaign_data->web_pages->check_out->page_id );

			$html .= <<<HTML
				<input type="hidden" name="3d_verify" value="1">
				<input type="hidden" name="x_amount" id="js-x-amount" value="{$amount}" data-threeds="amount">
HTML;
			if ( isset( $cookies->cart ) && $cookies->cart == 1 ) {
				$html .= <<<HTML
					<label>First Name</label>
						<input type="text" name="first_name">
					<label>Last Name</label>
						<input type="text" name="last_name">
HTML;
			} else {
				$html .= <<<HTML
					<input type="hidden" name="first_name" value="{$cookies->firstName}">
					<input type="hidden" name="last_name" value="{$cookies->lastName}">
HTML;
			}
		} else {

			if ( isset( $cookies->cart ) && $cookies->cart == 1 ) {
				$html .= <<<HTML
					<label>First Name</label>
						<input type="text" name="firstName" value="" >
					<label>Last Name</label>
						<input type="text" name="lastName" value="">
HTML;
			}
		}

		if ( isset( $cookies->cart ) && $cookies->cart == 1 ) {
			$html .= <<<HTML
				<label>Address</label>
					<input type="text" name="shippingAddress1" value="">
				<label>Address 2</label>
					<input type="text" value="" name="shippingAddress2" value="">
				<label>City</label>
					<input type="text" name="shippingCity" value="">
				<label>State</label>
HTML;

		if ( count( $countries ) > 1 ) {

			$html .= <<<HTML
				<select id="js-shipping-state" name="shippingState">
					<option>Please Select Your Country First</option>
				</select>
HTML;

		} elseif ( count( $states ) > 1 && count( $countries ) == 1 ) {

			$html .= <<<HTML
				<select id="js-shipping-state" name="shippingState">
					<option>Select Your State</option>
HTML;

			foreach ( $states as $state ) {

				$html .= <<<HTML
					<option value="{$state->state_code}">{$state->state_name}</option>
HTML;
			}

			$html .= '</select>';

		} elseif ( count( $states ) == 1 && count( $countries ) == 1 ) {

			$html .= <<<HTML
				<input type="text" name="shippingState" value="">
HTML;
		}

		$html .= <<<HTML
			<label>Zip</label>
				<input type="text" name="shippingZip" value="">
			<label>Country</label>
			<select name="shippingCountry" id="js-shipping-country">
HTML;

		if ( count( $countries ) > 1 ) {
			$html .= '<option>Select Your Country</option>';
		}

		foreach ( $countries as $country ) {

			$html .= <<<HTML
				<option value="{$country}">{$country}</option>
HTML;
		}

		$html .= <<<HTML
			</select>
			<label>Phone</label>
				<input type="text" name="phone" id="js-phone" value="">
			<label>Email</label>
				<input type="email" name="email" id="js-email" value="">
HTML;
		}

		return $html;
	}

	public function sc_check_out_billing_info() {

		$cookies = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
		$html    = '';

		if ( isset( $cookies->cart ) && $cookies->cart == 1 ) {
			$html .= <<<HTML
				<input type ="checkbox" name="billingSameAsShipping" id="js-billing-same-as-shipping" value="YES" checked="checked"><span class="limelight-strong">Billing Address Same As Shipping</span>
				<br>
				<br>
				<span id="js-billing-section">
				<!-- Intentionally left blank for JavaScript Insert -->
				</span>
HTML;
		}

		$html .= $this->echo_credit_card_form_fields();

		return $html;
	}

	public function sc_check_out_return_to_cart( $atts ) {

		$cart_url = $this->limelight_get_url( $this->campaign_data->web_pages->cart_page->page_id );
		$cookies  = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
		$html     = '';

		if ( isset( $cookies->cart ) && $cookies->cart == 1 ) {
			$atts = shortcode_atts( array(
				'text' => 'Return to Cart',
				),
				$atts, 'return_to_cart'
			);

			$html = '<a href="' . $cart_url . '">' . $atts['text'] . '</a>';
		}

		return $html;
	}

	public function sc_user_opt_in( $atts ) {

		$cookies = ( isset( $_COOKIE['limelight-data'] ) ? json_decode( stripslashes( $_COOKIE['limelight-data'] ) ) : '' );
		$html    = <<<HTML
			<input type="checkbox" name="opt_in" id="opt_in" value="1" checked="checked"><span class="limelight-strong">Create An Account / Log Me In</span>
			<br>
HTML;

		if ( $cookies && ! empty ( $cookies->prospectId ) ) {
			$html = '';
		}

		return $html;
	}


	public function sc_check_out_submit_button( $atts ) {
		$html  = '';
		$class = ( $this->options['use_limelight_css'] == 1 ? 'limelight-button' : 'limelight-custom-button' );
		$atts  = shortcode_atts( array(
			'text' => 'Check Out',
			),
			$atts, 'check_out_submit_button'
		);

		$html .= <<<HTML
			<button type="submit" class="{$class}">{$atts['text']}</button>
HTML;

		return $html;
	}

	public function sc_thank_you_page() {

		$this->require_file( 'pages/thank_you.php' );

	}

	public function sc_error_response() {

		return $this->error_codes['error_100'];

	}
	
	public function sc_store_name() {

		return $this->store_info['store_name'];

	}
	
	public function sc_store_toll_free() {

		return $this->store_info['store_toll_free'];

	}
	
	public function sc_store_address1() {

		return $this->store_info['store_address1'];

	}
	
	public function sc_store_address2() {

		return $this->store_info['store_address2'];

	}
	
	public function sc_store_city() {

		return $this->store_info['store_city'];

	}
	
	public function sc_store_state() {

		return $this->store_info['store_state'];

	}
	
	public function sc_store_zip() {

		return $this->store_info['store_zip'];

	}

	public function sc_single_page( $atts, $content = null ) {

		$this->require_file( 'pages/single_page.php' );

		$html = $this->fill_tpl(
			$this->client_tpl['limelight_forms'], array(
				'<!--CLASS-->' =>( $this->options['use_limelight_css'] == 1 ? 'limelight-form wpll-ajax' : 'one-click-check-out-form wpll-ajax' ),
				'<!--JS_ID-->' => 'js-one-click-check-out-form',
			)
		);

		if ( $this->options['3d_verify_enabled'] == 1 ) {
			$html .= $this->build_input( 'x_amount', array(
				'id'           => 'js-single-page-x-amount',
				'data-threeds' => 'amount',
				'type'         => 'hidden',
			) );

			$html .= $this->build_input( '3d_verify', array(
				'value' => 1,
				'type'  => 'hidden',
			) );
		}

		$html .= do_shortcode( $content ) . '</form><div id="js-response-container"><div id="js-response"></div></div>' . $this->get_google_analytic_script();
		$html .= $this->echo_single_page_javascript();

		return $html;

	}

	public function sc_single_page_billing_state( $atts ) {
		$atts['id']   = 'js-billing-state';
		$atts['name'] = 'billingState';

		return $this->sc_single_page_shipping_state( $atts );

	}

	public function sc_single_page_billing_country( $atts ) {

		return $this->sc_single_page_shipping_country( array_merge( $atts, array(
			'id'   => 'js-billing-country',
			'name' => 'billingCountry',
		) ) );

	}

	public function sc_single_page_shipping_same_as_billing( $atts ) {

		return $this->build_input( 'billingSameAsShipping', array_merge( array(
			'type'    => 'checkbox',
			'checked' => 'checked',
			'id'      => 'js-shipping-same-as-billing',
			'value'   => 'YES'
		), $atts ) );

	}

	public function sc_single_page_shipping_state( $atts ) {

		$html = '';

		if ( ! isset( $atts['id'] ) ) {
			$atts['id'] = 'js-shipping-state';
		}

		if ( ! isset( $atts['name'] ) ) {
			$atts['name'] = 'shippingState';
		}

		$html_attributes = $this->html_attributes_from_shortcode( $atts );

		if ( count( $this->campaign_data->countries ) > 1 ) {

			$html .= <<<HTML
				<select {$html_attributes}>
					<option value="">Please Select Your Country First</option>
				</select>
HTML;

		} elseif ( count( $this->campaign_data->states ) > 1 && count( $this->campaign_data->countries ) == 1 ) {

			$html .= <<<HTML
				<select {$html_attributes}>
					<option value="">Select Your State</option>
HTML;

			foreach ( $this->campaign_data->states as $state ) {

				$html .= <<<HTML
					<option value="{$state->state_code}">{$state->state_name}</option>
HTML;
			}

			$html .= '</select>';

		} elseif ( count( $this->campaign_data->states ) == 1 && count( $this->campaign_data->countries ) == 1 ) {

			$html .= <<<HTML
				<input type="text" name="shippingState" value="" {$html_attributes}>
HTML;
		}

		return $html;

	}

	public function sc_single_page_shipping_first_name( $atts ) {

		if ( $this->options['3d_verify_enabled'] == 1 ) {

			$html = $this->build_input( 'first_name', array_merge( array( 'id'   => 'js-single-page-first-name' ), $atts ) );

		} else {

			$html = $this->build_input( 'firstName', $atts );
		}

		return $html;

	}

	public function sc_single_page_shipping_last_name( $atts ) {

		if ( $this->options['3d_verify_enabled'] == 1 ) {
			$name = 'last_name';
		} else {
			$name = 'lastName';
		}

		return $this->build_input( $name, $atts );
	}

	public function sc_single_page_shipping_country( $atts ) {

		$html = '';

		if ( ! isset( $atts['id'] ) ) {
			$atts['id']='js-shipping-country';
		}

		if ( ! isset( $atts['name'] ) ) {
			$atts['name']='shippingCountry';
		}

		$html_attributes = $this->html_attributes_from_shortcode( $atts );
		$html           .= <<<HTML
				<select {$html_attributes}>
HTML;

		if ( count( $this->campaign_data->countries ) > 1 ) {
			$html .= '<option value="">Select Your Country</option>';
		}

		foreach ( $this->campaign_data->countries as $country ) {

			$html .= <<<HTML
				<option value="{$country}">{$country}</option>
HTML;
		}

		$html .= <<<HTML
			</select>
HTML;

		return $html;
	}

	public function sc_single_page_credit_card_type( $atts ) {

		$html_attributes = $this->html_attributes_from_shortcode( $atts );
		$cc_type_tokens  = array();

		foreach ( $this->cards_types as $cc_type => $cc_display_name ) {
			$cc_type_tokens[] = $this->fill_tpl( 
				$this->client_tpl['limelight_build_cc_types'],
				array(
					'<!--TYPE-->'    => $cc_type,
					'<!--DISPLAY-->' => $cc_display_name,
					)
				);
		}

		return $this->fill_tpl(
			$this->client_tpl['limelight_card_types'],
			array(
				'<!--LABEL-->'      => '',
				'<!--CC_TYPES-->'   => implode( "\n", $cc_type_tokens ),
				'<!--ATTRIBUTES-->' => $html_attributes,
				)
			);
	}

	public function sc_single_page_credit_card_number( $atts ) {

		if ( $this->options['3d_verify_enabled'] == 1 ) {

			$html = $this->build_input(
				'x_card_num',
				array_merge(
					array(
						'maxlength'    => '16',
						'data-threeds' => 'pan'
					),
					$atts
				)
			);

		} else {
			$html = $this->build_input( 'creditCardNumber', array_merge( array( 'maxlength' => '16' ), $atts) );
		}

		return $html;

	}

	public function sc_single_page_credit_card_cvv( $atts ) {

		if ( $this->options['3d_verify_enabled'] == 1 ) {
			return $this->build_input( 'x_cvv', $atts );
		} else {
			return $this->build_input( 'CVV', $atts );
		}

	}


	public function sc_single_page_customer_info() {

		$countries = $this->campaign_data->countries;
		$states    = $this->campaign_data->states;

		$html = ( $this->options['kount_enabled'] == 1 ? $this->echo_kount_data() : '' );

		if ( $this->options['3d_verify_enabled'] == 1 ) {

			$html .= $this->echo_single_page_javascript() . <<<HTML
				<input type="hidden" name="x_amount" id="js-single-page-x-amount" value="" data-threeds="amount">
				<input type="hidden" name="3d_verify" value="1">
				<label>First Name</label>
				<input type="text" id="js-single-page-first-name" name="first_name">
				<label>Last Name</label>
				<input type="text" name="last_name">
HTML;
		} else {
			$html .= <<<HTML
				<label>First Name</label>
				<input type="text" name="firstName" value="">
				<label>Last Name</label>
				<input type="text" name="lastName" value="">
HTML;
		}

		$html .= <<<HTML
				<label>Address</label>
				<input type="text" name="shippingAddress1" value="">
				
				<label>Address 2</label>
				<input type="text" name="shippingAddress2" value="" >
				
				<label>City</label>
				<input type="text" name="shippingCity" value="">
				
				<label>State</label>
HTML;

		if ( count( $states ) > 1 ) {

			if ( count( $countries ) > 1 ) {

				$html .= <<<HTML
					<select id="js-shipping-state" name="shippingState">
						<option>Please Select Your Country First</option>
					</select>
HTML;

			} elseif ( count( $countries ) == 1 ) {

				$html .= <<<HTML
					<select id="js-shipping-state" name="shippingState">
						<option>Select Your State</option>
HTML;

				foreach ( $states as $state ) {

					$html .= <<<HTML
						<option value="{$state->state_code}">{$state->state_name}</option>
HTML;
				}

				$html .= '</select>';

			}

		} elseif ( count( $states ) == 1 ) {

			$html .= <<<HTML
				<input type="text" name="shippingState" value="">
HTML;

		}

		$html .= <<<HTML
			<label>Zip</label>
				<input type="text" name="shippingZip" value="">
			<label>Country</label>
				<select name="shippingCountry" id="js-shipping-country">

HTML;

		if ( count( $countries ) > 1 ) {

			$html .= '<option>Select Your Country</option>';
		}

		foreach ( $countries as $country ) {

			$html .= <<<HTML
				<option value="{$country}">{$country}</option>
HTML;
		}

		$html .= <<<HTML
			</select>
			<label>Phone</label>
				<input type="text" name="phone" value="">
			<label>Email</label>
				<input type="text" name="email" value="">
HTML;

		return $html;

	}

	public function sc_single_page_billing_info() {

		$html = <<<HTML
			<br>
			<label>Billing Address Same As Shipping</label><input type ="checkbox" name="billingSameAsShipping" id="js-billing-same-as-shipping" value="YES" checked="checked">
			<br>
			<br>
			<span id="js-billing-section">
				<!-- Intentionally left blank for JavaScript Insert -->
			</span>
HTML;

		$html .= $this->echo_credit_card_form_fields();

		return $html;
	}

	public function sc_single_page_button( $atts ) {

		$class = ( $this->options['use_limelight_css'] == 1 ? 'limelight-button' : 'limelight-custom-button' );
		$atts  = shortcode_atts( array(
			'text' => 'Check Out',
			),
			$atts, 'single_page_button'
		);

		return <<<HTML
			<button type="submit" class="{$class}">{$atts['text']}</button>
HTML;

	}

	public function sc_single_page_products( $atts ) {

		$html_attributes = $this->html_attributes_from_shortcode( $atts );
		$javascript_id   = ( $this->options['3d_verify_enabled'] == 1 ? ' id="js-single-page-product-id"' : '' );
		$products        = $this->campaign_data->products;
		$html            = <<<HTML
			<select name="productId" {$javascript_id} {$html_attributes}>
HTML;

		foreach ( $products as $product ) {
			if ( $product->is_upsell == 0 )	{
				$html .= '<option value="' . $product->product_id . '">' . $product->product_name . '</option>';
			}
		}

		$html .= '</select>';

		return $html;
	}

	public function sc_single_page_shipping( $atts ) {
		$html_attributes = $this->html_attributes_from_shortcode( $atts );
		$shippings       = $this->campaign_data->shipping_info;
		$javascript_id   = ( $this->options['3d_verify_enabled'] == 1 ? ' id="js-single-page-shipping-id"' : '' );
		$html            = <<<HTML
			<select name="shippingId" {$javascript_id} {$html_attributes}>
HTML;

		foreach ( $shippings as $shipping ) {
			$html .= <<<HTML
				<option value="{$shipping->shipping_id}"> {$shipping->shipping_name} ({$shipping->shipping_initial_price})</option>
HTML;
		}

		$html .= '</select>';

		return $html;
	}

	public function sc_cart_page( $atts, $content = null ) {

		$this->require_file( 'pages/cart_page.php' );

		$html = $this->fill_tpl(
			$this->client_tpl['limelight_forms'],
			array(
				'<!--CLASS-->' =>( $this->options['use_limelight_css'] == 1 ? 'limelight-form wpll-ajax' : 'cart-form wpll-ajax' ),
				'<!--JS_ID-->' => 'js-cart-form',
				)
			);

		$html .= do_shortcode( $content ) . '</form><div id="js-response-container"><div id="js-response"></div></div>' . $this->get_google_analytic_script();

		return $html;

	}

	public function sc_cart_page_view_all( $atts ) {

		$atts = shortcode_atts( array(
			'text' => 'View All Products',
			),
			$atts, 'cart_page_view_all'
		);

		$catalog_url = get_post_type_archive_link( 'products' );
		$html        = '<a href="' . $catalog_url . '">' . $atts['text'] . '</a>';

		return $html;
	}

	public function sc_cart_page_cart() {

		$cookies = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
		$html    = <<<HTML
			<table name="products" id="js-cart-products">
				<tr>
					<td><strong>Product</strong></td>
					<td><strong>Price</strong></td>
					<td><strong>Quantity</strong></td>
					<td><strong>Total</strong></td>
				</tr>
HTML;
		$cart_total_label = array();
		$products         = $this->campaign_data->products;

		foreach ( $products as $product ) {
			$product_qty_x = 'product_qty_' . $product->product_id;
			$cookie_data_x = ( ! empty( $cookies->$product_qty_x ) ? $cookies->$product_qty_x : '' );

			if ( $cookie_data_x > 0 ) {
				$quantity_label = $cookie_data_x;
				$label_price    = number_format( $cookie_data_x * $product->product_price, 2 );
				$html          .= <<<HTML
					<tr>
						<td><a href="{$product->product_url}">{$product->product_name}</a></td>
						<td>$ {$product->product_price}</td>
						<td>
							<button type="button" name="remove-product" class="limelight-button-remove-product" id="js-button-remove-{$product->product_id}">-</button>
							<span id="js-{$product_qty_x}-label">{$quantity_label}</span>
							<button type="button" name="add-product" class="limelight-button-add-product" id="js-button-add-{$product->product_id}">+</button>
						</td>
						<td>
							<label id="js-{$product_qty_x}-total">$ {$label_price}</label>
						</td>
					</tr>
					<tr>
						<td align="center" colspan="4" id="js-product-div{$product->product_id}" name="product-div{$product->product_id}"></td>
					</tr>
					<input type="hidden" name="{$product_qty_x}" id="js-{$product_qty_x}" value="{$cookie_data_x}">
HTML;
				array_push( $cart_total_label, $label_price );
			}
		}

		$cart_total_label = number_format( array_sum( $cart_total_label ), 2 );
		$html            .= <<<HTML
			<tr>
				<td><strong>Grand Total:</strong></td>
				<td></td>
				<td></td>
				<td><label id="js-cart-total" name="cart-total">$ {$cart_total_label}</label></td>
			</tr>
			</table>
HTML;

		$html .= $this->echo_cart_javascript();

		return $html;
	}

	public function sc_cart_page_button( $atts ) {

		$class = ( $this->options['use_limelight_css'] == 1 ? 'limelight-button' : 'limelight-custom-button' );
		$atts  = shortcode_atts( array(
			'text' => 'Check Out',
			),
			$atts, 'cart_page_button'
		);

		return <<<HTML
			<input type="hidden" name="cart" value="1"><button type="submit" class="{$class}">{$atts['text']}</button>
HTML;

	}

	public function sc_product_details_page( $atts ) {

		$html         = $this->echo_javascript();
		$button_class = ( $this->options['use_limelight_css'] == 1 ? 'limelight-button' : 'limelight-custom-button' );
		$cookies      = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );

		$atts = shortcode_atts( array(
			'product_id' => '',
			'text'       => 'Add to Cart',
			),
			$atts, 'product_details'
		);

		$product_id    = $atts['product_id'];
		$text          = $atts['text'];
		$product_qty_x = 'product_qty_' . $product_id;
		$html         .= $this->fill_tpl(
			$this->client_tpl['limelight_forms'],
			array(
				'<!--CLASS-->' =>( $this->options['use_limelight_css'] == 1 ? "limelight-form wpll-ajax product-qty-{$product_id}" : "product-details-form wpll-ajax product-qty-{$product_id}" ),
				'<!--JS_ID-->' => 'js-product-details-form',
			)
		);
		$vars_to_js    = array(
			'ajaxUrl' => admin_url( 'admin-ajax.php', ( isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' ) ),
			'formId'  => 'js-product-details-form',
		);

		wp_localize_script( 'limelight-main-js', 'wpllParams', $vars_to_js );

		if ( isset( $cookies->$product_qty_x ) ) {
			$product_qty_x_value = $cookies->$product_qty_x + 1;
		} else {
			$product_qty_x_value = 1;
		}

		$html .= <<<HTML
			<input type="hidden" value="{$product_qty_x_value}" name="product_qty_{$product_id}">
HTML;

		$products = $this->campaign_data->products;

		foreach ( $products as $product ) {
			$other_product_qty_x = 'product_qty_' . $product->product_id;

			if ( isset( $cookies->$other_product_qty_x ) && $other_product_qty_x !== $product_qty_x ) {
				$html .= <<<HTML
					<input type="hidden" value ="{$cookies->$other_product_qty_x}" name ="{$other_product_qty_x}">
HTML;
			}
		}

		$html .= <<<HTML
			<button type="submit" class="{$button_class}" id="product-qty-{$product_id}-button">{$text}</button></form><div id="js-response-container"><div id="js-response"></div></div>
HTML;
		$html .= $this->get_google_analytic_script();

		$html .= <<<HTML
			<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				$.ajax( {
					type    : "POST",
					success : function( response) {
						var data = $( "#js-product-details-form", response );

						$( "#js-product-details-form" ).html( "" );
						$( "#js-product-details-form" ).html( data.html() );
					}
				} );
			} );
			</script>
HTML;

		if ( isset( $cookies->thankYou ) && $cookies->thankYou == 1 ) {
			$html = $this->echo_cookie_reset_script();
		}

		return $html;
	}

	public function fill_tpl( $tpl, $tokens = [] ) {
		return strtr( $tpl, $tokens );
	}

	public function display_tpl( $tpl, $tokens = array() ) {
		if ( is_array( $tpl ) ) {
			$tokens = ( ! empty( $tpl[1] ) ? ( array ) $tpl[1] : array() );
			$tpl    = ( string ) $tpl[0];
		}

		echo $this->fill_tpl( $tpl, $tokens );
	}

	public function membership_handling( $params, $redirect_to ) {

		if ( $params['order_history_page'] > 0 ) {

			$storage = array(
				'limelightOrderHistoryPage'    => $params['order_history_page'],
				'limelightOrderHistoryPerPage' => $params['order_history_per_page'],
			);

		} elseif ( $params['order_details_page'] > 0 ) {

			$storage = array(
				'limelightOrderDetailsPage' => $params['order_details_page'],
			);

		} else {

			$output = $this->api_membership( $params );
			
			if ( $output['response_code'] ) {
				$html = json_encode( $output ).json_encode( $params );
			}

		}

		return array(
			'storage'     => $storage,
			'redirect_to' => ( $html ? '' : $redirect_to ),
			'javascript'  => $this->echo_javascript(),
			'html'        => $html,
		);

	}

	public function api_membership( $params ) {

		$user_info   = get_userdata( get_current_user_id() );
		$customer_id = $user_info->user_login;

		if ( $params['method'] == 'order_find' ) {

			$params = array_merge( $params, array(
				'campaign_id' => $this->options['campaign_id'], 
				'start_date'  => date( 'm/d/Y', strtotime( '-1 day', strtotime(  $user_info->user_registered ) ) ),
				'end_date'    => date( 'm/d/Y' ),
				'criteria'    => "customer_id={$customer_id}",
			) );

		} elseif ( $params['method'] == 'order_view' ) {

			$params = array_merge( $params, array(
				'order_id' => $params['order_id'],
			));

		}

		$api_url      = $this->membership_api_url . http_build_query( $params );
		$api_response = wp_remote_retrieve_body( wp_remote_get( $api_url ) );
		parse_str( $api_response, $output );

		return $output;	

	}

	public function api_transact( $params, $redirect_to ) {

		$html            = '';
		$storage         = array();
		$notes_default   = "Created With Wordpress LimeLight CRM Version {$this->version}";
		$params['notes'] = ( $params['notes'] ? $params['notes'] . $notes_default : $notes_default );
		$api_url         = $this->transact_api_url . http_build_query( $params );
		$api_response    = wp_remote_retrieve_body( wp_remote_get( $api_url ) );

		parse_str( $api_response, $output );

		if ( $output['responseCode'] == '100' && $params['method'] == 'NewProspect' ) {
			$storage = array(
				'limelightProspectId' => $output['prospectId'],
				'limelightShippingId' => $params['shippingId'],
				'limelightProductId'  => $params['productId'],
				'limelightFirstName'  => $params['firstName'],
				'limelightLastName'   => $params['lastName'],
			);

			$this->create_wordpress_user( $params, $output );

		} elseif ( $output['responseCode'] == '100' && $params['method'] == 'NewOrderWithProspect' ) {
			$storage = array(
				'limelightOrderId'    => $output['orderId'],
				'limelightCustomerId' => $output['customerId'],
				'limelightOrderTotal' => $output['orderTotal'],
			);

			if ( $this->options['edigital_enabled'] == 1 && $params['edigital'] == 1 ) {

				$params = array(
					'productId'                 => $this->options['edigital_product_id'],
					'shippingId'                => $this->options['edigital_shipping_id'],
					'previousOrderId'           => $output['orderId'],
					'ipAddress'                 => $_SERVER['REMOTE_ADDR'],
					'tranType'                  => 'Sale',
					'method'                    => 'NewOrderCardOnFile',
					'initializeNewSubscription' => 1
				);

				$edigital = $this->api_edigital_new_order( $params );
				$html    .= $edigital['html'];

				array_merge( $storage, $edigital['storage'] );
			}

		} elseif ( $output['responseCode'] == '100' && $params['method'] == 'NewOrder' ) {
			$storage = array(
				'limelightShippingId' => $params['shippingId'],
				'limelightProductId'  => $params['productId'],
				'limelightOrderId'    => $output['orderId'],
				'limelightCustomerId' => $output['customerId'],
				'limelightOrderTotal' => $output['orderTotal'],
			);

			if ( $this->options['edigital_enabled'] == 1 && $params['edigital'] == 1 ) {
				$params = array(
					'productId'                 => $this->options['edigital_product_id'],
					'shippingId'                => $this->options['edigital_shipping_id'],
					'previousOrderId'           => $output['orderId'],
					'ipAddress'                 => $_SERVER['REMOTE_ADDR'],
					'tranType'                  => 'Sale',
					'method'                    => 'NewOrderCardOnFile',
					'initializeNewSubscription' => 1,
				);

				$edigital = $this->api_edigital_new_order( $params );
				$html    .= $edigital['html'];

				array_merge( $storage, $edigital['storage'] );
			}

			$this->create_wordpress_user( $params, $output );

		} elseif ( $output['responseCode'] == '330' && $params['method'] == 'NewOrderWithProspect' ) {
			$new_prospect_url = $this->limelight_get_url( $this->campaign_data->web_pages->new_prospect->page_id );

			$html .= <<<HTML
				<br>
				<p>Whoops! Looks like something went wrong.</p>
				<br>
				<p>Please <a href="{$new_prospect_url}">Click Here</a> and re-enter your information.</p>
				<br>
				<p>Thank you!</p>
				<br>
HTML;
		} elseif ( $output['responseCode'] == '600' && $params['method'] == 'NewOrder' ) {
			$cart_page_url = $this->limelight_get_url( $this->campaign_data->web_pages->cart_page->page_id );

			$html .= <<<HTML
				<strong>Please select at least one product and try again</strong>
				<br>
				<a href="{$cart_page_url}">Click Here to select product(s)</a>
				<br>
				<br>
HTML;
		} elseif ( $output['responseCode'] == '100' && $params['method'] == 'NewOrderCardOnFile' ) {

			$shipping_prices = $this->campaign_data->shipping_info;

			if ( ! empty( $params['upsell_page'] && $params['upsell_page'] == 1 ) ) {
				$storage = array(
					'limelightUpsellProductId1'  => $params['productId'],
					'limelightUpsellShippingId1' => $params['shippingId'],
					'limelightUpsellOrderTotal1' => $output['orderTotal'],
				);
			}

			if ( ! empty( $params['upsell_page'] && $params['upsell_page'] == 2 ) ) {
				$storage = array(
					'limelightUpsellProductId2'  => $params['productId'],
					'limelightUpsellShippingId2' => $params['shippingId'],
					'limelightUpsellOrderTotal2' => $output['orderTotal'],
				);
			}

			if ( ! empty( $params['upsell_page'] && $params['upsell_page'] == 3 ) ) {
				$storage = array(
					'limelightUpsellProductId3'  => $params['productId'],
					'limelightUpsellShippingId3' => $params['shippingId'],
					'limelightUpsellOrderTotal3' => $output['orderTotal'],
				);
			}
		} else {
			$html .= $this->get_error_response( $output['responseCode'] );
		}

		return array(
			'storage'     => $storage,
			'redirect_to' => ( $html ? '' : $redirect_to ),
			'javascript'  => $this->echo_javascript(),
			'html'        => $html,
		);
	}

	private function get_error_response( $error ) {
		$error_response = new Limelight_Error_Response( $error );

		return $error_response->get_response_message();
	}

	public function api_edigital_new_order( $params ) {
		$storage      = array();
		$html         = '';
		$api_url      = str_replace( 'campaignId=' . $this->options['campaign_id'], 'campaignId=' . $this->options['edigital_campaign_id'], $this->transact_api_url ) . http_build_query( $params );
		$api_response = wp_remote_retrieve_body( wp_remote_get( $api_url ) );

		parse_str( $api_response, $output );

		if ( $output['responseCode'] == '100' ) {
			$storage = array(
				'limelightEdigitalProductId'     => $this->options['edigital_product_id'],
				'limelightEdigitalShippingId'    => $this->options['edigital_shipping_id'],
				'limelightEdigitalOrderTotal'    => $output['orderTotal'],
				'limelightEdigitalShippingPrice' => '0'
			);
		} else {
			$html = <<<HTML
				<br>{$output['errorMessage']}
				<br>
				<strong>Please try again.</strong>
				<br>
HTML;
		}

		return array(
			'storage' => $storage,
			'html'    => $html,

		);
	}

	public function sc_single_page_billing_section( $atts, $content = null ) {
		$content = do_shortcode( $content );

		return "<div id='js-billing-section' style='display:none;'><label>Billing Info</label><br>{$content}</div>";
	}

	public function echo_credit_card_form_fields() {

		$cc_type_tokens  = array();
		$cc_month_tokens = array();
		$cc_year_tokens  = array();
		$html            = array();

		//build all of the option tags that are needed in these foreach statements
		foreach ( $this->cards_types as $cc_type => $cc_display_name ) {
			$cc_type_tokens[] = $this->fill_tpl( 
				$this->client_tpl['limelight_build_cc_types'],
				array(
					'<!--TYPE-->'    => $cc_type,
					'<!--DISPLAY-->' => $cc_display_name,
					)
				);
		}

		foreach ( $this->months as $value => $month ) {
			$cc_month_tokens[] = $this->fill_tpl(
				$this->client_tpl['limelight_build_cc_months'],
				array(
					'<!--VALUE-->' => $value,
					'<!--MONTH-->' => $month,
					)
				);
		}

		foreach ( $this->years as $value => $year ) {
			$cc_year_tokens[] = $this->fill_tpl(
				$this->client_tpl['limelight_build_cc_years'],
				array(
					'<!--VALUE-->' => $value,
					'<!--YEAR-->'  => $year,
					)
				);
		}

		//build the display for all of the pieces needed
		$html[] = $this->fill_tpl(
			$this->client_tpl['limelight_card_types'],
			array(
				'<!--CC_TYPES-->'   => implode( "\n", $cc_type_tokens ),
				'<!--ATTRIBUTES-->' => '',
				)
			);
		
		if ( $this->options['3d_verify_enabled'] == 1 ) {

			$html[] = $this->fill_tpl(
				$this->client_tpl['limelight_3d_verify_cc_number'],
				array(
					'<!--ATTRIBUTES-->' => '',
					'<!--LABEL-->'      => $this->get_label( 'Card Number' ),
					)
				);

			$html[] = $this->fill_tpl(
				$this->client_tpl['limelight_3d_verify_cc_cvv'],
				array(
					'<!--ATTRIBUTES-->' => '',
					'<!--LABEL-->'      => $this->get_label( 'CVV' ),
					)
				);

			$html[] = $this->fill_tpl(
				$this->client_tpl['limelight_3d_verify_cc_months'],
				array(
					'<!--ATTRIBUTES-->' => '',
					'<!--CC_MONTHS-->'  => implode( "\n", $cc_month_tokens ),
					'<!--LABEL-->'      => $this->get_label( 'Expiration' ),
					)
				);

			$html[] = $this->fill_tpl(
				$this->client_tpl['limelight_3d_verify_cc_years'],
				array(
					'<!--ATTRIBUTES-->' => '',
					'<!--LABEL-->'      => '',
					'<!--CC_YEARS-->'   => implode( "\n", $cc_year_tokens ),
					)
				);
		} else {

			$html[] = $this->fill_tpl(
				$this->client_tpl['limelight_cc_number'],
				array(
					'<!--ATTRIBUTES-->' => '',
					'<!--LABEL-->'      => $this->get_label( 'Card Number' ),
					)
				);

			$html[] = $this->fill_tpl(
				$this->client_tpl['limelight_cc_cvv'],
				array(
					'<!--ATTRIBUTES-->' => '',
					'<!--LABEL-->'      => $this->get_label( 'CVV' ),
					)
				);

			$html[] = $this->fill_tpl(
				$this->client_tpl['limelight_cc_months'],
				array(
					'<!--ATTRIBUTES-->' => '',
					'<!--CC_MONTHS-->'  => implode( "\n", $cc_month_tokens ),
					'<!--LABEL-->'      => $this->get_label( 'Expiration' ),
					)
				);

			$html[] = $this->fill_tpl(
				$this->client_tpl['limelight_cc_years'],
				array(
					'<!--ATTRIBUTES-->' => '',
					'<!--CC_YEARS-->'   => implode( "\n", $cc_year_tokens ),
					''
					)
				);
		}

		return implode( "\n", $html );
	}

	public function sc_single_page_credit_card_month( $atts ) {

		$cc_month_tokens = array();
		$html_attributes = $this->html_attributes_from_shortcode( $atts );

		foreach ( $this->months as $value => $month ) {
			$cc_month_tokens[] = $this->fill_tpl(
				$this->client_tpl['limelight_build_cc_months'],
				array(
					'<!--VALUE-->' => $value,
					'<!--MONTH-->' => $month,
					)
				);
		}

		$tokens = array(
			'<!--CC_MONTHS-->'  => implode( "\n", $cc_month_tokens ),
			'<!--ATTRIBUTES-->' => $html_attributes,
			'<!--LABEL-->'      => '',	
		);

		if ( $this->options['3d_verify_enabled'] == 1 ) {
			$html = $this->fill_tpl( $this->client_tpl['limelight_3d_verify_cc_months'], $tokens );
		} else {
			$html = $this->fill_tpl( $this->client_tpl['limelight_cc_months'], $tokens );
		}

		return $html;
	}

	public function sc_single_page_credit_card_year( $atts ) {

		$cc_year_tokens  = array();
		$html_attributes = $this->html_attributes_from_shortcode( $atts );

		foreach ( $this->years as $value => $year ) {
			$cc_year_tokens[] = $this->fill_tpl(
				$this->client_tpl['limelight_build_cc_years'],
				array(
					'<!--VALUE-->' => $value,
					'<!--YEAR-->'  => $year,
					)
				);

		}

		$tokens = array(
			'<!--CC_YEARS-->'   => implode( "\n", $cc_year_tokens ),
			'<!--ATTRIBUTES-->' => $html_attributes,
			'<!--LABEL-->'      => '',
		);

		if ( $this->options['3d_verify_enabled'] == 1 ) {
			$html = $this->fill_tpl( $this->client_tpl['limelight_3d_verify_cc_years'], $tokens );
		} else {
			$html = $this->fill_tpl( $this->client_tpl['limelight_cc_years'], $tokens );
		}

		return $html;
	}

	public function echo_single_page_javascript() {

		$products  = $this->campaign_data->products;
		$shippings = $this->campaign_data->shipping_info;
		$html      = '';
		$html     .= <<<EOT
			<script type="text/javascript">
			function limelightSinglePage() {
				var product  = document.getElementById( 'js-single-page-product-id' ).value;
				var shipping = document.getElementById( 'js-single-page-shipping-id' ).value;
EOT;

		foreach ( $products as $product ) {
			$price = ( ! empty( $product->product_rebill_price ) ? $product->product_rebill_price : $product->product_price );
			$html .= <<<EOT
				if ( product == $product->product_id ) {
					var product_price = $price;
				}
EOT;
		}

		foreach ( $shippings as $shipping ) {
			$html .= <<<EOT
				if ( shipping == $shipping->shipping_id ) {
					var shipping_price = $shipping->shipping_initial_price;
				}
EOT;
			}

		$html .= <<<EOT
				document.getElementById( 'js-single-page-x-amount' ).value = ( product_price + shipping_price ).toFixed( 2 );
			}
			</script>
EOT;

		return $html;
	}

	public function echo_cart_javascript() {

		$cookies          = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
		$html             = '';
		$products         = $this->campaign_data->products;

		foreach ( $products as $product ) {
			$product_qty_x = 'product_qty_' . $product->product_id;
			$cookie_data_x = ( ! empty( $cookies->$product_qty_x ) ? $cookies->$product_qty_x : '' );

			if ( $cookie_data_x > 0 ) {
				$java_total      = $cookie_data_x * $product->product_price;
				$java_max_qty    = $product->product_max_quantity;

				$html .= <<<EOT
					<script type="text/javascript">
					var $product_qty_x              = $cookie_data_x;
					var total$product->product_id   = $java_total;
					var max_qty$product->product_id = $java_max_qty;

					window.addEventListener( 'load', function() {

						var addButton{$product->product_id} = document.getElementById( 'js-button-add-{$product->product_id}' );
						if ( addButton{$product->product_id} ) {
							addButton{$product->product_id}.addEventListener( 'click', function() {
								if ( $product_qty_x == max_qty$product->product_id ) {
									document.getElementById( 'js-product-div$product->product_id' ).innerHTML = '<strong>' + max_qty$product->product_id + ' is the maximum number you may order of this product</strong>';
								} else {
									document.getElementById( 'js-$product_qty_x' ).value           = ++ $product_qty_x;
									document.getElementById( 'js-$product_qty_x-label' ).innerHTML = $product_qty_x;
									total$product->product_id                                      = $product->product_price * $product_qty_x;
									document.getElementById( 'js-$product_qty_x-total' ).innerHTML = '$' + total$product->product_id.toFixed( 2 );
									limelightCartTotal();
								}
							} );
						}
						var removeButton{$product->product_id} = document.getElementById( 'js-button-remove-{$product->product_id}' );
						if ( removeButton{$product->product_id} ) {
							removeButton{$product->product_id}.addEventListener( 'click', function() {
								if ( $product_qty_x > '0' ) {
									document.getElementById( 'js-product-div$product->product_id' ).innerHTML = '';
									document.getElementById( 'js-$product_qty_x' ).value                      = -- $product_qty_x;
									document.getElementById( 'js-$product_qty_x-label' ).innerHTML            = $product_qty_x;
									total$product->product_id                                                 = $product->product_price * $product_qty_x;
									document.getElementById( 'js-$product_qty_x-total' ).innerHTML            = '$' + total$product->product_id.toFixed(2);
									limelightCartTotal();
								}
							} );
						}
					} );

					</script>
EOT;
			}

		}

		$html .= <<<EOT
			<script type='text/javascript'>
				function limelightCartTotal() {
					var total = 0;
EOT;

		$product_vars     = array();
		$update_products  = '';

		foreach ( $products as $product ) {
			$product_qty_x = 'product_qty_' . $product->product_id;
			$cookie_data_x = ( ! empty( $cookies->$product_qty_x ) ? $cookies->$product_qty_x : '' );

			if ( $cookie_data_x > 0 ) {
				$var = 'var' . $product->product_id;
				array_push( $product_vars, $var );

				$product_qty_x    = 'product_qty_' . $product->product_id;
				$update_products .= 'sessionStorage.limelight' . $product_qty_x . ' = ' . $product_qty_x . ';';

				$html .= <<<EOT
					$var               = document.getElementById( 'js-$product_qty_x' ).value * $product->product_price;
					var $product_qty_x = document.getElementById( 'js-$product_qty_x' ).value;
EOT;
			}
		}

		$product_vars  = implode( '+', $product_vars );
		$html            .= <<<EOT
			var total = $product_vars;

			document.getElementById( 'js-cart-total' ).innerHTML = '$' + total.toFixed(2);

			$update_products

			var limelightData = {
				'prospectId'            : sessionStorage.limelightProspectId,
				'shippingId'            : sessionStorage.limelightShippingId,
				'productId'             : sessionStorage.limelightProductId,
				'firstName'             : sessionStorage.limelightFirstName,
				'lastName'              : sessionStorage.limelightLastName,
				'orderId'               : sessionStorage.limelightOrderId,
				'customerId'            : sessionStorage.limelightCustomerId,
				'orderTotal'            : sessionStorage.limelightOrderTotal,
				'upsellProductId1'      : sessionStorage.limelightUpsellProductId1,
				'upsellOrderTotal1'     : sessionStorage.limelightUpsellOrderTotal1,
				'upsellProductId2'      : sessionStorage.limelightUpsellProductId2,
				'upsellOrderTotal2'     : sessionStorage.limelightUpsellOrderTotal2,
				'upsellProductId3'      : sessionStorage.limelightUpsellProductId3,
				'upsellOrderTotal3'     : sessionStorage.limelightUpsellOrderTotal3,
				'edigitalProductId'     : sessionStorage.limelightEdigitalProductId,
				'edigitalShippingId'    : sessionStorage.limelightEdigitalShippingId,
				'eDigitalOrderTotal'    : sessionStorage.limelightEdigitalOrderTotal,
				'products'              : sessionStorage.limelightProducts,
				'edigitalShippingPrice' : sessionStorage.limelightEdigitalShippingPrice,
				'cart'                  : sessionStorage.limelightCart,
				'affiliates'            : sessionStorage.limelightAffiliates,
				'orderHistoryPage'      : sessionStorage.limelightOrderHistoryPage,
				'orderHistoryPerPage'   : sessionStorage.limelightOrderHistoryPerPage,
				'orderDetailsPage'      : sessionStorage.limelightOrderDetailsPage,
				'upsellProductIds'      : sessionStorage.limelightUpsellProductIds,
EOT;

		foreach ( $products as $product ) {
			$html .= "'product_qty_" . $product->product_id . "' : sessionStorage.limelightproduct_qty_" . $product->product_id . ",";
		}

		$html .= <<<EOT
			};

			document.cookie = "limelight-data=" + JSON.stringify(limelightData) + ";path=/";

			}
			</script>
EOT;

		if ( isset( $cookies->thankYou ) && $cookies->thankYou == 1 ) {
			$html = $this->echo_cookie_reset_script();
		}

		return $html;
	}

	public function echo_kount_data() {

		$kount_session = str_replace( '.', '', microtime( true ) );
		$src           = "https://{$this->options['app_key']}.limelightcrm.com/pixel.php?t=gif&campaign_id={$this->options['campaign_id']}&sessionId={$kount_session}";
		$html          = <<<HTML
		<!--KOUNT PIXEL-->
			<input type="hidden" name="sessionId" value="{$kount_session}">
			<iframe width=1 height=1 frameborder=0 scrolling=no src="{$src}">
				<img width=1 height=1 src="{$src}">
			</iframe>
		<!--/KOUNT PIXEL-->
HTML;
		return $html;
	}

	public function get_affiliates() {

		$requestUri = $_POST['url'];

		if ( strpos( $requestUri, '?' ) ) {
			$affs            = substr( $requestUri, strpos( $requestUri, '?' ) + 1 );
			$affs            = explode( '&', $affs );
			$aff_ids         = array();
			$session_aff_ids = array();
			$count           = count( $affs );

			for ( $i = 0; $i < $count; $i++ ) {
				$split_me                                           = strpos( $affs[$i], '=' );
				$aff_ids[trim( substr( $affs[$i], 0, $split_me ) )] = trim( substr( urldecode( $affs[$i] ), ( $split_me + 1 ) ) );
			}

			$check_affs = array(
				'AFID',
				'SID',
				'AFFID',
				'C1',
				'C2',
				'C3',
				'BID',
				'AID',
				'OPT',
				'ClickID',
				'click_id'
			);

			foreach ( $aff_ids as $aff_id => $value ) {

				if ( in_array( $aff_id, $check_affs ) )  {
					$session_aff_ids[$aff_id] = $value;
				}
			}

			$html = ( ! empty( $session_aff_ids ) ? '<script type="text/javascript">sessionStorage.limelightAffiliates= JSON.stringify(' . json_encode( $session_aff_ids ) . ');</script>' . $this->echo_javascript() : '' );
			
			echo $html;

			wp_die();

		}
	}

	public function get_google_analytic_script() {

		$google_script      = '';
		$dimension1         = $this->options['app_key'];
		$dimension2         = $this->options['campaign_id'];
		$dimension3         = get_bloginfo( 'version' );
		$dimension4         = $this->version;
		$userId             = $dimension1 . ':' . $dimension2;
		$google_tracking_id = $this->options['google_tracking_id'];
		$google_script     .= ( ( ! empty( $google_tracking_id ) ) ? "
			<script type='text/javascript'>
				( function( i,s,o,g,r,a,m ){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				( i[r].q=i[r].q||[] ).push( arguments )},i[r].l=1*new Date();a=s.createElement( o ),
				m=s.getElementsByTagName( o )[0];a.async=1;a.src=g;m.parentNode.insertBefore( a,m )
				} )( window,document,'script','https://www.google-analytics.com/analytics.js','ga' );

				ga( 'create', '{$google_tracking_id}', 'auto' );
				ga( 'create', 'UA-80325941-3', 'auto', 'limelightTracker' );
				ga( 'limelightTracker.set', 'dimension1', '{$dimension1}' );
				ga( 'limelightTracker.set', 'dimension2', '{$dimension2}' );
				ga( 'limelightTracker.set', 'dimension3', '{$dimension3}' );
				ga( 'limelightTracker.set', 'dimension4', '{$dimension4}' );
				ga( 'limelightTracker.set', 'userId', '{$userId}' );
				ga( 'limelightTracker.send', 'pageview' );
				ga( 'send', 'pageview' );
			</script>
			</script>
			" : "
			<script type='text/javascript'>
				( function( i,s,o,g,r,a,m ){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
				( i[r].q=i[r].q||[] ).push( arguments )},i[r].l=1*new Date();a=s.createElement( o ),
				m=s.getElementsByTagName( o )[0];a.async=1;a.src=g;m.parentNode.insertBefore( a,m )
				} )( window,document,'script','https://www.google-analytics.com/analytics.js','ga' );

				ga( 'create', 'UA-80325941-3', 'auto', 'limelightTracker' );
				ga( 'limelightTracker.set', 'dimension1', '{$dimension1}' );
				ga( 'limelightTracker.set', 'dimension2', '{$dimension2}' );
				ga( 'limelightTracker.set', 'dimension3', '{$dimension3}' );
				ga( 'limelightTracker.set', 'dimension4', '{$dimension4}' );
				ga( 'limelightTracker.set', 'userId', '{$userId}' );
				ga( 'limelightTracker.send', 'pageview' );
			</script>
		" );

		$google_script .= ( $this->options['https_enabled'] == '1' ? $this->https_enabled_redirect() : '' );

		return $google_script;
	}

	private function https_enabled_redirect() {

		$redirect_script = <<<JAVA
			<script type='text/javascript'>
				var currentPage  = window.location.href;
				var redirectPage = currentPage.replace( 'http://', 'https://' );
				if ( redirectPage !== currentPage ) {
					window.location = redirectPage;
				}
			</script>
JAVA;

		return $redirect_script;
	}

	public function get_google_ecommerce_script() {

		$ecommerce_script   = '';
		$affiliation        = "{$this->options['app_key']}:{$this->options['campaign_id']}";
		$google_tracking_id = $this->options['google_tracking_id'];
		$products           = $this->campaign_data->products;
		$shippings          = $this->campaign_data->shipping_info;
		$cookies            = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
		$order_id           = $cookies->orderId;

		foreach ( $shippings as $shipping ) {

			if ( $shipping->shipping_id == $cookies->shippingId ) {
				$shipping_price = $shipping->shipping_initial_price;
			}
		}

		if ( isset( $cookies->cart ) && $cookies->cart !== 1 ) {
			$product_1       = $cookies->productId;
			$product_2       = ( isset( $cookies->upsellProductId1 ) ? $cookies->upsellProductId1 : '' );
			$product_3       = ( isset( $cookies->upsellProductId2 ) ? $cookies->upsellProductId2 : '' );
			$product_4       = ( isset( $cookies->upsellProductId3 ) ? $cookies->upsellProductId3 : '' );
			$product_5       = ( isset( $cookies->edigitalProductId) ? $cookies->edigitalProductId : '' );
			$main_total      = $cookies->orderTotal;
			$upsell_1_total  = ( isset( $cookies->upsellOrderTotal1 ) ? $cookies->upsellOrderTotal1 : '' );
			$upsell_2_total  = ( isset( $cookies->upsellOrderTotal2 ) ? $cookies->upsellOrderTotal2 : '' );
			$upsell_3_total  = ( isset( $cookies->upsellOrderTotal3 ) ? $cookies->upsellOrderTotal3 : '' );
			$edigital_total  = ( isset( $cookies->edigitalOrderTotal) ? $cookies->edigitalOrderTotal : '' );
			$revenue         = number_format( $main_total + $upsell_1_total + $upsell_2_total + $upsell_3_total + $edigital_total, 2 );
			$shipping_price  = number_format( $shipping_price, 2);

			$google_products = array();

			if ( ! empty( $edigital_total ) ) {
				$google_product = array(
					'product_name'     => $this->options['edigital_product_name'],
					'product_sku'      => $this->options['edigital_product_sku'],
					'product_price'    => $edigital_total,
					'product_quantity' => 1
				);
				array_push( $google_products, $google_product );
			}

			foreach ( $products as $product ) {

				if ( $product->product_id == $product_1 ) {
					$google_product = array(
						'product_name'     => $product->product_name,
						'product_sku'      => $product->product_sku,
						'product_price'    => $product->product_price,
						'product_quantity' => 1
					);
					array_push( $google_products, $google_product );
				}

				if ( $product->product_id == $product_2 ) {
					$google_product = array(
						'product_name'     => $product->product_name,
						'product_sku'      => $product->product_sku,
						'product_price'    => $product->product_price,
						'product_quantity' => 1
					);
					array_push( $google_products, $google_product );
				}

				if ( $product->product_id == $product_3 ) {
					$google_product = array(
						'product_name'     => $product->product_name,
						'product_sku'      => $product->product_sku,
						'product_price'    => $product->product_price,
						'product_quantity' => 1
					);
					array_push( $google_products, $google_product );
				}

				if ( $product->product_id == $product_4 ) {
					$google_product = array(
						'product_name'     => $product->product_name,
						'product_sku'      => $product->product_sku,
						'product_price'    => $product->product_price,
						'product_quantity' => 1
					);
					array_push( $google_products, $google_product );
				}

				if ( $product->product_id == $product_5 ) {
					$google_product = array(
						'product_name'     => $product->product_name,
						'product_sku'      => $product->product_sku,
						'product_price'    => $product->product_price,
						'product_quantity' => 1
					);
					array_push( $google_products, $google_product );
				}
			}

			if ( ! empty( $google_tracking_id ) ) {
				$ecommerce_script .= <<<EOT
					<script type='text/javascript'>
						ga( 'require', 'ecommerce' );
						ga( 'ecommerce:addTransaction', {
							'id'          : '$order_id',
							'affiliation' : '$affiliation',
							'revenue'     : '$revenue',
							'shipping'    : '$shipping_price',
						} )
EOT;

				foreach ( $google_products as $google_product ) {
					$product_sku       = $google_product['product_sku'];
					$product_name      = $google_product['product_name'];
					$product_price     = $google_product['product_price'];
					$product_quantity  = $google_product['product_quantity'];
					$ecommerce_script .= <<<EOT

						ga( 'ecommerce:addItem', {
							'id'       : '$order_id',
							'sku'      : '$product_sku',
							'name'     : '$product_name',
							'price'    : '$product_price',
							'quantity' : '$product_quantity',
							'currency' : 'USD'
						} )
EOT;
				}

				$ecommerce_script .= <<<EOT

					ga( 'ecommerce:send' );
					</script>
EOT;

			}

			$ecommerce_script .= <<<EOT
				<script type='text/javascript'>
					ga( 'limelightTracker.require', 'ecommerce' );
					var transaction = {
						'id'          : '$order_id',
						'affiliation' : '$affiliation',
						'revenue'     : '$revenue',
						'shipping'    : '$shipping_price',
					};
					ga( 'limelightTracker.ecommerce:addTransaction', transaction );

EOT;

			foreach ( $google_products as $google_product ) {
				$product_sku       = $google_product['product_sku'];
				$product_name      = $google_product['product_name'];
				$product_price     = $google_product['product_price'];
				$product_quantity  = $google_product['product_quantity'];
				$ecommerce_script .= <<<EOT

					var item = {
						'id'       : '$order_id',
						'sku'      : '$product_sku',
						'name'     : '$product_name',
						'price'    : '$product_price',
						'quantity' : '$product_quantity',
						'currency' : 'USD'
					};
					ga( 'limelightTracker.ecommerce:addItem', item );
EOT;
			}
			$ecommerce_script .= <<<EOT

				ga( 'limelightTracker.ecommerce:send' );
				</script>
EOT;
		} else {
			$google_products = json_decode( $cookies->products, true );;

			if ( ! empty( $cookies->edigitalOrderTotal ) ) {
				$google_product = array(
					'product_name'     => $this->options['edigital_product_name'],
					'product_sku'      => $this->options['edigital_product_sku'],
					'product_price'    => $cookies->edigitalOrderTotal,
					'product_quantity' => 1
				);
				array_push( $google_products, $google_product );
			}

			if ( ! empty( $cookies->upsellOrderTotal1 ) ) {

				foreach ( $products as $product ) {

					if ( $product->product_id == $cookies->upsellProductId1 ) {
						$google_product = array(
							'product_name'     => $product->product_name,
							'product_sku'      => $product->product_sku,
							'product_price'    => $product->product_price,
							'product_quantity' => 1
						);
						array_push( $google_products, $google_product );
					}
				}
			}

			if ( ! empty( $cookies->upsellOrderTotal2 ) ) {

				foreach ( $products as $product ) {

					if ( $product->product_id == $cookies->upsellProductId2 ) {
						$google_product = array(
							'product_name'     => $product->product_name,
							'product_sku'      => $product->product_sku,
							'product_price'    => $product->product_price,
							'product_quantity' => 1
						);
						array_push( $google_products, $google_product );
					}
				}
			}

			if ( ! empty( $cookies->upsellOrderTotal3 ) ) {

				foreach ( $products as $product ) {

					if ( $product->product_id == $cookies->upsellProductId3 ) {
						$google_product = array(
							'product_name'     => $product->product_name,
							'product_sku'      => $product->product_sku,
							'product_price'    => $product->product_price,
							'product_quantity' => 1
						);
						array_push( $google_products, $google_product );
					}
				}
			}

			$main_total      = $cookies->orderTotal;
			$upsell_1_total  = ( isset( $cookies->upsellOrderTotal1 ) ? $cookies->upsellOrderTotal1 : '' );
			$upsell_2_total  = ( isset( $cookies->upsellOrderTotal2 ) ? $cookies->upsellOrderTotal2 : '' );
			$upsell_3_total  = ( isset( $cookies->upsellOrderTotal3 ) ? $cookies->upsellOrderTotal3 : '' );
			$edigital_total  = ( isset( $cookies->edigitalOrderTotal) ? $cookies->edigitalOrderTotal : '' );
			$revenue         = number_format( $main_total + $upsell_1_total + $upsell_2_total + $upsell_3_total + $edigital_total, 2 );
			$shipping_price  = number_format( $shipping_price, 2);

			if ( ! empty( $this->options['google_tracking_id'] ) ) {
				$ecommerce_script .= <<<EOT
					<script type='text/javascript'>
						ga( 'require', 'ecommerce' );
						ga( 'ecommerce:addTransaction', {
							'id'          : '$order_id',
							'affiliation' : '$affiliation',
							'revenue'     : '$revenue',
							'shipping'    : '$shipping_price',
						} )
EOT;

				foreach ( $google_products as $google_product ) {
					$product_sku       = $google_product['product_sku'];
					$product_name      = $google_product['product_name'];
					$product_price     = $google_product['product_price'];
					$product_quantity  = $google_product['product_quantity'];
					$ecommerce_script .= <<<EOT

						ga( 'ecommerce:addItem', {
							'id'       : '$order_id',
							'sku'      : '$product_sku',
							'name'     : '$product_name',
							'price'    : '$product_price',
							'quantity' : '$product_quantity',
							'currency' : 'USD'
						} )
EOT;
				}

				$ecommerce_script .= <<<EOT

					ga( 'ecommerce:send' );
					</script>
EOT;
			}

			$ecommerce_script .= <<<EOT
				<script type='text/javascript'>
					ga( 'limelightTracker.require', 'ecommerce' );
					var transaction = {
						'id'          : '$order_id',
						'affiliation' : '$affiliation',
						'revenue'     : '$revenue',
						'shipping'    : '$shipping_price',
					};
					ga( 'limelightTracker.ecommerce:addTransaction', transaction );
EOT;

			foreach ( $google_products as $google_product ) {
				$product_sku       = $google_product['product_sku'];
				$product_name      = $google_product['product_name'];
				$product_price     = $google_product['product_price'];
				$product_quantity  = $google_product['product_quantity'];
				$ecommerce_script .= <<<EOT

					var item = {
						'id'       : '$order_id',
						'sku'      : '$product_sku',
						'name'     : '$product_name',
						'price'    : '$product_price',
						'quantity' : '$product_quantity',
						'currency' : 'USD'
					};
					ga( 'limelightTracker.ecommerce:addItem', item );
EOT;
			}

			$ecommerce_script .= <<<EOT

					ga( 'limelightTracker.ecommerce:send' );
					</script>
EOT;
		}

		return $ecommerce_script;
	}

	public function echo_javascript() {

		$javascript_text = <<<EOT
			<script type="text/javascript">
				var limelightData = {
				'prospectId'            : sessionStorage.limelightProspectId,
				'shippingId'            : sessionStorage.limelightShippingId,
				'productId'             : sessionStorage.limelightProductId,
				'firstName'             : sessionStorage.limelightFirstName,
				'lastName'              : sessionStorage.limelightLastName,
				'orderId'               : sessionStorage.limelightOrderId,
				'customerId'            : sessionStorage.limelightCustomerId,
				'orderTotal'            : sessionStorage.limelightOrderTotal,
				'upsellProductId1'      : sessionStorage.limelightUpsellProductId1,
				'upsellShippingId1'     : sessionStorage.limelightUpsellShippingId1,
				'upsellOrderTotal1'     : sessionStorage.limelightUpsellOrderTotal1,
				'upsellProductId2'      : sessionStorage.limelightUpsellProductId2,
				'upsellShippingId2'     : sessionStorage.limelightUpsellShippingId2,
				'upsellOrderTotal2'     : sessionStorage.limelightUpsellOrderTotal2,
				'upsellProductId3'      : sessionStorage.limelightUpsellProductId3,
				'upsellShippingId3'     : sessionStorage.limelightUpsellShippingId3,
				'upsellOrderTotal3'     : sessionStorage.limelightUpsellOrderTotal3,
				'edigitalProductId'     : sessionStorage.limelightEdigitalProductId,
				'edigitalShippingId'    : sessionStorage.limelightEdigitalShippingId,
				'edigitalOrderTotal'    : sessionStorage.limelightEdigitalOrderTotal,
				'products'              : sessionStorage.limelightProducts,
				'edigitalShippingPrice' : sessionStorage.limelightEdigitalShippingPrice,
				'thankYou'              : sessionStorage.limelightThankYou,
				'cart'                  : sessionStorage.limelightCart,
				'affiliates'            : sessionStorage.limelightAffiliates,
				'orderHistoryPage'      : sessionStorage.limelightOrderHistoryPage,
				'orderHistoryPerPage'   : sessionStorage.limelightOrderHistoryPerPage,
				'orderDetailsPage'      : sessionStorage.limelightOrderDetailsPage,
				'upsellProductIds'      : sessionStorage.limelightUpsellProductIds,
EOT;

		$products = $this->campaign_data->products;

		foreach ( $products as $product ) {
			$javascript_text .= "'product_qty_" . $product->product_id . "' : sessionStorage.limelightproduct_qty_" . $product->product_id . ",";
		}

		$javascript_text .= <<<EOT

			};
				document.cookie = "limelight-data=" + JSON.stringify( limelightData ) + ";path=/";
			</script>
EOT;

		return $javascript_text;
	}

	public function echo_cookie_reset_script() {

		$cookie_reset = <<<EOT
			<script type="text/javascript">
				sessionStorage.limelightProspectId            = '';
				sessionStorage.limelightShippingId            = '';
				sessionStorage.limelightProductId             = '';
				sessionStorage.limelightFirstName             = '';
				sessionStorage.limelightLastName              = '';
				sessionStorage.limelightOrderId               = '';
				sessionStorage.limelightCustomerId            = '';
				sessionStorage.limelightOrderTotal            = '';
				sessionStorage.limelightUpsellProductId1      = '';
				sessionStorage.limelightUpsellOrderTotal1     = '';
				sessionStorage.limelightUpsellProductId2      = '';
				sessionStorage.limelightUpsellOrderTotal2     = '';
				sessionStorage.limelightUpsellProductId3      = '';
				sessionStorage.limelightUpsellOrderTotal3     = '';
				sessionStorage.limelightEdigitalProductId     = '';
				sessionStorage.limelightEdigitalShippingId    = '';
				sessionStorage.limelightEdigitalOrderTotal    = '';
				sessionStorage.limelightProducts              = '';
				sessionStorage.limelightEdigitalShippingPrice = '';
				sessionStorage.limelightThankYou              = '';
				sessionStorage.limelightCart                  = '';
				sessionStorage.limelightAffiliates            = '';
				sessionStorage.limelightOrderHistoryPage      = '';
				sessionStorage.limelightOrderHistoryPerPage   = '';
				sessionStorage.limelightUpsellProductIds      = '';
EOT;

		$products = $this->campaign_data->products;

		foreach ( $products as $product ) {
			$cookie_reset .= "
				sessionStorage.limelightproduct_qty_" . $product->product_id . " = '';";
		}

		$cookie_reset .= <<<EOT
				document.cookie = "limelight-data=" + ";path=/";
				window.parent.location.reload();
			</script>
EOT;

		return $cookie_reset;

	}

	public function echo_admin_ajax() {

		$loading = plugin_dir_url( __FILE__ ) . 'assets/LimeLightLoading.gif';

		echo <<<EOT
			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					var currentCampaign = $( '#campaign_id' ).val();
					var upsellContent1  = $( '#upsell_product_id_1' ).html();
					var upsellContent2  = $( '#upsell_product_id_2' ).html();
					var upsellContent3  = $( '#upsell_product_id_3' ).html();
					var prospectProduct  = $( '#prospect_product' ).html();

					$( '#js-campaign-id' ).change( function() {
						if ( $( '#js-campaign-id' ).val() == currentCampaign ) {
							$( '#upsell_product_id_1' ).html( upsellContent1 );
							$( '#upsell_product_id_2' ).html( upsellContent2 );
							$( '#upsell_product_id_3' ).html( upsellContent3 );
							$( '#prospect_product' ).html( prospectProduct );
						} else {
							$( '#js-campaign-loading' ).append( '<img src="$loading" />' );
							$( '#js-campaign-loading' ).addClass( 'limelight-loading' );
							$.ajax({
								type : 'POST',
								url : ajaxurl,
								data: {
									'action'      : 'campaign_change',
									'campaign_id' : $( '#js-campaign-id' ).val()
								},
								success : function( response ) {
									$( '#upsell_product_id_1' ).html( response );
									$( '#upsell_product_id_2' ).html( response );
									$( '#upsell_product_id_3' ).html( response );
									$( '#prospect_product' ).html( response );

									$( '#js-campaign-loading' ).html( '' );
									$( '#js-campaign-loading' ).removeClass( 'limelight-loading' );
								}
							} );
						}
					} );

					var edigitalCampaign = $( '#js-edigital-campaign-id' ).val();
					var edigitalProduct  = $( '#js-edigital-product-id' ).html();

					$( '#js-edigital-campaign-id' ).change( function() {
						if ( $( '#js-edigital-campaign-id' ).val() == edigitalCampaign ) {
							$( '#js-edigital-product-id' ).html( edigitalProduct );
						} else {
							$( '#js-campaign-loading' ).append( '<img src="$loading" />' );
							$( '#js-campaign-loading' ).addClass( 'limelight-loading' );
							$.ajax( {
								type : 'POST',
								url : ajaxurl,
								data: {
									'action'      : 'campaign_change',
									'campaign_id' : $( '#js-edigital-campaign-id' ).val()
								},
								success : function( response ) {
									$( '#js-edigital-product-id' ).html( response );
									$( '#js-campaign-loading' ).html( '' );
									$( '#js-campaign-loading' ).removeClass( 'limelight-loading' );
								}
							} );
						}
					} );
				} );
			</script>
EOT;

	}

	public function campaign_change() {

		$admin    = new Limelight_Admin;
		$products = $admin->api_get_campaign_products( $_POST['campaign_id'] );
		$html     = '<option>Please Select a Product</option>';

		foreach ( $products as $product ) {
			$product_id   = $product['product_id'];
			$product_name = $product['product_name'];
			$html        .= <<<HTML
				<option value="{$product_id}">{$product_name}</option>
HTML;
		}

		echo $html;

		wp_die();
	}

	public function update_states() {

		$country = sanitize_text_field( $_POST['country'] );
		$html    = '';

		$states = $this->campaign_data->states;

		foreach ( $states as $state ) {

			if ( $state->country_code == $country ) {
				$html .= <<<HTML
					<option value="{$state->state_code}">{$state->state_name}</option>
HTML;
			}
		}

		echo $html;

		wp_die();
	}

	public function create_wordpress_user( $params, $output ) {

		if ( $params['opt_in'] == 1 ) {
			$userdata = array(
				'user_login'           => ( isset( $output['customerId'] ) ? $output['customerId'] : $output['prospectId'] ), //I think we should move this to nickname and have the user_login be the email....
				'user_pass'            => wp_generate_password(),
				'user_email'           => $params['email'],
				'first_name'           => $params['firstName'],
				'last_name'            => $params['lastName'],
				'display_name'         => $params['firstName'],
				'nickname'             => $params['firstName'],
				'show_admin_bar_front' => false,
			);

			$id = wp_insert_user( $userdata );

			if ( ! is_wp_error( $id ) ) {
				wp_new_user_notification( $id, '', '' );
				wp_set_current_user( $id );
				wp_set_auth_cookie( $id );
			} else {
				foreach ( $id->errors as $error => $message ) {
					switch ( $error ) {
						case 'existing_user_email' :
							$user = get_user_by( 'email', $params['email'] );
							wp_set_auth_cookie( $user->ID );
						break;
					}
				}
			}
		}
	}

	public function wpll_process_form() {

		$result    = '';
		$form_data = array();

		// Format and sanitize form data
		if ( isset( $_REQUEST['formData'] ) ) {
			parse_str( $_REQUEST['formData'], $form_data );
			$form_data = array_map( 'sanitize_text_field', $form_data );

			if ( ! $form_data['order_id'] ) {
				$form_data['prospectId']      = ( ! empty( $form_data['prospectId'] ) ? $form_data['prospectId'] : sanitize_text_field( $_REQUEST['prospectId'] ) );
				$form_data['productId']       = ( ! empty( $form_data['productId'] ) ? $form_data['productId'] : sanitize_text_field( $_REQUEST['productId'] ) );
				$form_data['cart']            = ( ! empty( $form_data['cart'] ) ? $form_data['cart'] : sanitize_text_field( $_REQUEST['cart'] ) );
				$form_data['products']        = ( ! empty( $form_data['products'] ) ? $form_data['products'] : sanitize_text_field( $_REQUEST['products'] ) );
				$form_data['utm_medium']      = sanitize_text_field( $_REQUEST['utm_medium'] );
				$form_data['utm_source']      = sanitize_text_field( $_REQUEST['utm_source'] );
				$form_data['utm_campaign']    = sanitize_text_field( $_REQUEST['utm_campaign'] );
				$form_data['utm_term']        = sanitize_text_field( $_REQUEST['utm_term'] );
				$form_data['utm_content']     = sanitize_text_field( $_REQUEST['utm_content'] );
				$form_data['device_category'] = sanitize_text_field( $_REQUEST['device_category'] );
				$form_data['currentPage']     = sanitize_text_field( $_REQUEST['currentPage'] );
			}
			( isset( $form_data['email'] ) ? sanitize_email( $form_data['email'] ) : '' );

		}

		// clean data
		$fdata = array(
			'form_id'   => ( isset( $_REQUEST['formId'] ) ? sanitize_text_field( $_REQUEST['formId'] ) : '' ),
			'form_data' => $form_data
		);

		switch( $fdata['form_id'] ) {
			case 'js-one-click-check-out-form' :

				// Validate form data as needed
				if ( isset( $form_data['3d_verify'] ) ) {
					$result = $this->process_3d_verify ( $fdata );
				} else {
					$result = $this->process_check_out_form( $fdata );
				}

			break;

			case 'js-new-prospect-form' :

				$result = $this->process_new_prospect_form( $fdata );
				
			break;
			
			case 'js-my-account-form' :

				$result = $this->process_my_account_form( $fdata );
				
			break;
			
			case 'js-order-history-form' :

				$result = $this->process_order_history_form( $fdata );
				
			break;		

			case 'js-order-details-form' :

				$result = $this->process_order_details_form( $fdata );
				
			break;			

			case 'js-check-out-form' :

				if ( isset( $form_data['3d_verify'] ) ) {
					$result = $this->process_3d_verify ( $fdata );
				} else {
					$result = $this->process_check_out_form( $fdata );
				}
			break;

			case 'js-upsell-form' :

				if ( $form_data['upsell-no-thanks'] == 1 ) {

					$thanks = $this->limelight_get_url( $this->campaign_data->web_pages->thank_you->page_id );

					$result = array(
						'redirect_to' => $thanks,
					);
				} else {
					$result = $this->process_upsell( $fdata );
				}

			break;

			case 'js-cart-form' :

				$shipping_id = $form_data['shippingId'];
				$redirect_to = $this->limelight_get_url( $this->campaign_data->web_pages->check_out->page_id );
				$result      = array(
					'storage'     => [
						'limelightCart'       => '1',
						'limelightShippingId' => $shipping_id,
					],
					'redirect_to' => $redirect_to,
					'javascript'  => $this->echo_cart_javascript() . $this->echo_javascript(),
				);

			break;

			case 'js-product-details-form' :

				$storage = array();

				foreach ( $this->campaign_data->products as $product ) {
					$product_qty_x         = 'product_qty_' . $product->product_id;
					$storage_key           = 'limelight' . $product_qty_x;

					if ( $form_data[$product_qty_x] ) {
						$storage[$storage_key] = $form_data[$product_qty_x];
					}
				}

				$result = array(
					'storage'     => $storage,
					'javascript'  => $this->echo_javascript(),
					'redirect_to' => $this->limelight_get_url( $this->campaign_data->web_pages->cart_page->page_id ),
				);

			break;
		}

		echo json_encode( $result );

		wp_die();
	}

	private function process_upsell( $fdata ) {

		$fdata             = $fdata['form_data'];
		$cookies           = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
		$current_page      = ( $this->options['https_enabled'] == 1 ? str_replace( 'http://', 'https://', $fdata['currentPage'] ) : $fdata['currentPage'] );
		$upsell_page       = 1;
		$thank_you_page    = $this->limelight_get_url( $this->campaign_data->web_pages->thank_you->page_id );
		$upsell_page_1     = $this->limelight_get_url( $this->campaign_data->web_pages->upsell_page_1->page_id );
		$params            = array();

		if ( $this->campaign_data->web_pages->upsell_page_2->page_id ) {
			$upsell_page_2 = $this->limelight_get_url( $this->campaign_data->web_pages->upsell_page_2->page_id );
		}

		if ( $this->campaign_data->web_pages->upsell_page_3->page_id ) {
			$upsell_page_3 = $this->limelight_get_url( $this->campaign_data->web_pages->upsell_page_3->page_id );
		}

		if ( $upsell_page_2 && $current_page == $upsell_page_1 ) {
			$redirect_to = $upsell_page_2;
		} elseif ( $current_page == $upsell_page_2 ) {
			$upsell_page = 2;
			$redirect_to = ( $upsell_page_3 ? $upsell_page_3 : $thank_you_page );
		} elseif ( $current_page == $upsell_page_3 ) {
			$redirect_to = $thank_you_page;
			$upsell_page = 3;
		} else {
			$redirect_to = $thank_you_page;
		}

		foreach ( json_decode( stripslashes( $cookies->affiliates ) ) as $affiliate => $value ) {
			$params[$affiliate] = $value;
		}

		$params = array_merge( $params, array(
			'upsell_page'               => $upsell_page,
			'previousOrderId'           => $cookies->orderId,
			'ipAddress'                 => $_SERVER['REMOTE_ADDR'],
			'shippingId'                => $cookies->shippingId,
			'productId'                 => $fdata['productId'],
			'tranType'                  => 'Sale',
			'method'                    => 'NewOrderCardOnFile',
			'utm_medium'                => $fdata['utm_medium'],
			'utm_source'                => $fdata['utm_source'],
			'utm_campaign'              => $fdata['utm_campaign'],
			'utm_term'                  => $fdata['utm_term'],
			'utm_content'               => $fdata['utm_content'],
			'device_category'           => $fdata['device_category'],
			'initializeNewSubscription' => 1,
			'campaignId'                => $this->options['campaign_id'],
		) );

		if ( $this->options['group_upsells'] ) {
			
			$storage = array(
				'limelightUpsellProductIds' => trim( $cookies->upsellProductIds . ',' . $fdata['productId'], ',' ),
			);

			$result = array(
				'storage'     => $storage,
				'redirect_to' => $redirect_to,
				'javascript'  => $this->echo_javascript(),
				'html'        => '',
			);

			if ( $redirect_to == $thank_you_page ) {

				$params = array_merge( $params, array(
					'upsellCount'      => 1,
					'upsellProductIds' => $cookies->upsellProductIds,
				) );

				$result = $this->api_transact( $params, $redirect_to );

			}

		} else {

			$result = $this->api_transact( $params, $redirect_to );

		}

		return $result;
	}

	private function process_new_prospect_form( $fdata ) {

		$fdata       = $fdata['form_data'];
		$redirect_to = $this->limelight_get_url( $this->campaign_data->web_pages->check_out->page_id );
		$params      = array();
		$cookies     = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
		
		foreach ( json_decode( stripslashes( $cookies->affiliates ) ) as $affiliate => $value ) {
			$params[$affiliate] = $value;
		}

		$params = array_merge( $params, array(
			'opt_in'          => $fdata['opt_in'],
			'method'          => 'NewProspect',
			'productId'       => $fdata['productId'],
			'shippingId'      => $fdata['shippingId'],
			'firstName'       => $fdata['firstName'],
			'lastName'        => $fdata['lastName'],
			'address1'        => $fdata['address1'],
			'address2'        => $fdata['address2'],
			'city'            => $fdata['city'],
			'state'           => $fdata['state'],
			'zip'             => $fdata['zip'],
			'country'         => $fdata['country'],
			'phone'           => $fdata['phone'],
			'email'           => $fdata['email'],
			'ipAddress'       => $_SERVER['REMOTE_ADDR'],
			'utm_medium'      => $fdata['utm_medium'],
			'utm_source'      => $fdata['utm_source'],
			'utm_campaign'    => $fdata['utm_campaign'],
			'utm_term'        => $fdata['utm_term'],
			'utm_content'     => $fdata['utm_content'],
			'device_category' => $fdata['device_category'],
			'notes'           => 'Product ID: ' . $fdata['productId'] . '. ',
			'campaignId'      => $this->options['campaign_id'],
		) );

		return $this->api_transact( $params, $redirect_to );

		wp_die();
	}

	private function process_my_account_form( $fdata ) {

		$fdata       = $fdata['form_data'];
		$params      = array();
		$redirect_to = $this->limelight_get_url( $this->campaign_data->web_pages->my_account->page_id );
		$actions     = $values = $order_ids = '';
		$order_id    = array_shift( $fdata );
		
		foreach( $fdata as $key => $value ) {
			$actions   .= $key . ',';
			$values    .= $value . ',';
			$order_ids .= $order_id . ',';
		}
		
		$actions   = rtrim( $actions, ',' );
		$values    = rtrim( $values, ',' );
		$order_ids = rtrim( $order_ids, ',' );

		$params = array_merge( $params, array(
			'method'    => 'order_update',
			'sync_all'  => '1',
			'order_ids' => $order_ids,
			'actions'   => $actions,
			'values'    => $values,
		) );

		wp_update_user( array( 
			'ID'           => get_current_user_id(),
			'nickname'     => $fdata['shipping_first_name'],
			'display_name' => $fdata['shipping_first_name'],
			'first_name'   => $fdata['shipping_first_name'],
			'last_name'    => $fdata['shipping_last_name'],
			'user_email'   => $fdata['email'],
		) );
		
		return $this->membership_handling( $params, $redirect_to );

	}

	private function process_order_history_form( $fdata ) {

		$fdata	= $fdata['form_data'];
		$params	= array();
		
		if ( $fdata['order_details_page'] ) {

			$redirect_to = $this->limelight_get_url( $this->campaign_data->web_pages->order_details->page_id );
			$params      = array_merge( $params, array(
				'order_details_page' => $fdata['order_details_page'],
			) );

		} else {

			$redirect_to = $this->limelight_get_url( $this->campaign_data->web_pages->order_history->page_id );
			$params = array_merge( $params, array(
				'order_history_page'     => $fdata['page_num'],
				'order_history_per_page' => $fdata['per_page'],
			) );

		}

		return $this->membership_handling( $params, $redirect_to );

	}

	private function process_order_details_form( $fdata ) {

		$fdata       = $fdata['form_data'];
		$params      = array();
		$redirect_to = $this->limelight_get_url( $this->campaign_data->web_pages->order_details->page_id );
		$actions     = $values = $order_ids = '';
		$order_id    = array_shift( $fdata );

		foreach ( $fdata as $key => $value) {
			$actions   .= $key . ',';
			$values    .= $value . ',';
			$order_ids .= $order_id . ',';
		}

		$actions   = rtrim( $actions, ',' );
		$values    = rtrim( $values, ',' );
		$order_ids = rtrim( $order_ids, ',' );

		$params = array_merge( $params, array(
			'method'    => 'order_update',
			'order_ids' => $order_ids,
			'actions'   => $actions,
			'values'    => $values,
		) );

		return $this->membership_handling( $params, $redirect_to );

	}

	private function process_3d_verify( $fdata ) {

		$fdata = $fdata['form_data'];

		if ( ! empty( $fdata ) ) {
			$token_params     = array(
				'username' => $this->options['api_user_name'],
				'password' => $this->options['api_password'],
			);

			$verify_end_point = "https://{$this->options['app_key']}.limelightcrm.com/admin/alternative_provider/limelight_3d_verify.php";
			$token_end_point  = "https://{$this->options['app_key']}.limelightcrm.com/admin/api/api_token.php?" . http_build_query( $token_params );

			if ( $token_response  = wp_remote_retrieve_body( wp_remote_get( $token_end_point ) ) ) {
				parse_str( $token_response, $raw_token_data );
				$token = $raw_token_data['token'];
			}

			if ( ! empty( $fdata['products'] ) ) {

				$temp_products      = json_decode( stripslashes( $fdata['products'] ), true );
				$product_count      = count( $temp_products );
				$upsell_product_ids = array();

				if ( $product_count > 1 ) {
					unset( $fdata['productId'] );
					$fdata = array_merge( $fdata, array(
						'productId'   => $temp_products[0]['product_id'],
						'upsellCount' => 1
					) );

					foreach ( $temp_products as $temp_product ) {
						$product_qty_x          = 'product_qty_' . $temp_product['product_id'];
						$fdata[$product_qty_x]  = $temp_product['product_quantity'];
						$upsell_product_id      = $temp_product['product_id'];
						array_push( $upsell_product_ids, $upsell_product_id );
					}

					$replace_me = array_search( $temp_products[0]['product_id'], $upsell_product_ids );
					unset( $upsell_product_ids[$replace_me] );

					$upsell_product_ids        = implode( ',', $upsell_product_ids );
					$fdata['upsellProductIds'] = $upsell_product_ids;
				} else {
					$product_qty_x = 'product_qty_' . $temp_products[0]['product_id'];
					$fdata = array_merge( $fdata, array(
						'productId'    => $temp_products[0]['product_id'],
						$product_qty_x => $temp_products[0]['product_quantity']
					) );
				}

				unset( $fdata['products'] );
			}

			$unique_id       = uniqid();
			$verify_response = <<<HTML
				<html>
				<head>
				</head>
				<body onload="document.get3d.submit();">
				<form name="get3d" action="{$verify_end_point}" id="get3d" method="post">
					<input type="hidden" name="transactionId" data-threeds="id" value="{$unique_id}">
					<!--CREDS-->
					<input type="hidden" name="gateway_id" value="{$this->options['3d_gateway_id']}">
					<input type="hidden" name="x_relay_url" value="{$fdata['currentPage']}">
					<input type="hidden" name="ipAddress" value="{$_SERVER['REMOTE_ADDR']}">
					<input type="hidden" name="x_card_num" id="js-cc-num" data-threeds="pan" value="{$fdata['x_card_num']}">
					<input type="hidden" name="x_amount" data-threeds="amount" value="{$fdata['x_amount']}">
					<input type="hidden" name="x_exp_month" data-threeds="month" value="{$fdata['x_exp_month']}">
					<input type="hidden" name="x_exp_year" data-threeds="year" value="{$fdata['x_exp_year']}">

HTML;
			unset( $fdata['3d_verify'] );
			unset( $fdata['currentPage'] );
			unset( $fdata['x_card_num'] );
			unset( $fdata['x_amount'] );
			unset( $fdata['x_exp_month'] );
			unset( $fdata['x_exp_year'] );

			foreach ( $fdata as $k => $v ) {
				$verify_response .= <<<HTML
					<input type="hidden" name="{$k}" value="{$v}">

HTML;
			}

			$verify_response .= '</form></body></html>';

		}

		if ( $token ) {
			$replace = <<<HTML
				<input type="hidden" name="token" value="{$token}">
HTML;
		} else {
			$replace = <<<HTML
				<input type="hidden" name="username" value="{$this->options['api_user_name']}">
				<input type="hidden" name="password" value="{$this->options['api_password']}">
HTML;
		}

		return array(
			'html' => str_replace( '<!--CREDS-->', $replace, $verify_response ),
		);

		wp_die();
	}

	private function process_check_out_form( $fdata ) {

		$redirect_to = ( $this->campaign_data->any_upsells == 1 ? $this->limelight_get_url( $this->campaign_data->web_pages->upsell_page_1->page_id ) :  $this->limelight_get_url( $this->campaign_data->web_pages->thank_you->page_id ) );

		$fdata   = $fdata['form_data'];
		$cookies = json_decode( stripslashes( $_COOKIE['limelight-data'] ) );
		$params  = array();
		
		if ( ! empty( $cookies->affiliates ) ) {
			foreach ( json_decode( stripslashes( $cookies->affiliates ) ) as $affiliate => $value ) {
				$params[$affiliate] = $value;
			}
		}

		$params = array_merge( $params, array(
			'opt_in'          => $fdata['opt_in'],
			'utm_medium'      => $fdata['utm_medium'],
			'utm_source'      => $fdata['utm_source'],
			'utm_campaign'    => $fdata['utm_campaign'],
			'utm_term'        => $fdata['utm_term'],
			'utm_content'     => $fdata['utm_content'],
			'device_category' => $fdata['device_category']
		) );

		if ( ! empty( $fdata ) ) {

			// 3D Verify Orders

			if ( $this->options['3d_verify_enabled'] == 1 ) {

				$params = array_merge( $params, array(
					'firstName'             => $fdata['firstName'],
					'lastName'              => $fdata['lastName'],
					'billingFirstName'      => $fdata['billingFirstName'],
					'billingLastName'       => $fdata['billingLastName'],
					'creditCardNumber'      => $fdata['creditCardNumber'],
					'CVV'                   => $fdata['CVV'],
					'expirationDate'        => $fdata['expirationDate'],
					'auth_amount'           => $fdata['auth_amount'],
					'productId'             => $fdata['productId'],
					'shippingId'            => $fdata['shippingId'],
					'creditCardType'        => $fdata['creditCardType'],
					'email'                 => $fdata['email'],
					'ipAddress'             => $_SERVER['REMOTE_ADDR'],
					'edigital'              => $fdata['edigital'],
					'sessionId'             => $fdata['sessionId'],
					'shippingAddress1'      => $fdata['shippingAddress1'],
					'shippingAddress2'      => $fdata['shippingAddress2'],
					'shippingCity'          => $fdata['shippingCity'],
					'shippingState'         => $fdata['shippingState'],
					'shippingZip'           => $fdata['shippingZip'],
					'shippingCountry'       => $fdata['shippingCountry'],
					'phone'                 => $fdata['phone'],
					'billingSameAsShipping' => $fdata['billingSameAsShipping'],
					'billingAddress1'       => $fdata['billingAddress1'],
					'billingAddress2'       => $fdata['billingAddress2'],
					'billingCity'           => $fdata['billingCity'],
					'billingState'          => $fdata['billingState'],
					'billingZip'            => $fdata['billingZip'],
					'billingCountry'        => $fdata['billingCountry'],
					'method'                => 'NewOrder',
					'tranType'              => 'Sale',
					'forceGatewayId'        => $this->options['3d_gateway_id'],
				) );

				if ( ! empty( $fdata['prospectId'] ) ) {
					$params['method']     = 'NewOrderWithProspect';
					$params['prospectId'] = $fdata['prospectId'];
					$params['productId']  = $fdata['productId'];
					$params['shippingId'] = $fdata['shippingId'];
				}

				if ( ! empty( $fdata['cart'] ) ) {
					$temp_products      = json_decode( $fdata['products'], true );
					$product_count      = count( $temp_products );
					$upsell_product_ids = array();

					if ( $product_count > 1 ) {
						$params = array_merge( $params, array(
							'shippingId'  => $fdata['shippingId'],
							'productId'   => $temp_products[0]['product_id'],
							'upsellCount' => 1
						) );

						foreach ( $temp_products as $temp_product ) {
							$product_qty_x          = 'product_qty_' . $temp_product['product_id'];
							$params[$product_qty_x] = $temp_product['product_quantity'];
							$upsell_product_id      = $temp_product['product_id'];

							array_push( $upsell_product_ids, $upsell_product_id );
						}

						$replace_me = array_search( $temp_products[0]['product_id'], $upsell_product_ids );
						unset( $upsell_product_ids[$replace_me] );

						$upsell_product_ids         = implode( ',', $upsell_product_ids );
						$params['upsellProductIds'] = $upsell_product_ids;
					} else {
						$product_qty_x = 'product_qty_' . $temp_products[0]['product_id'];
						$params        = array_merge( $params, array(
							'productId'    => $temp_products[0]['product_id'],
							$product_qty_x => $temp_products[0]['product_quantity']
						) );
					}
				}

				if ( ! empty( $fdata['verify_3d_temp_id'] ) ) {
					$params['verify_3d_temp_id'] = $fdata['verify_3d_temp_id'];

					if ( ! empty( $fdata['eci'] ) ) {
						$params = array_merge( $params, array(
							'cavv' => $fdata['cavv'],
							'eci'  => $fdata['eci'],
							'xid'  => $fdata['xid']
						) );
					}
				}

				$new_order_response = $this->api_transact( $params, $redirect_to );

			}

			// Regular orders
			if ( $this->options['3d_verify_enabled'] == 0 ) {
				$params = array_merge( $params, array(
					'edigital'              => $fdata['edigital'],
					'sessionId'             => $fdata['sessionId'],
					'firstName'             => $fdata['firstName'],
					'lastName'              => $fdata['lastName'],
					'shippingAddress1'      => $fdata['shippingAddress1'],
					'shippingAddress2'      => $fdata['shippingAddress2'],
					'shippingCity'          => $fdata['shippingCity'],
					'shippingState'         => $fdata['shippingState'],
					'shippingZip'           => $fdata['shippingZip'],
					'shippingCountry'       => $fdata['shippingCountry'],
					'phone'                 => $fdata['phone'],
					'billingSameAsShipping' => $fdata['billingSameAsShipping'],
					'billingFirstName'      => $fdata['billingFirstName'],
					'billingLastName'       => $fdata['billingLastName'],
					'billingAddress1'       => $fdata['billingAddress1'],
					'billingAddress2'       => $fdata['billingAddress2'],
					'billingCity'           => $fdata['billingCity'],
					'billingState'          => $fdata['billingState'],
					'billingZip'            => $fdata['billingZip'],
					'billingCountry'        => $fdata['billingCountry'],
					'productId'             => $fdata['productId'],
					'shippingId'            => $fdata['shippingId'],
					'creditCardType'        => $fdata['creditCardType'],
					'creditCardNumber'      => $fdata['creditCardNumber'],
					'CVV'                   => $fdata['CVV'],
					'expirationDate'        => $fdata['cardMonth'] . $fdata['cardYear'],
					'email'                 => $fdata['email'],
					'ipAddress'             => $_SERVER['REMOTE_ADDR'],
					'tranType'              => 'Sale',
					'method'                => 'NewOrder'
					) );
			}

			if ( ! empty( $fdata['prospectId'] ) ) {
				$params['method']     = 'NewOrderWithProspect';
				$params['prospectId'] = $fdata['prospectId'];
				$params['productId']  = $fdata['productId'];
				$params['shippingId'] = $fdata['shippingId'];
			}

			if ( ! empty( $fdata['cart'] ) ) {

				$temp_products      = json_decode( stripslashes( $fdata['products'] ), true );
				$product_count      = count( $temp_products );
				$upsell_product_ids = array();

				if ( $product_count > 1 ) {
					$params = array_merge( $params, array(
						'shippingId'  => $fdata['shippingId'],
						'productId'   => $temp_products[0]['product_id'],
						'upsellCount' => 1
					) );

					foreach ( $temp_products as $temp_product ) {
						$product_qty_x          = 'product_qty_' . $temp_product['product_id'];
						$params[$product_qty_x] = $temp_product['product_quantity'];
						$upsell_product_id      = $temp_product['product_id'];

						array_push( $upsell_product_ids, $upsell_product_id );
					}

					$replace_me = array_search( $temp_products[0]['product_id'], $upsell_product_ids );
					unset( $upsell_product_ids[$replace_me] );

					$upsell_product_ids         = implode( ',', $upsell_product_ids );
					$params['upsellProductIds'] = $upsell_product_ids;
				} else {
					$product_qty_x = 'product_qty_' . $temp_products[0]['product_id'];

					$params = array_merge( $params, array(
						'productId'    => $temp_products[0]['product_id'],
						$product_qty_x => $temp_products[0]['product_quantity']
					) );
				}
			}

			if ( ! empty( $fdata['verify_3d_temp_id'] ) ) {
				$params['verify_3d_temp_id'] = $fdata['verify_3d_temp_id'];

				if ( ! empty( $fdata['eci'] ) ) {
					$params = array_merge( $params, array(
						'cavv' => $fdata['cavv'],
						'eci'  => $fdata['eci'],
						'xid'  => $fdata['xid']
					) );
				}
			}

			$params['campaignId'] = $this->options['campaign_id'];
			$new_order_response   = $this->api_transact( $params, $redirect_to );

		}

		return $new_order_response;

		wp_die();
	}

	private function get_label( $text ) {
		return '<label>' . $text . '</label>';
	}

	private function client_build_tpl() {

		$this->client_tpl['limelight_forms'] = <<<TPL
<form method="POST" action="" class="<!--CLASS-->" id="<!--JS_ID-->" novalidate>
TPL;
		$this->client_tpl['limelight_build_cc_types'] = <<<TPL
<option value="<!--TYPE-->"><!--DISPLAY--></option>
TPL;

		$this->client_tpl['limelight_build_cc_months'] = <<<TPL
<option value="<!--VALUE-->"><!--VALUE--> (<!--MONTH-->)</option>
TPL;

		$this->client_tpl['limelight_build_cc_years'] = <<<TPL
<option value="<!--VALUE-->"><!--YEAR--></option>";
TPL;
		$this->client_tpl['limelight_card_types'] = <<<TPL
<!--LABEL-->
<select name="creditCardType" id="js-cc-type"  <!--ATTRIBUTES-->>
	<!--CC_TYPES-->
</select>
TPL;
		$this->client_tpl['limelight_3d_verify_cc_months'] = <<<TPL
<!--LABEL-->
<select name='x_exp_month' data-threeds="month"  <!--ATTRIBUTES-->>
	<!--CC_MONTHS-->
</select>
TPL;
		$this->client_tpl['limelight_3d_verify_cc_number'] = <<<TPL
<!--LABEL-->
<input name='x_card_num' id='js-cc-num' type='TEXT' maxlength=16 data-threeds="pan" <!--ATTRIBUTES-->>
TPL;
		$this->client_tpl['limelight_3d_verify_cc_cvv'] = <<<TPL
<!--LABEL-->
<input name='x_cvv' type='TEXT' maxlength=4 <!--ATTRIBUTES-->>
TPL;
		$this->client_tpl['limelight_3d_verify_cc_years'] = <<<TPL
<!--LABEL-->
<select name='x_exp_year' data-threeds="year" <!--ATTRIBUTES-->>
	<!--CC_YEARS-->
</select>
TPL;
		$this->client_tpl['limelight_cc_number'] = <<<TPL
<!--LABEL-->
<input name='creditCardNumber' id='js-cc-num' type='TEXT' maxlength=16 <!--ATTRIBUTES-->>
TPL;
		$this->client_tpl['limelight_cc_cvv'] = <<<TPL
<!--LABEL-->
<input name='CVV' id="js-cvv" type='TEXT' maxlength=4 <!--ATTRIBUTES-->>
TPL;
		$this->client_tpl['limelight_cc_months'] = <<<TPL
<!--LABEL-->
<select name='cardMonth' <!--ATTRIBUTES-->>
	<!--CC_MONTHS-->
</select>
TPL;
		$this->client_tpl['limelight_cc_years'] = <<<TPL
<!--LABEL-->
<select name='cardYear' <!--ATTRIBUTES-->>
	<!--CC_YEARS-->
</select>
TPL;
		$this->client_tpl['new_prospect_states_multiple_countries'] = <<<TPL
<select <!--ATTRIBUTES--> name='state' id='js-state'>
	<option>Please Select Your Country First</option>
</select>
TPL;
		$this->client_tpl['new_prospect_states_single_country'] = <<<TPL
<select <!--ATTRIBUTES--> name='state' id='js-state'>
	<option value=''>Select Your State</option>
	<!--STATES-->
</select>
TPL;
		$this->client_tpl['new_prospect_state'] = <<<TPL
<input <!--ATTRIBUTES--> type='text' name='state' value=''>
TPL;
		$this->client_tpl['get_new_prospect_states'] = <<<TPL
<option value="<!--VALUE-->"><!--NAME--></option>
TPL;
		$this->client_tpl['get_new_prospect_countries'] = <<<TPL
<option value="<!--COUNTRY-->"><!--COUNTRY--></option>
TPL;
		$this->client_tpl['new_prospect_info_form'] = <<<TPL
<label>First Name</label>
	<input type="text" name="firstName" value="">
<label>Last Name</label>
	<input type="text" name="lastName" value="">
<label>Address</label>
	<input type="text" name="address1" value="">
<label>Address 2</label>
	<input type="text" name="address2" value="">
<label>City</label>
	<input type="text" name="city" value="">
<label>State</label>
	<!--STATES-->
<label>Zip</label>
	<input type="text" name="zip" value="">
<label>Country</label>
	<select name="country" id="js-country">
		<!--COUNTRIES-->
	</select>
<label>Phone</label>
	<input type="text" name="phone" value="">
<label>Email</label>
	<input type="text" name="email" value="">
TPL;
		$this->client_tpl['my_account_form'] = <<<TPL
<p>Updating this information will apply to all your current subscriptions.</p>
<table>
<tr>
	<td colspan="2">
			<input type="hidden" name="order_id" value="<!--ORDER_ID-->">
		<label>Email</label>
			<input type="text" name="email" value="<!--CUSTOMER_EMAIL-->">		
		<label>Phone</label>
			<input type="text" name="phone" value="<!--CUSTOMER_PHONE-->">	
	</td>
</tr>
<tr>
<td>
	<h4>Shipping Info</h4>
	<label>First Name</label>
		<input type="text" name="shipping_first_name" value="<!--SHIPPING_FIRST-->">
	<label>Last Name</label>
		<input type="text" name="shipping_last_name" value="<!--SHIPPING_LAST-->">			
	<label>Address</label>
		<input type="text" name="shipping_address1" value="<!--SHIPPING_ADDR1-->">
	<label>Address 2</label>
		<input type="text" name="shipping_address2" value="<!--SHIPPING_ADDR2-->">
	<label>City</label>
		<input type="text" name="shipping_city" value="<!--SHIPPING_CITY-->">
	<label>State</label>
		<input type="text" name="shipping_state" value="<!--SHIPPING_STATE-->">
	<label>Zip</label>
		<input type="text" name="shipping_zip" value="<!--SHIPPING_ZIP-->">
	<label>Country</label>
		<input type="text" name="shipping_country" value="<!--SHIPPING_COUNTRY-->">
</td>
<td>
	<h4>Billing Info</h4>
	<label>First Name</label>
		<input type="text" name="billing_first_name" value="<!--BILLING_FIRST-->">
	<label>Last Name</label>
		<input type="text" name="billing_last_name" value="<!--BILLING_LAST-->">	
	<label>Address</label>
		<input type="text" name="billing_address1" value="<!--BILLING_ADDR1-->">
	<label>Address 2</label>
		<input type="text" name="billing_address2" value="<!--BILLING_ADDR2-->">
	<label>City</label>
		<input type="text" name="billing_city" value="<!--BILLING_CITY-->">
	<label>State</label>
		<input type="text" name="billing_state" value="<!--BILLING_STATE-->">
	<label>Zip</label>
		<input type="text" name="billing_zip" value="<!--BILLING_ZIP-->">
	<label>Country</label>
		<input type="text" name="billing_country" value="<!--BILLING_COUNTRY-->">
</td>
<tr>
</table>
TPL;
		$this->client_tpl['order_history_form'] = <<<TPL
<!--ORDER_HISTORY-->
<input id="order_details_page" name="order_details_page" type="hidden" value="0">
TPL;
		$this->client_tpl['order_details_form'] = <<<TPL
<table>
<tr>
	<td colspan="3" class="limelight-order-id">
		#<!--ORDER_ID-->	
	</td>
	<td colspan="2" class="limelight-order-date">
		<!--ORDER_DATE-->
	</td>
	<td class="limelight-order-date">

	</td>	
</tr>
<tr>
	<td colspan="6">
		<input type="hidden" name="order_id" value="<!--ORDER_ID-->">
		<label>First Name</label>
			<input type="text" name="shipping_first_name" value="<!--SHIPPING_FIRST-->">
		<label>Last Name</label>
			<input type="text" name="shipping_last_name" value="<!--SHIPPING_LAST-->">	
		<label>Email</label>
			<input type="text" name="email" value="<!--CUSTOMER_EMAIL-->">		
		<label>Phone</label>
			<input type="text" name="phone" value="<!--CUSTOMER_PHONE-->">	
	</td>
</tr>
<tr>
	<td colspan="3">
		<h4>Shipping Info</h4>		
		<label>Address</label>
			<input type="text" name="shipping_address1" value="<!--SHIPPING_ADDR1-->">
		<label>Address 2</label>
			<input type="text" name="shipping_address2" value="<!--SHIPPING_ADDR2-->">
		<label>City</label>
			<input type="text" name="shipping_city" value="<!--SHIPPING_CITY-->">
		<label>State</label>
			<input type="text" name="shipping_state" value="<!--SHIPPING_STATE-->">
		<label>Zip</label>
			<input type="text" name="shipping_zip" value="<!--SHIPPING_ZIP-->">
		<label>Country</label>
			<input type="text" name="shipping_country" value="<!--SHIPPING_COUNTRY-->">
	</td>
	<td colspan="3">
		<h4>Billing Info</h4>
		<label>Address</label>
			<input type="text" name="billing_address1" value="<!--BILLING_ADDR1-->">
		<label>Address 2</label>
			<input type="text" name="billing_address2" value="<!--BILLING_ADDR2-->">
		<label>City</label>
			<input type="text" name="billing_city" value="<!--BILLING_CITY-->">
		<label>State</label>
			<input type="text" name="billing_state" value="<!--BILLING_STATE-->">
		<label>Zip</label>
			<input type="text" name="billing_zip" value="<!--BILLING_ZIP-->">
		<label>Country</label>
			<input type="text" name="billing_country" value="<!--BILLING_COUNTRY-->">
	</td>
</tr>
<!--LINE_ITEMS-->
<tr>
	<td colspan="6" class="limelight-order-total">
		Total: $<!--ORDER_TOTAL-->
	</td>
</tr>
</table>
TPL;
	}
}

<?php
/*************************************************************************
 *
 * Lime Light CRM, Inc.
 * __________________
 *
 * [2008] - [2017] Lime Light CRM, Inc.
 * All Rights Reserved.
 *
 * NOTICE:  All information contained herein is, and remains
 * the property of Lime Light CRM, Inc.  The intellectual and
 * technical concepts contained herein are proprietary to
 * Lime Light CRM, Inc. and may be covered by U.S. and Foreign
 * Patents, patents in process, and are protected by trade secret
 * or copyright law. Reproduction of this material is strictly
 * forbidden unless prior written permission is obtained from
 * Lime Light CRM, Inc.
 *
 * @link     https://limelightcrm.com
 */

wp_localize_script( 'limelight-main-js', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

$current_page  = sanitize_text_field( $_GET['page'] );
$page_settings = array(
	'LimeLight CRM' => array(
		'group'   => 'limelight_admin_group',
		'section' => 'Limelight_Admin',
	),
	'Error Responses'      => array(
		'group'   => 'limelight_error_group',
		'section' => 'limelight_error_responses',
	),
	'Store Settings'       => array(
		'group'   => 'limelight_store_group',
		'section' => 'limelight_store_settings',
	),
);

ob_start();

foreach ($page_settings as $page => $settings) {
	if ( $current_page == $page ) {
		settings_fields( $settings['group'] );
		do_settings_sections( $settings['section'] );
		submit_button( __( 'Update Settings' ), 'button button-primary', 'js-submit-button' );
	}
}

$tokens       = array(
	'<!--ERRORS-->'         => ( ! empty( $error ) ? "<p><span class='limelight-form-error-is-active'>ERROR: {$error}</span></p>" : '' ),
	'<!--ADMIN_FIELDS-->'   => ob_get_clean(),
	'<!--COPYRIGHT_DATE-->' => ( date( 'Y' ) ),
	'<!--WORKFLOW_PAGES-->' => '',
);

if ( ! empty( $this->campaign_data ) ) {
	$tokens['<!--WORKFLOW_PAGES-->'] = $this->fill_tpl( $this->admin_tpl['workflow_pages'], array(
		'<!--PRODUCTS_PAGE_URL-->'         => get_post_type_archive_link( 'products' ),
		'<!--HOME_PAGE_URL-->'             => $this->limelight_get_url( $this->campaign_data->web_pages->home_page->page_id ),
		'<!--BLOG_PAGE_URL-->'             => $this->limelight_get_url( $this->campaign_data->web_pages->blog_page->page_id ),
		'<!--NEW_PROSPECT_URL-->'          => $this->limelight_get_url( $this->campaign_data->web_pages->new_prospect->page_id ),
		'<!--SINGLE_PAGE_URL-->'           => $this->limelight_get_url( $this->campaign_data->web_pages->single_page->page_id ),
		'<!--ADVANCED_NEW_PROSPECT_URL-->' => $this->limelight_get_url( $this->campaign_data->web_pages->new_prospect_advanced->page_id ),
		'<!--ADVANCED_SINGLE_PAGE_URL-->'  => $this->limelight_get_url( $this->campaign_data->web_pages->single_page_advanced->page_id ),
		'<!--TERMS_URL-->'                 => $this->limelight_get_url( $this->campaign_data->web_pages->terms->page_id ),
		'<!--PRIVACY_URL-->'               => $this->limelight_get_url( $this->campaign_data->web_pages->privacy->page_id ),
		'<!--CONTACT_URL-->'               => $this->limelight_get_url( $this->campaign_data->web_pages->contact->page_id ),
		'<!--MY_ACCOUNT-->'                => $this->limelight_get_url( $this->campaign_data->web_pages->my_account->page_id ),
		'<!--ORDER_HISTORY-->'             => $this->limelight_get_url( $this->campaign_data->web_pages->order_history->page_id ),
		'<!--ORDER_DETAILS-->'             => $this->limelight_get_url( $this->campaign_data->web_pages->order_details->page_id ),
	) );
}

$updated = ( isset( $_GET['settings-updated'] ) && sanitize_text_field( $_GET['settings-updated'] ) == 'true' );
$pages   = array(
	'LimeLight CRM' => 'admin_index',
	'Error Responses'      => 'admin_responses',
	'Store Settings'       => 'admin_store',
);

foreach ( $pages as $page => $template ) {
	if ( $current_page == $page ) {
		if ( $updated ) {
			if ( $page == 'LimeLight CRM' ) {
				if ( empty( $this->api_check_credentials() ) ) {
					$this->api_get_campaign_data();
				}
			}
			$this->admin_build_tpl();
		}
		$this->display_tpl( $this->admin_tpl[$template], $tokens );
	}
}

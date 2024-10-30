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

class Limelight_Error_Response {

	const
		ERROR_101  = 'Order is 3DS and needs to go to bank url (use 3DS redirect method)',
		ERROR_123  = 'Prepaid Credit Cards Are Not Accepted',
		ERROR_200  = 'Invalid login credentials',
		ERROR_201  = 'three_d_redirect_url is required',
		ERROR_303  = 'Invalid upsell product Id of (XXX) found',
		ERROR_304  = 'Invalid first name of (XXX) found',
		ERROR_305  = 'Invalid last name of (XXX) found',
		ERROR_306  = 'Invalid shipping address1 of (XXX) found',
		ERROR_307  = 'Invalid shipping city of (XXX) found',
		ERROR_308  = 'Invalid shipping state of (XXX) found',
		ERROR_309  = 'Invalid shipping zip of (XXX) found',
		ERROR_310  = 'Invalid shipping country of (XXX) found',
		ERROR_311  = 'Invalid billing address1 of (XXX) found',
		ERROR_312  = 'Invalid billing city of (XXX) found',
		ERROR_313  = 'Invalid billing state of (XXX) found',
		ERROR_314  = 'Invalid billing zip of (XXX) found',
		ERROR_315  = 'Invalid billing country of (XXX) found',
		ERROR_316  = 'Invalid phone number of (XXX) found',
		ERROR_317  = 'Invalid email address of (XXX) found',
		ERROR_318  = 'Invalid credit card type of (XXX) found',
		ERROR_319  = 'Invalid credit card number of (XXX) found',
		ERROR_320  = 'Invalid expiration date of (XXX) found',
		ERROR_321  = 'Invalid IP address of (XXX) found',
		ERROR_322  = 'Invalid shipping id of (XXX) found',
		ERROR_323  = "CVV is required for tranType 'Sale'",
		ERROR_324  = 'Supplied CVV of (XXX) has an invalid length',
		ERROR_325  = 'Shipping state must be 2 characters for a shipping country of US',
		ERROR_326  = 'Billing state must be 2 characters for a billing country of US',
		ERROR_327  = 'Invalid payment type of XXX',
		ERROR_328  = 'Expiration month of (XXX) must be between 01 and 12',
		ERROR_329  = 'Expiration date of (XXX) must be 4 digits long',
		ERROR_330  = 'Could not find prospect record',
		ERROR_331  = 'Missing previous OrderId',
		ERROR_332  = 'Could not find original order Id',
		ERROR_333  = 'Order has been black listed',
		ERROR_334  = 'The credit card number or email address has already purchased this product(s)',
		ERROR_335  = 'Invalid Dynamic Price Format',
		ERROR_336  = 'checkRoutingNumber must be passed when checking is the payment type is checking or eft_germany',
		ERROR_337  = 'checkAccountNumber must be passed when checking is the payment type is checking or eft_germany',
		ERROR_338  = 'Invalid campaign to perform sale on.  No checking account on this campaign.',
		ERROR_339  = 'tranType missing or invalid',
		ERROR_340  = 'Invalid employee username of (XXX) found',
		ERROR_341  = 'Campaign Id (XXX) restricted to user (XXX)',
		ERROR_342  = 'The credit card has expired',
		ERROR_400  = 'Invalid campaign Id of (XXX) found',
		ERROR_411  = 'Invalid subscription field',
		ERROR_412  = 'Missing subscription field',
		ERROR_413  = 'Product is not subscription based',
		ERROR_414  = 'The product that is being purchased has a different subscription type than the next recurring product',
		ERROR_415  = 'Invalid subscription value',
		ERROR_600  = 'Invalid product Id of (XXX) found',
		ERROR_666  = 'User does not have permission to use this method',
		ERROR_667  = 'This user account is currently disabled',
		ERROR_668  = 'Unauthorized IP Address',
		ERROR_669  = 'Unauthorized to access campaign',
		ERROR_700  = 'Invalid method supplied',
		ERROR_705  = 'Order is not 3DS related',
		ERROR_800  = 'Transaction was declined',
		ERROR_900  = 'SSL is required to run a transaction',
		ERROR_901  = 'Alternative payment payer id is required for this payment type',
		ERROR_902  = 'Alternative payment token is required for this payment type',
		ERROR_1000 = 'Could not add record',
		ERROR_1001 = 'Invalid login credentials supplied',
		ERROR_1002 = 'Invalid method supplied';

		private
			$error,
			$error_codes = array();

		public function __construct( $error ) {
			$this->error       = $error;
			$admin             = new Limelight_Admin();
			$this->error_codes = $admin->error_codes;
		}

		public function get_response_message() {
			$error_code = 'error_' . $this->error;

			if ( ! empty( $this->error_codes[$error_code] ) ) {
				$message = $this->error_codes[$error_code];
			} else {
				$error_code = 'ERROR_' . $this->error;
				$message    = constant( 'self::' . $error_code );
			}
			
			return <<<HTML
				<p>$message</p>
				<p>Please try again</p>
HTML;
		}
}

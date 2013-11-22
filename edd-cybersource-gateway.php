<?php
/*
Plugin Name: Easy Digital Downloads - Cybersource Gateway
Plugin URL: http://easydigitaldownloads.com/extension/cybersource-gateway
Description: Cybersource Payment Gateway for Easy Digital Downloads
Version: 1.0
Author: WebDevStudios
Author URI: http://webdevstudios.com/
Contributors: webdevstudios, sc0ttkclark
*/

// Load the EDD license handler only if not already loaded. Must be placed in the main plugin file
if( !class_exists( 'EDD_License' ) ) {
	include( dirname( __FILE__ ) . '/includes/EDD_License_Handler.php' );
}

// Instantiate the licensing / updater. Must be placed in the main plugin file
$license = new EDD_License( __FILE__, 'Cybersource Gateway', '1.0', 'WebDevStudios' );

/**
 * Register Cybersource Gateway textdomain
 *
 * @since 1.0
 */
function cybersource_edd_load_textdomain() {

	load_plugin_textdomain( 'cybersource_edd', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

}
add_action( 'plugins_loaded', 'cybersource_edd_load_textdomain' );

/**
 * Register Cybersource Gateway
 *
 * @param array $gateways EDD Gateway configuration array
 *
 * @return array EDD Gateway configuration array
 *
 * @since 1.0
 */
function cybersource_edd_register_gateway( $gateways ) {

	$gateways[ 'cybersource_gateway' ] = array(
		'admin_label' => 'Cybersource Gateway',
		'checkout_label' => __( 'Cybersource Gateway', 'cybersource_edd' )
	);

	return $gateways;

}
add_filter( 'edd_payment_gateways', 'cybersource_edd_register_gateway' );

/**
 * Add Cybersource Gateway settings
 *
 * @param array $edd_options EDD Settings
 *
 * @return array EDD Settings
 */
function cybersource_edd_add_settings( $edd_options ) {

	$cybersource_gateway_settings = array(
		array(
			'id' => 'cybersource_gateway_settings',
			'name' => '<strong>' . __( 'Cybersource Gateway Settings', 'cybersource_edd' ) . '</strong>',
			'desc' => __( 'Configure the gateway settings', 'cybersource_edd' ),
			'type' => 'header'
		),
		array(
			'id' => 'cybersource_merchant_id',
			'name' => __( 'Merchant ID', 'cybersource_edd' ),
			'desc' => __( 'This is the same merchant ID you use to log into the CyberSource Business Center.', 'cybersource_edd' ),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'cybersource_live_security_key',
			'name' => __( 'Live Transaction Security Key', 'cybersource_edd' ),
			'desc' => __( 'You can find this by logging into your "Live" CyberSource Business Center, going to Account Management &gt; Transaction Security Keys &gt; Security Keys for the SOAP Toolkit API, and then click \'Generate\'.', 'cybersource_edd' ),
			'type' => 'textarea',
			'size' => 'regular'
		),
		array(
			'id' => 'cybersource_test_security_key',
			'name' => __( 'Test Transaction Security Key', 'cybersource_edd' ),
			'desc' => __( 'You can find this by logging into your "Test" CyberSource Business Center, going to Account Management &gt; Transaction Security Keys &gt; Security Keys for the SOAP Toolkit API, and then click \'Generate\'.', 'cybersource_edd' ),
			'type' => 'textarea',
			'size' => 'regular'
		),
		array(
			'id' => 'cybersource_sale_method',
			'name' => __( 'Transaction Sale Method', 'cybersource_edd' ),
			'desc' => __( '', 'cybersource_edd' ),
			'type' => 'select',
			'options' => array(
				'auth_capture' => __( 'Authorize and Capture - Charge the Credit Card for the total amount', 'cybersource_edd' ),
				'auth' => __( 'Authorize - Only authorize the Credit Card for the total amount', 'cybersource_edd' )
			),
			'std' => 'auth_capture'
		)
	);

	$edd_options = array_merge( $edd_options, $cybersource_gateway_settings );

	return $edd_options;

}
add_filter( 'edd_settings_gateways', 'cybersource_edd_add_settings' );

/**
 * Process payments for Cybersource Gateway
 *
 * @param array $purchase_data EDD purchase data
 *
 * @since 1.0
 */
function cybersource_edd_process_payment( $purchase_data ) {

	/**
	 * @var $edd_options array
	 */
	global $edd_options;

	/**********************************
	 * check for errors here
	 **********************************/

	/*
	// errors can be set like this
	if( ! isset($_POST['card_number'] ) ) {
		// error code followed by error message
		edd_set_error('empty_card', __('You must enter a card number', 'edd'));
	}
	*/

	// check for any stored errors
	$errors = edd_get_errors();

	$fail = false;

	if ( !$errors ) {
		$purchase_summary = edd_get_purchase_summary( $purchase_data );

		/****************************************
		 * setup the payment details to be stored
		 ****************************************/

		$payment_data = array(
			'price' => $purchase_data[ 'price' ],
			'date' => $purchase_data[ 'date' ],
			'user_email' => $purchase_data[ 'user_email' ],
			'purchase_key' => $purchase_data[ 'purchase_key' ],
			'currency' => edd_get_currency(),
			'downloads' => $purchase_data[ 'downloads' ],
			'cart_details' => $purchase_data[ 'cart_details' ],
			'user_info' => $purchase_data[ 'user_info' ],
			'status' => 'pending'
		);

		// record the pending payment
		$payment_id = edd_insert_payment( $payment_data );

		if ( 0 < $payment_id ) {
			try {
				// Make payment with Cybersource Gateway
				$transaction_id = cybersource_edd_do_payment( $purchase_data, $payment_data, $payment_id );

				// once a transaction is successful, set the purchase to complete
				edd_update_payment_status( $payment_id, 'complete' );

				// record transaction ID, or any other notes you need
				edd_insert_payment_note( $payment_id, 'Transaction ID: ' . $transaction_id );

				// go to the success page
				edd_send_to_success_page();
			}
			catch ( Exception $e ) {
				// Error sent back from Cybersource Gateway
				edd_set_error( 'cybersource_error', $e->getMessage() );

				$fail = true;
			}
		}
		// Payment not saved
		else {
			edd_set_error( 'cybersource_payment', __( 'Unable to save order, please contact customer service.', 'cybersource_edd' ) );

			$fail = true;
		}
	}
	else {
		$fail = true; // errors were detected
	}

	if ( !edd_get_errors() || !$fail ) {
		// if errors are present, send the user back to the purchase page so they can be corrected
		edd_send_back_to_checkout( '?payment-mode=' . $purchase_data[ 'post_data' ][ 'edd-gateway' ] );
	}

}
add_action( 'edd_gateway_cybersource_gateway', 'cybersource_edd_process_payment' );

/**
 * Make Payment through Cybersource Gateway
 *
 * @param array $purchase_data EDD purchase data
 *
 * @throws Exception
 *
 * @return int Cybersource Transaction ID
 *
 * @since 1.0
 */
function cybersource_edd_do_payment( $purchase_data, $payment_id ) {

	global $edd_options;

	// $edd_options contains an array of all options
	// $edd_options[ 'cybersource_whatever' ]

	/**********************************
	 * set transaction mode
	 **********************************/

	if ( edd_is_test_mode() ) {
		$url = 'https://ics2wstest.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.26.wsdl';
		$security_key = $edd_options[ 'cybersource_test_security_key' ];
	}
	else {
		$url = 'https://ics2ws.ic3.com/commerce/1.x/transactionProcessor/CyberSourceTransaction_1.26.wsdl';
		$security_key = $edd_options[ 'cybersource_live_security_key' ];
	}

	$paymentaction = 'Sale';

	if ( 'auth' == $edd_options[ 'cybersource_sale_method' ] ) {
		$paymentaction = 'Authorization';
	}

	/**********************************
	 * Purchase data comes in like this:
		$purchase_data = array(array(14) {
		  ["downloads"]=>
		  array(1) {
			[0]=>
			array(3) {
			  ["id"]=>
			  string(3) "349"
			  ["options"]=>
			  array(0) {
			  }
			  ["quantity"]=>
			  int(1)
			}
		  }
		  ["fees"]=>
		  array(0) {
		  }
		  ["subtotal"]=>
		  int(25)
		  ["discount"]=>
		  float(0)
		  ["tax"]=>
		  int(0)
		  ["price"]=>
		  float(25)
		  ["purchase_key"]=>
		  string(32) "5fb723c1c81e229a83eabb903fe3d6db"
		  ["user_email"]=>
		  string(15) "admin@local.dev"
		  ["date"]=>
		  string(19) "2013-10-26 09:12:06"
		  ["user_info"]=>
		  array(6) {
			["id"]=>
			int(1)
			["email"]=>
			string(15) "admin@local.dev"
			["first_name"]=>
			string(5) "Customer"
			["last_name"]=>
			string(5) "Name"
			["discount"]=>
			string(4) "none"
			["address"]=>
			array(6) {
			  ["line1"]=>
			  string(22) "1234 Main St."
			  ["line2"]=>
			  bool(false)
			  ["city"]=>
			  string(13) "Customer City"
			  ["state"]=>
			  string(2) "ST"
			  ["country"]=>
			  string(2) "US"
			  ["zip"]=>
			  string(5) "12345"
			}
		  }
		  ["post_data"]=>
		  array(17) {
			["edd_email"]=>
			string(15) "admin@local.dev"
			["edd_first"]=>
			string(5) "Customer"
			["edd_last"]=>
			string(5) "Name"
			["card_number"]=>
			string(16) "4242424242424242"
			["card_cvc"]=>
			string(3) "123"
			["card_name"]=>
			string(11) "Customer Name"
			["card_exp_month"]=>
			string(1) "1"
			["card_exp_year"]=>
			string(4) "2016"
			["card_address"]=>
			string(22) "1234 Main St."
			["card_address_2"]=>
			string(0) ""
			["card_city"]=>
			string(13) "Customer City"
			["card_zip"]=>
			string(5) "12345"
			["billing_country"]=>
			string(2) "US"
			["card_state"]=>
			string(2) "ST"
			["edd-user-id"]=>
			string(1) "1"
			["edd_action"]=>
			string(8) "purchase"
			["edd-gateway"]=>
			string(19) "gateway_name"
		  }
		  ["cart_details"]=>
		  array(1) {
			[0]=>
			array(6) {
			  ["name"]=>
			  string(7) "Download Name"
			  ["id"]=>
			  string(3) "349"
			  ["item_number"]=>
			  array(3) {
				["id"]=>
				string(3) "349"
				["options"]=>
				array(0) {
				}
				["quantity"]=>
				int(1)
			  }
			  ["price"]=>
			  string(2) "25"
			  ["quantity"]=>
			  int(1)
			  ["tax"]=>
			  float(0)
			}
		  }
		  ["gateway"]=>
		  string(19) "cybersource_gateway"
		  ["card_info"]=>
		  array(11) {
			["card_name"]=>
			string(11) "Customer Name"
			["card_number"]=>
			string(16) "4242424242424242"
			["card_cvc"]=>
			string(3) "123"
			["card_exp_month"]=>
			string(1) "1"
			["card_exp_year"]=>
			string(4) "2016"
			["card_address"]=>
			string(22) "1234 Main St"
			["card_address_2"]=>
			string(0) ""
			["card_city"]=>
			string(13) "Customer City"
			["card_state"]=>
			string(2) "ST"
			["card_country"]=>
			string(2) "US"
			["card_zip"]=>
			string(5) "12345"
		  }
		}
	 */

	$discount = $purchase_data[ 'discount' ];
	$taxes = $purchase_data[ 'tax' ];
	$total = $purchase_data[ 'price' ];

	$card_type = cybersource_edd_get_card_type( $purchase_data[ 'card_info' ][ 'card_number' ] );

	if ( empty( $card_type ) ) {
		throw new Exception( 'Invalid Credit Card' );
	}

	$card_type = $card_type[ 'name' ];

	$expiration = array(
		'month' => (int) $purchase_data[ 'card_info' ][ 'card_exp_month' ],
		'year' => (int) $purchase_data[ 'card_info' ][ 'card_exp_year' ]
	);

	if ( $expiration[ 'month' ] < 10 ) {
		$expiration[ 'month' ] = '0' . $expiration[ 'month' ];
	}

	if ( $expiration[ 'year' ] < 100 ) {
		$expiration[ 'year' ] = '20' . $expiration[ 'year' ];
	}

	$default_address = array(
		'first-name' => $purchase_data[ 'user_info' ][ 'first_name' ],
		'last-name' => $purchase_data[ 'user_info' ][ 'last_name' ],
		'company-name' => '',
		'address1' => $purchase_data[ 'user_info' ][ 'address' ][ 'line1' ],
		'address2' => $purchase_data[ 'user_info' ][ 'address' ][ 'line2' ],
		'city' => $purchase_data[ 'user_info' ][ 'address' ][ 'city' ],
		'state' => $purchase_data[ 'user_info' ][ 'address' ][ 'state' ],
		'zip' => $purchase_data[ 'user_info' ][ 'address' ][ 'zip' ],
		'country' => $purchase_data[ 'user_info' ][ 'address' ][ 'country' ],
		'email' => $purchase_data[ 'user_info' ][ 'email' ],
		'phone' => ''
	);

	$shipping_address = $default_address; // @todo Handle shipping address integration

	$billing_address = array(
		'first-name' => $purchase_data[ 'card_info' ][ 'first_name' ],
		'last-name' => $purchase_data[ 'card_info' ][ 'last_name' ],
		'company-name' => '',
		'address1' => $purchase_data[ 'card_info' ][ 'address' ][ 'line1' ],
		'address2' => $purchase_data[ 'card_info' ][ 'address' ][ 'line2' ],
		'city' => $purchase_data[ 'card_info' ][ 'address' ][ 'city' ],
		'state' => $purchase_data[ 'card_info' ][ 'address' ][ 'state' ],
		'zip' => $purchase_data[ 'card_info' ][ 'address' ][ 'zip' ],
		'country' => $purchase_data[ 'card_info' ][ 'address' ][ 'country' ],
		'email' => $purchase_data[ 'user_info' ][ 'email' ],
		'phone' => ''
	);

	if ( empty( $billing_address[ 'email' ] ) ) {
		$billing_address[ 'email' ] = $purchase_data[ 'user_email' ];
	}

	if ( empty( $shipping_address[ 'email' ] ) ) {
		$shipping_address[ 'email' ] = $billing_address[ 'user_email' ];
	}

	$request = new stdClass();

	$request->merchantID = $edd_options[ 'cybersource_merchant_id' ];

	$request->merchantReferenceCode = $purchase_data[ 'purchase_key' ];

	// Authorize
	$request->ccAuthService = (object) array( 'run' => 'true' );

	// Capture
	if ( 'Sale' == $paymentaction ) {
		$request->ccCaptureService = (object) array( 'run' => 'true' );
	}

	$request->billTo = (object) array(
		'email' => $billing_address[ 'email' ],
		'firstName' => $billing_address[ 'first-name' ],
		'lastName' => $billing_address[ 'last-name' ],
		'company' => $billing_address[ 'company' ],
		'street1' => $billing_address[ 'address1' ],
		'street2' => $billing_address[ 'address2' ],
		'city' => $billing_address[ 'city' ],
		'state' => $billing_address[ 'state' ],
		'postalCode' => $billing_address[ 'zip' ],
		'country' => $billing_address[ 'country' ],
		'phoneNumber' => $billing_address[ 'phone' ],
		'customerID' => $purchase_data[ 'user_info' ][ 'id' ]
	);

	// @todo support shipping address and other shipping information?

	$request->card = (object) array(
		'accountNumber' => $purchase_data[ 'card_info' ][ 'card_name' ],
		'expirationMonth' => $expiration[ 'month' ],
		'expirationYear' => $expiration[ 'year' ],
		'cvNumber' => $purchase_data[ 'card_info' ][ 'card_cvc' ]
	);

	$request->purchaseTotals = (object) array(
		'grandTotalAmount' => $total,
		'currency' => edd_get_currency()
	);

	$items = array();

	$item_count = 0;

	foreach ( $purchase_data[ 'cart_details' ] as $product ) {
		$price = edd_format_amount( $product[ 'price' ] );

		// @todo Handle taxes? $product[ 'tax' ]
		// @todo Product name? $product[ 'name' ]
		// @todo Product ID? $product[ 'id' ]

		$items[] = (object) array(
			'id' => $item_count,
			'unitPrice' => $price,
			'quantity' => $product[ 'quantity' ]
		);

		$item_count++;
	}

	if ( !empty( $items ) ) {
		$request->item = $items;
	}

	$request->clientLibrary = "PHP";
	$request->clientLibraryVersion = phpversion();
	$request->clientEnvironment = php_uname();

	/*ob_start();
	echo '<pre>';
	var_dump( $request );
	echo '</pre>';
	error_log( ob_get_clean() );*/

	// Setup client
	require_once 'soap.cybersource.php';

	$cybersource_soap = new CyberSource_SoapClient( $url );

	// Set credentials
	$cybersource_soap->set_credentials( $edd_options[ 'cybersource_merchant_id' ], $security_key );

	// Make request
	$response = $cybersource_soap->runTransaction( $request );

	/*ob_start();
	echo '<pre>';
	var_dump( $response );
	echo '</pre>';
	error_log( ob_get_clean() );*/

	$status = strtolower( $response->decision );

	// Success
	if ( 'accept' == $status ) {
		return $response->requestID;
	}
	// Payment under review
	elseif ( 'review' == $status ) {
		if ( 230 == $response->reasonCode ) {
			$messages = __( "The authorization request was approved by the issuing bank but declined by our merchant because it did not pass the CVN check.", 'LION' );
		}
		else {
			$messages = __( "This order is being placed on hold for review. You may contact the store to complete the transaction.", 'LION' );
		}

		throw new Exception( $messages );
	}

	// 'failure' and other statuses
	if ( 202 == $response->reasonCode ) {
		$messages = __( 'The provided card is expired, please use an alternate card or other form of payment.', 'LION' );
	}
	elseif ( 203 == $response->reasonCode ) {
		$messages = __( 'The provided card was declined, please use an alternate card or other form of payment.', 'LION' );
	}
	elseif ( 204 == $response->reasonCode ) {
		$messages = __( 'Insufficient funds in account, please use an alternate card or other form of payment.', 'LION' );
	}
	elseif ( 208 == $response->reasonCode ) {
		$messages = __( 'The card is inactivate or not authorized for card-not-present transactions, please use an alternate card or other form of payment.', 'LION' );
	}
	elseif ( 210 == $response->reasonCode ) {
		$messages = __( 'The credit limit for the card has been reached, please use an alternate card or other form of payment.', 'LION' );
	}
	elseif ( 211 == $response->reasonCode ) {
		$messages = __( 'The card verification number is invalid, please try again.', 'LION' );
	}
	elseif ( 231 == $response->reasonCode ) {
		$messages = __( 'The provided card number was invalid, or card type was incorrect. Please try again.', 'LION' );
	}
	elseif ( 232 == $response->reasonCode ) {
		$messages = __( 'That card type is not accepted, please use an alternate card or other form of payment.', 'LION' );
	}
	elseif ( 240 == $response->reasonCode ) {
		$messages = __( 'The card type is invalid or does not correlate with the credit card number. Please try again or use an alternate card or other form of payment.', 'LION' );
	}
	elseif ( 'ERROR' == $response->decision ) {
		$messages = __( 'An error occurred, please try again or try an alternate form of payment', 'LION' );
	}
	else {
		$messages = __( 'We cannot process your order with the payment information that you provided. Please use a different payment account or an alternate payment method.', 'LION' );
	}

	throw new Exception( $messages );

}

/**
 * Get card types and their settings
 *
 * Props to Gravity Forms / Rocket Genius for the logic
 *
 * @return array
 *
 * @since 1.0
 */
function cybersource_edd_get_card_types() {

	$cards = array(

		array(
			'name' => 'Amex',
			'slug' => 'amex',
			'lengths' => '15',
			'prefixes' => '34,37',
			'checksum' => true
		),
		array(
			'name' => 'Discover',
			'slug' => 'discover',
			'lengths' => '16',
			'prefixes' => '6011,622,64,65',
			'checksum' => true
		),
		array(
			'name' => 'MasterCard',
			'slug' => 'mastercard',
			'lengths' => '16',
			'prefixes' => '51,52,53,54,55',
			'checksum' => true
		),
		array(
			'name' => 'Visa',
			'slug' => 'visa',
			'lengths' => '13,16',
			'prefixes' => '4,417500,4917,4913,4508,4844',
			'checksum' => true
		),
		array(
			'name' => 'JCB',
			'slug' => 'jcb',
			'lengths' => '16',
			'prefixes' => '35',
			'checksum' => true
		),
		array(
			'name' => 'Maestro',
			'slug' => 'maestro',
			'lengths' => '12,13,14,15,16,18,19',
			'prefixes' => '5018,5020,5038,6304,6759,6761',
			'checksum' => true
		)

	);

	return $cards;

}

/**
 * Get the Card Type from a Card Number
 *
 * Props to Gravity Forms / Rocket Genius for the logic
 *
 * @param int|string $number
 *
 * @return bool
 *
 * @since 1.0
 */
function cybersource_edd_get_card_type( $number ) {

	//removing spaces from number
	$number = str_replace( array( '-', ' ' ), '', $number );

	if ( empty( $number ) ) {
		return false;
	}

	$cards = cybersource_edd_get_card_types();

	$matched_card = false;

	foreach ( $cards as $card ) {
		if ( cybersource_edd_matches_card_type( $number, $card ) ) {
			$matched_card = $card;

			break;
		}
	}

	if ( $matched_card && $matched_card[ 'checksum' ] && !cybersource_edd_is_valid_card_checksum( $number ) ) {
		$matched_card = false;
	}

	return $matched_card ? $matched_card : false;

}

/**
 * Match the Card Number to a Card Type
 *
 * Props to Gravity Forms / Rocket Genius for the logic
 *
 * @param int $number
 * @param array $card
 *
 * @return bool
 *
 * @since 1.0
 */
function cybersource_edd_matches_card_type( $number, $card ) {

	//checking prefix
	$prefixes = explode( ',', $card[ 'prefixes' ] );
	$matches_prefix = false;
	foreach ( $prefixes as $prefix ) {
		if ( preg_match( "|^{$prefix}|", $number ) ) {
			$matches_prefix = true;
			break;
		}
	}

	//checking length
	$lengths = explode( ',', $card[ 'lengths' ] );
	$matches_length = false;
	foreach ( $lengths as $length ) {
		if ( strlen( $number ) == absint( $length ) ) {
			$matches_length = true;
			break;
		}
	}

	return $matches_prefix && $matches_length;

}

/**
 * Check Credit Card number checksum
 *
 * Props to Gravity Forms / Rocket Genius for the logic
 *
 * @param int $number
 *
 * @return bool
 *
 * @since 1.0
 */
function cybersource_edd_is_valid_card_checksum( $number ) {

	$checksum = 0;
	$num = 0;
	$multiplier = 1;

	// Process each character starting at the right
	for ( $i = strlen( $number ) - 1; $i >= 0; $i-- ) {

		//Multiply current digit by multiplier (1 or 2)
		$num = $number{$i} * $multiplier;

		// If the result is in greater than 9, add 1 to the checksum total
		if ( $num >= 10 ) {
			$checksum++;
			$num -= 10;
		}

		//Update checksum
		$checksum += $num;

		//Update multiplier
		$multiplier = $multiplier == 1 ? 2 : 1;
	}

	return $checksum % 10 == 0;

}
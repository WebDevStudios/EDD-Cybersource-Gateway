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

// Don't forget to load the text domain here. Cybersource text domain is cybersource_edd

// registers the gateway
function cybersource_edd_register_gateway( $gateways ) {

	$gateways[ 'cybersource_gateway' ] = array(
		'admin_label' => 'Cybersource Gateway',
		'checkout_label' => __( 'Cybersource Gateway', 'cybersource_edd' )
	);

	return $gateways;

}
add_filter( 'edd_payment_gateways', 'cybersource_edd_register_gateway' );

// processes the payment
function cybersource_edd_process_payment( $purchase_data ) {

	global $edd_options;

	/**********************************
	 * set transaction mode
	 **********************************/

	if ( edd_is_test_mode() ) {
		// set test credentials here
	}
	else {
		// set live credentials here
	}

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

	/**********************************
	 * Purchase data comes in like this:
	 *
	 * $purchase_data = array(
	 * 'downloads'     => array of download IDs,
	 * 'tax'            => taxed amount on shopping cart
	 * 'fees'            => array of arbitrary cart fees
	 * 'discount'        => discounted amount, if any
	 * 'subtotal'        => total price before tax
	 * 'price'         => total price of cart contents after taxes,
	 * 'purchase_key'  =>  // Random key
	 * 'user_email'    => $user_email,
	 * 'date'          => date( 'Y-m-d H:i:s' ),
	 * 'user_id'       => $user_id,
	 * 'post_data'     => $_POST,
	 * 'user_info'     => array of user's information and used discount code
	 * 'cart_details'  => array of cart details,
	 * );
	 */

	// check for any stored errors
	$errors = edd_get_errors();

	$fail = false;

	if ( !$errors ) {
		$purchase_summary = edd_get_purchase_summary( $purchase_data );

		/****************************************
		 * setup the payment details to be stored
		 ****************************************/

		$payment = array(
			'price' => $purchase_data[ 'price' ],
			'date' => $purchase_data[ 'date' ],
			'user_email' => $purchase_data[ 'user_email' ],
			'purchase_key' => $purchase_data[ 'purchase_key' ],
			'currency' => $edd_options[ 'currency' ],
			'downloads' => $purchase_data[ 'downloads' ],
			'cart_details' => $purchase_data[ 'cart_details' ],
			'user_info' => $purchase_data[ 'user_info' ],
			'status' => 'pending'
		);

		// record the pending payment
		$payment = edd_insert_payment( $payment );

		$merchant_payment_confirmed = false;

		/**********************************
		 * Process the credit card here.
		 * If not using a credit card
		 * then redirect to merchant
		 * and verify payment with an IPN
		 **********************************/

		// if the merchant payment is complete, set a flag
		$merchant_payment_confirmed = true;

		if ( $merchant_payment_confirmed ) { // this is used when processing credit cards on site

			// once a transaction is successful, set the purchase to complete
			edd_update_payment_status( $payment, 'complete' );

			// record transaction ID, or any other notes you need
			edd_insert_payment_note( $payment, 'Transaction ID: XXXXXXXXXXXXXXX' );

			// go to the success page
			edd_send_to_success_page();

		}
		else {
			$fail = true; // payment wasn't recorded
		}
	}
	else {
		$fail = true; // errors were detected
	}

	if ( $fail !== false ) {
		// if errors are present, send the user back to the purchase page so they can be corrected
		edd_send_back_to_checkout( '?payment-mode=' . $purchase_data[ 'post_data' ][ 'edd-gateway' ] );
	}

}
add_action( 'edd_gateway_cybersource_gateway', 'cybersource_edd_process_payment' );

// adds the settings to the Payment Gateways section
function cybersource_edd_add_settings( $settings ) {

	$cybersource_gateway_settings = array(
        'cybersource_merchant_id'                => '',
        'cybersource_live_security_key'                => '',
        'cybersource_test_security_key'                => '',
		'cybersource_sale_method'                 => 'auth_capture',
        'cybersource_sandbox_mode'                => false,

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
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'cybersource_test_security_key',
			'name' => __( 'Test Transaction Security Key', 'cybersource_edd' ),
			'desc' => __( 'You can find this by logging into your "Test" CyberSource Business Center, going to Account Management &gt; Transaction Security Keys &gt; Security Keys for the SOAP Toolkit API, and then click \'Generate\'.', 'cybersource_edd' ),
			'type' => 'text',
			'size' => 'regular'
		),
		array(
			'id' => 'cybersource_sandbox_mode',
			'name' => __( 'Enable CyberSource Sandbox Mode', 'cybersource_edd' ),
			'desc' => __( 'Use this mode for testing your store. This mode will need to be disabled when the store is ready to process customer payments.', 'cybersource_edd' ),
			'type' => 'checkbox'
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

	return array_merge( $settings, $cybersource_gateway_settings );

}
add_filter( 'edd_settings_gateways', 'cybersource_edd_add_settings' );
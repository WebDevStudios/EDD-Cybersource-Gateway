<?php
/**
 * Pluginize.com License Loader.
 *
 * @package Pluginize Product License Menu
 * @author Pluginize Team
 * @copyright WebDevStudios
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once( dirname( __FILE__ ) . '/class-pluginize-product.php' );
include_once( dirname( __FILE__ ) . '/class-pluginize-product-api.php' );
include_once( dirname( __FILE__ ) . '/class-pluginize-product-license-menu.php' );

if ( ! function_exists( 'pluginize_plugin_edd_cybersource_gateway' ) ) {
	/**
	 * Fire it up.
	 *
	 * @since 1.0.0
	 */
	function pluginize_plugin_edd_cybersource_gateway() {

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Won't double add, if existing already.
		add_option( 'pluginize_edd_cybersource_settings', array() );
		add_option( 'pluginize_edd_cybersource_instance', '' );

		// Needs to fetch saved values from options.
		// All values are demo.
		// Should probably get its own method.
		// Will need to be changed to match value provided below.
		$pluginize_options                = get_option( 'pluginize_edd_cybersource_settings', array() );
		$instance                         = get_option( 'pluginize_edd_cybersource_instance', '' );
		$details['email']                 = ( ! empty( $pluginize_options['pluginize_email'] ) ) ? $pluginize_options['pluginize_email'] : '';
		$details['license_key']           = ( ! empty( $pluginize_options['pluginize_api_key'] ) ) ? $pluginize_options['pluginize_api_key'] : '';
		$details['product_id']            = 'EDD CyberSource Gateway';
		$details['product_slug']          = 'EDD-CyberSource-Gateway';
		$details['platform']              = str_ireplace( array( 'http://', 'https://' ), '', home_url() );
		$details['instance']              = $instance;
		$details['software_version']      = EDDCYBERSOURCEVERSION;
		$details['upgrade_url']           = 'http://pluginize.com/';
		$details['changelog_restapi_url'] = '';
		$details['plugin_name']           = plugin_basename( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/edd-cybersource-gateway.php';
		$details['menu_page']             = array(
			'parent_slug'    => 'edit.php?post_type=download',
			'page_title'     => esc_html__( 'EDD CyberSource Gateway License', 'cybersource_edd' ),
			'menu_title'     => esc_html__( 'EDD CyberSource Gateway License', 'cybersource_edd' ),
			'menu_slug'      => 'pluginize-edd-cybersource-license',
			'management_tab' => esc_html__( 'License Management', 'cybersource_edd' ),
			'button_text'    => esc_attr__( 'Save Changes', 'cybersource_edd' ),
		);
		$details['option_group']          = 'pluginize';
		$details['option_name']           = 'pluginize_edd_cybersource_settings';
		$details['instance_name']         = 'pluginize_edd_cybersource_instance';
		$details['api_errors_key']        = 'pluginize_edd_cybersource_api_key_errors';

		$product = new Pluginize_Product( $details );

		// Check on our status.
		$api = new Pluginize_Product_API( $product );
		$api->do_hooks();

		// Set up our menu based on the information above.
		$menu_setup = new Pluginize_Product_License_menu( $product, $api );
		$menu_setup->do_hooks();
	}
	add_action( 'init', 'pluginize_plugin_edd_cybersource_gateway' );
}

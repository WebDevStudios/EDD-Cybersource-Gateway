<?php
/**
 * Pluginize.com Product Details class.
 *
 * @package Product Details
 * @author Pluginize Team
 * @copyright WebDevStudios
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Pluginize_Product' ) ) {

	/**
	 * Create our product object.
	 *
	 * @since 1.0.0
	 */
	class Pluginize_Product {

		/**
		 * Licensed email.
		 *
		 * @since 1.0.0
		 * @var mixed|string
		 */
		public $email = '';

		/**
		 * Software title from WooCommerce product edit screen.
		 *
		 * @since 1.0.0
		 * @var mixed|string
		 */
		public $product_id = '';

		/**
		 * Software slug.
		 *
		 * @since 1.0.0
		 * @var mixed|string
		 */
		public $product_slug = '';

		/**
		 * Currently installed software version.
		 *
		 * @since 1.0.0
		 * @var mixed|string
		 */
		public $software_version = '';

		/**
		 * URL to check for upgrade at.
		 *
		 * @since 1.0.0
		 * @var mixed|string
		 */
		public $upgrade_url = '';

		/**
		 * URL with our remote changelog.
		 *
		 * @since 1.0.0
		 * @var mixed|string
		 */
		public $changelog_restapi_url = '';

		/**
		 * Plugin basename.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $plugin_name = '';

		/**
		 * Current domain name being activated for.
		 *
		 * @since 1.0.0
		 * @var mixed|string
		 */
		public $platform = '';

		/**
		 * Unique password-generated value for each installation.
		 *
		 * @since 1.0.0
		 * @var mixed|string
		 */
		public $instance = '';

		/**
		 * API License key, from the customer order.
		 *
		 * @since 1.0.0
		 * @var mixed|string
		 */
		public $license_key = '';

		/**
		 * Values to be used for WP Admin menu creation.
		 *
		 * @since 1.0.0
		 * @var array|mixed
		 */
		public $menu_page = array();

		/**
		 * Value for the option group key.
		 *
		 * @since 1.0.0
		 *
		 * @var mixed|string
		 */
		public $option_group = '';

		/**
		 * Value for the option name key.
		 *
		 * @since 1.0.0
		 *
		 * @var mixed|string
		 */
		public $option_name = '';

		/**
		 * Value for the instance name key.
		 *
		 * @since 1.0.0
		 *
		 * @var mixed|string
		 */
		public $instance_name = '';

		/**
		 * Value for the settings api error key.
		 *
		 * @since 1.0.0
		 *
		 * @var mixed|string
		 */
		public $api_errors_key = '';

		/**
		 * Pluginize_Product constructor.
		 *
		 * @param array $plugin_details Details for the current plugin.
		 */
		public function __construct( $plugin_details = array() ) {

			if ( empty( $plugin_details['instance'] ) ) {
				$plugin_details['instance'] = $this->set_instance( $plugin_details['instance_name'] );
			}
			$this->email                 = $plugin_details['email'];
			$this->product_id            = $plugin_details['product_id'];
			$this->product_slug          = $plugin_details['product_slug'];
			$this->software_version      = $plugin_details['software_version'];
			$this->upgrade_url           = $plugin_details['upgrade_url'];
			$this->changelog_restapi_url = $plugin_details['changelog_restapi_url'];
			$this->plugin_name           = $plugin_details['plugin_name'];
			$this->platform              = $plugin_details['platform'];
			$this->instance              = $plugin_details['instance'];
			$this->license_key           = $plugin_details['license_key'];
			$this->menu_page             = $plugin_details['menu_page'];
			$this->option_group          = $plugin_details['option_group'];
			$this->option_name           = $plugin_details['option_name'];
			$this->instance_name         = $plugin_details['instance_name'];
			$this->api_errors_key        = $plugin_details['api_errors_key'];
		}

		/**
		 * Retrieve product ID.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed|string
		 */
		public function get_product_id() {
			return $this->product_id;
		}

		/**
		 * Retrieve software version.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed|string
		 */
		public function get_software_version() {
			return $this->software_version;
		}

		/**
		 * Retrieve upgrade URL.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed
		 */
		public function get_upgrade_url() {
			return $this->upgrade_url;
		}

		/**
		 * Retrieve changelog REST API url.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed
		 */
		public function get_changelog_restapi_url() {
			return $this->changelog_restapi_url;
		}

		/**
		 * Retrieve platform.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed|string
		 */
		public function get_platform() {
			return $this->platform;
		}

		/**
		 * Retrieve instance value.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed|string
		 */
		public function get_instance() {
			return $this->instance;
		}

		/**
		 * Retrieve license key.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed|string
		 */
		public function get_license_key() {
			return $this->license_key;
		}

		/**
		 * Retrieve menu page array.
		 *
		 * @since 1.0.0
		 *
		 * @return array|mixed
		 */
		public function get_menu_page() {
			return $this->menu_page;
		}

		/**
		 * Retrieve option group key.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed|string
		 */
		public function get_option_group() {
			return $this->option_group;
		}

		/**
		 * Retrieve option name key.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed|string
		 */
		public function get_option_name() {
			return $this->option_name;
		}

		/**
		 * Retrieve instance option name key.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed
		 */
		public function get_instance_name() {
			return $this->instance_name;
		}

		/**
		 * Retrieve api_errors_key key.
		 *
		 * @since 1.0.0
		 *
		 * @return mixed
		 */
		public function get_api_errors_key() {
			return $this->api_errors_key;
		}

		/**
		 * Set instance value for plugin.
		 *
		 * @since 1.0.0
		 *
		 * @param string $instance_key Instance key to use for option.
		 * @return string $instance Saved instance value.
		 */
		private function set_instance( $instance_key ) {
			$instance = wp_generate_password( 12, false );
			update_option( $instance_key, $instance );

			return $instance;
		}
	}
}

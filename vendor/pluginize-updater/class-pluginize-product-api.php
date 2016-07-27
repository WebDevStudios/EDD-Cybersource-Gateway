<?php
/**
 * Pluginize.com Product WooCommerce API class.
 *
 * @package Update API Manager/Key Handler
 * @author Pluginize Team
 * @copyright WebDevStudios
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Pluginize_Product_API' ) ) {

	/**
	 * Manage communication with Pluginize WooCommerce API.
	 *
	 * @since 1.0.0
	 */
	class Pluginize_Product_API {

		/**
		 * Singleton
		 *
		 * @since 1.0.0
		 * @var null
		 */
		protected static $instance = null;

		/**
		 * Plugin being checked on with WooCommerce API.
		 *
		 * @since 1.0.0
		 * @var object|string
		 */
		private $plugin = '';

		/**
		 * Array of arguments for HTTP requests.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		private $args = array();

		/**
		 * Array of errors received from API request.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		private $errors = array();

		/**
		 * Pluginize_Product_API constructor.
		 *
		 * @since 1.0.0
		 *
		 * @param object $plugin Plugin to check on.
		 */
		public function __construct( $plugin ) {

			$this->plugin = $plugin;

			$this->set_args();
		}

		/**
		 * Run our hooks.
		 *
		 * @since 1.0.0
		 */
		public function do_hooks() {
			// Check For Plugin Updates.
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );

			// Check For Plugin Information to display on the update details page.
			add_filter( 'plugins_api', array( $this, 'plugininfo' ), 10, 3 );
		}

		/**
		 * Piece together our request URI value.
		 *
		 * @since 1.0.0
		 *
		 * @param string $endpoint API endpoint to make request to.
		 * @return string
		 */
		public function construct_uri( $endpoint = 'am-software-api' ) {

			// The am-software-api value is required to be named as such.
			$api_url = add_query_arg( 'wc-api', $endpoint, $this->plugin->upgrade_url );

			return esc_url_raw( $api_url . '&' . http_build_query( $this->args ) );
		}

		/**
		 * Set our default arguments.
		 *
		 * @since 1.0.0
		 */
		private function set_args() {

			$plugin = $this->plugin;
			$args = array();

			if ( ! empty( $plugin->email ) ) {
				$args['email'] = $plugin->email;
			}

			if ( ! empty( $plugin->product_id ) ) {
				$args['product_id'] = $plugin->product_id;
			}

			if ( ! empty( $plugin->instance ) ) {
				$args['instance'] = $plugin->instance;
			}

			if ( ! empty( $plugin->platform ) ) {
				$args['platform'] = $plugin->platform;
			}

			if ( ! empty( $plugin->license_key ) ) {
				$args['licence_key'] = $plugin->license_key;
			}

		    if ( ! empty( $args ) ) {
		    	$this->args = $args;
		    }
		}

		/**
		 * Make a request to the remote server to activate a license key.
		 *
		 * @since 1.0.0
		 *
		 * @param string $license_key License key to use for activation attempt.
		 * @param string $email       License email to use for activation attempt.
		 * @return array
		 */
		public function activate( $license_key = '', $email = '' ) {

			$extra = array(
			    'software_version' => $this->plugin->software_version,
			);

			if ( ! empty( $license_key ) ) {
				$extra['licence_key'] = $license_key;
			}

			if ( ! empty( $email ) ) {
				$extra['email'] = $email;
			}

			$result = $this->make_license_request( 'activation', $extra );
			return $this->get_activation_message( $result );
		}

		/**
		 * Make a request to the remote server to deactivate a license key.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
	    public function deactivate() {
			$result = $this->make_license_request( 'deactivation' );

		    return $this->get_activation_message( $result );
		}

		/**
		 * Make a request to the remote server to retrieve current status of a license key.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public function status() {
			$result = $this->make_license_request( 'status' );

			return $this->get_activation_message( $result );
		}

		/**
		 * Check if there is an update available.
		 *
		 * @since 1.0.0
		 *
		 * @param string|object $transient Transient object.
		 * @return mixed
		 */
		public function update_check( $transient = '' ) {

			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			$extra = array(
				'software_version' => $this->plugin->software_version,
				'activation_email' => $this->plugin->email,
				'api_key'          => $this->plugin->license_key,
				'domain'           => $this->plugin->platform,
				'version'          => $this->plugin->software_version,
				'plugin_name'      => $this->plugin->plugin_name,
			);

			$result = $this->make_update_request( 'pluginupdatecheck', $extra );

			if ( ! empty( $result ) ) {
				if ( ! empty( $result->errors ) ) {
					foreach( $result->errors as $error ) {
						echo $this->set_admin_error( $this->get_error_message( $error ) );
					}
				}

				if ( ! empty( $result->new_version ) && version_compare( $result->new_version, $this->plugin->software_version, '>' ) ) {
					$transient->response[ $this->plugin->plugin_name ] = $result;
				}
			}

			return $transient;
		}

		/**
		 * Generic request helper.
		 *
		 * @since 1.0.0
		 *
		 * @param bool  $false  Something.
		 * @param mixed $action Something.
		 * @param mixed $args   Something.
		 *
		 * @return mixed $response or boolean false
		 */
		public function plugininfo( $false, $action, $args ) {

			// Check if this plugins API is about this plugin.
			if ( isset( $args->slug ) ) {
				if ( $args->slug != $this->plugin->product_slug ) {
					return $false;
				}
			} else {
				return $false;
			}

			$extra = array(
				'software_version' => $this->plugin->software_version,
				'activation_email' => $this->plugin->email,
				'api_key'          => $this->plugin->license_key,
				'domain'           => $this->plugin->platform,
				'version'          => $this->plugin->software_version,
				'plugin_name'      => $this->plugin->plugin_name,
				'product_id'       => $this->plugin->product_id,
				'instance'         => $this->plugin->instance,
			);

			$result = $this->make_infoupdate_request( 'plugininformation', $extra );

			if ( is_object( $result ) ) {

				if ( ! empty( $this->plugin->changelog_restapi_url ) ) {
					$changelog_page = wp_remote_get( $this->plugin->changelog_restapi_url );
					if ( 200 === wp_remote_retrieve_response_code( $changelog_page ) ) {
						$content = json_decode( wp_remote_retrieve_body( $changelog_page ) );
					}
					$result->sections['changelog'] = $content->content->rendered;
				}
				return $result;
			}

			return false;
		}

		/**
		 * Make our WooCommerce API license request.
		 *
		 * @since 1.0.0
		 *
		 * @param string $request_type Request type being made.
		 * @param array  $extra_args   Any extra arguments for request.
		 *
		 * @return string
		 */
		public function make_license_request( $request_type = '', $extra_args = array() ) {

			$this->args['request'] = $request_type;

			if ( ! empty( $extra_args ) ) {
				foreach ( $extra_args as $key => $arg ) {
					$this->args[ $key ] = $arg;
				}
			}

			$target_url = $this->construct_uri( 'am-software-api' );

			$response = wp_safe_remote_get( $target_url );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return '';
			}

			return wp_remote_retrieve_body( $response );
		}

		/**
		 * Make our WooCommerce API update request.
		 *
		 * @since 1.0.0
		 *
		 * @param string $request_type Request type being made.
		 * @param array  $extra_args   Any extra arguments for request.
		 * @return array
		 */
		public function make_update_request( $request_type = '', $extra_args = array() ) {

			$this->args['request'] = $request_type;

			if ( ! empty( $extra_args ) ) {
				foreach ( $extra_args as $key => $arg ) {
					$this->args[ $key ] = $arg;
				}
			}

			$target_url = $this->construct_uri( 'upgrade-api' );

			$response = wp_safe_remote_get( $target_url );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return array();
			}

			$data = maybe_unserialize( wp_remote_retrieve_body( $response ) );
			if ( is_object( $data ) ) {
				return $data;
			}

			return array();
		}

		/**
		 * Make our WooCommerce API plugin information request.
		 *
		 * @since 1.0.0
		 *
		 * @param string $request_type Request type being made.
		 * @param array  $extra_args   Any extra arguments for request.
		 * @return object
		 */
		public function make_infoupdate_request( $request_type = '', $extra_args = array() ) {
			$this->args['request'] = $request_type;

			if ( ! empty( $extra_args ) ) {
				foreach ( $extra_args as $key => $arg ) {
					$this->args[ $key ] = $arg;
				}
			}

			$target_url = $this->construct_uri( 'upgrade-api' );

			$response = wp_safe_remote_get( $target_url );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return '';
			}

			$data = maybe_unserialize( wp_remote_retrieve_body( $response ) );
			if ( isset( $data ) && is_object( $data ) && false !== $data ) {
				return $data;
			}

			return '';
		}

		/**
		 * Return the provided status message from remote server.
		 *
		 * These are related to the activation of the license key/email pairing.
		 *
		 * @since 1.0.0
		 *
		 * @param string $result HTTP request result from WooCommerce API.
		 * @return array
		 */
		public function get_activation_message( $result = '' ) {
			$response = json_decode( $result );


			if ( isset( $response->code ) ) {
				$slug_title = $slug_name = $message = $pluginize_message = '';
				switch ( $response->code ) {
					case '100':
						$slug_name  = 'api_email_error';
						$pluginize_message = esc_html__( 'Please confirm your provided settings.', 'pluginize_updater' );
						break;
					case '101':
						$slug_name  = 'api_key_error';
						break;
					case '102':
						$slug_name  = 'api_key_purchase_incomplete_error';
						$pluginize_message = esc_html__( 'Contact Pluginize support for possible issues with your order.', 'pluginize_updater' );
						break;
					case '103':
						$slug_name  = 'api_key_exceeded_error';
						break;
					case '104':
						$slug_name  = 'api_key_not_activated_error';
						break;
					case '105':
						$slug_name  = 'api_key_invalid_error';
						break;
					case '106':
						$slug_name  = 'sub_not_active_error';
						break;
				}
				$slug_title = $this->plugin->api_errors_key;
				$message    = $response->error;
				if ( ! empty( $response->additional_info ) ) {
					$message .= $response->additional_info;
				}

				// Add our own custom notes.
				if ( ! empty( $pluginize_message ) ) {
					// Punctuation from WC Software API would be nice.
					if ( '.' !== substr( $message, -1 ) ) {
						$message .= '.';
					}
					$message .= ' ' . $pluginize_message;
				}

				$response->statuses = array( $slug_title, $slug_name, $message );
			}

			return $response;
		}

		/**
		 * Create individual divs for each available error.
		 *
		 * @since 1.0.0
		 *
		 * @param string $message Error message to be shown.
		 * @return string
		 */
		public function set_admin_error( $message = '' ) {
			return sprintf( '<div id="message" class="error is-dismissible"><p>%s</p></div>', $message );
		}

		/**
		 * Method to hold all of our potential error messages.
		 *
		 * These are related to attempting to update the plugin from the Updates area.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		private function get_error_messages() {
			$messages = array();

			$messages['no_key']                 = sprintf( __( 'A license key for %s could not be found. Maybe you forgot to enter a license key when setting up, or the key was deactivated in your account. You can reactivate or purchase a license key from your account <a href="%s" target="_blank">dashboard</a>.', 'pluginize_updater' ), $this->plugin->product_id, $this->plugin->upgrade_url );
			$messages['no_subscription']        = sprintf( __( 'A subscription for %s could not be found. You can purchase a subscription from your account <a href="%s" target="_blank">dashboard</a>.', 'pluginize_updater' ), $this->plugin->product_id, $this->plugin->upgrade_url );
			$messages['exp_license']            = sprintf( __( 'The license key for %s has expired. You can reactivate or purchase a license key from your account <a href="%s" target="_blank">dashboard</a>.', 'pluginize_updater' ), $this->plugin->product_id, $this->plugin->upgrade_url );
			$messages['on_hold']                = sprintf( __( 'The subscription for %s is on-hold. You can reactivate the subscription from your account <a href="%s" target="_blank">dashboard</a>.', 'pluginize_updater' ), $this->plugin->product_id, $this->plugin->upgrade_url );
			$messages['cancelled_subscription'] = sprintf( __( 'The subscription for %s has been cancelled. You can renew the subscription from your account <a href="%s" target="_blank">dashboard</a>. A new license key will be emailed to you after your order has been completed.', 'pluginize_updater' ), $this->plugin->product_id, $this->plugin->upgrade_url );
			$messages['expired_subscription']   = sprintf( __( 'The subscription for %s has expired. You can reactivate the subscription from your account <a href="%s" target="_blank">dashboard</a>.', 'pluginize_updater' ), $this->plugin->product_id, $this->plugin->upgrade_url );
			$messages['suspended_subscription'] = sprintf( __( 'The subscription for %s has been suspended. You can reactivate the subscription from your account <a href="%s" target="_blank">dashboard</a>.', 'pluginize_updater' ), $this->plugin->product_id, $this->plugin->upgrade_url );
			$messages['pending_subscription']   = sprintf( __( 'The subscription for %s is still pending. You can check on the status of the subscription from your account <a href="%s" target="_blank">dashboard</a>.', 'pluginize_updater' ), $this->plugin->product_id, $this->plugin->upgrade_url );
			$messages['trash_subscription']     = sprintf( __( 'The subscription for %s has been placed in the trash and will be deleted soon. You can purchase a new subscription from your account <a href="%s" target="_blank">dashboard</a>.', 'pluginize_updater' ), $this->plugin->product_id, $this->plugin->upgrade_url );
			$messages['download_revoked'] = sprintf( __( 'Your license for %s has expired. You can continue using the current version without issue. For access to support and plugin updates, you will need to <a href="%s" target="_blank">upgrade your license here</a>.', 'pluginize_updater' ), $this->plugin->product_id, $this->plugin->upgrade_url );
			$messages['switched_subscription']  = sprintf( __( 'You changed the subscription for %s, so you will need to enter your new API License Key in the settings page. The License Key should have arrived in your email inbox, if not you can get it by logging into your account <a href="%s" target="_blank">dashboard</a>.', 'pluginize_updater' ), $this->plugin->product_id, $this->plugin->upgrade_url );

			return $messages;
		}

		/**
		 * Retrieve individual error message.
		 *
		 * @since 1.0.0
		 *
		 * @param string $key Error key to fetch.
		 * @return mixed|string
		 */
		public function get_error_message( $key = '' ) {

			$messages = $this->get_error_messages();

			if ( ! empty( $key ) ) {
				return $messages[ $key ];
			}

			return '';
		}

		/**
		 * Retrieve an array of potential error codes.
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public function get_error_codes() {
			return array(
				'no_key',
				'no_subscription',
				'exp_license',
				'on_hold',
				'cancelled_subscription',
				'expired_subscription',
				'suspended_subscription',
				'pending_subscription',
				'trash_subscription',
				'download_revoked',
				'switched_subscription',
			);
		}
	}
}

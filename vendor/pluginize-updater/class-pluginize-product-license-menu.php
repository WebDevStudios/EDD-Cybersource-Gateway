<?php
/**
 * Pluginize.com Product License page.
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

if ( ! class_exists( 'Pluginize_Product_License_menu' ) ) {

	/**
	 * Add a license page to our menu for the provided plugin.
	 *
	 * @since 1.0.0
	 */
	class Pluginize_Product_License_menu {

		/**
		 * Plugin license page being added to the menu.
		 *
		 * @since 1.0.0
		 * @var string|object
		 */
		private $plugin = '';

		/**
		 * Pluginize API object.
		 *
		 * @since 1.0.0
		 * @var string|object
		 */
		private $api_object = '';

		/**
		 * Our currently saved options.
		 *
		 * @since 1.0.0
		 * @var array|mixed|void
		 */
		private $options = array();

		/**
		 * Pluginize_Product_License_menu constructor.
		 *
		 * @param object $plugin Plugin being set up.
		 * @param object $api_object Pluginize API instance.
		 */
		public function __construct( $plugin, $api_object ) {
			$this->plugin = $plugin;
			$this->menu_details = $this->plugin->menu_page;
			$this->api_object = $api_object;

			$this->options = get_option( $this->plugin->option_name, array() );
		}

		/**
		 * Run our hooks.
		 *
		 * @since 1.0.0
		 */
		public function do_hooks() {
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'admin_init', array( $this, 'licenses_settings' ) );
			add_action( 'admin_notices', array( $this, 'check_external_blocking' ) );
			add_action( 'network_admin_notices', array( $this, 'check_external_blocking' ) );
			add_action( 'admin_notices', array( $this, 'api_activation_notices' ) );
			add_action( 'admin_head', array( $this, 'inline_styles' ) );
		}

		/**
		 * Add our menu item.
		 *
		 * @since 1.0.0
		 */
		public function add_menu() {
			// Set options to our associated menus.
			$page = add_submenu_page(
				$this->menu_details['parent_slug'],
				$this->menu_details['page_title'],
				$this->menu_details['menu_title'],
				apply_filters( 'pluginize_updater_options_page_role', 'manage_options' ),
				$this->menu_details['menu_slug'],
				array( $this, 'licenses_page' )
			);
			add_action( 'admin_print_styles-' . $page, array( $this, 'scripts_styles' ) );
		}

		/**
		 * Enqueue scripts and stylesheets.
		 *
		 * @since 1.0.0
		 */
		public function scripts_styles() {}

		/**
		 * Inline styles.
		 *
		 * @since 1.0.0
		 */
		public function inline_styles() {
		?>
			<style>
				.pluginize_status_active {
					color: yellowgreen;
					font-weight: bold;
				}
				.pluginize_status_inactive {
					color: red;
					font-weight: bold;
				}
			</style>
		<?php
		}

		/**
		 * Output our license page.
		 *
		 * @since 1.0.0
		 */
		public function licenses_page() {
			?>
				<div class="wrap">
					<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

					<h2 class="nav-tab-wrapper">
						<?php
							$classes            = array( 'nav-tab' );
							$classes_activate   = $classes;
							$classes_deactivate = $classes;
							if ( ! empty( $_GET ) && isset( $_GET['tab'] ) && 'pluginize_deactivate' === $_GET['tab'] ) {
								$classes_deactivate[] = 'nav-tab-active';
							} else {
								$classes_activate[] = 'nav-tab-active';
							}

							$tab = '<a href="%s" class="%s">%s</a>';
							printf(
								$tab,
								remove_query_arg( array( 'tab' ) ),
								implode( ' ', $classes_activate ),
								esc_html( $this->menu_details['management_tab'] )
							);
						?>
					</h2>

					<form action='options.php' method='post'>
						<div class="main">
							<?php
								if ( ! empty( $_GET ) && isset( $_GET['tab'] ) && 'pluginize_deactivate' === $_GET['tab'] ) {

								}
								$fields = $this->plugin->option_group;
								$sections = $this->plugin->menu_page['menu_slug'];

								settings_fields( $fields );
								do_settings_sections( $sections );

								submit_button( $this->menu_details['button_text'] );
							?>
						</div>
					</form>
				</div>
			<?php
		}

		/**
		 * Register our settings for use on the license page.
		 *
		 * @since 1.0.0
		 */
		public function licenses_settings() {

			register_setting(
				$this->plugin->option_group,
				$this->plugin->option_name,
				array( $this, 'validate_license_settings' )
			);

			// Something needs to return callback text.
			add_settings_section(
				'pluginize', // ID
				__( 'API License Management', 'pluginize_updater' ), // Title
				array( $this, 'above_settings' ), // Callback
				$this->plugin->menu_page['menu_slug'] // Page aka $menu_slug.
			);
			// Something_else needs to return key status.
			add_settings_field(
				'pluginize_status', // ID.
				__( 'API License Key Status', 'pluginize_updater' ), // Title
				array( $this, 'get_key_status' ), // Callback
				$this->plugin->menu_page['menu_slug'], // Page
				'pluginize', // Section.
				array( 'label_for' => 'pluginize_status' )
			);
			// The get_key needs to return key.
			add_settings_field(
				'pluginize_api_key',
				__( 'API License Key', 'pluginize_updater' ),
				array( $this, 'get_key' ),
				$this->plugin->menu_page['menu_slug'],
				'pluginize',
				array( 'label_for' => 'pluginize_api_key' )
			);
			// The get_email needs to return email.
			add_settings_field(
				'pluginize_email',
				__( 'API License email', 'pluginize_updater' ),
				array( $this, 'get_email_field' ),
				$this->plugin->menu_page['menu_slug'],
				'pluginize',
				array( 'label_for' => 'pluginize_email' )
			);
		}

		/**
		 * Display content above our fields.
		 *
		 * @since 1.0.0
		 */
		public function above_settings() {
			printf( '<p>%s</p>', esc_html__( 'Clear fields to deactivate license. Re-save with fields filled in to re-attempt activation.', 'pluginize_updater' ) );
		}

		/**
		 * Returns our license key status option value.
		 *
		 * @since 1.0.0
		 */
		public function get_key_status() {
			$status = ( ! empty( $this->options['pluginize_status'] ) ) ? $this->options['pluginize_status'] : esc_html__( 'Inactive', 'pluginize_updater' );
			printf( '<p class="%s">%s</p>', 'pluginize_status_' . strtolower( $status ), $status );
		}

		/**
		 * Returns our license key option value.
		 *
		 * @since 1.0.0
		 */
		public function get_key() {
			$api_key = ( ! empty( $this->options['pluginize_api_key'] ) ) ? $this->options['pluginize_api_key'] : '';
			printf( '<input id="%s" name="%s[%s]" size="25" type="text" value="%s" />',
				'pluginize_api_key',
				$this->plugin->option_name,
				'pluginize_api_key',
				esc_attr( $api_key )
			);
		}

		/**
		 * Returns our license email option value.
		 *
		 * @since 1.0.0
		 */
		public function get_email_field() {
			$email = ( ! empty( $this->options['pluginize_email'] ) ) ? $this->options['pluginize_email'] : '';
			printf( '<input id="%s" name="%s[%s]" size="25" type="email" value="%s" />',
				'pluginize_email',
				$this->plugin->option_name,
				'pluginize_email',
				esc_attr( $email )
			);
		}

		/**
		 * Validate our saved settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $saved_values Array of values being saved.
		 * @return array $new_values Array of new values to save to setting.
		 */
		public function validate_license_settings( $saved_values = array() ) {

			$new_values      = array();
			$options         = $this->options;
			$error           = false;
			$success_message = esc_html__( 'License key activated.', 'pluginize_updater' );
			$key             = '';
			$error_message   = '';
			$type            = 'updated';
			$activate_result = '';

			$new_values['pluginize_api_key'] = sanitize_text_field( $saved_values['pluginize_api_key'] );
			$new_values['pluginize_email']   = sanitize_email( $saved_values['pluginize_email'] );

			// Attempt to activate new values, if we have them.
			if ( empty( $options['pluginize_api_key'] ) && empty( $options['pluginize_email'] ) ) {
				// Only run if we have something to try with.
				if ( ! empty( $new_values['pluginize_api_key'] ) && ! empty( $new_values['pluginize_email'] ) ) {
					$activate_result = $this->api_object->activate( $new_values['pluginize_api_key'], $new_values['pluginize_email'] );
				}

				if ( is_object( $activate_result ) && ! empty( $activate_result->statuses )) {
					$error = true;
					$key = $activate_result->statuses[1];
					$error_message = $activate_result->statuses[2];
					$type = 'error';
				}
			}

			// Changed values deactivation of old and reactivation of new.
			if ( ! empty( $options['pluginize_api_key'] ) && $new_values['pluginize_api_key'] !== $options['pluginize_api_key'] ) {
				$deactivate_result = $this->api_object->deactivate();

				if ( is_object( $deactivate_result ) && ! empty( $deactivate_result->statuses ) ) {
					$error = true;
					$key = $deactivate_result->statuses[1];
					$error_message = $deactivate_result->statuses[2];
					$type = 'error';
				}

				$activate_result = $this->api_object->activate( $new_values['pluginize_api_key'], $new_values['pluginize_email'] );

				if ( ! empty( $activate_result->statuses )) {
					$error = true;
					$key = $activate_result->statuses[1];
					$error_message = $activate_result->statuses[2];
					$type = 'error';
				}
			}

			// Deactivate keys.
			if ( empty( $new_values['pluginize_api_key'] ) && empty( $new_values['pluginize_email'] ) ) {
				$deactivate_result = $this->api_object->deactivate();

				if ( ! empty( $deactivate_result->statuses )) {
					$error = true;
					$key = $deactivate_result->statuses[1];
					$error_message = $deactivate_result->statuses[2];
					$type = 'error';
				}

				$success_message = esc_html__( 'License key deactivated.', 'pluginize_updater' );
			}

			$temp_status = $this->api_object->status();

			if ( ( ! empty( $options['pluginize_api_key'] ) && ! empty( $options['pluginize_email'] ) ) && 'inactive' === $temp_status->activated || 'inactive' === $temp_status->status_check ) {
				$activate_result = $this->api_object->activate();

				if ( ! empty( $activate_result->statuses ) ) {
					$error = true;
					$key = $activate_result->statuses[1];
					$error_message = $activate_result->statuses[2];
					$type = 'error';
				}
			}

			$current_status = $this->api_object->status();

			$status = ( isset( $current_status->status_check ) && 'active' === $current_status->status_check ) ? esc_html__( 'Active', 'pluginize_updater' ) : esc_html__( 'Inactive', 'pluginize_updater' );
			$new_values['pluginize_status'] = $status;

			if ( ! $error ) {
				add_settings_error( $this->plugin->api_errors_key, 'pluginize_api_updated', $success_message, $type );
			} else {
				add_settings_error( $this->plugin->api_errors_key, $key, $error_message, $type );
			}

			return $new_values;
		}

		/**
		 * Display any settings errors.
		 *
		 * Utilizes the settings API.
		 *
		 * @since 1.0.0
		 */
		function api_activation_notices() {
			settings_errors( $this->plugin->api_errors_key );
		}

		/**
		 * Check whether or not we have external call blocking.
		 *
		 * @since 1.0.0
		 *
		 * @return bool $value Whether or not externall calls are blocked.
		 */
		public function check_external_blocking() {

			if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL === true ) {
				// Check if our API endpoint is in the allowed hosts.
				$host = wp_parse_url( $this->plugin->upgrade_url, PHP_URL_HOST );

				if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) || false === stristr( WP_ACCESSIBLE_HOSTS, $host ) ) {
					?>
					<div class="error">
						<p><?php printf( esc_html__( "Warning! You're blocking external requests which means you won't be able to get %s updates. Please add %s to %s.", 'cptuiext' ), esc_html( $this->plugin->product_id ), '<strong>' . esc_html( $host ) . '</strong>', '<code>WP_ACCESSIBLE_HOSTS</code>' ); ?></p>
					</div>
					<?php
				}

				return true;
			}

			return false;
		}
	}
}

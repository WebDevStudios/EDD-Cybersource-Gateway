<?php
/**
 * EDD Cybersource Gateway Updater Class File
 *
 * @package    EDDCybersourceGateway
 * @subpackage EDDCybersourceGatewayUpdater
 * @author     WebDevStudios
 * @since      1.1.0
 */

/**
 * Main initiation class
 * @internal
 * @since 1.1.0
 */
class EDDCybersourceGatewayUpdater {

	/**
	 * Current version.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	const VERSION = '1.1.0';

	/**
	 * Parent plugin class.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	protected $plugin = null;

	/**
	 * Holds an instance of the object.
	 *
	 * @since 1.1.0
	 * @var Object EDDCybersourceGatewayUpdater
	 */
	private static $instance = null;

	/**
	 * Plugin name.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public $plugin_name = 'Easy Digital Downloads - CyberSource Gateway';


	/**
	 * Self Upgrade Values.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public $upgrade_url = 'http://pluginize.com/';

	/**
	 * This version is saved after an upgrade to compare this db version to $version.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public $plugin_version_name = 'plugin_cptui_extended_plugin_version';

	/**
	 * Site Url to plugin download.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Used to defined localization for translation, but a string literal is preferred.
	 *
	 * @since 1.1.0
	 * @var string
	 */
	public $text_domain = 'cybersource_edd';

	/**
	 * Data defaults
	 * @var mixed
	 */
	private $ame_software_product_id;

	public $ame_data_key;
	public $ame_api_key;
	public $ame_activation_email;
	public $ame_product_id_key;
	public $ame_instance_key;
	public $ame_deactivate_checkbox_key;
	public $ame_activated_key;

	public $ame_deactivate_checkbox;
	public $ame_activation_tab_key;
	public $ame_deactivation_tab_key;
	public $ame_settings_menu_title;
	public $ame_settings_title;
	public $ame_menu_tab_activation_title;
	public $ame_menu_tab_deactivation_title;

	public $ame_options;
	public $ame_plugin_name;
	public $ame_product_id;
	public $ame_renew_license_url;
	public $ame_instance_id;
	public $ame_domain;
	public $ame_software_version;
	public $ame_plugin_or_theme;
	public $ame_update_version;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param class $plugin this class.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
		$this->version = self::VERSION;

		if ( is_admin() ) {

			// Check for external connection blocking.
			add_action( 'admin_notices', array( $this, 'check_external_blocking' ) );
			add_action( 'network_admin_notices', array( $this, 'check_external_blocking' ) );

			/**
			 * Software Product ID is the product title string
			 * This value must be unique, and it must match the API tab for the product in WooCommerce
			 */
			$this->ame_software_product_id = $this->plugin_name;

			/**
			 * Set all data defaults here
			 */
			$this->ame_data_key                = $this->text_domain . '_plugin';
			$this->ame_api_key                 = 'api_key';
			$this->ame_activation_email        = 'activation_email';
			$this->ame_product_id_key          = $this->text_domain . '_plugin_product_id';
			$this->ame_instance_key            = $this->text_domain . '_plugin_instance';
			$this->ame_deactivate_checkbox_key = $this->text_domain . '_plugin_deactivate_checkbox';
			$this->ame_activated_key           = $this->text_domain . '_plugin_activated';

			/**
			 * Set all admin menu data
			 */
			$this->ame_deactivate_checkbox         = 'am_deactivate_eddcs_checkbox';
			$this->ame_activation_tab_key          = $this->text_domain . '_plugin_dashboard';
			$this->ame_deactivation_tab_key        = $this->text_domain . '_plugin_deactivation';
			$this->ame_settings_menu_title         = 'Easy Digital Downloads - CyberSource Gateway';
			$this->ame_settings_title              = $this->plugin_name;
			$this->ame_menu_tab_activation_title   = __( 'License Activation', $this->text_domain );
			$this->ame_menu_tab_deactivation_title = __( 'License Deactivation', $this->text_domain );

			/**
			 * Set all software update data here
			 */
			$this->ame_options           = get_option( $this->ame_data_key );
			$this->ame_plugin_name       = $this->plugin->basename; // Same as plugin slug. if a theme use a theme name like 'twentyeleven'.
			$this->ame_product_id        = get_option( $this->ame_product_id_key ); // Software Title
			$this->ame_renew_license_url = $this->upgrade_url; // URL to renew a license. Trailing slash in the upgrade_url is required.
			$this->ame_instance_id       = get_option( $this->ame_instance_key ); // Instance ID (unique to each blog activation).
			/**
			 * Some web hosts have security policies that block the : (colon) and // (slashes) in http://,
			 * so only the host portion of the URL can be sent. For example the host portion might be
			 * www.example.com or example.com. http://www.example.com includes the scheme http,
			 * and the host www.example.com.
			 * Sending only the host also eliminates issues when a client site changes from http to https,
			 * but their activation still uses the original scheme.
			 * To send only the host, use a line like the one below:
			 * $this->ame_domain = str_ireplace( array( 'http://', 'https://' ), '', home_url() ); // blog domain name
			 */
			$this->ame_domain           = str_ireplace( array(
				'http://',
				'https://'
			), '', home_url() ); // Blog domain name.
			$this->ame_software_version = $this->plugin->version; // The software version.
			$this->ame_plugin_or_theme  = 'plugin'; // 'theme or plugin'.

			// Performs activations and deactivations of API License Keys.
			require_once( $this->plugin->path . 'vendor/updater/am/classes/class-wc-key-api.php' );

			// Checks for software updatess.
			require_once( $this->plugin->path . 'vendor/updater/am/classes/class-wc-plugin-update.php' );

			// Admin menu with the license key and license email form.
			require_once( $this->plugin->path . 'vendor/updater/am/admin/class-wc-api-manager-menu.php' );

			$options = get_option( $this->ame_data_key );

			/**
			 * Check for software updates
			 */
			if ( ! empty( $options ) && false !== $options ) {

				$this->update_check(
					$this->upgrade_url,
					$this->ame_plugin_name,
					$this->ame_product_id,
					$this->ame_options[ $this->ame_api_key ],
					$this->ame_options[ $this->ame_activation_email ],
					$this->ame_renew_license_url,
					$this->ame_instance_id,
					$this->ame_domain,
					$this->ame_software_version,
					$this->ame_plugin_or_theme,
					$this->text_domain
				);
			}
		}

	}

	/**
	 * Initiate our hooks
	 * @since 1.0.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ) );
	}


	/**
	 * Set it off!
	 * @since  1.0.0
	 */
	public function init() {
	}

	/**
	 * API Key Class.
	 * @return Api_Manager_Example_Key
	 */
	public function key() {
		return CPTUI_Extended_Manager_Example_Key::instance();
	}

	/**
	 * Update Check Class.
	 * @return CPTUI_Extended_Plugin_Update_API_Check
	 */
	public function update_check( $upgrade_url, $plugin_name, $product_id, $api_key, $activation_email, $renew_license_url, $instance, $domain, $software_version, $plugin_or_theme, $text_domain, $extra = '' ) {

		return CPTUI_Extended_Plugin_Update_API_Check::instance( $upgrade_url, $plugin_name, $product_id, $api_key, $activation_email, $renew_license_url, $instance, $domain, $software_version, $plugin_or_theme, $text_domain, $extra );
	}

	/**
	 * Plugin url
	 * @return string
	 */
	public function plugin_url() {
		if ( isset( $this->plugin->url ) ) {
			return $this->plugin->url;
		}

		return $this->plugin_url = $this->plugin->url;
	}

	/**
	 * Generate the default data arrays
	 */
	public function activation() {
		global $wpdb;

		$global_options = array(
			$this->ame_api_key          => '',
			$this->ame_activation_email => '',
		);

		update_option( $this->ame_data_key, $global_options );

		$single_options = array(
			$this->ame_product_id_key          => $this->ame_software_product_id,
			$this->ame_instance_key            => wp_generate_password( 12, false ),
			$this->ame_deactivate_checkbox_key => 'on',
			$this->ame_activated_key           => 'Deactivated',
		);

		foreach ( $single_options as $key => $value ) {
			update_option( $key, $value );
		}

		$curr_ver = get_option( $this->plugin_version_name );

		// Checks if the current plugin version is lower than the version being installed.
		if ( version_compare( $this->plugin->version, $curr_ver, '>' ) ) {
			// Update the version.
			update_option( $this->plugin_version_name, $this->version );
		}

	}

	/**
	 * Deletes all data if plugin deactivated
	 * @return void
	 */
	public function uninstall() {
		global $wpdb, $blog_id;

		$this->license_key_deactivation();

		// Remove options.
		if ( is_multisite() ) {

			switch_to_blog( $blog_id );

			foreach (
				array(
					$this->ame_data_key,
					$this->ame_product_id_key,
					$this->ame_instance_key,
					$this->ame_deactivate_checkbox_key,
					$this->ame_activated_key,
				) as $option
			) {

				delete_option( $option );

			}

			restore_current_blog();

		} else {

			foreach (
				array(
					$this->ame_data_key,
					$this->ame_product_id_key,
					$this->ame_instance_key,
					$this->ame_deactivate_checkbox_key,
					$this->ame_activated_key,
				) as $option
			) {

				delete_option( $option );
			}
		}

	}

	/**
	 * Deactivates the license on the API server
	 * @return void
	 */
	public function license_key_deactivation() {

		$activation_status = get_option( $this->ame_activated_key );

		$api_email = $this->ame_options[ $this->ame_activation_email ];
		$api_key   = $this->ame_options[ $this->ame_api_key ];

		$args = array(
			'email'       => $api_email,
			'licence_key' => $api_key,
		);

		if ( 'Activated' === $activation_status && '' !== $api_key && '' !== $api_email ) {
			$this->key()->deactivate( $args ); // Reset license key activation.
		}
	}

	/**
	 * Displays an inactive notice when the software is inactive.
	 */
	public static function am_example_inactive_notice() {
		?>

		<?php if ( ! current_user_can( 'manage_options' ) ) {
			return;
		} ?>
		<?php if ( cptui_extended()->is_network_activated() && 1 !== get_current_blog_id() ) {
			return;
		} ?>
		<?php if ( isset( $_GET['page'] ) && 'cptuiext_plugin_dashboard' == $_GET['page'] ) {
			return;
		} ?>

		<script>
			jQuery(document).on('click', '.cptuiextended-dismiss .notice-dismiss', function () {
				window.location.href = '<?php echo esc_url( add_query_arg( 'cptui-extended-dismiss-activation', 'dismiss' ) ) ?>';
			})
		</script>

		<div id="message" class="cptuiextended-dismiss error notice is-dismissible">
			<?php printf( __( '<p>%sActivate%s your Custom Post Types UI Extended license here to receive support and upgrade notifications.</p>', 'cptuiext' ), '<a href="' . esc_url( admin_url( 'options-general.php?page=cptuiext_plugin_dashboard' ) ) . '">', '</a>' ); ?>
		</div>
		<?php
	}

	/**
	 * Check for external blocking contstant
	 * @return void
	 */
	public function check_external_blocking() {
		// Show notice if external requests are blocked through the WP_HTTP_BLOCK_EXTERNAL constant.
		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL === true ) {

			// Check if our API endpoint is in the allowed hosts.
			$host = wp_parse_url( $this->upgrade_url, PHP_URL_HOST );

			if ( ! defined( 'WP_ACCESSIBLE_HOSTS' ) || stristr( WP_ACCESSIBLE_HOSTS, $host ) === false ) {
				?>
				<div class="error">
					<p><?php printf( __( '<b>Warning!</b> You\'re blocking external requests which means you won\'t be able to get %s updates. Please add %s to %s.', 'cptuiext' ), $this->ame_software_product_id, '<strong>' . esc_attr( $host ) . '</strong>', '<code>WP_ACCESSIBLE_HOSTS</code>' ); ?></p>
				</div>
				<?php
			}
		}
	}
}


/**
 * Displays an inactive message if the API License Key has not yet been activated
 */
#if ( get_option( 'cptui_extended_plugin_activated_dismissed' ) !== 'dismissed' ) {
	#add_action( 'admin_notices', 'CPTUIEXT_Updater::am_example_inactive_notice' );
	#add_action( 'network_admin_notices', 'CPTUIEXT_Updater::am_example_inactive_notice' );
#}

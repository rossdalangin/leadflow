<?php
/**
 * LeadFlow_Core class for initializing the plugin.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Core {

	protected $loader;
	protected $plugin_name;
	protected $version;

	public function __construct() {
		$this->plugin_name = 'leadflow-pro';
		$this->version     = LEADFLOW_PRO_VERSION;
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	private function load_dependencies() {
		require_once LEADFLOW_PRO_PATH . 'includes/class-leadflow-loader.php';
		require_once LEADFLOW_PRO_PATH . 'includes/class-leadflow-security.php';
		require_once LEADFLOW_PRO_PATH . 'includes/class-leadflow-license.php';
		require_once LEADFLOW_PRO_PATH . 'database/class-leadflow-db.php';
		require_once LEADFLOW_PRO_PATH . 'api/class-leadflow-rest-api.php';
		require_once LEADFLOW_PRO_PATH . 'modules/ai/class-leadflow-ai.php';
		require_once LEADFLOW_PRO_PATH . 'modules/discovery/class-leadflow-discovery.php';
		require_once LEADFLOW_PRO_PATH . 'modules/crm/class-leadflow-crm.php';
		require_once LEADFLOW_PRO_PATH . 'modules/scraper/class-leadflow-scraper.php';
		require_once LEADFLOW_PRO_PATH . 'modules/outreach/class-leadflow-outreach.php';
		require_once LEADFLOW_PRO_PATH . 'modules/email/class-leadflow-email.php';
		require_once LEADFLOW_PRO_PATH . 'modules/email/class-leadflow-templates.php';
		require_once LEADFLOW_PRO_PATH . 'modules/compliance/class-leadflow-compliance.php';
		require_once LEADFLOW_PRO_PATH . 'modules/analytics/class-leadflow-analytics.php';

		$this->loader = new LeadFlow_Loader();
	}

	private function set_locale() {
		// Set locale logic if needed.
	}

	private function define_admin_hooks() {
		$this->loader->add_action( 'admin_init', $this, 'check_license_kill_switch' );
		$this->loader->add_action( 'admin_menu', $this, 'add_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_init', $this, 'register_settings' );
		$this->loader->add_action( 'admin_init', $this, 'handle_manual_actions' );
		$this->loader->add_action( 'admin_init', $this, 'check_db_version' );
		$this->loader->add_action( 'admin_notices', $this, 'display_usage_notices' );
	}

	/**
	 * Ensure DB tables exist on every admin init.
	 */
	public function check_db_version() {
		if ( ! get_option( 'leadflow_db_version' ) ) {
			require_once LEADFLOW_PRO_PATH . 'database/class-leadflow-db.php';
			LeadFlow_DB::create_tables();
			update_option( 'leadflow_db_version', LEADFLOW_PRO_VERSION );
		}
	}

	/**
	 * Handle manual actions like data seeding and OAuth callbacks.
	 */
	public function handle_manual_actions() {
		if ( ! current_user_can( 'manage_options' ) ) return;

		// Data Seeding
		if ( isset( $_GET['leadflow_seed'] ) ) {
			require_once LEADFLOW_PRO_PATH . 'database/class-leadflow-db.php';
			LeadFlow_DB::seed_data();
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-success is-dismissible"><p><strong>Success:</strong> Sample leads and tags have been seeded into your CRM.</p></div>';
			} );
		}

		// Gmail OAuth Callback
		if ( isset( $_GET['gmail_callback'] ) && isset( $_GET['code'] ) ) {
			$this->handle_gmail_oauth_callback( $_GET['code'] );
		}
	}

	private function handle_gmail_oauth_callback( $code ) {
		$client_id = LeadFlow_Security::get_decrypted_option( 'leadflow_gmail_client_id' );
		$client_secret = LeadFlow_Security::get_decrypted_option( 'leadflow_gmail_client_secret' );
		$redirect_uri = admin_url( 'admin.php?page=leadflow-settings&gmail_callback=1' );

		$response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
			'body' => array(
				'code'          => $code,
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri'  => $redirect_uri,
				'grant_type'    => 'authorization_code',
			),
		) );

		if ( is_wp_error( $response ) ) {
			wp_die( 'Failed to connect to Google: ' . $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['access_token'] ) ) {
			update_option( 'leadflow_gmail_token', $body['access_token'] ); // Filter handles encryption
			if ( isset( $body['refresh_token'] ) ) {
				update_option( 'leadflow_gmail_refresh_token', $body['refresh_token'] );
			}
			wp_redirect( admin_url( 'admin.php?page=leadflow-settings#smtp' ) );
			exit;
		} else {
			wp_die( 'OAuth Error: ' . wp_remote_retrieve_body( $response ) );
		}
	}

	private function define_public_hooks() {
		$this->loader->add_action( 'leadflow_process_scraper_queue', 'LeadFlow_Scraper', 'process_batch' );
		$this->loader->add_action( 'leadflow_process_campaigns', 'LeadFlow_Outreach', 'process_campaigns' );
		$this->loader->add_action( 'leadflow_process_sending_queue', 'LeadFlow_Outreach', 'process_sending_queue' );
		$this->loader->add_action( 'leadflow_poll_inbox', 'LeadFlow_Email', 'poll_inbox' );
		$this->loader->add_action( 'leadflow_check_usage', $this, 'check_ai_usage_alerts' );
		$this->loader->add_action( 'phpmailer_init', 'LeadFlow_Email', 'configure_smtp' );
		$this->loader->add_action( 'rest_api_init', $this, 'register_rest_routes' );
		$this->loader->add_action( 'init', $this, 'register_shortcodes' );
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_public_assets' );
	}

	public function register_shortcodes() {
		add_shortcode( 'leadflow_audit_form', array( $this, 'render_audit_form' ) );
	}

	public function enqueue_public_assets() {
		wp_register_script( 'leadflow-magnet', LEADFLOW_PRO_URL . 'assets/lead-magnet.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( 'leadflow-magnet', 'leadflowMagnet', array(
			'apiUrl' => get_rest_url( null, 'leadflow/v1' ),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
			'redirectUrl' => get_option( 'leadflow_magnet_redirect' ),
		) );
	}

	public function render_audit_form( $atts ) {
		wp_enqueue_script( 'leadflow-magnet' );
		ob_start();
		include LEADFLOW_PRO_PATH . 'admin/views/lead-magnet-form.php';
		return ob_get_clean();
	}

	/**
	 * Register all REST API routes on rest_api_init.
	 */
	public function check_ai_usage_alerts() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		foreach ( array( 'openai', 'gemini' ) as $provider ) {
			$budget = (int) get_option( "leadflow_token_budget_$provider", 50000 );
			$used = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(tokens_used) FROM {$prefix}ai_usage WHERE provider = %s", $provider ) );

			if ( $used >= $budget ) {
				$this->send_usage_email( $provider, '100%' );
			} elseif ( $used >= ( $budget * 0.8 ) ) {
				$this->send_usage_email( $provider, '80%' );
			}
		}
	}

	private function send_usage_email( $provider, $percent ) {
		$admin_email = get_option( 'admin_email' );
		$subject = "LeadFlow Pro: AI Usage Alert ($percent)";
		$message = "Your $provider AI token usage has reached $percent of your monthly budget. Please consider upgrading or increasing your budget in the plugin settings.";
		wp_mail( $admin_email, $subject, $message );
	}

	public function register_rest_routes() {
		$api = new LeadFlow_REST_API();
		$api->register_routes();
	}

	public function display_usage_notices() {
		if ( ! LeadFlow_License::is_pro() ) {
			global $wpdb;
			$prefix = $wpdb->prefix . 'leadflow_';

			foreach ( array( 'openai', 'gemini' ) as $provider ) {
				$budget = (int) get_option( "leadflow_token_budget_$provider", 50000 );
				$used = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(tokens_used) FROM {$prefix}ai_usage WHERE provider = %s", $provider ) );

				if ( $used >= ( $budget * 0.8 ) ) {
					echo '<div class="notice notice-warning"><p><strong>LeadFlow Pro:</strong> You have consumed ' . $used . ' ' . ucfirst( $provider ) . ' tokens (80% of your budget). Consider upgrading to Pro for unlimited AI.</p></div>';
				}
			}
		}
	}

	public function register_settings() {
		register_setting( 'leadflow-settings-group', 'leadflow_google_places_api_key' );
		register_setting( 'leadflow-settings-group', 'leadflow_ai_provider' );
		register_setting( 'leadflow-settings-group', 'leadflow_openai_api_key' );
		register_setting( 'leadflow-settings-group', 'leadflow_gemini_api_key' );
		register_setting( 'leadflow-settings-group', 'leadflow_license_key' );
		register_setting( 'leadflow-settings-group', 'leadflow_smtp_host' );
		register_setting( 'leadflow-settings-group', 'leadflow_smtp_port' );
		register_setting( 'leadflow-settings-group', 'leadflow_smtp_user' );
		register_setting( 'leadflow-settings-group', 'leadflow_smtp_pass' );
		register_setting( 'leadflow-settings-group', 'leadflow_email_provider' );
		register_setting( 'leadflow-settings-group', 'leadflow_gmail_token' );
		register_setting( 'leadflow-settings-group', 'leadflow_gmail_client_id' );
		register_setting( 'leadflow-settings-group', 'leadflow_gmail_client_secret' );
		register_setting( 'leadflow-settings-group', 'leadflow_smtp_encryption' );
		register_setting( 'leadflow-settings-group', 'leadflow_token_budget_openai' );
		register_setting( 'leadflow-settings-group', 'leadflow_token_budget_gemini' );
		register_setting( 'leadflow-settings-group', 'leadflow_crawl_delay' );
		register_setting( 'leadflow-settings-group', 'leadflow_linkedin_client_id' );
		register_setting( 'leadflow-settings-group', 'leadflow_facebook_app_id' );
		register_setting( 'leadflow-settings-group', 'leadflow_smtp_from_name' );
		register_setting( 'leadflow-settings-group', 'leadflow_smtp_from_email' );
		register_setting( 'leadflow-settings-group', 'leadflow_imap_host' );
		register_setting( 'leadflow-settings-group', 'leadflow_imap_port' );
		register_setting( 'leadflow-settings-group', 'leadflow_imap_user' );
		register_setting( 'leadflow-settings-group', 'leadflow_imap_pass' );
		register_setting( 'leadflow-settings-group', 'leadflow_imap_encryption' );
		register_setting( 'leadflow-settings-group', 'leadflow_email_signature' );
		register_setting( 'leadflow-settings-group', 'leadflow_webhook_qualified' );
		register_setting( 'leadflow-settings-group', 'leadflow_weight_name' );
		register_setting( 'leadflow-settings-group', 'leadflow_weight_url' );
		register_setting( 'leadflow-settings-group', 'leadflow_weight_email' );
		register_setting( 'leadflow-settings-group', 'leadflow_weight_phone' );
		register_setting( 'leadflow-settings-group', 'leadflow_weight_social' );
		register_setting( 'leadflow-settings-group', 'leadflow_auto_archive_negative' );

		// Lead Magnet Settings
		register_setting( 'leadflow-magnet-group', 'leadflow_magnet_success' );
		register_setting( 'leadflow-magnet-group', 'leadflow_magnet_redirect' );

		// Encryption hooks
		add_filter( 'pre_update_option_leadflow_smtp_pass', array( 'LeadFlow_Security', 'encrypt' ) );
		add_filter( 'pre_update_option_leadflow_imap_pass', array( 'LeadFlow_Security', 'encrypt' ) );
		add_filter( 'pre_update_option_leadflow_gmail_token', array( 'LeadFlow_Security', 'encrypt' ) );
		add_filter( 'pre_update_option_leadflow_gmail_client_id', array( 'LeadFlow_Security', 'encrypt' ) );
		add_filter( 'pre_update_option_leadflow_gmail_client_secret', array( 'LeadFlow_Security', 'encrypt' ) );
		add_filter( 'pre_update_option_leadflow_openai_api_key', array( 'LeadFlow_Security', 'encrypt' ) );
		add_filter( 'pre_update_option_leadflow_gemini_api_key', array( 'LeadFlow_Security', 'encrypt' ) );
		add_filter( 'pre_update_option_leadflow_google_places_api_key', array( 'LeadFlow_Security', 'encrypt' ) );
	}

	/**
	 * Deactivate core features if license is explicitly revoked.
	 */
	public function check_license_kill_switch() {
		$license_key = get_option('leadflow_license_key');
		if ( $license_key && ! LeadFlow_License::is_pro() ) {
			// License was revoked or expired
			add_action( 'admin_notices', function() {
				echo '<div class="notice notice-error"><p><strong>LeadFlow Pro:</strong> Your license is invalid or has been disabled by the administrator. Features are currently locked.</p></div>';
			} );
		}
	}

	public function add_admin_menu() {
		$is_activated = LeadFlow_License::is_pro();
		$capability = 'manage_options';

		add_menu_page(
			'LeadFlow Pro',
			'LeadFlow Pro',
			$capability,
			'leadflow-pro',
			array( $this, $is_activated ? 'display_dashboard' : 'display_activation' ),
			'dashicons-chart-line',
			25
		);

		if ( $is_activated ) {
			add_submenu_page( 'leadflow-pro', 'Dashboard', 'Dashboard', $capability, 'leadflow-pro', array( $this, 'display_dashboard' ) );
			add_submenu_page( 'leadflow-pro', 'Lead Discovery', 'Lead Discovery', $capability, 'leadflow-discovery', array( $this, 'display_discovery' ) );
			add_submenu_page( 'leadflow-pro', 'Leads', 'Leads', $capability, 'leadflow-leads', array( $this, 'display_leads' ) );
			add_submenu_page( 'leadflow-pro', 'Campaigns', 'Campaigns', $capability, 'leadflow-campaigns', array( $this, 'display_campaigns' ) );
			add_submenu_page( 'leadflow-pro', 'Inbox', 'Inbox', $capability, 'leadflow-inbox', array( $this, 'display_inbox' ) );
		add_submenu_page( 'leadflow-pro', 'Email Templates', 'Templates', $capability, 'leadflow-templates', array( $this, 'display_templates' ) );
			add_submenu_page( 'leadflow-pro', 'Settings', 'Settings', $capability, 'leadflow-settings', array( $this, 'display_settings' ) );
		} else {
			add_submenu_page( 'leadflow-pro', 'Activate', 'Activate License', $capability, 'leadflow-pro', array( $this, 'display_activation' ) );
		}

		add_submenu_page(
			'leadflow-pro',
			'Upgrade to Pro',
			'Upgrade to Pro',
			$capability,
			'leadflow-upgrade',
			array( $this, 'display_upgrade' )
		);
	}

	public function display_activation() {
		include_once LEADFLOW_PRO_PATH . 'admin/views/activation.php';
	}

	public function display_dashboard() {
		include_once LEADFLOW_PRO_PATH . 'admin/views/dashboard.php';
	}

	public function display_discovery() {
		include_once LEADFLOW_PRO_PATH . 'admin/views/discovery.php';
	}

	public function display_leads() {
		include_once LEADFLOW_PRO_PATH . 'admin/views/leads.php';
	}

	public function display_campaigns() {
		include_once LEADFLOW_PRO_PATH . 'admin/views/campaigns.php';
	}

	public function display_inbox() {
		include_once LEADFLOW_PRO_PATH . 'admin/views/inbox.php';
	}

	public function display_templates() {
		include_once LEADFLOW_PRO_PATH . 'admin/views/templates.php';
	}

	public function display_settings() {
		include_once LEADFLOW_PRO_PATH . 'admin/views/settings.php';
	}

	public function display_upgrade() {
		include_once LEADFLOW_PRO_PATH . 'admin/views/upgrade.php';
	}

	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, LEADFLOW_PRO_URL . 'admin/css/leadflow-admin.css', array(), $this->version, 'all' );
	}

	public function enqueue_scripts() {
		// Enqueue Chart.js for Analytics
		wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '4.4.0', true );

		$users = get_users( array( 'role__in' => array( 'administrator', 'editor' ) ) );
		$user_list = array();
		foreach ( $users as $user ) {
			$user_list[] = array( 'id' => $user->ID, 'name' => $user->display_name );
		}

		wp_enqueue_script( $this->plugin_name, LEADFLOW_PRO_URL . 'admin/js/leadflow-admin.js', array( 'jquery', 'chart-js' ), $this->version, false );
		wp_localize_script( $this->plugin_name, 'leadflowData', array(
			'apiUrl' => get_rest_url( null, 'leadflow/v1' ),
			'adminUrl' => admin_url(),
			'nonce'  => wp_create_nonce( 'wp_rest' ),
			'isPro'  => LeadFlow_License::is_pro(),
			'users'  => $user_list,
			'budgets' => array(
				'openai' => (int) get_option( 'leadflow_token_budget_openai', 50000 ),
				'gemini' => (int) get_option( 'leadflow_token_budget_gemini', 50000 ),
			)
		) );
	}

	public function run() {
		$this->loader->run();
	}
}

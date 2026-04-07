<?php
/**
 * Plugin Name: LeadFlow License Manager (Server)
 * Description: The central authority for LeadFlow Pro licenses. Generate and control licenses for your customers.
 * Version:     1.0.0
 * Author:      LeadFlow Team
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LFM_PATH', plugin_dir_path( __FILE__ ) );

class LeadFlow_License_Manager {

	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'install' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'rest_api_init', array( $this, 'register_api' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function install() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'lfm_licenses';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			license_key varchar(50) NOT NULL,
			license_type varchar(20) DEFAULT 'pro',
			status varchar(20) DEFAULT 'active',
			domain varchar(255),
			customer_name varchar(255),
			activated_at datetime,
			expires_at datetime,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY license_key (license_key)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function add_menu() {
		add_menu_page( 'LeadFlow Licenses', 'LF Licenses', 'manage_options', 'lf-licenses', array( $this, 'admin_page' ), 'dashicons-shield-lock' );
	}

	public function admin_page() {
		include LFM_PATH . 'admin/views/manager.php';
	}

	public function enqueue_assets() {
		if ( isset( $_GET['page'] ) && 'lf-licenses' === $_GET['page'] ) {
			wp_enqueue_style( 'lfm-admin', plugins_url( 'admin/css/manager.css', __FILE__ ) );
		}
	}

	public function register_api() {
		register_rest_route( 'lfm/v1', '/activate', array(
			'methods' => 'POST',
			'callback' => array( $this, 'api_activate' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( 'lfm/v1', '/validate', array(
			'methods' => 'POST',
			'callback' => array( $this, 'api_validate' ),
			'permission_callback' => '__return_true',
		) );
	}

	public function api_activate( $request ) {
		global $wpdb;
		$key = sanitize_text_field( $request->get_param( 'license_key' ) );
		$domain = esc_url_raw( $request->get_param( 'domain' ) );
		$domain = parse_url( $domain, PHP_URL_HOST );

		$license = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lfm_licenses WHERE license_key = %s", $key ) );

		if ( ! $license ) {
			return new WP_Error( 'invalid_key', 'License key not found.', array( 'status' => 403 ) );
		}

		if ( 'active' !== $license->status ) {
			return new WP_Error( 'inactive_key', 'This license has been disabled.', array( 'status' => 403 ) );
		}

		if ( ! empty( $license->domain ) && $license->domain !== $domain ) {
			return new WP_Error( 'domain_mismatch', 'This license is already tied to another domain: ' . $license->domain, array( 'status' => 403 ) );
		}

		$wpdb->update( "{$wpdb->prefix}lfm_licenses",
			array( 'domain' => $domain, 'activated_at' => current_time( 'mysql' ) ),
			array( 'id' => $license->id )
		);

		return rest_ensure_response( array( 'success' => true, 'message' => 'License activated for ' . $domain ) );
	}

	public function api_validate( $request ) {
		global $wpdb;
		$key = sanitize_text_field( $request->get_param( 'license_key' ) );
		$domain = esc_url_raw( $request->get_param( 'domain' ) );
		$domain = parse_url( $domain, PHP_URL_HOST );

		$license = $wpdb->get_row( $wpdb->prepare( "SELECT status, domain, license_type, expires_at FROM {$wpdb->prefix}lfm_licenses WHERE license_key = %s", $key ) );

		if ( ! $license || 'active' !== $license->status || $license->domain !== $domain ) {
			return rest_ensure_response( array( 'valid' => false ) );
		}

		// Expiration check
		if ( ! empty( $license->expires_at ) && strtotime( $license->expires_at ) < time() ) {
			return rest_ensure_response( array( 'valid' => false, 'message' => 'License has expired.' ) );
		}

		return rest_ensure_response( array(
			'valid' => true,
			'license_type' => $license->license_type
		) );
	}
}

new LeadFlow_License_Manager();

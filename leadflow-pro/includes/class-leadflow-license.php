<?php
/**
 * LeadFlow_License class for handling license validation and Pro features.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_License {

	private static $license_option = 'leadflow_license_key';
	private static $license_status = 'leadflow_license_status';

	private static function get_server_url() {
		return get_option( 'leadflow_license_server_url', 'https://license.leadflowpro.com/wp-json/lfm/v1' );
	}

	/**
	 * Check if the current user has access to Pro features.
	 *
	 * Uses a transient cache to minimize remote requests.
	 */
	public static function is_pro() {
		$status = get_transient( 'leadflow_license_cache' );

		if ( false === $status ) {
			$license_key = get_option( self::$license_option );
			if ( ! $license_key ) {
				return false;
			}

			$remote = self::remote_validate( $license_key );
			$status = $remote['status'];
			update_option( 'leadflow_license_type', $remote['type'] );

			set_transient( 'leadflow_license_cache', $status, 12 * HOUR_IN_SECONDS );
		}

		return 'active' === $status;
	}

	/**
	 * Activate license key against the remote LeadFlow License Manager.
	 */
	public static function validate_license( $license_key ) {
		$response = wp_remote_post( self::get_server_url() . '/activate', array(
			'body' => array(
				'license_key' => $license_key,
				'domain'      => get_site_url(),
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => 'Connection failed: ' . $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['success'] ) && $body['success'] ) {
			update_option( self::$license_option, $license_key );
			update_option( self::$license_status, 'active' );
			delete_transient( 'leadflow_license_cache' );
			return array( 'success' => true, 'message' => $body['message'] );
		}

		return array( 'success' => false, 'message' => isset( $body['message'] ) ? $body['message'] : 'Invalid license key.' );
	}

	/**
	 * Internal validation check (periodic).
	 */
	private static function remote_validate( $license_key ) {
		$response = wp_remote_post( self::get_server_url() . '/validate', array(
			'body' => array(
				'license_key' => $license_key,
				'domain'      => get_site_url(),
			),
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'status' => 'active', 'type' => get_option('leadflow_license_type', 'pro') );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return array(
			'status' => ( isset( $body['valid'] ) && $body['valid'] ) ? 'active' : 'inactive',
			'type'   => isset( $body['license_type'] ) ? $body['license_type'] : 'pro'
		);
	}

	/**
	 * Enforce limits based on plan.
	 */
	public static function check_limit( $resource ) {
		if ( self::is_pro() ) {
			return true;
		}

		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		switch ( $resource ) {
			case 'leads':
				// Global check
				$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}leads" );
				if ( ! self::is_pro() && $count >= 50 ) return false;

				// Team Quota check (Pro feature)
				$args = func_get_args();
				$user_id = isset( $args[1] ) ? $args[1] : get_current_user_id();
				$quota = (int) get_option( "leadflow_quota_user_$user_id", 0 );

				if ( $quota > 0 ) {
					$user_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}leads WHERE assigned_to = %d", $user_id ) );
					if ( $user_count >= $quota ) return false;
				}

				return true;
			case 'campaigns':
				$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}campaigns" );
				return $count < 1;
			case 'ai_usage':
				$provider = get_option( 'leadflow_ai_provider', 'openai' );
				$budget = (int) get_option( "leadflow_token_budget_$provider", 50000 );
				$used = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(tokens_used) FROM {$prefix}ai_usage WHERE provider = %s", $provider ) );
				return $used < $budget;
			default:
				return false;
		}
	}

	/**
	 * Helper for the "Kill Switch"
	 */
	public static function get_status() {
		return get_option( self::$license_status, 'inactive' );
	}

	/**
	 * Activate demo license (Internal use only).
	 */
	public static function activate_demo_license() {
		update_option( self::$license_option, 'LF-DEMO-PRO-VERSION' );
		update_option( self::$license_status, 'active' );
		update_option( 'leadflow_license_type', 'pro' );
		set_transient( 'leadflow_license_cache', 'active', 30 * DAY_IN_SECONDS );
	}
}

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

	/**
	 * Check if the current user has access to Pro features.
	 *
	 * This single helper ensures that Pro-only logic is consistently applied.
	 *
	 * @return bool
	 */
	public static function is_pro() {
		// For development purposes, let's allow setting a license via an option
		$status = get_option( self::$license_status );
		return 'active' === $status;
	}

	/**
	 * Validate license key against a remote API.
	 *
	 * @param string $license_key The license key to validate.
	 * @return array Validation result.
	 */
	public static function validate_license( $license_key ) {
		// Mock remote API validation
		$response = wp_remote_post( 'https://api.leadflowpro.com/v1/license/validate', array(
			'body' => array(
				'license_key' => $license_key,
				'site_url'    => get_site_url(),
			),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => 'Failed to connect to license server.' );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['status'] ) && 'active' === $body['status'] ) {
			update_option( self::$license_option, $license_key );
			update_option( self::$license_status, 'active' );
			return array( 'success' => true, 'message' => 'License activated successfully!' );
		}

		return array( 'success' => false, 'message' => 'Invalid license key.' );
	}

	/**
	 * Activate a demo license for development/testing.
	 */
	public static function activate_demo_license() {
		update_option( self::$license_option, 'LF-DEMO-PRO-2024' );
		update_option( self::$license_status, 'active' );
		return true;
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
				$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}leads" );
				return $count < 50;
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
}

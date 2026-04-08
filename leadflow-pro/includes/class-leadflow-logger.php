<?php
/**
 * LeadFlow_Logger class for centralized system logging.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Logger {

	public static function log( $module, $message, $level = 'info' ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		return $wpdb->insert(
			"{$prefix}logs",
			array(
				'level'      => $level,
				'module'     => $module,
				'message'    => $message,
				'created_at' => current_time( 'mysql' ),
			)
		);
	}

	public static function error( $module, $message ) {
		return self::log( $module, $message, 'error' );
	}

	public static function get_logs( $limit = 50 ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$prefix}logs ORDER BY created_at DESC LIMIT %d", $limit ) );
	}
}

<?php
/**
 * LeadFlow_Webhooks class for managing multiple event-driven webhooks.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Webhooks {

	/**
	 * Trigger webhooks for a specific event.
	 *
	 * @param string $event   The event name (e.g. 'lead_created').
	 * @param array  $payload Data to send.
	 */
	public static function trigger( $event, $payload ) {
		if ( ! LeadFlow_License::is_pro() ) return;

		$webhooks = get_option( 'leadflow_webhooks', array() );
		foreach ( $webhooks as $webhook ) {
			if ( isset( $webhook['events'] ) && in_array( $event, $webhook['events'] ) ) {
				self::dispatch( $webhook['url'], $event, $payload );
			}
		}
	}

	private static function dispatch( $url, $event, $payload ) {
		$response = wp_remote_post( $url, array(
			'timeout' => 10,
			'body'    => wp_json_encode( array(
				'event'     => $event,
				'site'      => get_site_url(),
				'timestamp' => current_time( 'timestamp' ),
				'data'      => $payload,
			) ),
			'headers' => array( 'Content-Type' => 'application/json' ),
		) );

		$status = is_wp_error( $response ) ? 'failed' : ( wp_remote_retrieve_response_code( $response ) === 200 ? 'success' : 'failed' );
		$msg = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $response );

		LeadFlow_Logger::log( 'Webhook', "Event '$event' sent to $url - Status: $status ($msg)", $status === 'failed' ? 'error' : 'info' );
	}
}

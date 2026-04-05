<?php
/**
 * LeadFlow_AI_Gemini class for handling Gemini-specific AI completion requests.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_AI_Gemini {

	private static $api_key_option = 'leadflow_gemini_api_key';

	/**
	 * Perform Gemini completion.
	 *
	 * @param string $prompt  The prompt to complete.
	 * @param array  $context Optional context for the request.
	 * @return string Completion result.
	 */
	public static function complete( $prompt, $context = array() ) {
		$api_key = LeadFlow_Security::get_decrypted_option( self::$api_key_option );

		if ( ! $api_key ) {
			return 'Gemini API key not configured.';
		}

		$response = wp_remote_post( 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent?key=' . $api_key, array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body' => wp_json_encode( array(
				'contents' => array(
					array( 'parts' => array( array( 'text' => $prompt ) ) ),
				),
				'generationConfig' => array(
					'temperature' => 0.7,
				),
			) ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return 'Gemini API request failed: ' . $response->get_error_message();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['candidates'][0]['content']['parts'][0]['text'] ) ) {
			// Gemini doesn't always provide token usage in the same way; mock log for now or extract if present.
			$tokens = isset( $body['usageMetadata']['totalTokenCount'] ) ? $body['usageMetadata']['totalTokenCount'] : 0;
			LeadFlow_AI::log_usage( 'gemini', isset( $context['feature'] ) ? $context['feature'] : 'unknown', $tokens, $context );
			return $body['candidates'][0]['content']['parts'][0]['text'];
		}

		return 'Invalid response from Gemini: ' . wp_json_encode( $body );
	}
}

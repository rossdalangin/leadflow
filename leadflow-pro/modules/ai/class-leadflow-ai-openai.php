<?php
/**
 * LeadFlow_AI_OpenAI class for handling OpenAI-specific AI completion requests.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_AI_OpenAI {

	private static $api_key_option = 'leadflow_openai_api_key';

	/**
	 * Perform OpenAI completion.
	 *
	 * @param string $prompt  The prompt to complete.
	 * @param array  $context Optional context for the request.
	 * @return string Completion result.
	 */
	public static function complete( $prompt, $context = array() ) {
		$api_key = LeadFlow_Security::get_decrypted_option( self::$api_key_option );

		if ( ! $api_key ) {
			return 'OpenAI API key not configured.';
		}

		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			),
			'body' => wp_json_encode( array(
				'model'    => 'gpt-4o',
				'messages' => array(
					array( 'role' => 'user', 'content' => $prompt ),
				),
				'temperature' => 0.7,
			) ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return 'OpenAI API request failed: ' . $response->get_error_message();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['choices'][0]['message']['content'] ) ) {
			$tokens = isset( $body['usage']['total_tokens'] ) ? $body['usage']['total_tokens'] : 0;
			LeadFlow_AI::log_usage( 'openai', isset( $context['feature'] ) ? $context['feature'] : 'unknown', $tokens, $context );
			return $body['choices'][0]['message']['content'];
		}

		return 'Invalid response from OpenAI: ' . wp_json_encode( $body );
	}
}

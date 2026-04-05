<?php
/**
 * LeadFlow_AI class for routing AI requests to the appropriate adapter.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_AI {

	private static $active_provider_option = 'leadflow_ai_provider';

	/**
	 * Route completion request to active provider.
	 *
	 * @param string $prompt  The prompt to complete.
	 * @param array  $context Optional context for the request.
	 * @return string Completion result.
	 */
	public static function complete( $prompt, $context = array() ) {
		$provider = self::get_active_provider();

		switch ( $provider ) {
			case 'openai':
				require_once LEADFLOW_PRO_PATH . 'modules/ai/class-leadflow-ai-openai.php';
				return LeadFlow_AI_OpenAI::complete( $prompt, $context );
			case 'gemini':
				require_once LEADFLOW_PRO_PATH . 'modules/ai/class-leadflow-ai-gemini.php';
				return LeadFlow_AI_Gemini::complete( $prompt, $context );
			default:
				return 'No AI provider selected or configured.';
		}
	}

	/**
	 * Get the currently active AI provider.
	 *
	 * @return string Provider slug.
	 */
	public static function get_active_provider() {
		return get_option( self::$active_provider_option, 'openai' );
	}

	/**
	 * Log AI usage to the database.
	 *
	 * @param string $provider The provider used.
	 * @param string $feature  The feature triggered.
	 * @param int    $tokens   The number of tokens consumed.
	 * @param array  $ids      Optional lead and campaign IDs.
	 */
	public static function log_usage( $provider, $feature, $tokens, $ids = array() ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$wpdb->insert(
			"{$prefix}ai_usage",
			array(
				'provider'    => $provider,
				'feature'     => $feature,
				'tokens_used' => $tokens,
				'lead_id'     => isset( $ids['lead_id'] ) ? $ids['lead_id'] : null,
				'campaign_id' => isset( $ids['campaign_id'] ) ? $ids['campaign_id'] : null,
				'created_at'  => current_time( 'mysql' ),
			)
		);
	}
}

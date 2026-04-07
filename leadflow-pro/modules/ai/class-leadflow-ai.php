<?php
/**
 * LeadFlow_AI: The Intelligence Core
 *
 * This module serves as the brain of the plugin. It uses a provider-agnostic
 * adapter pattern, allowing you to switch between OpenAI and Google Gemini
 * without changing a single line of business logic.
 *
 * Key Features:
 * - Content Generation: Writing personalized cold emails.
 * - Lead Scoring: Categorizing leads by 'temperature' based on data.
 * - Sentiment Analysis: Detecting if a lead is interested or wants to opt-out.
 * - Audit Summaries: Converting technical data into sales hooks.
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
		if ( ! LeadFlow_License::check_limit( 'ai_usage' ) ) {
			return 'AI usage limit reached on Free plan.';
		}

		$provider = isset( $context['provider'] ) ? $context['provider'] : self::get_active_provider();

		// Pro gate for switching providers
		if ( $provider !== 'openai' && ! LeadFlow_License::is_pro() ) {
			return 'Switching AI providers is a Pro feature.';
		}

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

	/**
	 * AI: Lead temperature scorer.
	 */
	public static function score_lead( $lead_data, $audit_data ) {
		$prompt = "Score this lead (Hot/Warm/Cold) based on: " . wp_json_encode( $lead_data ) . " and audit: " . wp_json_encode( $audit_data ) . ". Provide one line reasoning.";
		return self::complete( $prompt, array( 'feature' => 'lead_scorer', 'lead_id' => $lead_data['id'] ) );
	}

	/**
	 * AI: Subject line generator.
	 */
	public static function generate_subject_lines( $business_name ) {
		$prompt = "Generate 3 high-converting cold email subject lines for $business_name. Rank them by predicted open rate. Output only the lines, one per line.";
		return self::complete( $prompt, array( 'feature' => 'subject_generator' ) );
	}

	/**
	 * AI: Reply sentiment analyzer.
	 */
	public static function analyze_sentiment( $reply_text ) {
		$prompt = "Classify this email reply sentiment as Positive, Neutral, Negative, or Unsubscribe Intent: \"$reply_text\"";
		return self::complete( $prompt, array( 'feature' => 'sentiment_analysis' ) );
	}

	/**
	 * AI: Automatically tag lead based on audit results.
	 */
	public static function auto_tag_lead( $lead_id, $audit_results ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$tags_to_apply = array();
		if ( ! $audit_results['has_ssl'] ) $tags_to_apply[] = 'no-ssl';
		if ( ! $audit_results['is_mobile_responsive'] ) $tags_to_apply[] = 'needs-mobile';
		if ( isset($audit_results['outdated_design']) && $audit_results['outdated_design'] ) $tags_to_apply[] = 'outdated';
		if ( $audit_results['load_time'] > 3 ) $tags_to_apply[] = 'slow-load';
		if ( $audit_results['cms'] === 'WordPress' ) $tags_to_apply[] = 'wordpress';
		if ( $audit_results['is_ecommerce'] ) $tags_to_apply[] = 'ecommerce';
		if ( isset($audit_results['page_builder']) ) $tags_to_apply[] = strtolower($audit_results['page_builder']);
		if ( ! isset($audit_results['seo_plugin']) && $audit_results['cms'] === 'WordPress' ) $tags_to_apply[] = 'no-seo';

		foreach ( $tags_to_apply as $slug ) {
			$tag_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$prefix}lead_tags WHERE slug = %s", $slug ) );
			if ( $tag_id ) {
				$wpdb->replace( "{$prefix}lead_tag_relationships", array( 'lead_id' => $lead_id, 'tag_id' => $tag_id ) );
			}
		}
	}

	/**
	 * AI: Audit insight summary.
	 */
	public static function summarize_audit( $audit_results ) {
		$prompt = "Convert these raw website audit flags into a plain-English sentence usable as an outreach hook: " . wp_json_encode( $audit_results );
		return self::complete( $prompt, array( 'feature' => 'audit_insight' ) );
	}

	/**
	 * AI: Suggest a reply to an inbound message with thread context.
	 */
	public static function suggest_reply( $inbound_text, $lead_id = null ) {
		$thread_context = "";
		if ( $lead_id ) {
			global $wpdb;
			$prefix = $wpdb->prefix . 'leadflow_';
			$last_messages = $wpdb->get_results( $wpdb->prepare( "SELECT subject, status FROM {$prefix}email_log WHERE lead_id = %d ORDER BY created_at DESC LIMIT 3", $lead_id ) );
			foreach ( $last_messages as $msg ) {
				$thread_context .= "Previous interaction: " . $msg->subject . " (Status: " . $msg->status . ")\n";
			}
		}

		$prompt = "Based on this thread context:\n$thread_context\nAnd this new inbound message: \"$inbound_text\"\nSuggest a professional and friendly reply that moves the lead towards a discovery call.";
		return self::complete( $prompt, array( 'feature' => 'reply_suggestion' ) );
	}

	/**
	 * Test connection to AI providers.
	 */
	public static function test_connectivity( $provider ) {
		$prompt = "Ping";
		$result = self::complete( $prompt, array( 'provider' => $provider, 'feature' => 'test_connection' ) );
		return ( stripos( $result, 'Error' ) === false && stripos( $result, 'reached' ) === false );
	}
}

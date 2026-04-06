<?php
/**
 * LeadFlow_Analytics class for aggregating plugin metrics.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Analytics {

	/**
	 * Get lead counts by status for doughnut chart.
	 */
	public static function get_leads_by_status() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		return $wpdb->get_results( "SELECT status, COUNT(*) as count FROM {$prefix}leads GROUP BY status", ARRAY_A );
	}

	/**
	 * Get outreach metrics (sent, opened, clicked, replied).
	 */
	public static function get_outreach_metrics( $start_date = null, $end_date = null ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$where = "WHERE 1=1";
		if ( $start_date && $end_date ) {
			$where .= $wpdb->prepare( " AND created_at BETWEEN %s AND %s", $start_date, $end_date );
		}

		$sent    = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}email_log $where" );
		$opened  = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}email_log $where AND opens_count > 0" );
		$clicked = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}email_log $where AND clicks_count > 0" );
		$replies = $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}email_log $where AND status = 'Replied'" );

		return array(
			'sent'    => (int) $sent,
			'opened'  => (int) $opened,
			'clicked' => (int) $clicked,
			'replies' => (int) $replies,
		);
	}

	/**
	 * Get AI usage stats per provider.
	 */
	public static function get_ai_usage_stats() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		return $wpdb->get_results( "SELECT provider, SUM(tokens_used) as total_tokens, COUNT(*) as request_count FROM {$prefix}ai_usage GROUP BY provider", ARRAY_A );
	}
}

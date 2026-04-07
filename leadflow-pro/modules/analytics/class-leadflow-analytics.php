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
	 * Get lead counts by source.
	 */
	public static function get_leads_by_source() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		return $wpdb->get_results( "SELECT lead_source as source, COUNT(*) as count FROM {$prefix}leads GROUP BY lead_source", ARRAY_A );
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
			'top_template' => self::get_top_performing_template()
		);
	}

	public static function get_top_performing_template() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		return $wpdb->get_var( "
			SELECT t.name
			FROM {$prefix}email_templates t
			JOIN {$prefix}email_log e ON t.id = e.template_id
			GROUP BY e.template_id
			ORDER BY (SUM(CASE WHEN e.opens_count > 0 THEN 1 ELSE 0 END) / COUNT(*)) DESC
			LIMIT 1" ) ?: 'None yet';
	}

	/**
	 * Get AI usage stats per provider.
	 */
	public static function get_ai_usage_stats() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		return $wpdb->get_results( "SELECT provider, SUM(tokens_used) as total_tokens, COUNT(*) as request_count FROM {$prefix}ai_usage GROUP BY provider", ARRAY_A );
	}

	/**
	 * Get latest activity across the plugin.
	 */
	public static function get_recent_activity( $limit = 10 ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		// Combine emails and new leads into a single timeline
		$query = "
			(SELECT 'email' as type, subject as activity, l.business_name as lead, e.created_at
			 FROM {$prefix}email_log e
			 JOIN {$prefix}leads l ON e.lead_id = l.id)
			UNION
			(SELECT 'lead' as type, 'New Lead Discovered' as activity, business_name as lead, created_at
			 FROM {$prefix}leads)
			ORDER BY created_at DESC LIMIT %d";

		return $wpdb->get_results( $wpdb->prepare( $query, $limit ), ARRAY_A );
	}
}

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
		$cache = get_transient( 'leadflow_leads_by_status' );
		if ( false !== $cache ) return $cache;

		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		// Map existing leads to all possible statuses to ensure chart consistency
		$statuses = array( 'New', 'Contacted', 'Replied', 'Qualified', 'Proposal Sent', 'Closed Won', 'Closed Lost' );
		$results = array();

		$counts = $wpdb->get_results( "SELECT status, COUNT(*) as count FROM {$prefix}leads GROUP BY status", OBJECT_K );

		foreach ( $statuses as $status ) {
			$results[] = array(
				'status' => $status,
				'count'  => isset( $counts[ $status ] ) ? (int) $counts[ $status ]->count : 0
			);
		}

		set_transient( 'leadflow_leads_by_status', $results, HOUR_IN_SECONDS );
		return $results;
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
	 * Get template performance comparison for A/B testing insights.
	 */
	public static function get_ab_test_insights() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		return $wpdb->get_results( "
			SELECT
				t.name as template_name,
				COUNT(e.id) as sent,
				SUM(CASE WHEN e.opens_count > 0 THEN 1 ELSE 0 END) as opened,
				SUM(CASE WHEN e.clicks_count > 0 THEN 1 ELSE 0 END) as clicked,
				SUM(CASE WHEN e.status = 'Replied' THEN 1 ELSE 0 END) as replied
			FROM {$prefix}email_templates t
			JOIN {$prefix}email_log e ON t.id = e.template_id
			GROUP BY t.id
			HAVING sent > 0
			ORDER BY (replied / sent) DESC", ARRAY_A );
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
	 * Get lead counts by assignee for team performance widget.
	 */
	public static function get_leads_by_assignee() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$query = "
			SELECT u.display_name as name, COUNT(l.id) as count
			FROM {$wpdb->users} u
			LEFT JOIN {$prefix}leads l ON u.ID = l.assigned_to
			GROUP BY u.ID
			HAVING count > 0";

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get leads that need immediate attention.
	 */
	public static function get_daily_pulse() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		// Leads who replied but haven't been contacted since
		$replied_no_followup = $wpdb->get_results( "
			SELECT l.id, l.business_name, l.status, 'Replied - No follow-up' as reason
			FROM {$prefix}leads l
			WHERE l.status = 'Replied'
			AND l.id NOT IN (SELECT lead_id FROM {$prefix}email_log WHERE status = 'Sent' AND created_at > (SELECT MAX(created_at) FROM {$prefix}lead_notes WHERE lead_id = l.id AND content LIKE 'Inbound%'))
			LIMIT 5", ARRAY_A );

		// Leads with failed scraper jobs
		$failed_scrapes = $wpdb->get_results( "
			SELECT l.id, l.business_name, l.status, 'Website audit failed' as reason
			FROM {$prefix}leads l
			JOIN {$prefix}scrape_queue q ON l.id = q.lead_id
			WHERE q.status = 'Failed'
			LIMIT 5", ARRAY_A );

		// Pending tasks
		$pending_tasks = $wpdb->get_results( "
			SELECT l.id, l.business_name, l.status, CONCAT('Pending task: ', t.description) as reason
			FROM {$prefix}leads l
			JOIN {$prefix}tasks t ON l.id = t.lead_id
			WHERE t.status = 'pending' AND t.due_date <= NOW()
			LIMIT 5", ARRAY_A );

		return array(
			'leads' => array_merge( $replied_no_followup, $failed_scrapes, $pending_tasks ),
			'queue_stats' => array(
				'outreach' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}sending_queue WHERE status = 'Scheduled'" ),
				'scraper'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}scrape_queue WHERE status = 'Pending'" ),
				'tasks'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$prefix}tasks WHERE status = 'pending'" )
			)
		);
	}

	/**
	 * Get sentiment breakdown for the last 30 days.
	 */
	public static function get_sentiment_pulse() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$results = $wpdb->get_results( "
			SELECT
				CASE
					WHEN content LIKE '%Sentiment: Positive%' THEN 'Positive'
					WHEN content LIKE '%Sentiment: Negative%' THEN 'Negative'
					WHEN content LIKE '%Sentiment: Unsubscribe%' THEN 'Opt-out'
					ELSE 'Neutral'
				END as sentiment,
				COUNT(*) as count
			FROM {$prefix}lead_notes
			WHERE content LIKE 'Inbound Reply%'
			AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY sentiment", ARRAY_A );

		return $results;
	}

	/**
	 * Get latest activity across the plugin.
	 */
	public static function get_recent_activity( $limit = 10 ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		// Combine emails, tasks, and new leads into a single timeline
		$query = "
			(SELECT 'email' as type, subject as activity, l.business_name as lead, e.created_at
			 FROM {$prefix}email_log e
			 JOIN {$prefix}leads l ON e.lead_id = l.id)
			UNION
			(SELECT 'task' as type, CONCAT('Task Created: ', t.description) as activity, l.business_name as lead, t.created_at
			 FROM {$prefix}tasks t
			 JOIN {$prefix}leads l ON t.lead_id = l.id)
			UNION
			(SELECT 'lead' as type, 'New Lead Discovered' as activity, business_name as lead, created_at
			 FROM {$prefix}leads)
			ORDER BY created_at DESC LIMIT %d";

		return $wpdb->get_results( $wpdb->prepare( $query, $limit ), ARRAY_A );
	}
}

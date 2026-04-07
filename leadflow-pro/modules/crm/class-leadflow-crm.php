<?php
/**
 * LeadFlow_CRM class for handling lead management.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_CRM {

	/**
	 * Create a new lead.
	 */
	public static function create_lead( $data ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		if ( ! LeadFlow_License::check_limit( 'leads' ) ) {
			return new WP_Error( 'limit_reached', 'Lead limit reached on Free plan.' );
		}

		$defaults = array(
			'first_name'    => '',
			'business_name' => '',
			'website_url'   => '',
			'email'         => '',
			'phone'         => '',
			'social_links'  => wp_json_encode( array() ),
			'lead_source'   => 'Manual',
			'status'        => 'New',
			'assigned_to'   => get_current_user_id(),
			'created_at'    => current_time( 'mysql' ),
			'updated_at'    => current_time( 'mysql' ),
		);

		$data = wp_parse_args( $data, $defaults );

		// Final sanitization before DB
		$data['email']       = sanitize_email( $data['email'] );
		$data['website_url'] = esc_url_raw( $data['website_url'] );

		// Calculate score
		$score = 0;
		if ( ! empty( $data['business_name'] ) ) $score += 20;
		if ( ! empty( $data['website_url'] ) ) $score += 20;
		if ( ! empty( $data['email'] ) ) $score += 30;
		if ( ! empty( $data['phone'] ) ) $score += 15;
		if ( ! empty( $data['social_links'] ) && '[]' !== $data['social_links'] ) $score += 15;
		$data['completeness_score'] = $score;

		$result = $wpdb->insert( "{$prefix}leads", $data );

		if ( ! $result ) {
			return new WP_Error( 'db_error', 'Failed to create lead.' );
		}

		delete_transient( 'leadflow_leads_by_status' );

		$lead_id = $wpdb->insert_id;

		// Add lead to scraping queue
		if ( ! empty( $data['website_url'] ) ) {
			LeadFlow_Scraper::add_to_queue( $lead_id );
		}

		return $lead_id;
	}

	/**
	 * Get leads with filters and pagination.
	 */
	public static function get_leads( $args = array() ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$defaults = array(
			'status'  => '',
			'search'  => '',
			'limit'   => 20,
			'offset'  => 0,
			'orderby' => 'created_at',
			'order'   => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		// Whitelist for SQL Injection prevention
		$allowed_orderby = array( 'id', 'business_name', 'email', 'status', 'created_at', 'updated_at', 'completeness_score' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';

		$where = array( '1=1' );
		if ( ! empty( $args['id'] ) ) {
			$where[] = $wpdb->prepare( 'id = %d', $args['id'] );
		}
		if ( ! empty( $args['status'] ) ) {
			$where[] = $wpdb->prepare( 'status = %s', $args['status'] );
		}
		if ( ! empty( $args['search'] ) ) {
			$where[] = $wpdb->prepare( '(business_name LIKE %s OR email LIKE %s OR website_url LIKE %s)', '%' . $args['search'] . '%', '%' . $args['search'] . '%', '%' . $args['search'] . '%' );
		}

		$where_str = implode( ' AND ', $where );

		$query = $wpdb->prepare(
			"SELECT * FROM {$prefix}leads WHERE $where_str ORDER BY $orderby $order LIMIT %d OFFSET %d",
			$args['limit'],
			$args['offset']
		);

		return $wpdb->get_results( $query );
	}

	/**
	 * Update lead data.
	 */
	public static function update_lead( $lead_id, $data ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		if ( isset( $data['email'] ) ) $data['email'] = sanitize_email( $data['email'] );
		if ( isset( $data['website_url'] ) ) $data['website_url'] = esc_url_raw( $data['website_url'] );

		// Re-calculate score if relevant fields changed
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}leads WHERE id = %d", $lead_id ), ARRAY_A );
		if ( $lead ) {
			$merged = array_merge( $lead, $data );
			$score = 0;
			if ( ! empty( $merged['business_name'] ) ) $score += 20;
			if ( ! empty( $merged['website_url'] ) ) $score += 20;
			if ( ! empty( $merged['email'] ) ) $score += 30;
			if ( ! empty( $merged['phone'] ) ) $score += 15;
			if ( ! empty( $merged['social_links'] ) && '[]' !== $merged['social_links'] ) $score += 15;
			$data['completeness_score'] = $score;
		}

		$data['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->update( "{$prefix}leads", $data, array( 'id' => $lead_id ) );

		if ( $result ) {
			delete_transient( 'leadflow_leads_by_status' );
			if ( isset( $data['status'] ) && 'Qualified' === $data['status'] ) {
				self::trigger_webhook( $lead_id, 'qualified' );
			}
		}

		return $result;
	}

	/**
	 * Update lead status.
	 */
	public static function update_status( $lead_id, $status ) {
		return self::update_lead( $lead_id, array( 'status' => $status ) );
	}

	/**
	 * Trigger external webhook.
	 */
	private static function trigger_webhook( $lead_id, $event ) {
		$url = get_option( "leadflow_webhook_$event" );
		if ( ! $url || ! LeadFlow_License::is_pro() ) return;

		global $wpdb;
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}leadflow_leads WHERE id = %d", $lead_id ), ARRAY_A );

		wp_remote_post( $url, array(
			'body' => array(
				'event' => $event,
				'lead'  => $lead,
				'site'  => get_site_url(),
				'timestamp' => current_time('timestamp')
			)
		) );
	}

	/**
	 * Calculate lead score based on data completeness (0-100).
	 */
	public static function calculate_completeness_score( $lead_id ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}leads WHERE id = %d", $lead_id ) );

		if ( ! $lead ) return 0;

		$w_name   = (int) get_option( 'leadflow_weight_name', 20 );
		$w_url    = (int) get_option( 'leadflow_weight_url', 20 );
		$w_email  = (int) get_option( 'leadflow_weight_email', 30 );
		$w_phone  = (int) get_option( 'leadflow_weight_phone', 15 );
		$w_social = (int) get_option( 'leadflow_weight_social', 15 );

		$score = 0;
		if ( ! empty( $lead->business_name ) ) $score += $w_name;
		if ( ! empty( $lead->website_url ) ) $score += $w_url;
		if ( ! empty( $lead->email ) ) $score += $w_email;
		if ( ! empty( $lead->phone ) ) $score += $w_phone;
		if ( ! empty( $lead->social_links ) && '[]' !== $lead->social_links ) $score += $w_social;

		return min( $score, 100 );
	}

	/**
	 * Add note to lead.
	 */
	public static function add_note( $lead_id, $content, $author_id = null ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		if ( ! $author_id ) {
			$author_id = get_current_user_id();
		}

		return $wpdb->insert(
			"{$prefix}lead_notes",
			array(
				'lead_id'    => $lead_id,
				'author_id'  => $author_id,
				'content'    => $content,
				'created_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Export leads to CSV (Pro only).
	 */
	/**
	 * Task Management.
	 */
	public static function get_tasks( $lead_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}leadflow_tasks WHERE lead_id = %d ORDER BY due_date ASC", $lead_id ) );
	}

	public static function add_task( $data ) {
		global $wpdb;
		$data['created_at'] = current_time( 'mysql' );
		return $wpdb->insert( $wpdb->prefix . 'leadflow_tasks', $data );
	}

	public static function update_task_status( $task_id, $status ) {
		global $wpdb;
		return $wpdb->update( $wpdb->prefix . 'leadflow_tasks', array( 'status' => $status ), array( 'id' => $task_id ) );
	}

	public static function delete_task( $task_id ) {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . 'leadflow_tasks', array( 'id' => $task_id ) );
	}

	public static function export_to_csv() {
		if ( ! LeadFlow_License::is_pro() ) {
			return new WP_Error( 'pro_required', 'CSV export is a Pro feature.' );
		}

		$leads = self::get_leads( array( 'limit' => 10000 ) );

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="leadflow_leads_' . date( 'Y-m-d' ) . '.csv"' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'Business Name', 'Website', 'Email', 'Phone', 'Status', 'Created At' ) );

		foreach ( $leads as $lead ) {
			fputcsv( $output, array(
				$lead->id,
				$lead->business_name,
				$lead->website_url,
				$lead->email,
				$lead->phone,
				$lead->status,
				$lead->created_at,
			) );
		}

		fclose( $output );
		exit;
	}
}

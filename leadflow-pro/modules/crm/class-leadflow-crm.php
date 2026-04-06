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

		$result = $wpdb->insert( "{$prefix}leads", $data );

		if ( ! $result ) {
			return new WP_Error( 'db_error', 'Failed to create lead.' );
		}

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

		$where = array( '1=1' );
		if ( ! empty( $args['status'] ) ) {
			$where[] = $wpdb->prepare( 'status = %s', $args['status'] );
		}
		if ( ! empty( $args['search'] ) ) {
			$where[] = $wpdb->prepare( '(business_name LIKE %s OR email LIKE %s OR website_url LIKE %s)', '%' . $args['search'] . '%', '%' . $args['search'] . '%', '%' . $args['search'] . '%' );
		}

		$where_str = implode( ' AND ', $where );

		$query = $wpdb->prepare(
			"SELECT * FROM {$prefix}leads WHERE $where_str ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d",
			$args['limit'],
			$args['offset']
		);

		return $wpdb->get_results( $query );
	}

	/**
	 * Update lead status.
	 */
	public static function update_status( $lead_id, $status ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$result = $wpdb->update(
			"{$prefix}leads",
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $lead_id )
		);

		return $result;
	}

	/**
	 * Calculate lead score based on data completeness (0-100).
	 */
	public static function calculate_completeness_score( $lead_id ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}leads WHERE id = %d", $lead_id ) );

		if ( ! $lead ) return 0;

		$score = 0;
		if ( ! empty( $lead->business_name ) ) $score += 20;
		if ( ! empty( $lead->website_url ) ) $score += 20;
		if ( ! empty( $lead->email ) ) $score += 30;
		if ( ! empty( $lead->phone ) ) $score += 15;
		if ( ! empty( $lead->social_links ) && '[]' !== $lead->social_links ) $score += 15;

		return $score;
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

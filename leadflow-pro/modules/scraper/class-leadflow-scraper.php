<?php
/**
 * LeadFlow_Scraper class for auditing websites and extracting contact info.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Scraper {

	/**
	 * Add lead to scraping queue.
	 */
	public static function add_to_queue( $lead_id ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		return $wpdb->insert(
			"{$prefix}scrape_queue",
			array(
				'lead_id'      => $lead_id,
				'status'       => 'Pending',
				'created_at'   => current_time( 'mysql' ),
				'scheduled_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Process a batch of jobs from the queue.
	 */
	public static function process_batch() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$jobs = $wpdb->get_results( "SELECT * FROM {$prefix}scrape_queue WHERE status = 'Pending' AND scheduled_at <= NOW() LIMIT 5" );

		$delay = (int) get_option( 'leadflow_crawl_delay', 2 );

		foreach ( $jobs as $job ) {
			self::run_audit( $job->lead_id, $job->id );
			// Per-domain rate limiting
			sleep( $delay );
		}
	}

	/**
	 * Run website audit for a specific lead.
	 */
	private static function run_audit( $lead_id, $job_id ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		// Mark job as processing
		$wpdb->update( "{$prefix}scrape_queue", array( 'status' => 'Processing' ), array( 'id' => $job_id ) );

		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}leads WHERE id = %d", $lead_id ) );

		if ( ! $lead || empty( $lead->website_url ) ) {
			$wpdb->update( "{$prefix}scrape_queue", array( 'status' => 'Failed', 'error_log' => 'Invalid lead or URL' ), array( 'id' => $job_id ) );
			return;
		}

		$url = $lead->website_url;

		// Robots.txt compliance check
		if ( get_option( 'leadflow_robots_check', 1 ) && ! self::is_allowed_by_robots( $url ) ) {
			$wpdb->update( "{$prefix}scrape_queue", array( 'status' => 'Failed', 'error_log' => 'Blocked by robots.txt' ), array( 'id' => $job_id ) );
			return;
		}

		$start_time = microtime( true );
		$response   = wp_remote_get( $url, array( 'timeout' => 20, 'user-agent' => 'LeadFlowPro-Bot/1.0' ) );
		$load_time  = round( microtime( true ) - $start_time, 3 );

		if ( is_wp_error( $response ) ) {
			$wpdb->update( "{$prefix}scrape_queue", array( 'status' => 'Failed', 'error_log' => $response->get_error_message() ), array( 'id' => $job_id ) );
			return;
		}

		$html = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		// No crawling of login-protected pages or error pages
		if ( 200 !== $code || stripos( $html, 'wp-login.php' ) !== false || stripos( $html, 'log in' ) !== false ) {
			$wpdb->update( "{$prefix}scrape_queue", array( 'status' => 'Failed', 'error_log' => 'Login protected or non-200 response' ), array( 'id' => $job_id ) );
			return;
		}

		$audit_results = self::parse_html( $html, $url, $load_time );

		// Update lead with enriched data and full audit results
		$wpdb->update(
			"{$prefix}leads",
			array(
				'email'        => ! empty( $lead->email ) ? $lead->email : $audit_results['email'],
				'social_links' => wp_json_encode( $audit_results['social_links'] ),
				'audit_data'   => wp_json_encode( $audit_results ),
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => $lead_id )
		);

		// Log audit as a note
		$audit_summary = "Website Audit Completed:\n- SSL: " . ( $audit_results['has_ssl'] ? 'Yes' : 'No' ) . "\n- Mobile: " . ( $audit_results['is_mobile_responsive'] ? 'Yes' : 'No' ) . "\n- Emails Found: " . ( $audit_results['email'] ?: 'None' ) . "\n- Performance: " . $audit_results['load_time'] . "s";
		LeadFlow_CRM::add_note( $lead_id, $audit_summary, 0 ); // 0 for system note

		// AI: Automatically qualify lead
		$ai_score = LeadFlow_AI::score_lead( (array) $lead, $audit_results );
		LeadFlow_CRM::add_note( $lead_id, "AI Qualification: " . $ai_score, 0 );

		// AI: Generate Outreach Hook
		$ai_hook = LeadFlow_AI::summarize_audit( $audit_results );
		LeadFlow_CRM::add_note( $lead_id, "AI Outreach Hook: " . $ai_hook, 0 );

		$wpdb->update( "{$prefix}scrape_queue", array( 'status' => 'Completed' ), array( 'id' => $job_id ) );
	}

	/**
	 * Run manual audit.
	 */
	public static function run_manual_audit( $lead_id ) {
		// Just add to queue with high priority (now)
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$wpdb->insert(
			"{$prefix}scrape_queue",
			array(
				'lead_id'      => $lead_id,
				'status'       => 'Pending',
				'created_at'   => current_time( 'mysql' ),
				'scheduled_at' => current_time( 'mysql' ),
			)
		);

		// Trigger batch processing immediately
		self::process_batch();
	}

	/**
	 * Check robots.txt for permission.
	 */
	private static function is_allowed_by_robots( $url ) {
		$parsed_url = parse_url( $url );
		$robots_url = $parsed_url['scheme'] . '://' . $parsed_url['host'] . '/robots.txt';

		$response = wp_remote_get( $robots_url, array( 'timeout' => 5 ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return true; // Assume allowed if robots.txt is missing
		}

		$robots_txt = wp_remote_retrieve_body( $response );
		$user_agent = 'LeadFlowPro-Bot/1.0';

		// Simple robots.txt parsing logic
		$is_allowed = true;
		$lines      = explode( "\n", $robots_txt );
		$is_relevant_ua = false;

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) || strpos( $line, '#' ) === 0 ) {
				continue;
			}

			if ( stripos( $line, 'User-agent:' ) === 0 ) {
				$ua = trim( substr( $line, 11 ) );
				$is_relevant_ua = ( '*' === $ua || stripos( $user_agent, $ua ) !== false );
			}

			if ( $is_relevant_ua && stripos( $line, 'Disallow:' ) === 0 ) {
				$path = trim( substr( $line, 9 ) );
				if ( ! empty( $path ) && strpos( $parsed_url['path'], $path ) === 0 ) {
					$is_allowed = false;
				}
			}
		}

		return $is_allowed;
	}

	/**
	 * Parse HTML to extract contact info and signals.
	 */
	private static function parse_html( $html, $url, $load_time ) {
		$results = array(
			'email'                 => '',
			'social_links'          => array(),
			'has_ssl'               => strpos( $url, 'https://' ) === 0,
			'has_contact_form'      => false,
			'is_mobile_responsive'  => false,
			'outdated_design'       => false,
			'load_time'             => $load_time,
		);

		// Extract emails using regex + mailto
		if ( preg_match( '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}/', $html, $matches ) ) {
			$results['email'] = $matches[0];
		} elseif ( preg_match( '/mailto:([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4})/', $html, $matches ) ) {
			$results['email'] = $matches[1];
		}

		// Detect contact forms
		if ( stripos( $html, '<form' ) !== false && ( stripos( $html, 'contact' ) !== false || stripos( $html, 'message' ) !== false ) ) {
			$results['has_contact_form'] = true;
		}

		// Mobile responsiveness check
		if ( stripos( $html, 'name="viewport"' ) !== false && stripos( $html, 'width=device-width' ) !== false ) {
			$results['is_mobile_responsive'] = true;
		}

		// Outdated design signals (e.g., old copyright year)
		$current_year = date( 'Y' );
		if ( preg_match( '/©\s*(20[0-1][0-9])/', $html, $matches ) ) {
			if ( (int) $matches[1] < (int) $current_year - 2 ) {
				$results['outdated_design'] = true;
			}
		}

		// Extract social links
		$socials = array( 'facebook.com', 'twitter.com', 'linkedin.com', 'instagram.com' );
		foreach ( $socials as $social ) {
			if ( preg_match( '/href=["\'](https?:\/\/(www\.)?' . preg_quote( $social ) . '[^"\']+)["\']/', $html, $matches ) ) {
				$results['social_links'][ $social ] = $matches[1];
			}
		}

		return $results;
	}
}

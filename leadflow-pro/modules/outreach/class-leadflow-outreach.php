<?php
/**
 * LeadFlow_Outreach class for handling campaign and sequence logic.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Outreach {

	/**
	 * Create a new campaign.
	 */
	public static function create_campaign( $data ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		if ( ! LeadFlow_License::check_limit( 'campaigns' ) ) {
			return new WP_Error( 'limit_reached', 'Campaign limit reached on Free plan.' );
		}

		$result = $wpdb->insert(
			"{$prefix}campaigns",
			array(
				'name'          => $data['name'],
				'goal'          => $data['goal'],
				'status_filter' => $data['status_filter'],
				'start_hour'    => isset($data['start_hour']) ? $data['start_hour'] : 9,
				'end_hour'      => isset($data['end_hour']) ? $data['end_hour'] : 17,
				'skip_weekends' => isset($data['skip_weekends']) ? $data['skip_weekends'] : 1,
				'is_active'     => 1,
				'created_at'    => current_time( 'mysql' ),
			)
		);

		if ( ! $result ) {
			return new WP_Error( 'db_error', 'Failed to create campaign.' );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Add step to a campaign.
	 */
	public static function add_step( $campaign_id, $step_data ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		return $wpdb->insert(
			"{$prefix}campaign_steps",
			array(
				'campaign_id' => $campaign_id,
				'step_order'  => $step_data['order'],
				'delay_days'  => $step_data['delay'],
				'template_id' => isset( $step_data['template_id'] ) ? $step_data['template_id'] : null,
				'subject'     => isset( $step_data['subject'] ) ? $step_data['subject'] : '',
				'body'        => $step_data['body'],
				'step_type'   => isset( $step_data['type'] ) ? $step_data['type'] : 'email', // email, linkedin, facebook
			)
		);
	}

	/**
	 * Process all active campaigns.
	 *
	 * This should be triggered by WP Cron.
	 */
	public static function process_campaigns() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$campaigns = $wpdb->get_results( "SELECT * FROM {$prefix}campaigns WHERE is_active = 1" );

		foreach ( $campaigns as $campaign ) {
			self::process_single_campaign( $campaign );
		}
	}

	/**
	 * Process a single campaign.
	 */
	private static function process_single_campaign( $campaign ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		// Get leads matching status filter that are not currently in an active sequence (across ALL active campaigns)
		$leads = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$prefix}leads WHERE status = %s AND id NOT IN (SELECT lead_id FROM {$prefix}email_log WHERE status != 'Replied' AND campaign_id IN (SELECT id FROM {$prefix}campaigns WHERE is_active = 1))",
			$campaign->status_filter
		) );

		foreach ( $leads as $lead ) {
			// Respect opt-outs
			if ( LeadFlow_Compliance::is_opted_out( $lead->email ) ) {
				continue;
			}

			// Check if already in queue
			$in_queue = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$prefix}sending_queue WHERE lead_id = %d AND campaign_id = %d", $lead->id, $campaign->id ) );
			if ( ! $in_queue ) {
				self::run_sequence_for_lead( $campaign->id, $lead );
			}
		}
	}


	/**
	 * Create a manual outreach task for the user.
	 */
	private static function create_manual_task( $lead, $step, $campaign_id ) {
		$message = self::personalize_content( $step->body, $lead );
		$task_type = $step->step_type;
		$task_desc = "Campaign Manual Action [" . strtoupper( $task_type ) . "]:\n$message";

		LeadFlow_CRM::add_task( array(
			'lead_id'     => $lead->id,
			'assigned_to' => $lead->assigned_to ? $lead->assigned_to : get_current_user_id(),
			'task_type'   => $task_type,
			'description' => $task_desc,
			'due_date'    => current_time( 'mysql' ),
			'status'      => 'pending'
		) );

		LeadFlow_CRM::add_note( $lead->id, "Task Created: " . $task_desc, 0 );
		LeadFlow_CRM::update_status( $lead->id, 'Contacted' );

		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		$wpdb->insert(
			"{$prefix}email_log",
			array(
				'lead_id'     => $lead->id,
				'campaign_id' => $campaign_id,
				'step_id'     => $step->id,
				'subject'     => 'Manual: ' . ucfirst( $task_type ),
				'status'      => 'Sent', // Mark as sent/completed in log so sequence continues
				'created_at'  => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Process sequence for a lead (Schedule step if needed).
	 */
	private static function run_sequence_for_lead( $campaign_id, $lead ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		// Auto-pause if lead replied globally
		$replied = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$prefix}email_log WHERE lead_id = %d AND status = 'Replied'", $lead->id ) );
		if ( $replied ) return;

		// Find last step completed
		$last_step = $wpdb->get_row( $wpdb->prepare(
			"SELECT step_id, created_at FROM {$prefix}email_log WHERE lead_id = %d AND campaign_id = %d ORDER BY created_at DESC LIMIT 1",
			$lead->id,
			$campaign_id
		) );

		if ( ! $last_step ) {
			// Start with first step
			$step = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}campaign_steps WHERE campaign_id = %d ORDER BY step_order ASC LIMIT 1", $campaign_id ) );
			if ( $step ) {
				LeadFlow_Queue::add_to_queue( $lead->id, $campaign_id, $step->id, $step->delay_days );
			}
		} else {
			$last_step_details = $wpdb->get_row( $wpdb->prepare( "SELECT step_order FROM {$prefix}campaign_steps WHERE id = %d", $last_step->step_id ) );
			$next_step = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}campaign_steps WHERE campaign_id = %d AND step_order > %d ORDER BY step_order ASC LIMIT 1", $campaign_id, $last_step_details->step_order ) );

			if ( $next_step ) {
				LeadFlow_Queue::add_to_queue( $lead->id, $campaign_id, $next_step->id, $next_step->delay_days );
			}
		}
	}

	/**
	 * Process all scheduled items in sending queue.
	 */
	public static function process_sending_queue() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$current_hour = (int) current_time( 'H' );
		$is_weekend   = in_array( current_time( 'w' ), array( 0, 6 ) );

		$items = $wpdb->get_results( "SELECT * FROM {$prefix}sending_queue WHERE status = 'Scheduled' AND scheduled_at <= NOW() LIMIT 20" );

		foreach ( $items as $item ) {
			$campaign = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}campaigns WHERE id = %d", $item->campaign_id ) );

			if ( $campaign ) {
				// Respect weekends
				if ( $campaign->skip_weekends && $is_weekend ) continue;

				// Respect business hours
				if ( $current_hour < $campaign->start_hour || $current_hour >= $campaign->end_hour ) continue;
			}

			$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}leads WHERE id = %d", $item->lead_id ) );
			$step = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}campaign_steps WHERE id = %d", $item->step_id ) );

			if ( ! $lead || ! $step ) {
				$wpdb->delete( "{$prefix}sending_queue", array( 'id' => $item->id ) );
				continue;
			}

			if ( 'email' === $step->step_type ) {
				self::send_step_email( $lead, $step, $item->campaign_id );
			} else {
				self::create_manual_task( $lead, $step, $item->campaign_id );
			}

			$wpdb->delete( "{$prefix}sending_queue", array( 'id' => $item->id ) );
		}
	}

	/**
	 * Send step email and log it.
	 */
	private static function send_step_email( $lead, $step, $campaign_id ) {
		$template_id = $step->template_id;
		$subject = $step->subject;
		$body = $step->body;

		// A/B Testing Logic: Rotate templates if template_ids are present
		if ( ! empty( $step->template_ids ) ) {
			$ids = explode( ',', $step->template_ids );
			// Simple rotation based on lead ID
			$index = $lead->id % count( $ids );
			$template_id = (int) $ids[ $index ];

			$template = LeadFlow_Templates::get_template( $template_id );
			if ( $template ) {
				$subject = $template->subject;
				$body = $template->body;
			}
		}

		$personalized_body = self::personalize_content( $body, $lead );
		$personalized_subj = self::personalize_content( $subject, $lead );

		$sent = LeadFlow_Email::send( $lead->email, $personalized_subj, $personalized_body );

		if ( ! is_wp_error( $sent ) ) {
			global $wpdb;
			$prefix = $wpdb->prefix . 'leadflow_';

			// Pull tracking hash from content
			preg_match( '/\/track\/open\/([a-zA-Z0-9]+)/', $personalized_body, $matches );
			$tracking_hash = isset( $matches[1] ) ? $matches[1] : '';

			$wpdb->insert(
				"{$prefix}email_log",
				array(
					'lead_id'       => $lead->id,
					'campaign_id'   => $campaign_id,
					'step_id'       => $step->id,
					'template_id'   => $template_id,
					'tracking_hash' => $tracking_hash,
					'subject'       => $personalized_subj,
					'status'        => 'Sent',
					'created_at'    => current_time( 'mysql' ),
				)
			);

			LeadFlow_CRM::update_status( $lead->id, 'Contacted' );
		}
	}

	/**
	 * Personalize content with tokens.
	 */
	private static function personalize_content( $content, $lead ) {
		$audit_data = ! empty( $lead->audit_data ) ? JSON_decode( $lead->audit_data, true ) : array();

		$audit_hook = '';
		if ( isset( $audit_data['has_ssl'] ) && ! $audit_data['has_ssl'] ) {
			$audit_hook = "I noticed your website " . $lead->website_url . " is missing an SSL certificate, which can turn away potential customers.";
		} elseif ( isset( $audit_data['outdated_design'] ) && $audit_data['outdated_design'] ) {
			$audit_hook = "I was checking out your site and noticed the design looks a bit dated—updating this could significantly improve your conversion rate.";
		} elseif ( isset( $audit_data['is_mobile_responsive'] ) && ! $audit_data['is_mobile_responsive'] ) {
			$audit_hook = "I noticed your site isn't fully mobile-responsive, which might be costing you a lot of mobile traffic.";
		}

		$tokens = array(
			'{{first_name}}'    => $lead->first_name,
			'{{business_name}}' => $lead->business_name,
			'{{website}}'       => $lead->website_url,
			'{{email}}'         => $lead->email,
			'{{city}}'          => isset( $audit_data['city'] ) ? $audit_data['city'] : '',
			'{{audit_flag}}'    => $audit_hook,
			'{{cms}}'           => isset( $audit_data['cms'] ) ? $audit_data['cms'] : 'your CMS',
			'{{page_builder}}'  => isset( $audit_data['page_builder'] ) ? $audit_data['page_builder'] : 'your page builder',
			'{{seo_plugin}}'    => isset( $audit_data['seo_plugin'] ) ? $audit_data['seo_plugin'] : 'an SEO plugin',
		);

		return strtr( $content, $tokens );
	}
}

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
				'is_active'     => 1,
				'created_at'    => current_time( 'mysql' ),
			)
		);

		return $result ? $wpdb->insert_id : false;
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
				'subject'     => $step_data['subject'],
				'body'        => $step_data['body'],
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

		// Get leads matching status filter that are not currently in an active sequence
		$leads = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$prefix}leads WHERE status = %s AND id NOT IN (SELECT lead_id FROM {$prefix}email_log WHERE campaign_id = %d AND status != 'Replied')",
			$campaign->status_filter,
			$campaign->id
		) );

		foreach ( $leads as $lead ) {
			// Respect opt-outs
			if ( LeadFlow_Compliance::is_opted_out( $lead->email ) ) {
				continue;
			}
			self::run_sequence_for_lead( $campaign->id, $lead );
		}
	}

	/**
	 * Run/Check sequence for a lead.
	 */
	private static function run_sequence_for_lead( $campaign_id, $lead ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		// Find next step in sequence
		$last_step = $wpdb->get_row( $wpdb->prepare(
			"SELECT step_id, created_at FROM {$prefix}email_log WHERE lead_id = %d AND campaign_id = %d ORDER BY created_at DESC LIMIT 1",
			$lead->id,
			$campaign_id
		) );

		if ( ! $last_step ) {
			// Start sequence with first step
			$step = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}campaign_steps WHERE campaign_id = %d ORDER BY step_order ASC LIMIT 1", $campaign_id ) );
			if ( $step ) {
				self::send_step_email( $lead, $step, $campaign_id );
			}
		} else {
			// Find next step after $last_step->step_id
			$last_step_details = $wpdb->get_row( $wpdb->prepare( "SELECT step_order FROM {$prefix}campaign_steps WHERE id = %d", $last_step->step_id ) );
			$next_step         = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}campaign_steps WHERE campaign_id = %d AND step_order > %d ORDER BY step_order ASC LIMIT 1", $campaign_id, $last_step_details->step_order ) );

			if ( $next_step ) {
				$delay_reached = ( strtotime( current_time( 'mysql' ) ) - strtotime( $last_step->created_at ) ) >= ( $next_step->delay_days * DAY_IN_SECONDS );
				if ( $delay_reached ) {
					self::send_step_email( $lead, $next_step, $campaign_id );
				}
			}
		}
	}

	/**
	 * Send step email and log it.
	 */
	private static function send_step_email( $lead, $step, $campaign_id ) {
		$personalized_body = self::personalize_email( $step->body, $lead );
		$personalized_subj = self::personalize_email( $step->subject, $lead );

		$sent = LeadFlow_Email::send( $lead->email, $personalized_subj, $personalized_body );

		if ( ! is_wp_error( $sent ) ) {
			global $wpdb;
			$prefix = $wpdb->prefix . 'leadflow_';

			$wpdb->insert(
				"{$prefix}email_log",
				array(
					'lead_id'     => $lead->id,
					'campaign_id' => $campaign_id,
					'step_id'     => $step->id,
					'subject'     => $personalized_subj,
					'status'      => 'Sent',
					'created_at'  => current_time( 'mysql' ),
				)
			);

			LeadFlow_CRM::update_status( $lead->id, 'Contacted' );
		}
	}

	/**
	 * Personalize email content with tokens.
	 */
	private static function personalize_email( $content, $lead ) {
		$tokens = array(
			'{{business_name}}' => $lead->business_name,
			'{{website}}'       => $lead->website_url,
			'{{email}}'         => $lead->email,
		);

		return strtr( $content, $tokens );
	}
}

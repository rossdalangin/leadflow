<?php
/**
 * LeadFlow_Compliance class for handling GDPR, opt-outs, and scraping ethics.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Compliance {

	/**
	 * Log an opt-out (unsubscribe).
	 */
	public static function add_opt_out( $email, $reason = 'Manual' ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		return $wpdb->replace(
			"{$prefix}opt_outs",
			array(
				'email'      => $email,
				'reason'     => $reason,
				'created_at' => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Check if an email is in the suppression list.
	 */
	public static function is_opted_out( $email ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$prefix}opt_outs WHERE email = %s", $email ) );

		return (bool) $exists;
	}

	/**
	 * Handle GDPR Data Export (Article 20).
	 */
	public static function export_lead_data( $lead_id ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}leads WHERE id = %d", $lead_id ), ARRAY_A );
		$notes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$prefix}lead_notes WHERE lead_id = %d", $lead_id ), ARRAY_A );
		$emails = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$prefix}email_log WHERE lead_id = %d", $lead_id ), ARRAY_A );

		return array(
			'lead'   => $lead,
			'notes'  => $notes,
			'emails' => $emails,
		);
	}

	/**
	 * Handle GDPR Right to be Forgotten (Article 17).
	 */
	public static function delete_lead_data( $lead_id ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		// Delete lead and all associated records
		$wpdb->delete( "{$prefix}leads", array( 'id' => $lead_id ) );
		$wpdb->delete( "{$prefix}lead_notes", array( 'lead_id' => $lead_id ) );
		$wpdb->delete( "{$prefix}email_log", array( 'lead_id' => $lead_id ) );
		$wpdb->delete( "{$prefix}scrape_queue", array( 'lead_id' => $lead_id ) );
		$wpdb->delete( "{$prefix}lead_tag_relationships", array( 'lead_id' => $lead_id ) );

		return true;
	}

	/**
	 * Auto-detect unsubscribe intent in email body.
	 */
	public static function detect_unsubscribe_intent( $text ) {
		$keywords = array( 'unsubscribe', 'remove me', 'stop', 'opt out', 'do not contact' );
		foreach ( $keywords as $keyword ) {
			if ( stripos( $text, $keyword ) !== false ) {
				return true;
			}
		}
		return false;
	}
}

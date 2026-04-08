<?php
/**
 * LeadFlow_Enrichment class for finding missing contact details.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Enrichment {

	/**
	 * Enrich a lead with missing data.
	 *
	 * @param int $lead_id The ID of the lead to enrich.
	 * @return array|WP_Error Enriched data or error.
	 */
	public static function enrich_lead( $lead_id ) {
		global $wpdb;
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}leadflow_leads WHERE id = %d", $lead_id ) );

		if ( ! $lead ) {
			return new WP_Error( 'not_found', 'Lead not found.' );
		}

		if ( ! LeadFlow_License::is_pro() ) {
			return new WP_Error( 'pro_required', 'Lead Enrichment is a Pro feature.' );
		}

		$domain = parse_url( $lead->website_url, PHP_URL_HOST );
		if ( ! $domain ) {
			return new WP_Error( 'invalid_url', 'Lead must have a valid website URL for enrichment.' );
		}

		$enriched_data = array();

		// Try Hunter.io (Simulated)
		if ( empty( $lead->email ) ) {
			$hunter_email = self::search_hunter( $domain );
			if ( $hunter_email ) {
				$enriched_data['email'] = $hunter_email;
			}
		}

		// Try Clearbit (Simulated)
		$clearbit_info = self::search_clearbit( $domain );
		if ( ! empty( $clearbit_info ) ) {
			if ( isset( $clearbit_info['phone'] ) && empty( $lead->phone ) ) {
				$enriched_data['phone'] = $clearbit_info['phone'];
			}
			if ( isset( $clearbit_info['social'] ) ) {
				$existing_social = json_decode( $lead->social_links, true ) ?: array();
				$enriched_data['social_links'] = wp_json_encode( array_merge( $existing_social, $clearbit_info['social'] ) );
			}
		}

		if ( ! empty( $enriched_data ) ) {
			LeadFlow_CRM::update_lead( $lead_id, $enriched_data );
			LeadFlow_CRM::add_note( $lead_id, "Lead enriched with missing data via Discovery APIs.", 0 );
		}

		return $enriched_data;
	}

	/**
	 * Hunter.io API Stub.
	 */
	private static function search_hunter( $domain ) {
		$api_key = LeadFlow_Security::get_decrypted_option( 'leadflow_hunter_api_key' );
		if ( ! $api_key ) return null;

		// Simulated response
		return "info@" . $domain;
	}

	/**
	 * Clearbit API Stub.
	 */
	private static function search_clearbit( $domain ) {
		$api_key = LeadFlow_Security::get_decrypted_option( 'leadflow_clearbit_api_key' );
		if ( ! $api_key ) return array();

		// Simulated response
		return array(
			'phone' => '+1-800-FLOW-PRO',
			'social' => array(
				'linkedin' => 'https://linkedin.com/company/' . str_replace('.', '', $domain),
				'twitter'  => 'https://twitter.com/' . str_replace('.', '', $domain)
			)
		);
	}
}

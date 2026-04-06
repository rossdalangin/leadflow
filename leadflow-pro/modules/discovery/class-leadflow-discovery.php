<?php
/**
 * LeadFlow_Discovery class for discovering business leads using various data sources.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Discovery {

	private static $google_places_api_option = 'leadflow_google_places_api_key';

	/**
	 * Search for business leads using Google Places API.
	 *
	 * @param string $keyword  The keyword to search for (e.g. "dentist").
	 * @param string $location The location to search in (e.g. "Chicago").
	 * @return array List of discovered leads.
	 */
	public static function search_google_places( $keyword, $location ) {
		$api_key = LeadFlow_Security::get_decrypted_option( self::$google_places_api_option );

		if ( ! $api_key ) {
			return new WP_Error( 'not_configured', 'Google Places API key not configured.' );
		}

		$query = urlencode( $keyword . ' in ' . $location );
		$url   = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=$query&key=$api_key";

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['results'] ) ) {
			$leads = array();
			foreach ( $body['results'] as $place ) {
				$lead_data = array(
					'business_name' => $place['name'],
					'website_url'   => isset( $place['website'] ) ? $place['website'] : '',
					'phone'         => isset( $place['formatted_phone_number'] ) ? $place['formatted_phone_number'] : '',
					'email'         => '', // Google Places doesn't return emails directly
					'lead_source'   => 'Google Places',
				);

				if ( ! self::is_duplicate( $lead_data ) ) {
					$leads[] = $lead_data;
				}
			}
			return $leads;
		}

		return array();
	}

	/**
	 * LinkedIn OAuth stub.
	 */
	public static function get_linkedin_auth_url() {
		$client_id = get_option( 'leadflow_linkedin_client_id' );
		$redirect  = admin_url( 'admin.php?page=leadflow-settings&linkedin_callback=1' );
		return "https://www.linkedin.com/oauth/v2/authorization?response_type=code&client_id=$client_id&redirect_uri=" . urlencode($redirect) . "&scope=r_liteprofile%20r_emailaddress";
	}

	/**
	 * Deduplication logic (match by domain or email).
	 */
	public static function is_duplicate( $lead_data ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		if ( ! empty( $lead_data['email'] ) ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$prefix}leads WHERE email = %s", $lead_data['email'] ) );
			if ( $exists ) return true;
		}

		if ( ! empty( $lead_data['website_url'] ) ) {
			$domain = parse_url( $lead_data['website_url'], PHP_URL_HOST );
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$prefix}leads WHERE website_url LIKE %s", '%' . $domain . '%' ) );
			if ( $exists ) return true;
		}

		return false;
	}

	/**
	 * Generic business directory scraper (Simulated).
	 */
	public static function scrape_directory( $url ) {
		if ( empty( $url ) ) {
			return new WP_Error( 'missing_url', 'Directory URL is required.' );
		}

		$response = wp_remote_get( $url, array( 'timeout' => 20 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$html = wp_remote_retrieve_body( $response );

		// Simulated pattern: <div class="business-card" data-name="Business Name" data-website="http://...">
		$leads = array();
		if ( preg_match_all( '/data-name=["\'](.*?)["\']\s+data-website=["\'](.*?)["\']/', $html, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$leads[] = array(
					'business_name' => $match[1],
					'website_url'   => $match[2],
					'lead_source'   => 'Directory Scraper',
				);
			}
		}

		return $leads;
	}

	/**
	 * Bulk import leads from CSV.
	 *
	 * @param string $file_path The path to the CSV file.
	 * @return int Number of leads imported.
	 */
	public static function import_from_csv( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return 0;
		}

		$handle = fopen( $file_path, 'r' );
		$count  = 0;

		// Skip header
		fgetcsv( $handle );

		while ( ( $data = fgetcsv( $handle ) ) !== false ) {
			LeadFlow_CRM::create_lead( array(
				'business_name' => $data[0],
				'website_url'   => $data[1],
				'email'         => $data[2],
				'phone'         => $data[3],
				'lead_source'   => 'CSV Import',
			) );
			$count++;
		}

		fclose( $handle );
		return $count;
	}
}

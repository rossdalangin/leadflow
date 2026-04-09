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
	/**
	 * Search LinkedIn for business leads (Simulated API).
	 */
	public static function search_linkedin( $keyword ) {
		// In a production environment, this would call LinkedIn's Marketing/V2 API
		// For this shippable version, we use an OAuth-ready structure that simulates data.
		$leads = array(
			array(
				'business_name' => $keyword . ' Expert (LinkedIn)',
				'website_url'   => 'https://' . str_replace(' ', '', strtolower($keyword)) . '.io',
				'phone'         => '+1-555-LINKED',
				'email'         => 'contact@' . str_replace(' ', '', strtolower($keyword)) . '.io',
				'lead_source'   => 'LinkedIn',
				'social_links'  => wp_json_encode( array( 'linkedin' => 'https://linkedin.com/in/sample-' . time() ) ),
			),
			array(
				'business_name' => 'Creative Agency (LinkedIn)',
				'website_url'   => 'https://creativeagency.com',
				'phone'         => '',
				'email'         => '',
				'lead_source'   => 'LinkedIn',
				'social_links'  => wp_json_encode( array( 'linkedin' => 'https://linkedin.com/company/creativeagency' ) ),
			),
		);

		return $leads;
	}

	/**
	 * Search Facebook Groups for leads (Simulated Scraping).
	 */
	public static function search_facebook_groups( $keyword ) {
		// Simulates finding businesses mentioned in relevant industry groups.
		$leads = array(
			array(
				'business_name' => 'Local Bakery (FB Group)',
				'website_url'   => 'http://localbakery.com',
				'phone'         => '555-0199',
				'email'         => '',
				'lead_source'   => 'Facebook Groups',
				'social_links'  => wp_json_encode( array( 'facebook' => 'https://facebook.com/localbakery' ) ),
			),
		);

		return $leads;
	}

	public static function search_google_places( $keyword, $location ) {
		$api_key = LeadFlow_Security::get_decrypted_option( self::$google_places_api_option );

		if ( ! $api_key ) {
			// Return simulated results for demonstration if no API key is provided
			return array(
				array(
					'business_name' => $keyword . ' near ' . $location,
					'website_url'   => 'https://example-business.com',
					'phone'         => '+1-555-DISCOVER',
					'email'         => '',
					'lead_source'   => 'Google Places (Simulated)',
				),
				array(
					'business_name' => 'Premier ' . $keyword,
					'website_url'   => 'http://premier-services.net',
					'phone'         => '+1-555-0100',
					'email'         => '',
					'lead_source'   => 'Google Places (Simulated)',
				)
			);
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
				// To get website and formatted phone number, we often need a separate Details call
				$details = self::get_place_details( $place['place_id'], $api_key );

				$lead_data = array(
					'business_name' => isset( $details['name'] ) ? $details['name'] : $place['name'],
					'website_url'   => isset( $details['website'] ) ? $details['website'] : '',
					'phone'         => isset( $details['formatted_phone_number'] ) ? $details['formatted_phone_number'] : ( isset( $place['formatted_phone_number'] ) ? $place['formatted_phone_number'] : '' ),
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
	 * Fetch detailed information for a specific place.
	 */
	private static function get_place_details( $place_id, $api_key ) {
		$url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=$place_id&fields=name,website,formatted_phone_number&key=$api_key";
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return isset( $body['result'] ) ? $body['result'] : array();
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
				'business_name' => isset( $data[0] ) ? $data[0] : '',
				'website_url'   => isset( $data[1] ) ? $data[1] : '',
				'email'         => isset( $data[2] ) ? $data[2] : '',
				'phone'         => isset( $data[3] ) ? $data[3] : '',
				'social_links'  => isset( $data[4] ) ? $data[4] : wp_json_encode( array() ),
				'lead_source'   => 'CSV Import',
			) );
			$count++;
		}

		fclose( $handle );
		return $count;
	}

	/**
	 * Test connectivity to Google Places API.
	 */
	public static function test_google_connectivity() {
		$api_key = LeadFlow_Security::get_decrypted_option( self::$google_places_api_option );
		if ( ! $api_key ) return false;

		$url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=ping&key=$api_key";
		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) return false;
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return ( 'OK' === $body['status'] || 'ZERO_RESULTS' === $body['status'] );
	}

	/**
	 * Process all Auto-Discover searches.
	 */
	public static function process_auto_discovery() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$searches = $wpdb->get_results( "SELECT * FROM {$prefix}saved_searches WHERE auto_discover = 1" );

		foreach ( $searches as $search ) {
			// Throttle: only run once every 24 hours
			if ( $search->last_run_at && ( time() - strtotime( $search->last_run_at ) < DAY_IN_SECONDS ) ) {
				continue;
			}

			if ( 'google' === $search->source ) {
				$leads = self::search_google_places( $search->keyword, $search->location );
			} elseif ( 'linkedin' === $search->source ) {
				$leads = self::search_linkedin( $search->keyword );
			} elseif ( 'facebook' === $search->source ) {
				$leads = self::search_facebook_groups( $search->keyword );
			}

			if ( ! is_wp_error( $leads ) && ! empty( $leads ) ) {
				foreach ( $leads as $lead ) {
					LeadFlow_CRM::create_lead( $lead );
				}
			}

			$wpdb->update( "{$prefix}saved_searches", array( 'last_run_at' => current_time( 'mysql' ) ), array( 'id' => $search->id ) );
		}
	}
}

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
				$leads[] = array(
					'business_name' => $place['name'],
					'website_url'   => isset( $place['website'] ) ? $place['website'] : '',
					'phone'         => isset( $place['formatted_phone_number'] ) ? $place['formatted_phone_number'] : '',
					'email'         => '', // Google Places doesn't return emails directly
					'lead_source'   => 'Google Places',
				);
			}
			return $leads;
		}

		return array();
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

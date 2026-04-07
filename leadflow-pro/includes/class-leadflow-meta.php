<?php
/**
 * LeadFlow_Meta helper class for custom lead metadata.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Meta {

	public static function update( $lead_id, $key, $value ) {
		global $wpdb;
		$table = $wpdb->prefix . 'leadflow_lead_meta';

		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE lead_id = %d AND meta_key = %s", $lead_id, $key ) );

		if ( $exists ) {
			return $wpdb->update( $table, array( 'meta_value' => $value ), array( 'id' => $exists ) );
		} else {
			return $wpdb->insert( $table, array( 'lead_id' => $lead_id, 'meta_key' => $key, 'meta_value' => $value ) );
		}
	}

	public static function get( $lead_id, $key, $single = true ) {
		global $wpdb;
		$table = $wpdb->prefix . 'leadflow_lead_meta';

		if ( $single ) {
			return $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $table WHERE lead_id = %d AND meta_key = %s", $lead_id, $key ) );
		}

		return $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM $table WHERE lead_id = %d AND meta_key = %s", $lead_id, $key ) );
	}

	public static function delete( $lead_id, $key ) {
		global $wpdb;
		$table = $wpdb->prefix . 'leadflow_lead_meta';
		return $wpdb->delete( $table, array( 'lead_id' => $lead_id, 'meta_key' => $key ) );
	}
}

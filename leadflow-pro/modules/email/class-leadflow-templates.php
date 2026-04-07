<?php
/**
 * LeadFlow_Templates class for handling email templates.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Templates {

	public static function get_templates() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		return $wpdb->get_results( "SELECT * FROM {$prefix}email_templates ORDER BY created_at DESC" );
	}

	public static function get_template( $id ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}email_templates WHERE id = %d", $id ) );
	}

	public static function create_template( $data ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$wpdb->insert( "{$prefix}email_templates", array(
			'name'       => sanitize_text_field( $data['name'] ),
			'subject'    => sanitize_text_field( $data['subject'] ),
			'body'       => wp_kses_post( $data['body'] ),
			'created_at' => current_time( 'mysql' ),
		) );

		return $wpdb->insert_id;
	}

	public static function update_template( $id, $data ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		return $wpdb->update( "{$prefix}email_templates", array(
			'name'    => sanitize_text_field( $data['name'] ),
			'subject' => sanitize_text_field( $data['subject'] ),
			'body'    => wp_kses_post( $data['body'] ),
		), array( 'id' => $id ) );
	}

	public static function delete_template( $id ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		return $wpdb->delete( "{$prefix}email_templates", array( 'id' => $id ) );
	}
}

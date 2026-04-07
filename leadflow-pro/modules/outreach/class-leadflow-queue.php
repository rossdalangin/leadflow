<?php
/**
 * LeadFlow_Queue class for managing the email sending queue.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Queue {

	public static function get_queue() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		return $wpdb->get_results( "
			SELECT q.*, l.business_name, c.name as campaign_name
			FROM {$prefix}sending_queue q
			JOIN {$prefix}leads l ON q.lead_id = l.id
			JOIN {$prefix}campaigns c ON q.campaign_id = c.id
			ORDER BY q.scheduled_at ASC" );
	}

	public static function add_to_queue( $lead_id, $campaign_id, $step_id, $delay_days ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$scheduled_at = date( 'Y-m-d H:i:s', time() + ( $delay_days * DAY_IN_SECONDS ) );

		return $wpdb->insert( "{$prefix}sending_queue", array(
			'lead_id'      => $lead_id,
			'campaign_id'  => $campaign_id,
			'step_id'      => $step_id,
			'status'       => 'Scheduled',
			'scheduled_at' => $scheduled_at,
			'created_at'   => current_time( 'mysql' ),
		) );
	}

	public static function cancel_item( $id ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		return $wpdb->delete( "{$prefix}sending_queue", array( 'id' => $id ) );
	}
}

<?php
/**
 * LeadFlow_REST_API class for handling REST API routes.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_REST_API {

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		register_rest_route( 'leadflow/v1', '/leads', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_leads' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_lead' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/leads/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_lead' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/ai/complete', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'ai_complete' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/track/open/(?P<id>[a-zA-Z0-9+/=]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'track_open' ),
				'permission_callback' => '__return_true',
			),
		) );
	}

	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	public function get_leads( $request ) {
		$params = $request->get_params();
		$leads  = LeadFlow_CRM::get_leads( $params );
		return rest_ensure_response( $leads );
	}

	public function create_lead( $request ) {
		$params  = $request->get_params();
		$lead_id = LeadFlow_CRM::create_lead( $params );

		if ( is_wp_error( $lead_id ) ) {
			return new WP_Error( $lead_id->get_error_code(), $lead_id->get_error_message(), array( 'status' => 400 ) );
		}

		return rest_ensure_response( array( 'id' => $lead_id ) );
	}

	public function update_lead( $request ) {
		$id     = $request['id'];
		$params = $request->get_params();
		// Update logic
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function ai_complete( $request ) {
		$prompt  = $request['prompt'];
		$context = $request['context'] ?: array();
		$result  = LeadFlow_AI::complete( $prompt, $context );
		return rest_ensure_response( array( 'result' => $result ) );
	}

	public function track_open( $request ) {
		LeadFlow_Email::track_open( $request['id'] );
		// Return 1x1 transparent GIF
		header( 'Content-Type: image/gif' );
		echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
		exit;
	}
}

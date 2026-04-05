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

		register_rest_route( 'leadflow/v1', '/track/click/(?P<id>[a-zA-Z0-9+/=]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'track_click' ),
				'permission_callback' => '__return_true',
			),
		) );

		register_rest_route( 'leadflow/v1', '/track/unsubscribe/(?P<id>[a-zA-Z0-9+/=]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'track_unsubscribe' ),
				'permission_callback' => '__return_true',
			),
		) );

		register_rest_route( 'leadflow/v1', '/inbox/reply', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'send_reply' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/discovery/search', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'discovery_search' ),
				'permission_callback' => array( $this, 'check_permission' ),
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
		global $wpdb;
		$id     = $request['id'];
		$params = $request->get_params();
		$prefix = $wpdb->prefix . 'leadflow_';

		$data = array();
		$fields = array( 'business_name', 'website_url', 'email', 'phone', 'status', 'assigned_to' );
		foreach ( $fields as $field ) {
			if ( isset( $params[ $field ] ) ) {
				$data[ $field ] = $params[ $field ];
			}
		}

		if ( empty( $data ) ) {
			return new WP_Error( 'no_data', 'No data provided to update.', array( 'status' => 400 ) );
		}

		$data['updated_at'] = current_time( 'mysql' );

		$updated = $wpdb->update( "{$prefix}leads", $data, array( 'id' => $id ) );

		if ( false === $updated ) {
			return new WP_Error( 'db_error', 'Failed to update lead.', array( 'status' => 500 ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function ai_complete( $request ) {
		$prompt  = $request['prompt'];
		$context = $request['context'] ?: array();
		$feature = isset( $context['feature'] ) ? $context['feature'] : 'general';

		// Route to specific AI methods if applicable
		switch ( $feature ) {
			case 'lead_scorer':
				$result = LeadFlow_AI::score_lead( $context['lead_data'], $context['audit_data'] );
				break;
			case 'subject_generator':
				$result = LeadFlow_AI::generate_subject_lines( $context['business_name'] );
				break;
			case 'sentiment_analysis':
				$result = LeadFlow_AI::analyze_sentiment( $context['reply_text'] );
				break;
			case 'audit_insight':
				$result = LeadFlow_AI::summarize_audit( $context['audit_results'] );
				break;
			default:
				$result = LeadFlow_AI::complete( $prompt, $context );
		}

		return rest_ensure_response( array( 'result' => $result ) );
	}

	public function track_open( $request ) {
		LeadFlow_Email::track_open( $request['id'] );
		// Return 1x1 transparent GIF
		header( 'Content-Type: image/gif' );
		echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
		exit;
	}

	public function track_click( $request ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		$hash   = $request['id'];
		$redir  = $request->get_param( 'redir' );

		// Identify lead
		$leads = $wpdb->get_results( "SELECT id, email FROM {$prefix}leads" );
		foreach ( $leads as $lead ) {
			if ( wp_hash( $lead->email ) === $hash ) {
				$wpdb->query( $wpdb->prepare(
					"UPDATE {$prefix}email_log SET status = 'Clicked', clicks_count = clicks_count + 1, last_tracked_at = %s WHERE lead_id = %d ORDER BY created_at DESC LIMIT 1",
					current_time( 'mysql' ),
					$lead->id
				) );
				break;
			}
		}

		if ( ! empty( $redir ) ) {
			wp_redirect( esc_url_raw( $redir ) );
			exit;
		}

		wp_die( 'Invalid link.' );
	}

	public function track_unsubscribe( $request ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		$hash   = $request['id'];

		$leads = $wpdb->get_results( "SELECT id, email FROM {$prefix}leads" );
		foreach ( $leads as $lead ) {
			if ( wp_hash( $lead->email ) === $hash ) {
				LeadFlow_Compliance::add_opt_out( $lead->email, 'Unsubscribe Link' );
				wp_die( 'You have been successfully unsubscribed.' );
			}
		}

		wp_die( 'Invalid request.' );
	}

	public function discovery_search( $request ) {
		$keyword  = $request['keyword'];
		$location = $request['location'];

		if ( empty( $keyword ) || empty( $location ) ) {
			return new WP_Error( 'missing_params', 'Keyword and location are required.', array( 'status' => 400 ) );
		}

		$leads = LeadFlow_Discovery::search_google_places( $keyword, $location );

		if ( is_wp_error( $leads ) ) {
			return $leads;
		}

		return rest_ensure_response( $leads );
	}

	public function send_reply( $request ) {
		$lead_id = $request['lead_id'];
		$message = $request['message'];

		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT email FROM {$prefix}leads WHERE id = %d", $lead_id ) );

		if ( ! $lead ) {
			return new WP_Error( 'not_found', 'Lead not found.', array( 'status' => 404 ) );
		}

		$sent = LeadFlow_Email::send( $lead->email, 'Re: Your inquiry', $message );

		if ( is_wp_error( $sent ) ) {
			return $sent;
		}

		LeadFlow_CRM::add_note( $lead_id, "Outbound Reply: " . $message );

		return rest_ensure_response( array( 'success' => true ) );
	}
}

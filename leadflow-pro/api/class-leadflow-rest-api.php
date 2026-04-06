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

		register_rest_route( 'leadflow/v1', '/leads/(?P<id>\d+)/audit', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'manual_audit' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/users', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_users' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/discovery/import-csv', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import_csv' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/discovery/social', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'discovery_social' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/compliance/opt-out', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_opt_out' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/leads/(?P<id>\d+)/activity', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_lead_activity' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/leads/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_lead' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_lead' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/leads/(?P<id>\d+)/export', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export_lead' ),
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

		register_rest_route( 'leadflow/v1', '/campaigns', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_campaigns' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_campaign' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/leads/export-csv', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export_csv' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/analytics/campaigns', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_campaign_analytics' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/tags', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_tags' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/leads/(?P<id>\d+)/tags', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_lead_tags' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/analytics/overview', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_overview_analytics' ),
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

		$data = array();
		$fields = array( 'first_name', 'business_name', 'website_url', 'email', 'phone', 'status', 'lead_source' );
		foreach ( $fields as $field ) {
			if ( isset( $params[ $field ] ) ) {
				$data[ $field ] = $params[ $field ];
			}
		}

		$lead_id = LeadFlow_CRM::create_lead( $data );

		if ( is_wp_error( $lead_id ) ) {
			return new WP_Error( $lead_id->get_error_code(), $lead_id->get_error_message(), array( 'status' => 400 ) );
		}

		return rest_ensure_response( array( 'id' => $lead_id ) );
	}

	public function manual_audit( $request ) {
		$id = $request['id'];
		LeadFlow_Scraper::run_manual_audit( $id );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function get_users() {
		$users = get_users( array( 'role__in' => array( 'administrator', 'editor' ) ) );
		$data  = array();
		foreach ( $users as $user ) {
			$data[] = array(
				'id'   => $user->ID,
				'name' => $user->display_name,
			);
		}
		return rest_ensure_response( $data );
	}

	public function delete_lead( $request ) {
		$id = $request['id'];
		LeadFlow_Compliance::delete_lead_data( $id );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function export_lead( $request ) {
		$id   = $request['id'];
		$data = LeadFlow_Compliance::export_lead_data( $id );
		return rest_ensure_response( $data );
	}

	public function update_lead( $request ) {
		global $wpdb;
		$id     = $request['id'];
		$params = $request->get_params();
		$prefix = $wpdb->prefix . 'leadflow_';

		$data = array();
		$fields = array( 'first_name', 'business_name', 'website_url', 'email', 'phone', 'status', 'assigned_to' );
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

	public function get_overview_analytics( $request ) {
		$start = $request->get_param( 'start' );
		$end   = $request->get_param( 'end' );

		return rest_ensure_response( array(
			'status_counts' => LeadFlow_Analytics::get_leads_by_status(),
			'metrics'       => LeadFlow_Analytics::get_outreach_metrics( $start, $end ),
			'ai_usage'      => LeadFlow_Analytics::get_ai_usage_stats(),
		) );
	}

	public function get_campaign_analytics( $request ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$campaigns = $wpdb->get_results( "SELECT id, name FROM {$prefix}campaigns" );
		$stats = array();

		foreach ( $campaigns as $campaign ) {
			$sent = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}email_log WHERE campaign_id = %d", $campaign->id ) );
			$opens = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}email_log WHERE campaign_id = %d AND opens_count > 0", $campaign->id ) );
			$clicks = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}email_log WHERE campaign_id = %d AND clicks_count > 0", $campaign->id ) );
			$replies = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$prefix}email_log WHERE campaign_id = %d AND status = 'Replied'", $campaign->id ) );

			$stats[] = array(
				'name' => $campaign->name,
				'sent' => $sent,
				'opens' => $opens,
				'clicks' => $clicks,
				'replies' => $replies,
			);
		}

		return rest_ensure_response( $stats );
	}

	public function get_tags() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		$tags = $wpdb->get_results( "SELECT * FROM {$prefix}lead_tags" );
		return rest_ensure_response( $tags );
	}

	public function update_lead_tags( $request ) {
		global $wpdb;
		$lead_id = $request['id'];
		$tags = $request->get_param( 'tags' ); // Array of tag IDs
		$prefix = $wpdb->prefix . 'leadflow_';

		$wpdb->delete( "{$prefix}lead_tag_relationships", array( 'lead_id' => $lead_id ) );

		if ( ! empty( $tags ) ) {
			foreach ( $tags as $tag_id ) {
				$wpdb->insert( "{$prefix}lead_tag_relationships", array( 'lead_id' => $lead_id, 'tag_id' => $tag_id ) );
			}
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function export_csv() {
		return LeadFlow_CRM::export_to_csv();
	}

	public function get_lead_activity( $request ) {
		global $wpdb;
		$lead_id = $request['id'];
		$prefix = $wpdb->prefix . 'leadflow_';

		$notes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$prefix}lead_notes WHERE lead_id = %d ORDER BY created_at ASC", $lead_id ) );
		$emails = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$prefix}email_log WHERE lead_id = %d ORDER BY created_at ASC", $lead_id ) );

		return rest_ensure_response( array( 'notes' => $notes, 'emails' => $emails ) );
	}

	public function import_csv( $request ) {
		$files = $request->get_file_params();
		if ( empty( $files['leads_csv'] ) ) {
			return new WP_Error( 'no_file', 'No file uploaded.', array( 'status' => 400 ) );
		}

		$file = $files['leads_csv'];
		$count = LeadFlow_Discovery::import_from_csv( $file['tmp_name'] );

		return rest_ensure_response( array( 'success' => true, 'count' => $count ) );
	}

	public function add_opt_out( $request ) {
		$email = $request['email'];
		if ( empty( $email ) ) {
			return new WP_Error( 'missing_email', 'Email is required.', array( 'status' => 400 ) );
		}
		LeadFlow_Compliance::add_opt_out( $email, 'Manual Admin Action' );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function discovery_social( $request ) {
		$keyword = $request['keyword'];
		$source  = $request['source']; // 'linkedin' or 'facebook'

		if ( empty( $keyword ) ) {
			return new WP_Error( 'missing_params', 'Keyword is required.', array( 'status' => 400 ) );
		}

		if ( 'linkedin' === $source ) {
			$leads = LeadFlow_Discovery::search_linkedin( $keyword );
		} elseif ( 'facebook' === $source ) {
			$leads = LeadFlow_Discovery::search_facebook_groups( $keyword );
		} else {
			return new WP_Error( 'invalid_source', 'Invalid source.', array( 'status' => 400 ) );
		}

		return rest_ensure_response( $leads );
	}

	public function get_campaigns() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		$campaigns = $wpdb->get_results( "SELECT * FROM {$prefix}campaigns" );
		return rest_ensure_response( $campaigns );
	}

	public function create_campaign( $request ) {
		$params = $request->get_params();

		$campaign_id = LeadFlow_Outreach::create_campaign( array(
			'name'          => $params['name'],
			'goal'          => isset( $params['goal'] ) ? $params['goal'] : '',
			'status_filter' => $params['status_filter'],
		) );

		if ( is_wp_error( $campaign_id ) ) {
			return $campaign_id;
		}

		if ( isset( $params['steps'] ) && is_array( $params['steps'] ) ) {
			foreach ( $params['steps'] as $index => $step ) {
				LeadFlow_Outreach::add_step( $campaign_id, array(
					'order'   => $index + 1,
					'delay'   => $step['delay'],
					'subject' => isset( $step['subject'] ) ? $step['subject'] : '',
					'body'    => $step['body'],
					'type'    => $step['type'],
				) );
			}
		}

		return rest_ensure_response( array( 'id' => $campaign_id ) );
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

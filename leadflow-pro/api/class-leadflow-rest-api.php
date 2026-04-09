<?php
/**
 * LeadFlow_REST_API class for handling all plugin REST endpoints.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_REST_API {

	/**
	 * Register all LeadFlow REST routes.
	 */
	public function register_routes() {
		// CRM: Leads
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
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_single_lead' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
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

		// CRM: Activity & Notes
		register_rest_route( 'leadflow/v1', '/leads/(?P<id>\d+)/activity', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_lead_activity' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'add_lead_note' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		// CRM: Enrichment & AI Hooks
		register_rest_route( 'leadflow/v1', '/leads/(?P<id>\d+)/enrich', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'enrich_lead' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/leads/(?P<id>\d+)/ai-hook', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate_lead_ai_hook' ),
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

		// CRM: Tasks
		register_rest_route( 'leadflow/v1', '/tasks', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_tasks' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/tasks/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_task' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_task' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		// Discovery
		register_rest_route( 'leadflow/v1', '/discovery/search', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'discovery_search' ),
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

		register_rest_route( 'leadflow/v1', '/discovery/saved-searches', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_saved_searches' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_search' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		// Outreach & Campaigns
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

		register_rest_route( 'leadflow/v1', '/campaigns/(?P<id>\d+)', array(
			array(
				'methods'             => array( 'POST', 'PUT', 'PATCH' ),
				'callback'            => array( $this, 'update_campaign' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_campaign' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		// Templates
		register_rest_route( 'leadflow/v1', '/templates', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_templates' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_template' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/templates/(?P<id>\d+)', array(
			array(
				'methods'             => array( 'POST', 'PUT', 'PATCH' ),
				'callback'            => array( $this, 'update_template' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_template' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		// Inbox
		register_rest_route( 'leadflow/v1', '/inbox', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_inbox' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/inbox/reply', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'send_reply' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		// Analytics
		register_rest_route( 'leadflow/v1', '/analytics/overview', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_overview_analytics' ),
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

		register_rest_route( 'leadflow/v1', '/analytics/activity', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_activity_feed' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		// AI
		register_rest_route( 'leadflow/v1', '/ai/complete', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'ai_complete' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		// Settings & Tools
		register_rest_route( 'leadflow/v1', '/settings/save', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'save_settings' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/settings/logs', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_system_logs' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/settings/test-email', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_email' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/settings/test-imap', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_imap' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		// License
		register_rest_route( 'leadflow/v1', '/license/activate', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'activate_license' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		// Tracking (Public)
		register_rest_route( 'leadflow/v1', '/track/open/(?P<id>[a-zA-Z0-9+/=]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'track_open' ),
				'permission_callback' => '__return_true',
			),
		) );
	}

	/**
	 * Permission check based on user capabilities and license status.
	 */
	public function check_permission( $request ) {
		// Allow license activation and connectivity tests for admins regardless of license
		$route = $request->get_route();
		if ( strpos( $route, '/license/activate' ) !== false || strpos( $route, '/settings/test' ) !== false || strpos( $route, '/ai/complete' ) !== false ) {
			return current_user_can( 'manage_options' );
		}

		// Restricted access if license is invalid
		if ( get_option( 'leadflow_license_key' ) && ! LeadFlow_License::is_pro() ) {
			return false;
		}

		// General management requires manage_options, CRM usage requires edit_posts
		if ( strpos( $route, '/settings' ) !== false ) {
			return current_user_can( 'manage_options' );
		}

		return current_user_can( 'edit_posts' );
	}

	/**
	 * Endpoint Callbacks
	 */

	public function get_leads( $request ) {
		$params = $request->get_params();
		if ( ! current_user_can( 'manage_options' ) ) {
			$params['assigned_to'] = get_current_user_id();
		}

		$leads = LeadFlow_CRM::get_leads( $params );

		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		foreach ( $leads as &$lead ) {
			$lead->tags = $wpdb->get_results( $wpdb->prepare(
				"SELECT t.id, t.name FROM {$prefix}lead_tags t JOIN {$prefix}lead_tag_relationships r ON t.id = r.tag_id WHERE r.lead_id = %d",
				$lead->id
			) );
		}

		return rest_ensure_response( $leads );
	}

	public function get_single_lead( $request ) {
		$leads = LeadFlow_CRM::get_leads( array( 'id' => $request['id'] ) );
		if ( empty( $leads ) ) {
			return new WP_Error( 'not_found', 'Lead not found', array( 'status' => 404 ) );
		}

		$lead = $leads[0];

		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		$lead->tags = $wpdb->get_results( $wpdb->prepare(
			"SELECT t.id, t.name FROM {$prefix}lead_tags t JOIN {$prefix}lead_tag_relationships r ON t.id = r.tag_id WHERE r.lead_id = %d",
			$lead->id
		) );

		return rest_ensure_response( $lead );
	}

	public function create_lead( $request ) {
		$params = $request->get_params();
		$id = LeadFlow_CRM::create_lead( $params );
		return is_wp_error( $id ) ? $id : rest_ensure_response( array( 'id' => $id ) );
	}

	public function update_lead( $request ) {
		$id = $request['id'];
		$params = $request->get_params();
		$success = LeadFlow_CRM::update_lead( $id, $params );
		return rest_ensure_response( array( 'success' => (bool) $success ) );
	}

	public function delete_lead( $request ) {
		LeadFlow_Compliance::delete_lead_data( $request['id'] );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function get_lead_activity( $request ) {
		global $wpdb;
		$lead_id = $request['id'];
		$prefix = $wpdb->prefix . 'leadflow_';
		$notes = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$prefix}lead_notes WHERE lead_id = %d ORDER BY created_at ASC", $lead_id ) );
		$emails = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$prefix}email_log WHERE lead_id = %d ORDER BY created_at ASC", $lead_id ) );
		return rest_ensure_response( array( 'notes' => $notes, 'emails' => $emails ) );
	}

	public function add_lead_note( $request ) {
		$id = LeadFlow_CRM::add_note( $request['id'], sanitize_textarea_field( $request->get_param( 'content' ) ) );
		return rest_ensure_response( array( 'success' => (bool) $id ) );
	}

	public function enrich_lead( $request ) {
		$result = LeadFlow_Enrichment::enrich_lead( $request['id'] );
		return is_wp_error( $result ) ? $result : rest_ensure_response( array( 'success' => true, 'data' => $result ) );
	}

	public function manual_audit( $request ) {
		LeadFlow_Scraper::run_manual_audit( $request['id'] );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function generate_lead_ai_hook( $request ) {
		global $wpdb;
		$id = $request['id'];
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}leadflow_leads WHERE id = %d", $id ) );

		if ( ! $lead ) {
			return new WP_Error( 'not_found', 'Lead not found.', array( 'status' => 404 ) );
		}

		$audit_results = json_decode( $lead->audit_data, true ) ?: array();
		$hook = LeadFlow_AI::summarize_audit( $audit_results );

		LeadFlow_CRM::add_note( $id, "AI Outreach Hook: " . $hook, 0 );

		return rest_ensure_response( array( 'success' => true, 'hook' => $hook ) );
	}

	public function get_tasks( $request ) {
		global $wpdb;
		$status = $request->get_param( 'status' );
		$prefix = $wpdb->prefix . 'leadflow_';
		$query = "SELECT t.*, l.business_name FROM {$prefix}tasks t JOIN {$prefix}leads l ON t.lead_id = l.id WHERE 1=1";
		if ( $status ) $query .= $wpdb->prepare( " AND t.status = %s", $status );
		if ( ! current_user_can( 'manage_options' ) ) $query .= $wpdb->prepare( " AND t.assigned_to = %d", get_current_user_id() );
		return rest_ensure_response( $wpdb->get_results( $query ) );
	}

	public function update_task( $request ) {
		$success = LeadFlow_CRM::update_task_status( $request['id'], sanitize_text_field( $request->get_param( 'status' ) ) );
		return rest_ensure_response( array( 'success' => (bool) $success ) );
	}

	public function delete_task( $request ) {
		$success = LeadFlow_CRM::delete_task( $request['id'] );
		return rest_ensure_response( array( 'success' => (bool) $success ) );
	}

	public function discovery_search( $request ) {
		$leads = LeadFlow_Discovery::search_google_places( $request['keyword'], $request['location'] );
		return is_wp_error( $leads ) ? $leads : rest_ensure_response( $leads );
	}

	public function discovery_social( $request ) {
		$keyword = $request['keyword'];
		$source = $request['source'];
		if ( 'linkedin' === $source ) $leads = LeadFlow_Discovery::search_linkedin( $keyword );
		else if ( 'facebook' === $source ) $leads = LeadFlow_Discovery::search_facebook_groups( $keyword );
		else return new WP_Error( 'invalid_source', 'Invalid discovery source', array( 'status' => 400 ) );
		return rest_ensure_response( $leads );
	}

	public function get_saved_searches() {
		global $wpdb;
		return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}leadflow_saved_searches ORDER BY created_at DESC" ) );
	}

	public function save_search( $request ) {
		global $wpdb;
		$wpdb->insert( "{$wpdb->prefix}leadflow_saved_searches", array(
			'name' => sanitize_text_field( $request->get_param('name') ),
			'source' => sanitize_text_field( $request->get_param('source') ),
			'keyword' => sanitize_text_field( $request->get_param('keyword') ),
			'location' => sanitize_text_field( $request->get_param('location') ),
			'created_at' => current_time('mysql'),
		) );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function get_campaigns( $request ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		$id = $request->get_param('id');

		if ( $id ) {
			$campaign = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}campaigns WHERE id = %d", $id ) );
			if ( $campaign ) {
				$campaign->steps = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$prefix}campaign_steps WHERE campaign_id = %d ORDER BY step_order ASC", $id ) );
			}
			return rest_ensure_response( $campaign );
		}

		return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$prefix}campaigns" ) );
	}

	public function create_campaign( $request ) {
		$id = LeadFlow_Outreach::create_campaign( $request->get_params() );
		if ( is_wp_error( $id ) ) return $id;
		$steps = $request->get_param( 'steps' );
		if ( is_array( $steps ) ) {
			foreach ( $steps as $idx => $step ) {
				LeadFlow_Outreach::add_step( $id, array_merge( $step, array( 'order' => $idx + 1 ) ) );
			}
		}
		return rest_ensure_response( array( 'id' => $id ) );
	}

	public function update_campaign( $request ) {
		global $wpdb;
		$id = $request['id'];
		$params = $request->get_params();
		$prefix = $wpdb->prefix . 'leadflow_';

		$data = array();
		if ( isset( $params['name'] ) ) $data['name'] = sanitize_text_field( $params['name'] );
		if ( isset( $params['is_active'] ) ) $data['is_active'] = (int) $params['is_active'];
		if ( isset( $params['status_filter'] ) ) $data['status_filter'] = sanitize_text_field( $params['status_filter'] );

		if ( ! empty( $data ) ) {
			$wpdb->update( "{$prefix}campaigns", $data, array( 'id' => $id ) );
		}

		if ( isset( $params['steps'] ) && is_array( $params['steps'] ) ) {
			$wpdb->delete( "{$prefix}campaign_steps", array( 'campaign_id' => $id ) );
			foreach ( $params['steps'] as $idx => $step ) {
				LeadFlow_Outreach::add_step( $id, array_merge( $step, array( 'order' => $idx + 1 ) ) );
			}
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function delete_campaign( $request ) {
		global $wpdb;
		$id = $request['id'];
		$prefix = $wpdb->prefix . 'leadflow_';
		$wpdb->delete( "{$prefix}campaigns", array( 'id' => $id ) );
		$wpdb->delete( "{$prefix}campaign_steps", array( 'campaign_id' => $id ) );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function get_templates() {
		return rest_ensure_response( LeadFlow_Templates::get_templates() );
	}

	public function create_template( $request ) {
		$params = $request->get_params();
		$id = LeadFlow_Templates::create_template( $params );
		return rest_ensure_response( array( 'id' => $id ) );
	}

	public function update_template( $request ) {
		$id = $request['id'];
		$params = $request->get_params();
		LeadFlow_Templates::update_template( $id, $params );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function delete_template( $request ) {
		$id = $request['id'];
		LeadFlow_Templates::delete_template( $id );
		return rest_ensure_response( array( 'success' => true ) );
	}

	public function get_inbox() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		$query = "SELECT l.id, l.business_name, MAX(e.created_at) as last_reply FROM {$prefix}leads l JOIN {$prefix}email_log e ON l.id = e.lead_id WHERE l.status = 'Replied' GROUP BY l.id ORDER BY last_reply DESC";
		return rest_ensure_response( $wpdb->get_results( $query ) );
	}

	public function send_reply( $request ) {
		$lead_id = $request['lead_id'];
		$message = $request['message'];

		global $wpdb;
		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}leadflow_leads WHERE id = %d", $lead_id ) );

		if ( ! $lead ) {
			return new WP_Error( 'not_found', 'Lead not found', array( 'status' => 404 ) );
		}

		$sent = LeadFlow_Email::send( $lead->email, 'Re: Your inquiry', $message );

		if ( is_wp_error( $sent ) ) {
			return $sent;
		}

		LeadFlow_CRM::add_note( $lead_id, "Outbound Reply: " . $message, get_current_user_id() );

		$wpdb->insert( $wpdb->prefix . 'leadflow_email_log', array(
			'lead_id' => $lead_id,
			'subject' => 'Re: Your inquiry',
			'status'  => 'Sent',
			'created_at' => current_time( 'mysql' ),
		) );

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function get_overview_analytics() {
		return rest_ensure_response( array(
			'status_counts'   => LeadFlow_Analytics::get_leads_by_status(),
			'source_counts'   => LeadFlow_Analytics::get_leads_by_source(),
			'assignee_counts' => LeadFlow_Analytics::get_leads_by_assignee(),
			'roi'             => LeadFlow_Analytics::get_roi_metrics(),
			'sentiment_pulse' => LeadFlow_Analytics::get_sentiment_pulse(),
		) );
	}

	public function get_campaign_analytics() {
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
				'name'    => $campaign->name,
				'sent'    => (int) $sent,
				'opens'   => (int) $opens,
				'clicks'  => (int) $clicks,
				'replies' => (int) $replies,
			);
		}

		return rest_ensure_response( $stats );
	}

	public function get_activity_feed() {
		return rest_ensure_response( LeadFlow_Analytics::get_recent_activity() );
	}

	public function ai_complete( $request ) {
		$result = LeadFlow_AI::complete( $request['prompt'], $request['context'] ?: array() );
		return rest_ensure_response( array( 'result' => $result ) );
	}

	public function save_settings( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'rest_forbidden', 'You do not have permission to save settings.', array( 'status' => 401 ) );
		}

		$params = $request->get_params();
		$allowed_options = array(
			'leadflow_openai_api_key',
			'leadflow_google_places_api_key',
			'leadflow_smtp_from_name',
			'leadflow_smtp_from_email',
			'leadflow_setup_complete',
			'leadflow_license_server_url'
		);

		foreach ( $params as $key => $value ) {
			if ( in_array( $key, $allowed_options ) ) {
				update_option( $key, $value );
			}
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function get_system_logs() {
		return rest_ensure_response( LeadFlow_Logger::get_logs() );
	}

	public function test_email( $request ) {
		$sent = LeadFlow_Email::send( $request['email'], 'Test', 'Connectivity Test' );
		return is_wp_error( $sent ) ? $sent : rest_ensure_response( array( 'success' => true ) );
	}

	public function test_imap() {
		$success = LeadFlow_Email::test_imap_connectivity();
		return is_wp_error( $success ) ? $success : rest_ensure_response( array( 'success' => true ) );
	}

	public function activate_license( $request ) {
		$server_url = $request->get_param( 'license_server_url' );
		if ( ! empty( $server_url ) ) {
			update_option( 'leadflow_license_server_url', esc_url_raw( $server_url ) );
		}

		$result = LeadFlow_License::validate_license( $request['license_key'] );
		return $result['success'] ? rest_ensure_response( $result ) : new WP_Error( 'failed', $result['message'], array( 'status' => 403 ) );
	}

	public function track_open( $request ) {
		LeadFlow_Email::track_open( $request['id'] );
		header( 'Content-Type: image/gif' );
		echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' );
		exit;
	}
}

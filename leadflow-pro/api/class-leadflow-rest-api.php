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

		register_rest_route( 'leadflow/v1', '/campaigns/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_campaign' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_campaign' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/scraper/queue', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_scraper_queue' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'trigger_scraper_batch' ),
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

		register_rest_route( 'leadflow/v1', '/settings/test-email', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'test_email' ),
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

		register_rest_route( 'leadflow/v1', '/license/activate-demo', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'activate_demo_license' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/discovery/sample-csv', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'download_sample_csv' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

		register_rest_route( 'leadflow/v1', '/license/activate', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'activate_license' ),
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

		register_rest_route( 'leadflow/v1', '/discovery/saved-searches/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_saved_search' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );

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
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_template' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_template' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );
	}

	public function check_permission() {
		if ( get_option( 'leadflow_license_key' ) && ! LeadFlow_License::is_pro() ) {
			return false;
		}
		return current_user_can( 'manage_options' );
	}

	public function get_leads( $request ) {
		global $wpdb;
		$params = $request->get_params();
		$leads  = LeadFlow_CRM::get_leads( $params );
		$prefix = $wpdb->prefix . 'leadflow_';

		foreach ( $leads as &$lead ) {
			$lead->tags = $wpdb->get_results( $wpdb->prepare(
				"SELECT t.id, t.name FROM {$prefix}lead_tags t JOIN {$prefix}lead_tag_relationships r ON t.id = r.tag_id WHERE r.lead_id = %d",
				$lead->id
			) );
		}

		return rest_ensure_response( $leads );
	}

	public function create_lead( $request ) {
		$params  = $request->get_params();

		$data = array();
		$fields = array(
			'first_name'    => 'sanitize_text_field',
			'business_name' => 'sanitize_text_field',
			'website_url'   => 'esc_url_raw',
			'email'         => 'sanitize_email',
			'phone'         => 'sanitize_text_field',
			'status'        => 'sanitize_text_field',
			'lead_source'   => 'sanitize_text_field',
			'social_links'  => 'wp_kses_post', // JSON string
		);

		foreach ( $fields as $field => $sanitizer ) {
			if ( isset( $params[ $field ] ) ) {
				$data[ $field ] = call_user_func( $sanitizer, $params[ $field ] );
			}
		}

		if ( empty( $data['business_name'] ) ) {
			return new WP_Error( 'missing_field', 'Business Name is required.', array( 'status' => 400 ) );
		}

		$lead_id = LeadFlow_CRM::create_lead( $data );

		if ( is_wp_error( $lead_id ) ) {
			return new WP_Error( $lead_id->get_error_code(), $lead_id->get_error_message(), array( 'status' => 400 ) );
		}

		return rest_ensure_response( array( 'id' => $lead_id ) );
	}

	public function get_scraper_queue() {
		return rest_ensure_response( LeadFlow_Scraper::get_queue_status() );
	}

	public function trigger_scraper_batch() {
		LeadFlow_Scraper::process_batch();
		return rest_ensure_response( array( 'success' => true ) );
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
		$fields = array(
			'first_name'    => 'sanitize_text_field',
			'business_name' => 'sanitize_text_field',
			'website_url'   => 'esc_url_raw',
			'email'         => 'sanitize_email',
			'phone'         => 'sanitize_text_field',
			'status'        => 'sanitize_text_field',
			'assigned_to'   => 'absint',
			'social_links'  => 'wp_kses_post', // JSON string
			'proposal_url'  => 'esc_url_raw',
		);

		foreach ( $fields as $field => $sanitizer ) {
			if ( isset( $params[ $field ] ) ) {
				$data[ $field ] = call_user_func( $sanitizer, $params[ $field ] );
			}
		}

		if ( empty( $data ) ) {
			return new WP_Error( 'no_data', 'No data provided to update.', array( 'status' => 400 ) );
		}

		// Status automation: If proposal URL is added, mark as Proposal Sent
		if ( ! empty( $data['proposal_url'] ) ) {
			$data['status'] = 'Proposal Sent';
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
			case 'reply_suggestion':
				$result = LeadFlow_AI::suggest_reply( $context['inbound_text'] );
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

		$wpdb->query( $wpdb->prepare(
			"UPDATE {$prefix}email_log SET status = 'Clicked', clicks_count = clicks_count + 1, last_tracked_at = %s WHERE tracking_hash = %s",
			current_time( 'mysql' ),
			$hash
		) );

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

		$email = $wpdb->get_var( $wpdb->prepare(
			"SELECT l.email FROM {$prefix}leads l JOIN {$prefix}email_log e ON l.id = e.lead_id WHERE e.tracking_hash = %s",
			$hash
		) );

		if ( $email ) {
			LeadFlow_Compliance::add_opt_out( $email, 'Unsubscribe Link' );
			wp_die( 'You have been successfully unsubscribed.' );
		}

		wp_die( 'Invalid request.' );
	}

	public function get_overview_analytics( $request ) {
		$start = $request->get_param( 'start' );
		$end   = $request->get_param( 'end' );

		return rest_ensure_response( array(
			'status_counts' => LeadFlow_Analytics::get_leads_by_status(),
			'source_counts' => LeadFlow_Analytics::get_leads_by_source(),
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
					'order'       => $index + 1,
					'delay'       => $step['delay'],
					'template_id' => isset( $step['template_id'] ) ? $step['template_id'] : null,
					'subject'     => isset( $step['subject'] ) ? $step['subject'] : '',
					'body'        => $step['body'],
					'type'        => $step['type'],
				) );
			}
		}

		return rest_ensure_response( array( 'id' => $campaign_id ) );
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
			// Wipe and rebuild steps for simplicity in this version
			$wpdb->delete( "{$prefix}campaign_steps", array( 'campaign_id' => $id ) );
			foreach ( $params['steps'] as $index => $step ) {
				LeadFlow_Outreach::add_step( $id, array(
					'order'       => $index + 1,
					'delay'       => $step['delay'],
					'template_id' => isset( $step['template_id'] ) ? $step['template_id'] : null,
					'subject'     => isset( $step['subject'] ) ? $step['subject'] : '',
					'body'        => $step['body'],
					'type'        => $step['type'],
				) );
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

	public function test_email( $request ) {
		$to = $request->get_param( 'email' );
		if ( empty( $to ) ) {
			return new WP_Error( 'missing_email', 'Test email address is required.', array( 'status' => 400 ) );
		}

		$sent = LeadFlow_Email::send( $to, 'LeadFlow Pro: Test Email', '<p>Your email configuration is working correctly!</p>' );

		if ( is_wp_error( $sent ) ) {
			return $sent;
		}

		return rest_ensure_response( array( 'success' => true ) );
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

	public function download_sample_csv() {
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="leadflow_sample_import.csv"' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'Business Name', 'Website', 'Email', 'Phone', 'Social Links (JSON String)' ) );
		fputcsv( $output, array( 'Acme Corp', 'https://acme.com', 'info@acme.com', '+1-555-0199', '{"linkedin":"https://linkedin.com/company/acme"}' ) );
		fputcsv( $output, array( 'Globex', 'https://globex.co', 'hr@globex.co', '+1-555-0200', '{}' ) );
		fclose( $output );
		exit;
	}

	public function activate_license( $request ) {
		$key = sanitize_text_field( $request->get_param( 'license_key' ) );
		$result = LeadFlow_License::validate_license( $key );

		if ( ! $result['success'] ) {
			return new WP_Error( 'activation_failed', $result['message'], array( 'status' => 403 ) );
		}

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function get_activity_feed() {
		return rest_ensure_response( LeadFlow_Analytics::get_recent_activity() );
	}

	public function get_saved_searches() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';
		return rest_ensure_response( $wpdb->get_results( "SELECT * FROM {$prefix}saved_searches ORDER BY created_at DESC" ) );
	}

	public function save_search( $request ) {
		global $wpdb;
		$params = $request->get_params();
		$prefix = $wpdb->prefix . 'leadflow_';

		$wpdb->insert( "{$prefix}saved_searches", array(
			'name'       => sanitize_text_field( $params['name'] ),
			'source'     => sanitize_text_field( $params['source'] ),
			'keyword'    => sanitize_text_field( $params['keyword'] ),
			'location'   => sanitize_text_field( $params['location'] ),
			'created_at' => current_time( 'mysql' ),
		) );

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function delete_saved_search( $request ) {
		global $wpdb;
		$id = $request['id'];
		$prefix = $wpdb->prefix . 'leadflow_';
		$wpdb->delete( "{$prefix}saved_searches", array( 'id' => $id ) );
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

	public function activate_demo_license() {
		LeadFlow_License::activate_demo_license();
		return rest_ensure_response( array( 'success' => true ) );
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

		// Pull tracking hash from content (if any)
		preg_match( '/\/track\/open\/([a-zA-Z0-9]+)/', $message, $matches );
		$tracking_hash = isset( $matches[1] ) ? $matches[1] : '';

		$wpdb->insert(
			"{$prefix}email_log",
			array(
				'lead_id'       => $lead_id,
				'tracking_hash' => $tracking_hash,
				'subject'       => 'Re: Your inquiry',
				'status'        => 'Sent',
				'created_at'    => current_time( 'mysql' ),
			)
		);

		LeadFlow_CRM::add_note( $lead_id, "Outbound Reply: " . $message );

		return rest_ensure_response( array( 'success' => true ) );
	}
}

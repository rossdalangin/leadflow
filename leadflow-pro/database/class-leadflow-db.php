<?php
/**
 * LeadFlow_DB class for handling custom database tables and migrations.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_DB {

	/**
	 * Create/Update custom tables using dbDelta.
	 *
	 * Custom tables are used instead of Custom Post Types for performance,
	 * granular indexing, and scalability. This is critical for high-volume
	 * lead generation and high-frequency background processing.
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix . 'leadflow_';

		$sql = "CREATE TABLE {$prefix}leads (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			first_name varchar(100),
			business_name varchar(255) NOT NULL,
			website_url varchar(255),
			email varchar(100),
			phone varchar(20),
			social_links text,
			lead_source varchar(50),
			status varchar(30) DEFAULT 'New',
			assigned_to bigint(20) unsigned,
			audit_data longtext,
			is_pro_only tinyint(1) DEFAULT 0,
			completeness_score tinyint(3) unsigned DEFAULT 0,
			proposal_url varchar(255),
			consent_at datetime,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY email (email),
			KEY website_url (website_url)
		) $charset_collate;
		CREATE TABLE {$prefix}lead_notes (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			lead_id bigint(20) unsigned NOT NULL,
			author_id bigint(20) unsigned NOT NULL,
			content text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY lead_id (lead_id)
		) $charset_collate;
		CREATE TABLE {$prefix}lead_tags (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(50) NOT NULL,
			slug varchar(50) NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug)
		) $charset_collate;
		CREATE TABLE {$prefix}lead_tag_relationships (
			lead_id bigint(20) unsigned NOT NULL,
			tag_id bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (lead_id, tag_id)
		) $charset_collate;
		CREATE TABLE {$prefix}campaigns (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			goal text,
			status_filter varchar(30),
			is_active tinyint(1) DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;
		CREATE TABLE {$prefix}campaign_steps (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			campaign_id bigint(20) unsigned NOT NULL,
			step_order int(10) unsigned NOT NULL,
			delay_days int(10) unsigned DEFAULT 0,
			template_id bigint(20) unsigned DEFAULT NULL,
			subject varchar(255) NOT NULL,
			body longtext NOT NULL,
			template_ids text, -- comma-separated list for A/B testing
			step_type varchar(20) DEFAULT 'email',
			PRIMARY KEY  (id),
			KEY campaign_id (campaign_id)
		) $charset_collate;
		CREATE TABLE {$prefix}email_log (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			lead_id bigint(20) unsigned NOT NULL,
			campaign_id bigint(20) unsigned,
			step_id bigint(20) unsigned,
			template_id bigint(20) unsigned DEFAULT NULL,
			tracking_hash varchar(64),
			subject varchar(255),
			status varchar(20) DEFAULT 'Sent',
			opens_count int(10) unsigned DEFAULT 0,
			clicks_count int(10) unsigned DEFAULT 0,
			last_tracked_at datetime,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY tracking_hash (tracking_hash),
			KEY lead_id (lead_id),
			KEY campaign_id (campaign_id)
		) $charset_collate;
		CREATE TABLE {$prefix}scrape_queue (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			lead_id bigint(20) unsigned NOT NULL,
			status varchar(20) DEFAULT 'Pending',
			retry_count tinyint(3) unsigned DEFAULT 0,
			error_log text,
			created_at datetime NOT NULL,
			scheduled_at datetime,
			PRIMARY KEY  (id),
			KEY status (status)
		) $charset_collate;
		CREATE TABLE {$prefix}opt_outs (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(100) NOT NULL,
			reason varchar(255),
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email)
		) $charset_collate;
		CREATE TABLE {$prefix}ai_usage (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			provider varchar(20) NOT NULL,
			feature varchar(50) NOT NULL,
			tokens_used int(10) unsigned NOT NULL DEFAULT 0,
			lead_id bigint(20) unsigned DEFAULT NULL,
			campaign_id bigint(20) unsigned DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY provider_date (provider, created_at)
		) $charset_collate;
		CREATE TABLE {$prefix}email_templates (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			subject varchar(255) NOT NULL,
			body longtext NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;
		CREATE TABLE {$prefix}saved_searches (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			source varchar(20) NOT NULL,
			keyword varchar(255) NOT NULL,
			location varchar(255),
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;
		CREATE TABLE {$prefix}sending_queue (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			lead_id bigint(20) unsigned NOT NULL,
			campaign_id bigint(20) unsigned NOT NULL,
			step_id bigint(20) unsigned NOT NULL,
			status varchar(20) DEFAULT 'Scheduled',
			scheduled_at datetime NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status)
		) $charset_collate;
		CREATE TABLE {$prefix}lead_magnets (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			description text,
			form_config text,
			success_message text,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Seed initial tags or data if necessary.
	 */
	public static function seed_data() {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		// Seed sample tags
		$tags = array(
			array( 'name' => 'High Intent', 'slug' => 'high-intent' ),
			array( 'name' => 'Needs Website', 'slug' => 'needs-website' ),
			array( 'name' => 'No SSL', 'slug' => 'no-ssl' ),
			array( 'name' => 'Slow Load', 'slug' => 'slow-load' ),
			array( 'name' => 'Outdated', 'slug' => 'outdated' ),
			array( 'name' => 'WordPress', 'slug' => 'wordpress' ),
			array( 'name' => 'eCommerce', 'slug' => 'ecommerce' ),
		);

		foreach ( $tags as $tag ) {
			$wpdb->insert( "{$prefix}lead_tags", $tag );
		}

		$sample_leads = array(
			array( 'business_name' => 'Acme Dental', 'website_url' => 'https://acmedental.com', 'email' => 'info@acmedental.com', 'phone' => '555-0101', 'status' => 'New' ),
			array( 'business_name' => 'Swift Logistics', 'website_url' => 'https://swiftlogistics.io', 'email' => 'ops@swiftlogistics.io', 'phone' => '555-0102', 'status' => 'Contacted' ),
			array( 'business_name' => 'Blue Sky Realty', 'website_url' => 'https://blueskyrealty.com', 'email' => 'sales@blueskyrealty.com', 'phone' => '555-0103', 'status' => 'Replied' ),
			array( 'business_name' => 'Elite Plumbing', 'website_url' => 'http://eliteplumbing.net', 'email' => 'help@eliteplumbing.net', 'phone' => '555-0104', 'status' => 'New' ),
			array( 'business_name' => 'Main St Cafe', 'website_url' => 'https://mainstcafe.com', 'email' => 'hello@mainstcafe.com', 'phone' => '555-0105', 'status' => 'Qualified' ),
			array( 'business_name' => 'Global Tech Sol', 'website_url' => 'https://globaltech.com', 'email' => 'hr@globaltech.com', 'phone' => '555-0106', 'status' => 'New' ),
			array( 'business_name' => 'Precision Law', 'website_url' => 'https://precisionlaw.com', 'email' => 'legal@precisionlaw.com', 'phone' => '555-0107', 'status' => 'Contacted' ),
			array( 'business_name' => 'Sparkle Cleaning', 'website_url' => 'http://sparkleclean.io', 'email' => 'clean@sparkleclean.io', 'phone' => '555-0108', 'status' => 'New' ),
			array( 'business_name' => 'Peak Fitness', 'website_url' => 'https://peakfit.com', 'email' => 'gym@peakfit.com', 'phone' => '555-0109', 'status' => 'Replied' ),
			array( 'business_name' => 'Urban Architect', 'website_url' => 'https://urbanarch.com', 'email' => 'design@urbanarch.com', 'phone' => '555-0110', 'status' => 'New' ),
		);

		foreach ( $sample_leads as $lead ) {
			$wpdb->insert( "{$prefix}leads", array_merge( $lead, array(
				'lead_source'   => 'Sample Data',
				'social_links'  => wp_json_encode( array( 'facebook' => 'https://facebook.com/sample', 'linkedin' => 'https://linkedin.com/company/sample' ) ),
				'completeness_score' => 60,
				'created_at'    => current_time( 'mysql' ),
				'updated_at'    => current_time( 'mysql' ),
			) ) );
		}

		// Seed templates
		$wpdb->insert( "{$prefix}email_templates", array(
			'name' => 'Initial Outreach (SSL Hook)',
			'subject' => 'Quick question about {{business_name}} website',
			'body' => 'Hi {{first_name}},\n\n{{audit_flag}}\n\nI specialize in fixing these issues for local businesses in {{city}}. Would you be open to a quick chat?\n\nBest,\nAdmin',
			'created_at' => current_time( 'mysql' )
		) );
	}
}

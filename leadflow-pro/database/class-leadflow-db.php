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
			subject varchar(255) NOT NULL,
			body longtext NOT NULL,
			step_type varchar(20) DEFAULT 'email',
			PRIMARY KEY  (id),
			KEY campaign_id (campaign_id)
		) $charset_collate;
		CREATE TABLE {$prefix}email_log (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			lead_id bigint(20) unsigned NOT NULL,
			campaign_id bigint(20) unsigned,
			step_id bigint(20) unsigned,
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
		);

		foreach ( $tags as $tag ) {
			$wpdb->insert( "{$prefix}lead_tags", $tag );
		}

		// Seed a sample lead
		$wpdb->insert( "{$prefix}leads", array(
			'business_name' => 'Sample Agency',
			'website_url'   => 'https://example.com',
			'email'         => 'contact@example.com',
			'lead_source'   => 'Sample Data',
			'status'        => 'New',
			'created_at'    => current_time( 'mysql' ),
			'updated_at'    => current_time( 'mysql' ),
		) );
	}
}

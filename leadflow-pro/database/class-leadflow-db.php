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

		$sql = "
		-- Core lead records
		CREATE TABLE {$prefix}leads (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			first_name VARCHAR(100),
			business_name VARCHAR(255) NOT NULL,
			website_url VARCHAR(255),
			email VARCHAR(100),
			phone VARCHAR(20),
			social_links TEXT,
			lead_source VARCHAR(50),
			status VARCHAR(30) DEFAULT 'New',
			assigned_to BIGINT UNSIGNED,
			audit_data LONGTEXT,
			is_pro_only TINYINT(1) DEFAULT 0,
			consent_at DATETIME,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY status (status),
			KEY email (email),
			KEY website_url (website_url)
		) $charset_collate;

		-- Timestamped notes per lead
		CREATE TABLE {$prefix}lead_notes (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT UNSIGNED NOT NULL,
			author_id BIGINT UNSIGNED NOT NULL,
			content TEXT NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY lead_id (lead_id)
		) $charset_collate;

		-- Tag taxonomy for leads (Custom implementation for performance)
		CREATE TABLE {$prefix}lead_tags (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(50) NOT NULL,
			slug VARCHAR(50) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug)
		) $charset_collate;

		-- Lead-Tag relationship
		CREATE TABLE {$prefix}lead_tag_relationships (
			lead_id BIGINT UNSIGNED NOT NULL,
			tag_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY (lead_id, tag_id)
		) $charset_collate;

		-- Outreach campaign definitions
		CREATE TABLE {$prefix}campaigns (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL,
			goal TEXT,
			status_filter VARCHAR(30),
			is_active TINYINT(1) DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id)
		) $charset_collate;

		-- Individual steps per campaign
		CREATE TABLE {$prefix}campaign_steps (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			campaign_id BIGINT UNSIGNED NOT NULL,
			step_order INT UNSIGNED NOT NULL,
			delay_days INT UNSIGNED DEFAULT 0,
			subject VARCHAR(255) NOT NULL,
			body LONGTEXT NOT NULL,
			step_type VARCHAR(20) DEFAULT 'email',
			PRIMARY KEY (id),
			KEY campaign_id (campaign_id)
		) $charset_collate;

		-- All sent emails with tracking status
		CREATE TABLE {$prefix}email_log (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT UNSIGNED NOT NULL,
			campaign_id BIGINT UNSIGNED,
			step_id BIGINT UNSIGNED,
			subject VARCHAR(255),
			status VARCHAR(20) DEFAULT 'Sent', -- Sent, Failed, Opened, Clicked, Replied
			opens_count INT UNSIGNED DEFAULT 0,
			clicks_count INT UNSIGNED DEFAULT 0,
			last_tracked_at DATETIME,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY lead_id (lead_id),
			KEY campaign_id (campaign_id)
		) $charset_collate;

		-- Background crawl jobs for Scraper
		CREATE TABLE {$prefix}scrape_queue (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			lead_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(20) DEFAULT 'Pending', -- Pending, Processing, Completed, Failed
			retry_count TINYINT UNSIGNED DEFAULT 0,
			error_log TEXT,
			created_at DATETIME NOT NULL,
			scheduled_at DATETIME,
			PRIMARY KEY (id),
			KEY status (status)
		) $charset_collate;

		-- Suppression list (Opt-outs)
		CREATE TABLE {$prefix}opt_outs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			email VARCHAR(100) NOT NULL,
			reason VARCHAR(255),
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY email (email)
		) $charset_collate;

		-- Per-provider AI usage tracking
		CREATE TABLE {$prefix}ai_usage (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			provider VARCHAR(20) NOT NULL,        -- 'openai' or 'gemini'
			feature VARCHAR(50) NOT NULL,         -- 'email_writer', 'lead_scorer', etc.
			tokens_used INT UNSIGNED NOT NULL DEFAULT 0,
			lead_id BIGINT UNSIGNED DEFAULT NULL,
			campaign_id BIGINT UNSIGNED DEFAULT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY provider_date (provider, created_at)
		) $charset_collate;
		";

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

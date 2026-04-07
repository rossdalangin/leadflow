<?php
/**
 * LeadFlow_Activator class for plugin activation.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Activator {

	/**
	 * Run activation logic.
	 */
	public static function activate() {
		require_once LEADFLOW_PRO_PATH . 'database/class-leadflow-db.php';
		LeadFlow_DB::create_tables();
		LeadFlow_DB::seed_data();

		// Schedule background tasks if not already scheduled
		if ( ! wp_next_scheduled( 'leadflow_process_scraper_queue' ) ) {
			wp_schedule_event( time(), 'hourly', 'leadflow_process_scraper_queue' );
		}
		if ( ! wp_next_scheduled( 'leadflow_process_campaigns' ) ) {
			wp_schedule_event( time(), 'hourly', 'leadflow_process_campaigns' );
		}
		if ( ! wp_next_scheduled( 'leadflow_process_sending_queue' ) ) {
			wp_schedule_event( time(), 'hourly', 'leadflow_process_sending_queue' );
		}
		if ( ! wp_next_scheduled( 'leadflow_poll_inbox' ) ) {
			wp_schedule_event( time(), 'hourly', 'leadflow_poll_inbox' );
		}
	}
}

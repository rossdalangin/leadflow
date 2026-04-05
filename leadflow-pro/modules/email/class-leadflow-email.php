<?php
/**
 * LeadFlow_Email class for handling SMTP sending and IMAP inboxing.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Email {

	private static $smtp_settings_option = 'leadflow_smtp_settings';

	/**
	 * Send an email via SMTP or Gmail API.
	 */
	public static function send( $to, $subject, $body, $headers = array() ) {
		$settings = get_option( self::$smtp_settings_option );

		if ( empty( $settings ) ) {
			return new WP_Error( 'not_configured', 'SMTP settings not configured.' );
		}

		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$headers[] = 'From: ' . $settings['from_name'] . ' <' . $settings['from_email'] . '>';

		// Open tracking pixel (using hash for privacy)
		$tracking_hash  = wp_hash( $to );
		$tracking_pixel = '<img src="' . get_rest_url( null, 'leadflow/v1/track/open/' . $tracking_hash ) . '" width="1" height="1" />';
		$body .= $tracking_pixel;

		// Mock send logic using wp_mail for now, assuming SMTP plugin or custom filter handles it.
		// In production, we'd use PHPMailer or Gmail API.
		$sent = wp_mail( $to, $subject, $body, $headers );

		return $sent ? true : new WP_Error( 'send_failed', 'Failed to send email.' );
	}

	/**
	 * Poll IMAP inbox for replies.
	 */
	public static function poll_inbox() {
		if ( ! function_exists( 'imap_open' ) ) {
			return;
		}

		$settings = get_option( 'leadflow_imap_settings' );

		if ( empty( $settings ) ) {
			return;
		}

		$server = '{' . $settings['host'] . ':' . $settings['port'] . '/imap/' . $settings['encryption'] . '}INBOX';
		$user   = $settings['user'];
		$pass   = LeadFlow_Security::get_decrypted_option( 'leadflow_imap_pass' );

		$inbox = imap_open( $server, $user, $pass );

		if ( $inbox ) {
			$emails = imap_search( $inbox, 'UNSEEN' );

			if ( $emails ) {
				foreach ( $emails as $email_number ) {
					$overview = imap_fetch_overview( $inbox, $email_number, 0 );
					$from     = $overview[0]->from;
					$body     = imap_fetchbody( $inbox, $email_number, 1 );

					// Identify lead by sender email
					if ( preg_match( '/<(.+?)>/', $from, $matches ) ) {
						$sender_email = $matches[1];
					} else {
						$sender_email = $from;
					}

					self::process_incoming_reply( $sender_email, $body );
					imap_setflag_full( $inbox, $email_number, "\\Seen" );
				}
			}

			imap_close( $inbox );
		}
	}

	/**
	 * Process incoming reply.
	 */
	private static function process_incoming_reply( $email, $body ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$prefix}leads WHERE email = %s", $email ) );

		if ( $lead ) {
			LeadFlow_CRM::update_status( $lead->id, 'Replied' );
			LeadFlow_CRM::add_note( $lead->id, "Inbound Reply: " . $body );

			// Log as replied in email log
			$wpdb->update(
				"{$prefix}email_log",
				array( 'status' => 'Replied' ),
				array( 'lead_id' => $lead->id, 'status' => 'Sent' )
			);
		}
	}

	/**
	 * Handle tracking of email opens.
	 */
	public static function track_open( $hash ) {
		global $wpdb;
		$prefix = $wpdb->prefix . 'leadflow_';

		// Identify lead by matching hash (Iterate or store hash in lead table for performance)
		$leads = $wpdb->get_results( "SELECT id, email FROM {$prefix}leads" );
		$lead_id = 0;

		foreach ( $leads as $lead ) {
			if ( wp_hash( $lead->email ) === $hash ) {
				$lead_id = $lead->id;
				break;
			}
		}

		if ( $lead_id ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$prefix}email_log SET status = 'Opened', opens_count = opens_count + 1, last_tracked_at = %s WHERE lead_id = %d ORDER BY created_at DESC LIMIT 1",
				current_time( 'mysql' ),
				$lead_id
			) );
		}
	}
}

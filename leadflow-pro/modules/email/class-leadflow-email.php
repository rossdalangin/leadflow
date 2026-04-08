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
		$from_name  = get_option( 'leadflow_smtp_from_name' );
		$from_email = get_option( 'leadflow_smtp_from_email' );
		$provider   = get_option( 'leadflow_email_provider', 'smtp' );

		if ( empty( $from_email ) ) {
			return new WP_Error( 'not_configured', 'Email settings not configured.' );
		}

		$headers[] = 'Content-Type: text/html; charset=UTF-8';
		$headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';

		// Generate a unique tracking hash for this specific email instance
		$tracking_hash = wp_hash( $to . current_time( 'mysql' ) . uniqid() );

		// Open tracking pixel
		$tracking_pixel = '<img src="' . get_rest_url( null, 'leadflow/v1/track/open/' . $tracking_hash ) . '" width="1" height="1" />';
		$body .= $tracking_pixel;

		// Unsubscribe link
		$unsubscribe_url = get_rest_url( null, 'leadflow/v1/track/unsubscribe/' . $tracking_hash );
		$body .= '<br><br><small><a href="' . $unsubscribe_url . '">Unsubscribe</a></small>';

		// Add Signature
		$signature = get_option( 'leadflow_email_signature' );
		if ( ! empty( $signature ) ) {
			$body .= '<br><br>' . wpautop( $signature );
		}

		// Click tracking replacement
		$body = preg_replace_callback( '/<a\s+href=["\'](https?:\/\/[^"\']+)["\']/', function( $matches ) use ( $tracking_hash ) {
			$original_url = $matches[1];
			$track_url = get_rest_url( null, 'leadflow/v1/track/click/' . $tracking_hash ) . '?redir=' . urlencode( $original_url );
			return '<a href="' . $track_url . '"';
		}, $body );

		if ( 'gmail' === $provider && LeadFlow_License::is_pro() ) {
			return self::send_via_gmail_api( $to, $subject, $body, $headers );
		}

		// Dispatch email via WordPress core with configured SMTP settings.
		$sent = wp_mail( $to, $subject, $body, $headers );

		return $sent ? true : new WP_Error( 'send_failed', 'Failed to send email.' );
	}

	/**
	 * Gmail API send implementation (Pro Feature).
	 */
	private static function send_via_gmail_api( $to, $subject, $body, $headers ) {
		$token = LeadFlow_Security::get_decrypted_option( 'leadflow_gmail_token' );
		if ( ! $token ) {
			return new WP_Error( 'gmail_not_auth', 'Gmail not authenticated.' );
		}

		// Encapsulate the raw RFC 2822 message
		$boundary = uniqid( 'np', true );
		$raw_message  = "To: $to\r\n";
		$raw_message .= "Subject: $subject\r\n";
		$raw_message .= "MIME-Version: 1.0\r\n";
		$raw_message .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n\r\n";
		$raw_message .= "--$boundary\r\n";
		$raw_message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
		$raw_message .= $body . "\r\n\r\n";
		$raw_message .= "--$boundary--";

		$encoded_message = strtr( base64_encode( $raw_message ), array( '+' => '-', '/' => '_', '=' => '' ) );

		$response = wp_remote_post( 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body' => wp_json_encode( array( 'raw' => $encoded_message ) ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return true;
	}

	/**
	 * Configure SMTP via PHPMailer hook.
	 */
	public static function configure_smtp( $phpmailer ) {
		$host = get_option( 'leadflow_smtp_host' );
		if ( empty( $host ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host       = $host;
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Port       = get_option( 'leadflow_smtp_port', 587 );
		$phpmailer->Username   = get_option( 'leadflow_smtp_user' );
		$phpmailer->Password   = LeadFlow_Security::get_decrypted_option( 'leadflow_smtp_pass' );
		$phpmailer->SMTPSecure = get_option( 'leadflow_smtp_encryption', 'tls' );
		$phpmailer->From       = get_option( 'leadflow_smtp_from_email' );
		$phpmailer->FromName   = get_option( 'leadflow_smtp_from_name' );
	}

	/**
	 * Poll IMAP inbox for replies.
	 */
	public static function poll_inbox() {
		if ( ! function_exists( 'imap_open' ) ) {
			return;
		}

		$host = get_option( 'leadflow_imap_host' );
		$port = get_option( 'leadflow_imap_port' );
		$user = get_option( 'leadflow_imap_user' );
		$pass = LeadFlow_Security::get_decrypted_option( 'leadflow_imap_pass' );
		$enc  = get_option( 'leadflow_imap_encryption', 'ssl' );

		if ( empty( $host ) || empty( $user ) || empty( $pass ) ) {
			return;
		}

		$server = '{' . $host . ':' . $port . '/imap/' . $enc . '}INBOX';

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

		// Skip auto-replies
		$auto_reply_keywords = array( 'out of office', 'auto-reply', 'automatic reply', 'vacation response' );
		foreach ( $auto_reply_keywords as $keyword ) {
			if ( stripos( $body, $keyword ) !== false ) {
				return;
			}
		}

		$lead = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$prefix}leads WHERE email = %s", $email ) );

		if ( $lead ) {
			LeadFlow_CRM::update_status( $lead->id, 'Replied' );

			// AI: Analyze sentiment and intent
			$sentiment_full = LeadFlow_AI::analyze_sentiment( $body );
			LeadFlow_CRM::add_note( $lead->id, "Inbound Reply AI Analysis: $sentiment_full", 0 );

			// Intent-based automation
			if ( stripos( $sentiment_full, 'Meeting' ) !== false ) {
				LeadFlow_CRM::update_status( $lead->id, 'Qualified' );
				LeadFlow_CRM::add_note( $lead->id, "Status automatically updated to Qualified: AI detected Meeting Intent.", 0 );
				LeadFlow_CRM::add_task( array(
					'lead_id'     => $lead->id,
					'task_type'   => 'follow-up',
					'description' => 'Schedule discovery call with lead (AI detected meeting intent)',
					'due_date'    => date( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ),
					'assigned_to' => $lead->assigned_to
				) );
			}

			// Auto-tagging based on sentiment
			$tag_slug = '';
			if ( stripos( $sentiment_full, 'Positive' ) !== false ) $tag_slug = 'high-intent';
			elseif ( stripos( $sentiment_full, 'Unsubscribe' ) !== false ) $tag_slug = 'opt-out';

			if ( $tag_slug ) {
				$tag_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$prefix}lead_tags WHERE slug = %s", $tag_slug ) );
				if ( $tag_id ) {
					$wpdb->replace( "{$prefix}lead_tag_relationships", array( 'lead_id' => $lead->id, 'tag_id' => $tag_id ) );
				}
			}

			// Auto-detect unsubscribe intent
			if ( LeadFlow_Compliance::detect_unsubscribe_intent( $body ) || stripos( $sentiment_full, 'Unsubscribe' ) !== false ) {
				LeadFlow_Compliance::add_opt_out( $email, 'Detected in reply' );
				LeadFlow_CRM::add_note( $lead->id, "Lead automatically added to suppression list due to unsubscribe intent.", 0 );

				if ( get_option( 'leadflow_auto_archive_negative', 1 ) ) {
					LeadFlow_CRM::update_status( $lead->id, 'Closed Lost' );
					LeadFlow_CRM::add_note( $lead->id, "Lead automatically moved to Closed Lost due to negative sentiment/opt-out.", 0 );
				}
			}

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

		$wpdb->query( $wpdb->prepare(
			"UPDATE {$prefix}email_log SET status = 'Opened', opens_count = opens_count + 1, last_tracked_at = %s WHERE tracking_hash = %s",
			current_time( 'mysql' ),
			$hash
		) );
	}
}

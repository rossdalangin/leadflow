<?php
/**
 * LeadFlow_Deliverability class for checking email domain health.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Deliverability {

	/**
	 * Check health of the sending domain.
	 *
	 * @return array Health status and recommendations.
	 */
	public static function check_domain_health() {
		$from_email = get_option( 'leadflow_smtp_from_email' );
		if ( ! $from_email ) {
			return array( 'status' => 'error', 'message' => 'Sender email not configured.' );
		}

		$domain = substr( strrchr( $from_email, "@" ), 1 );
		if ( ! $domain ) {
			return array( 'status' => 'error', 'message' => 'Invalid domain in sender email.' );
		}

		$health = array(
			'domain' => $domain,
			'spf'    => self::check_spf( $domain ),
			'dmarc'  => self::check_dmarc( $domain ),
			'dkim'   => self::check_dkim( $domain ), // Basic presence check
		);

		$score = 0;
		if ( $health['spf']['valid'] ) $score += 34;
		if ( $health['dmarc']['valid'] ) $score += 33;
		if ( $health['dkim']['valid'] ) $score += 33;

		$health['score'] = $score;
		$health['status'] = $score >= 67 ? 'good' : ( $score >= 34 ? 'warning' : 'critical' );

		return $health;
	}

	private static function check_spf( $domain ) {
		$records = dns_get_record( $domain, DNS_TXT );
		$found = false;
		foreach ( $records as $record ) {
			if ( isset( $record['txt'] ) && stripos( $record['txt'], 'v=spf1' ) !== false ) {
				$found = true;
				break;
			}
		}
		return array(
			'valid' => $found,
			'message' => $found ? 'SPF record found.' : 'SPF record missing. This may cause emails to land in spam.'
		);
	}

	private static function check_dmarc( $domain ) {
		$records = dns_get_record( '_dmarc.' . $domain, DNS_TXT );
		$found = ! empty( $records );
		return array(
			'valid' => $found,
			'message' => $found ? 'DMARC record found.' : 'DMARC record missing. Essential for modern email security.'
		);
	}

	private static function check_dkim( $domain ) {
		// DKIM is hard to check without the selector.
		// We'll check for a common default 'google._domainkey' if using Gmail, otherwise just a note.
		$provider = get_option( 'leadflow_email_provider' );
		$selector = ( 'gmail' === $provider ) ? 'google' : 'default';

		$records = dns_get_record( $selector . '._domainkey.' . $domain, DNS_TXT );
		$found = ! empty( $records );

		return array(
			'valid' => $found,
			'message' => $found ? 'DKIM record detected.' : 'DKIM record could not be verified automatically. Check your DNS provider.'
		);
	}
}

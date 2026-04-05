<?php
/**
 * LeadFlow_Security class for handling encryption and security helpers.
 *
 * @package LeadFlowPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LeadFlow_Security {

	/**
	 * Encrypt a value using AES-256-CTR.
	 *
	 * @param string $value The value to encrypt.
	 * @return string The encrypted value (Base64).
	 */
	public static function encrypt( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		$key = self::get_encryption_key();
		$iv  = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-ctr' ) );

		$encrypted = openssl_encrypt( $value, 'aes-256-ctr', $key, 0, $iv );

		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt a value using AES-256-CTR.
	 *
	 * @param string $value The encrypted value (Base64).
	 * @return string The decrypted value.
	 */
	public static function decrypt( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		$data = base64_decode( $value );
		$key  = self::get_encryption_key();
		$iv_length = openssl_cipher_iv_length( 'aes-256-ctr' );
		$iv        = substr( $data, 0, $iv_length );
		$encrypted = substr( $data, $iv_length );

		return openssl_decrypt( $encrypted, 'aes-256-ctr', $key, 0, $iv );
	}

	/**
	 * Get the encryption key from wp-config.php or generate a consistent one.
	 */
	private static function get_encryption_key() {
		if ( defined( 'SECURE_AUTH_KEY' ) ) {
			return SECURE_AUTH_KEY;
		}
		return 'leadflow_default_secret_key'; // Fallback (not ideal, but consistent)
	}

	/**
	 * Save an encrypted option.
	 */
	public static function update_encrypted_option( $option, $value ) {
		$encrypted = self::encrypt( $value );
		return update_option( $option, $encrypted );
	}

	/**
	 * Get a decrypted option.
	 */
	public static function get_decrypted_option( $option, $default = '' ) {
		$encrypted = get_option( $option );
		if ( ! $encrypted ) {
			return $default;
		}
		return self::decrypt( $encrypted );
	}
}

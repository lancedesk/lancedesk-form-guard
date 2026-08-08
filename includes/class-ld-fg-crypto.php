<?php
/**
 * Encrypt and decrypt sensitive option values (API keys).
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AES-256-CBC encryption using AUTH_KEY-derived material.
 */
final class LD_FG_Crypto {

	/**
	 * Stored ciphertext prefix.
	 */
	public const PREFIX = 'ldfgenc:';

	/**
	 * Encrypt a plaintext secret for storage.
	 *
	 * @param string $plaintext Raw secret.
	 *
	 * @return string
	 */
	public static function encrypt( string $plaintext ): string {
		if ( '' === $plaintext ) {
			return '';
		}

		if ( self::is_encrypted( $plaintext ) ) {
			return $plaintext;
		}

		if ( ! self::can_encrypt() ) {
			return $plaintext;
		}

		$key = self::get_encryption_key();
		$iv  = random_bytes( openssl_cipher_iv_length( 'AES-256-CBC' ) );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- transport encoding for ciphertext blob.
		$ciphertext = openssl_encrypt( $plaintext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $ciphertext ) {
			return $plaintext;
		}

		return self::PREFIX . base64_encode( $iv . $ciphertext );
	}

	/**
	 * Decrypt a stored secret.
	 *
	 * @param string $stored Stored value (encrypted or legacy plaintext).
	 *
	 * @return string
	 */
	public static function decrypt( string $stored ): string {
		if ( '' === $stored || ! self::is_encrypted( $stored ) ) {
			return $stored;
		}

		if ( ! self::can_encrypt() ) {
			return '';
		}

		$payload = base64_decode( substr( $stored, strlen( self::PREFIX ) ), true );

		if ( false === $payload || strlen( $payload ) < 17 ) {
			return '';
		}

		$iv_len     = openssl_cipher_iv_length( 'AES-256-CBC' );
		$iv         = substr( $payload, 0, $iv_len );
		$ciphertext = substr( $payload, $iv_len );
		$key        = self::get_encryption_key();
		$plaintext  = openssl_decrypt( $ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

		return false === $plaintext ? '' : $plaintext;
	}

	/**
	 * Whether a stored value is encrypted by this plugin.
	 *
	 * @param string $value Stored value.
	 *
	 * @return bool
	 */
	public static function is_encrypted( string $value ): bool {
		return 0 === strpos( $value, self::PREFIX );
	}

	/**
	 * Whether OpenSSL encryption is available.
	 *
	 * @return bool
	 */
	public static function can_encrypt(): bool {
		return function_exists( 'openssl_encrypt' )
			&& function_exists( 'openssl_decrypt' )
			&& defined( 'AUTH_KEY' )
			&& '' !== AUTH_KEY;
	}

	/**
	 * Derive a binary encryption key from WordPress salts.
	 *
	 * @return string
	 */
	private static function get_encryption_key(): string {
		return hash( 'sha256', AUTH_KEY . 'ld_fg_secrets_v1', true );
	}
}

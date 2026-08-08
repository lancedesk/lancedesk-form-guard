<?php
/**
 * Settings helpers with defaults.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read and update plugin settings.
 */
final class LD_FG_Settings {

	/**
	 * Option key.
	 */
	public const OPTION_KEY = 'ld_fg_settings';

	/**
	 * Default settings structure.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'enabled'               => 0,
			'retention_days'        => 90,
			'delete_on_uninstall'   => 1,
			'privacy_ack'           => 0,
			'default_action'        => 'silent_discard',
			'ai_fail_mode'          => 'fail_open',
			'heuristics'            => array(
				'bbcode_url' => 1,
				'url_count'  => 1,
				'honeypot'   => 1,
				'too_fast'   => 1,
			),
			'ai_enabled'            => 1,
			'confidence_quarantine' => 0.7,
			'confidence_block'      => 0.9,
			'protected_forms'       => array(),
			'protect_all'           => array(),
			'providers'             => array(
				'groq'      => array(
					'enabled' => 0,
					'api_key' => '',
					'model'   => 'llama-3.1-8b-instant',
				),
				'openai'    => array(
					'enabled' => 0,
					'api_key' => '',
					'model'   => 'gpt-4o-mini',
				),
				'anthropic' => array(
					'enabled' => 0,
					'api_key' => '',
					'model'   => 'claude-3-5-haiku-latest',
				),
			),
			'provider_order'        => array( 'groq', 'openai', 'anthropic' ),
			'setup_dismissed'       => 0,
		);
	}

	/**
	 * Get merged settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_replace_recursive( self::defaults(), $stored );
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key (dot notation supported for one level).
	 * @param mixed  $default Fallback value.
	 *
	 * @return mixed
	 */
	public static function get_value( string $key, $default = null ) {
		$settings = self::get();

		if ( false !== strpos( $key, '.' ) ) {
			list( $parent, $child ) = explode( '.', $key, 2 );
			if ( isset( $settings[ $parent ] ) && is_array( $settings[ $parent ] ) && array_key_exists( $child, $settings[ $parent ] ) ) {
				return $settings[ $parent ][ $child ];
			}
			return $default;
		}

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Update settings (partial merge).
	 *
	 * @param array<string, mixed> $patch Settings patch.
	 *
	 * @return bool
	 */
	public static function update( array $patch ): bool {
		$current = self::get();
		$merged  = array_replace_recursive( $current, $patch );

		return update_option( self::OPTION_KEY, $merged );
	}

	/**
	 * Whether a form is protected.
	 *
	 * @param string $integration Integration slug.
	 * @param string $form_id     Form identifier.
	 *
	 * @return bool
	 */
	public static function is_form_protected( string $integration, string $form_id ): bool {
		$settings = self::get();

		if ( empty( $settings['enabled'] ) ) {
			return false;
		}

		if ( ! empty( $settings['protect_all'][ $integration ] ) ) {
			return true;
		}

		$key = $integration . ':' . $form_id;

		return in_array( $key, (array) $settings['protected_forms'], true );
	}

	/**
	 * Decrypt provider API keys in settings for runtime use.
	 *
	 * @param array<string, mixed> $settings Settings array.
	 *
	 * @return array<string, mixed>
	 */
	public static function decrypt_provider_keys( array $settings ): array {
		if ( empty( $settings['providers'] ) || ! is_array( $settings['providers'] ) ) {
			return $settings;
		}

		foreach ( $settings['providers'] as $id => $provider ) {
			if ( ! is_array( $provider ) || empty( $provider['api_key'] ) ) {
				continue;
			}

			$settings['providers'][ $id ]['api_key'] = LD_FG_Crypto::decrypt( (string) $provider['api_key'] );
		}

		return $settings;
	}

	/**
	 * Encrypt provider API keys before storage.
	 *
	 * @param array<string, mixed> $settings Settings array.
	 *
	 * @return array<string, mixed>
	 */
	public static function encrypt_provider_keys( array $settings ): array {
		if ( empty( $settings['providers'] ) || ! is_array( $settings['providers'] ) ) {
			return $settings;
		}

		foreach ( $settings['providers'] as $id => $provider ) {
			if ( ! is_array( $provider ) || empty( $provider['api_key'] ) ) {
				continue;
			}

			$key = (string) $provider['api_key'];

			if ( ! LD_FG_Crypto::is_encrypted( $key ) ) {
				$settings['providers'][ $id ]['api_key'] = LD_FG_Crypto::encrypt( $key );
			}
		}

		return $settings;
	}
}

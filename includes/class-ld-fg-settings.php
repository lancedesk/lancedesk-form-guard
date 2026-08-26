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
			'delete_on_uninstall'   => 0,
			'privacy_ack'           => 0,
			'default_action'        => 'silent_discard',
			'ai_fail_mode'          => 'fail_open',
			'heuristics'            => array(
				'bbcode_url'           => 1,
				'url_count'            => 1,
				'honeypot'             => 1,
				'too_fast'             => 1,
				'suspicious_links'     => 1,
				'commercial_outreach'  => 1,
				'gibberish'            => 1,
			),
			'ai_enabled'            => 0,
			'confidence_quarantine' => 0.7,
			'confidence_block'      => 0.9,
			'protected_forms'          => array(),
			'protect_all'              => array(),
			'protect_core_comments'    => 1,
			'protect_core_registration' => 0,
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
	 * Replace the protected_forms list in the stored option (no recursive merge).
	 *
	 * @param array<int, string> $list Form keys (`integration:id`).
	 *
	 * @return bool
	 */
	public static function set_protected_forms( array $list ): bool {
		$list = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $item ): string {
							return sanitize_text_field( (string) $item );
						},
						$list
					),
					static function ( string $item ): bool {
						return '' !== $item;
					}
				)
			)
		);

		$stored = get_option( self::OPTION_KEY, array() );
		// Copy — mutating the cached get_option() array in place makes update_option() no-op.
		$stored = is_array( $stored ) ? array_merge( array(), $stored ) : array();

		$stored['protected_forms'] = $list;

		$result = update_option( self::OPTION_KEY, $stored );

		// false can mean "unchanged" after a successful prior write — treat matching value as OK.
		if ( false === $result ) {
			$existing = get_option( self::OPTION_KEY, array() );
			$got      = ( is_array( $existing ) && isset( $existing['protected_forms'] ) && is_array( $existing['protected_forms'] ) )
				? array_values( array_map( 'strval', $existing['protected_forms'] ) )
				: array();
			$expected = $list;
			sort( $expected );
			sort( $got );
			return $expected === $got;
		}

		return true;
	}

	/**
	 * Update settings (partial merge).
	 *
	 * Indexed lists (e.g. protected_forms) are replaced wholesale — array_replace_recursive
	 * cannot shorten numeric arrays and would leave stale entries.
	 *
	 * @param array<string, mixed> $patch Settings patch.
	 *
	 * @return bool
	 */
	public static function update( array $patch ): bool {
		if ( array_key_exists( 'protected_forms', $patch ) && is_array( $patch['protected_forms'] ) ) {
			$ok = self::set_protected_forms( $patch['protected_forms'] );
			unset( $patch['protected_forms'] );
			if ( empty( $patch ) ) {
				return $ok;
			}
		}

		$current = self::get();

		if ( array_key_exists( 'provider_order', $patch ) ) {
			$current['provider_order'] = is_array( $patch['provider_order'] )
				? array_values( $patch['provider_order'] )
				: array();
			unset( $patch['provider_order'] );
		}

		$merged = empty( $patch ) ? $current : array_replace_recursive( $current, $patch );

		return (bool) update_option( self::OPTION_KEY, $merged, false );
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

		if ( 'core' === $integration ) {
			if ( 'comment' === $form_id ) {
				return ! empty( $settings['protect_core_comments'] );
			}

			if ( 'registration' === $form_id ) {
				return ! empty( $settings['protect_core_registration'] );
			}

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

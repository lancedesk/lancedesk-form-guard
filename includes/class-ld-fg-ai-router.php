<?php
/**
 * Multi-provider AI spam classification router.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calls Groq, OpenAI, or Anthropic for JSON spam verdicts.
 */
final class LD_FG_AI_Router {

	/**
	 * HTTP timeout in seconds.
	 */
	public const TIMEOUT = 12;

	/**
	 * Transient cache TTL in seconds.
	 */
	public const CACHE_TTL = 3600;

	/**
	 * Classify submission content via configured providers.
	 *
	 * @param string               $content  Combined field text.
	 * @param array<string, mixed> $settings Plugin settings (keys decrypted).
	 *
	 * @return array{verdict: string, confidence: float, reason: string, layer: string, provider: string}
	 */
	public static function classify( string $content, array $settings ): array {
		$fail = array(
			'verdict'    => 'clean',
			'confidence' => 0.0,
			'reason'     => '',
			'layer'      => 'ai',
			'provider'   => '',
		);

		if ( empty( $settings['ai_enabled'] ) || '' === trim( $content ) ) {
			return $fail;
		}

		$hash  = md5( $content );
		$cache = get_transient( 'ld_fg_ai_' . $hash );

		if ( is_array( $cache ) && isset( $cache['verdict'] ) ) {
			$cache['layer'] = 'ai';

			return $cache;
		}

		$order = isset( $settings['provider_order'] ) && is_array( $settings['provider_order'] )
			? $settings['provider_order']
			: array( 'groq', 'openai', 'anthropic' );

		$providers = isset( $settings['providers'] ) && is_array( $settings['providers'] )
			? $settings['providers']
			: array();

		foreach ( $order as $provider_id ) {
			$provider_id = sanitize_key( (string) $provider_id );

			if ( empty( $providers[ $provider_id ] ) || ! is_array( $providers[ $provider_id ] ) ) {
				continue;
			}

			$config = $providers[ $provider_id ];

			if ( empty( $config['enabled'] ) || empty( $config['api_key'] ) ) {
				continue;
			}

			$result = self::call_provider( $provider_id, (string) $config['api_key'], (string) ( $config['model'] ?? '' ), $content );

			if ( null === $result ) {
				continue;
			}

			$result['layer']    = 'ai';
			$result['provider'] = $provider_id;

			set_transient( 'ld_fg_ai_' . $hash, $result, self::CACHE_TTL );

			return $result;
		}

		return $fail;
	}

	/**
	 * Test provider connectivity with a minimal prompt.
	 *
	 * @param string $provider_id Provider slug.
	 * @param string $api_key     API key.
	 * @param string $model       Model id.
	 *
	 * @return array{success: bool, message: string, latency_ms?: int}
	 */
	public static function test_connection( string $provider_id, string $api_key, string $model ): array {
		$start = microtime( true );

		$result = self::call_provider( $provider_id, $api_key, $model, 'Test message: hello world.' );

		$latency = (int) round( ( microtime( true ) - $start ) * 1000 );

		if ( null === $result ) {
			return array(
				'success' => false,
				'message' => __( 'Connection failed or invalid response.', 'lancedesk-form-guard' ),
			);
		}

		return array(
			'success'    => true,
			'message'    => __( 'Connection successful.', 'lancedesk-form-guard' ),
			'latency_ms' => $latency,
		);
	}

	/**
	 * Call a single provider.
	 *
	 * @param string $provider_id Provider slug.
	 * @param string $api_key     API key.
	 * @param string $model       Model id.
	 * @param string $content     Content to classify.
	 *
	 * @return array{verdict: string, confidence: float, reason: string, provider: string}|null
	 */
	private static function call_provider( string $provider_id, string $api_key, string $model, string $content ): ?array {
		switch ( $provider_id ) {
			case 'groq':
				return self::call_openai_compatible(
					'https://api.groq.com/openai/v1/chat/completions',
					$api_key,
					$model ?: 'llama-3.1-8b-instant',
					$content,
					'groq'
				);
			case 'openai':
				return self::call_openai_compatible(
					'https://api.openai.com/v1/chat/completions',
					$api_key,
					$model ?: 'gpt-4o-mini',
					$content,
					'openai'
				);
			case 'anthropic':
				return self::call_anthropic( $api_key, $model ?: 'claude-3-5-haiku-latest', $content );
			default:
				return null;
		}
	}

	/**
	 * OpenAI-compatible chat completion.
	 *
	 * @param string $endpoint    API URL.
	 * @param string $api_key     API key.
	 * @param string $model       Model id.
	 * @param string $content     User content.
	 * @param string $provider_id Provider slug for response.
	 *
	 * @return array{verdict: string, confidence: float, reason: string, provider: string}|null
	 */
	private static function call_openai_compatible( string $endpoint, string $api_key, string $model, string $content, string $provider_id ): ?array {
		$body = array(
			'model'       => $model,
			'temperature' => 0,
			'max_tokens'  => 256,
			'messages'    => array(
				array(
					'role'    => 'system',
					'content' => 'You classify form submissions as spam or not spam. Reply with JSON only: {"verdict":"spam"|"clean","confidence":0.0-1.0,"reason":"short explanation"}',
				),
				array(
					'role'    => 'user',
					'content' => mb_substr( $content, 0, 4000 ),
				),
			),
		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			return null;
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['choices'][0]['message']['content'] ) ) {
			return null;
		}

		return self::parse_verdict_json( (string) $data['choices'][0]['message']['content'], $provider_id );
	}

	/**
	 * Anthropic messages API.
	 *
	 * @param string $api_key  API key.
	 * @param string $model    Model id.
	 * @param string $content  User content.
	 *
	 * @return array{verdict: string, confidence: float, reason: string, provider: string}|null
	 */
	private static function call_anthropic( string $api_key, string $model, string $content ): ?array {
		$body = array(
			'model'      => $model,
			'max_tokens' => 256,
			'messages'   => array(
				array(
					'role'    => 'user',
					'content' => 'Classify this form submission as spam or clean. Reply JSON only: {"verdict":"spam"|"clean","confidence":0.0-1.0,"reason":"..."}' . "\n\n" . mb_substr( $content, 0, 4000 ),
				),
			),
		);

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => self::TIMEOUT,
				'headers' => array(
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 300 ) {
			return null;
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) || empty( $data['content'][0]['text'] ) ) {
			return null;
		}

		return self::parse_verdict_json( (string) $data['content'][0]['text'], 'anthropic' );
	}

	/**
	 * Parse JSON verdict from model output.
	 *
	 * @param string $raw         Model text.
	 * @param string $provider_id Provider slug.
	 *
	 * @return array{verdict: string, confidence: float, reason: string, provider: string}|null
	 */
	private static function parse_verdict_json( string $raw, string $provider_id ): ?array {
		$raw = trim( $raw );

		if ( preg_match( '/\{.*\}/s', $raw, $match ) ) {
			$raw = $match[0];
		}

		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) || empty( $data['verdict'] ) ) {
			return null;
		}

		$verdict = 'spam' === sanitize_key( (string) $data['verdict'] ) ? 'spam' : 'clean';

		return array(
			'verdict'    => $verdict,
			'confidence' => isset( $data['confidence'] ) ? max( 0.0, min( 1.0, (float) $data['confidence'] ) ) : 0.5,
			'reason'     => sanitize_text_field( (string) ( $data['reason'] ?? '' ) ),
			'provider'   => $provider_id,
		);
	}
}

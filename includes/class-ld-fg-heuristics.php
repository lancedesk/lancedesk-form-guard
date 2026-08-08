<?php
/**
 * Layer 1 deterministic heuristic checks.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fast-path spam heuristics.
 */
final class LD_FG_Heuristics {

	/**
	 * Minimum seconds between form render and submit.
	 */
	public const MIN_SUBMIT_SECONDS = 3;

	/**
	 * Maximum URLs allowed in combined text fields.
	 */
	public const MAX_URL_COUNT = 5;

	/**
	 * Run enabled heuristic checks on submission payload.
	 *
	 * @param array<string, mixed> $payload Submission data with keys: fields, honeypot, render_time, submit_time.
	 * @param array<string, mixed> $settings Plugin settings.
	 *
	 * @return array{verdict: string, confidence: float, reason: string, layer: string, matched: string}
	 */
	public static function evaluate( array $payload, array $settings ): array {
		$clean = array(
			'verdict'    => 'clean',
			'confidence' => 0.0,
			'reason'     => '',
			'layer'      => 'heuristic',
			'matched'    => '',
		);

		$toggles = isset( $settings['heuristics'] ) && is_array( $settings['heuristics'] )
			? $settings['heuristics']
			: array();

		$fields = isset( $payload['fields'] ) && is_array( $payload['fields'] )
			? $payload['fields']
			: array();

		$combined = self::combine_field_text( $fields );

		if ( ! empty( $toggles['honeypot'] ) && ! empty( $payload['honeypot'] ) ) {
			return array(
				'verdict'    => 'spam',
				'confidence' => 1.0,
				'reason'     => __( 'Honeypot field filled.', 'lancedesk-form-guard' ),
				'layer'      => 'heuristic',
				'matched'    => 'honeypot',
			);
		}

		if ( ! empty( $toggles['too_fast'] ) ) {
			$render = isset( $payload['render_time'] ) ? (int) $payload['render_time'] : 0;
			$submit = isset( $payload['submit_time'] ) ? (int) $payload['submit_time'] : 0;

			if ( $render > 0 && $submit > 0 ) {
				$elapsed = $submit - $render;

				if ( $elapsed < self::MIN_SUBMIT_SECONDS ) {
					return array(
						'verdict'    => 'spam',
						'confidence' => 0.95,
						'reason'     => __( 'Submission occurred too quickly.', 'lancedesk-form-guard' ),
						'layer'      => 'heuristic',
						'matched'    => 'too_fast',
					);
				}
			}
		}

		if ( ! empty( $toggles['bbcode_url'] ) && preg_match( '/\[url\s*=/i', $combined ) ) {
			return array(
				'verdict'    => 'spam',
				'confidence' => 0.98,
				'reason'     => __( 'BBCode [url=] injection detected.', 'lancedesk-form-guard' ),
				'layer'      => 'heuristic',
				'matched'    => 'bbcode_url',
			);
		}

		if ( ! empty( $toggles['url_count'] ) ) {
			$url_count = self::count_urls( $combined );

			if ( $url_count > self::MAX_URL_COUNT ) {
				return array(
					'verdict'    => 'spam',
					'confidence' => 0.92,
					/* translators: %d: number of URLs found. */
					'reason'     => sprintf( __( 'Link stuffing detected (%d URLs).', 'lancedesk-form-guard' ), $url_count ),
					'layer'      => 'heuristic',
					'matched'    => 'url_count',
				);
			}
		}

		return $clean;
	}

	/**
	 * Combine text field values for analysis.
	 *
	 * @param array<string, mixed> $fields Field map.
	 *
	 * @return string
	 */
	private static function combine_field_text( array $fields ): string {
		$parts = array();

		foreach ( $fields as $value ) {
			if ( is_string( $value ) ) {
				$parts[] = $value;
			} elseif ( is_array( $value ) ) {
				$parts[] = implode( ' ', array_map( 'strval', $value ) );
			}
		}

		return implode( "\n", $parts );
	}

	/**
	 * Count URLs in text.
	 *
	 * @param string $text Combined text.
	 *
	 * @return int
	 */
	private static function count_urls( string $text ): int {
		if ( '' === $text ) {
			return 0;
		}

		$count = preg_match_all( '#https?://[^\s<>"\'\)\]]+#i', $text, $matches );

		return false === $count ? 0 : $count;
	}
}

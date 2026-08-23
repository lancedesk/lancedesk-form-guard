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
	 * Maximum URLs allowed in combined text fields (contact forms rarely need more).
	 */
	public const MAX_URL_COUNT = 2;

	/**
	 * Minimum commercial-outreach score to treat as spam.
	 */
	public const COMMERCIAL_SCORE_THRESHOLD = 3;

	/**
	 * Host fragments commonly used for link / image spam (matched against URL hosts).
	 *
	 * @var array<int, string>
	 */
	private static function suspicious_host_fragments(): array {
		// phpcs:disable PluginCheck.CodeAnalysis.Offloading.OffloadedContent -- Substring blocklist for spam URL detection; no assets are enqueued or loaded from these hosts.
		return array(
			'im.ge',
			'imglink.',
			'uploadimages.',
			'imgbb.',
			'postimg.',
			'freeimage.host',
			'ibb.co',
			'bit.ly',
			'tinyurl.',
			't.co',
			'goo.gl',
			'cutt.ly',
			'rebrand.ly',
			'is.gd',
			'ow.ly',
			'rb.gy',
			'imgur.',
		);
		// phpcs:enable PluginCheck.CodeAnalysis.Offloading.OffloadedContent
	}

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

		if ( ! empty( $toggles['suspicious_links'] ) ) {
			$host = self::first_suspicious_host( $combined );

			if ( '' !== $host ) {
				return array(
					'verdict'    => 'spam',
					'confidence' => 0.94,
					/* translators: %s: suspicious hostname. */
					'reason'     => sprintf( __( 'Suspicious link host detected (%s).', 'lancedesk-form-guard' ), $host ),
					'layer'      => 'heuristic',
					'matched'    => 'suspicious_links',
				);
			}
		}

		if ( ! empty( $toggles['commercial_outreach'] ) ) {
			$commercial = self::score_commercial_outreach( $combined );

			if ( $commercial['score'] >= self::COMMERCIAL_SCORE_THRESHOLD ) {
				return array(
					'verdict'    => 'spam',
					'confidence' => min( 0.97, 0.75 + ( 0.03 * $commercial['score'] ) ),
					'reason'     => sprintf(
						/* translators: %s: short matched pattern list. */
						__( 'Commercial / SEO outreach patterns detected (%s).', 'lancedesk-form-guard' ),
						$commercial['label']
					),
					'layer'      => 'heuristic',
					'matched'    => 'commercial_outreach',
				);
			}

			$handle_count = self::count_handle_dump_lines( $combined );

			if ( $handle_count >= 4 ) {
				return array(
					'verdict'    => 'spam',
					'confidence' => 0.9,
					/* translators: %d: number of handle-like lines. */
					'reason'     => sprintf( __( 'Bulk social/handle dump detected (%d lines).', 'lancedesk-form-guard' ), $handle_count ),
					'layer'      => 'heuristic',
					'matched'    => 'commercial_outreach',
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

	/**
	 * Return first suspicious URL host found, or empty string.
	 *
	 * @param string $text Combined text.
	 *
	 * @return string
	 */
	private static function first_suspicious_host( string $text ): string {
		if ( '' === $text ) {
			return '';
		}

		if ( ! preg_match_all( '#https?://([^\s<>"\'\)\]/]+)#i', $text, $matches ) ) {
			return '';
		}

		foreach ( $matches[1] as $host ) {
			$host = strtolower( (string) $host );
			$host = preg_replace( '/:\d+$/', '', $host ) ?? $host;

			foreach ( self::suspicious_host_fragments() as $fragment ) {
				if ( false !== strpos( $host, $fragment ) ) {
					return $host;
				}
			}
		}

		return '';
	}

	/**
	 * Score commercial / SEO cold-outreach language.
	 *
	 * @param string $text Combined text.
	 *
	 * @return array{score: int, label: string}
	 */
	private static function score_commercial_outreach( string $text ): array {
		if ( '' === trim( $text ) ) {
			return array(
				'score' => 0,
				'label' => '',
			);
		}

		$haystack = strtolower( $text );
		$score    = 0;
		$hits     = array();

		$patterns = array(
			'leaving money on the table'         => array( 3, 'money on the table' ),
			'we analyzed'                        => array( 2, 'we analyzed' ),
			'seo audit'                          => array( 2, 'SEO audit' ),
			'full-scale seo'                     => array( 2, 'full-scale SEO' ),
			'full scale seo'                     => array( 2, 'full-scale SEO' ),
			'keyword strategy'                   => array( 2, 'keyword strategy' ),
			'outranking your competitors'        => array( 3, 'outranking competitors' ),
			'outranking'                         => array( 1, 'outranking' ),
			'reply to this email'                => array( 2, 'reply to this email' ),
			'lead-generating'                    => array( 2, 'lead-generating' ),
			'lead generating'                    => array( 2, 'lead-generating' ),
			'revenue-generating'                 => array( 2, 'revenue-generating' ),
			'revenue generating'                 => array( 2, 'revenue-generating' ),
			'not targeting the right keywords'   => array( 3, 'wrong keywords' ),
			'poorly optimized meta tags'         => array( 2, 'meta tags' ),
			'missing or poorly optimized'        => array( 2, 'optimization pitch' ),
			'converting visitors into customers' => array( 2, 'conversion pitch' ),
			'your website is currently'          => array( 2, 'website pitch' ),
			'critical seo issues'                => array( 3, 'critical SEO issues' ),
			'seo knights'                        => array( 3, 'SEO vendor' ),
			'digital marketing agency'           => array( 2, 'marketing agency' ),
			'we can help you rank'               => array( 2, 'rank pitch' ),
			'guaranteed first page'              => array( 3, 'guaranteed ranking' ),
			'increase your traffic'              => array( 1, 'traffic pitch' ),
			'grow your business online'          => array( 2, 'growth pitch' ),
			'would you be open to'               => array( 2, 'sales opener' ),
			'reach out to discuss'               => array( 2, 'outreach CTA' ),
			'partnership opportun'               => array( 2, 'partnership pitch' ),
			'quick call'                         => array( 2, 'call CTA' ),
			'schedule a call'                    => array( 2, 'call CTA' ),
			'free consultation'                  => array( 2, 'consultation pitch' ),
			'guest post'                         => array( 3, 'guest post pitch' ),
			'link building'                      => array( 3, 'link building pitch' ),
			'we noticed your website'          => array( 2, 'website pitch' ),
			'seo services'                       => array( 2, 'SEO services pitch' ),
			'rank better on google'              => array( 2, 'rank pitch' ),
		);

		foreach ( $patterns as $needle => $meta ) {
			if ( false !== strpos( $haystack, $needle ) ) {
				$score  += (int) $meta[0];
				$hits[] = (string) $meta[1];
			}
		}

		// Combo boost: sales CTA + SEO/marketing vocabulary.
		$has_seo_vocab = ( false !== strpos( $haystack, 'seo' ) || false !== strpos( $haystack, 'ranking' ) || false !== strpos( $haystack, 'keywords' ) );
		$has_sales_cta = ( false !== strpos( $haystack, 'reply' ) || false !== strpos( $haystack, 'let’s discuss' ) || false !== strpos( $haystack, "let's discuss" ) || false !== strpos( $haystack, 'contact us' ) );

		if ( $has_seo_vocab && $has_sales_cta ) {
			$score += 2;
			$hits[] = 'SEO + sales CTA';
		}

		$hits  = array_values( array_unique( $hits ) );
		$label = implode( ', ', array_slice( $hits, 0, 3 ) );

		return array(
			'score' => $score,
			'label' => $label,
		);
	}

	/**
	 * Count pipe-prefixed handle dump lines (common in social spam payloads).
	 *
	 * @param string $text Combined text.
	 *
	 * @return int
	 */
	private static function count_handle_dump_lines( string $text ): int {
		if ( '' === $text ) {
			return 0;
		}

		$count = preg_match_all( '/^\s*\|[a-z0-9_.\-\/]+\s*$/mi', $text );

		return false === $count ? 0 : $count;
	}
}

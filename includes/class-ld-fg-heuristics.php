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
	 * Minimum number of gibberish-looking text fields to treat as bot spam.
	 */
	public const GIBBERISH_FIELD_THRESHOLD = 2;

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
			'.monster',
			'.casino',
			'.onion',
			'graph.org',
			'dseo24.',
			'blacksprut',
			'bs2best.',
			'bs2web.',
			'blsp.at',
			'bchgames.',
			'bch-games.',
			'bch-casino.',
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

		if ( ! empty( $toggles['gibberish'] ) ) {
			$gibberish = self::score_gibberish_fields( $fields );

			if ( $gibberish['spam'] ) {
				return array(
					'verdict'    => 'spam',
					'confidence' => (float) $gibberish['confidence'],
					'reason'     => (string) $gibberish['reason'],
					'layer'      => 'heuristic',
					'matched'    => 'gibberish',
				);
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
	 * Detect random-keyboard / dotted-email bot fills across fields.
	 *
	 * @param array<string, mixed> $fields Field map.
	 *
	 * @return array{spam: bool, confidence: float, reason: string}
	 */
	private static function score_gibberish_fields( array $fields ): array {
		$clean = array(
			'spam'       => false,
			'confidence' => 0.0,
			'reason'     => '',
		);

		$gibberish_count = 0;
		$seen_values     = array();

		foreach ( $fields as $value ) {
			if ( ! is_string( $value ) ) {
				if ( is_array( $value ) ) {
					$value = implode( ' ', array_map( 'strval', $value ) );
				} else {
					continue;
				}
			}

			$value = trim( $value );
			if ( '' === $value ) {
				continue;
			}

			// Deduplicate composite name parts that repeat the full name.
			$norm = strtolower( preg_replace( '/\s+/', ' ', $value ) ?? $value );
			if ( isset( $seen_values[ $norm ] ) ) {
				continue;
			}
			$seen_values[ $norm ] = true;

			if ( self::is_dotted_spam_email( $value ) ) {
				return array(
					'spam'       => true,
					'confidence' => 0.96,
					'reason'     => __( 'Obfuscated / dotted spam email address detected.', 'lancedesk-form-guard' ),
				);
			}

			if ( self::value_looks_like_email( $value ) ) {
				continue;
			}

			if ( self::looks_like_gibberish( $value ) ) {
				++$gibberish_count;
			}
		}

		if ( $gibberish_count >= self::GIBBERISH_FIELD_THRESHOLD ) {
			return array(
				'spam'       => true,
				'confidence' => min( 0.97, 0.85 + ( 0.03 * $gibberish_count ) ),
				/* translators: %d: number of gibberish-looking fields. */
				'reason'     => sprintf( __( 'Random / gibberish field content detected (%d fields).', 'lancedesk-form-guard' ), $gibberish_count ),
			);
		}

		return $clean;
	}

	/**
	 * Emails like a.b.c.d.e88@gmail.com used by bots to evade filters.
	 *
	 * @param string $value Candidate value.
	 *
	 * @return bool
	 */
	private static function is_dotted_spam_email( string $value ): bool {
		if ( ! self::value_looks_like_email( $value ) ) {
			return false;
		}

		$local = strtolower( (string) strstr( $value, '@', true ) );
		if ( '' === $local ) {
			return false;
		}

		// Three or more single-char segments separated by dots, or many dots overall.
		if ( preg_match( '/^(?:[a-z0-9]\.){3,}[a-z0-9]+$/i', $local ) ) {
			return true;
		}

		$dot_count = substr_count( $local, '.' );
		if ( $dot_count >= 4 && strlen( $local ) <= 40 ) {
			return true;
		}

		return false;
	}

	/**
	 * Email check that works in WP and CLI harnesses.
	 *
	 * @param string $value Candidate value.
	 *
	 * @return bool
	 */
	private static function value_looks_like_email( string $value ): bool {
		if ( function_exists( 'is_email' ) ) {
			return (bool) is_email( $value );
		}

		return (bool) filter_var( $value, FILTER_VALIDATE_EMAIL );
	}

	/**
	 * Whether a field value looks like random keyboard spam rather than natural language.
	 *
	 * @param string $text Field value.
	 *
	 * @return bool
	 */
	private static function looks_like_gibberish( string $text ): bool {
		$text = trim( $text );
		$len  = strlen( $text );

		if ( $len < 6 ) {
			return false;
		}

		// Long unbroken mixed-case token (e.g. DYsbQojWeFeKANbmOgBS).
		if ( $len >= 12 && false === strpos( $text, ' ' ) && preg_match( '/^[A-Za-z0-9]+$/', $text ) ) {
			if ( preg_match( '/[a-z][A-Z]/', $text ) || preg_match( '/[A-Z]{2,}[a-z]/', $text ) ) {
				return true;
			}
		}

		$words = preg_split( '/\s+/u', $text );
		if ( ! is_array( $words ) || empty( $words ) ) {
			return false;
		}

		$gibberish_words = 0;
		$alpha_words     = 0;

		foreach ( $words as $word ) {
			$word = preg_replace( '/[^A-Za-z]/', '', (string) $word );
			if ( ! is_string( $word ) || strlen( $word ) < 5 ) {
				continue;
			}
			++$alpha_words;
			if ( self::word_is_gibberish( $word ) ) {
				++$gibberish_words;
			}
		}

		if ( $alpha_words < 1 ) {
			return false;
		}

		return ( $gibberish_words / $alpha_words ) >= 0.6;
	}

	/**
	 * Score a single alphabetic token for random-looking structure.
	 *
	 * @param string $word Alphabetic word.
	 *
	 * @return bool
	 */
	private static function word_is_gibberish( string $word ): bool {
		$lower = strtolower( $word );
		$len   = strlen( $lower );

		if ( $len < 5 ) {
			return false;
		}

		$vowels      = preg_match_all( '/[aeiou]/', $lower );
		$vowel_ratio = $vowels / $len;

		// Natural English is typically ~35–45% vowels; bot fills often sit much lower.
		if ( $vowel_ratio < 0.22 && $len >= 6 ) {
			return true;
		}

		// Five+ consonants in a row is rare in real names/messages.
		if ( preg_match( '/[bcdfghjklmnpqrstvwxyz]{5,}/', $lower ) ) {
			return true;
		}

		// CamelCase mid-token without spaces.
		if ( preg_match( '/[a-z][A-Z]/', $word ) && $len >= 8 ) {
			return true;
		}

		return false;
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
			'we noticed your website'            => array( 2, 'website pitch' ),
			'seo services'                       => array( 2, 'SEO services pitch' ),
			'rank better on google'              => array( 2, 'rank pitch' ),
			// Soft collaboration / link-exchange outreach.
			'came across your website'           => array( 2, 'website outreach' ),
			'content collaboration'              => array( 3, 'content collaboration' ),
			'link opportunit'                    => array( 3, 'link opportunity pitch' ),
			'link exchange'                      => array( 3, 'link exchange pitch' ),
			'both audiences'                     => array( 2, 'mutual audience pitch' ),
			'relevant websites'                  => array( 2, 'relevant websites pitch' ),
			'established websites'               => array( 2, 'site inventory pitch' ),
			'exploring opportunities'            => array( 1, 'opportunity pitch' ),
			'get in touch about'                 => array( 1, 'outreach CTA' ),
			'backlink'                           => array( 3, 'backlink pitch' ),
			'backlink collaboration'             => array( 3, 'backlink collaboration' ),
			'sponsored post'                     => array( 3, 'sponsored post pitch' ),
			'sponsored content'                  => array( 3, 'sponsored content pitch' ),
			'guest blogging'                     => array( 3, 'guest blogging pitch' ),
			'write for us'                       => array( 2, 'write-for-us pitch' ),
			// Soft SEO proposal pitches (corpus #1 / #5).
			'looking over your website'          => array( 2, 'website review pitch' ),
			'google rankings'                    => array( 2, 'Google rankings pitch' ),
			'seo strategy'                       => array( 3, 'SEO strategy pitch' ),
			'organic growth'                     => array( 2, 'organic growth pitch' ),
			'pricing proposal'                   => array( 2, 'pricing proposal' ),
			'higher-quality leads'               => array( 2, 'leads pitch' ),
			'higher quality leads'               => array( 2, 'leads pitch' ),
			'would you be open to reviewing'     => array( 2, 'review CTA' ),
			'thought you might be a good fit'    => array( 2, 'fit pitch' ),
			'no payment needed'                  => array( 2, 'free link pitch' ),
			'client sites'                       => array( 1, 'client sites pitch' ),
			'dr30'                               => array( 2, 'DR metrics pitch' ),
			// SEO tooling spam (Xrumer / GSA).
			'xrumer'                             => array( 3, 'Xrumer spam tool' ),
			'gsa search engine'                  => array( 3, 'GSA Ranker pitch' ),
			'search engine ranker'               => array( 3, 'SER spam tool' ),
			// Crypto / token credit scams.
			'tokens credited'                    => array( 3, 'token credit scam' ),
			'credited automatically to your balance' => array( 3, 'balance credit scam' ),
			'usd receive'                        => array( 2, 'USD receive scam' ),
			'pxqr'                               => array( 3, 'obfuscated token marker' ),
			// Darknet / mirror spam.
			'.onion'                             => array( 3, 'onion address' ),
			'даркнет'                            => array( 3, 'darknet pitch' ),
			'черный рынок'                       => array( 3, 'black market pitch' ),
			'фальшивых документов'               => array( 3, 'fake documents pitch' ),
			'кракен'                             => array( 2, 'Kraken darknet' ),
			'blacksprut'                         => array( 3, 'BlackSprut mirror' ),
			// Russian SEO spam (tehosmotr / inspection linkbait).
			'техосмотр'                          => array( 3, 'tehosmotr SEO spam' ),
			'еаисто'                             => array( 3, 'EAISTO SEO spam' ),
			'диагностическ'                      => array( 2, 'diagnostic card spam' ),
			'мрэо'                               => array( 2, 'MREO SEO spam' ),
			'постановки на учёт'                 => array( 2, 'registration SEO spam' ),
			'постановки на учет'                 => array( 2, 'registration SEO spam' ),
			// Misc commercial / product dump spam.
			'geltaxi'                            => array( 3, 'GelTaxi spam' ),
			'facade glazing'                     => array( 2, 'facade pitch' ),
			'translucent structures'             => array( 3, 'translucent structures pitch' ),
			'curtain wall'                       => array( 2, 'curtain wall pitch' ),
			'smartal'                            => array( 3, 'SMARTAL pitch' ),
			'wechselrichter'                     => array( 3, 'inverter product spam' ),
			'solar dieburg'                      => array( 3, 'solar product spam' ),
			'азартных игр'                       => array( 3, 'gambling pitch' ),
			'bc game'                            => array( 2, 'BC Game pitch' ),
			'онлайн-гемблинга'                   => array( 3, 'gambling pitch' ),
			'онлайн-гейминга'                    => array( 2, 'gaming pitch' ),
			// Vendor / agency contact-form solicitation (self-promo cards).
			'prefer email'                       => array( 2, 'prefer-email CTA' ),
			'reach us at'                        => array( 3, 'reach-us CTA' ),
			'contact us at'                      => array( 2, 'contact-us-at CTA' ),
			'visit our website'                  => array( 2, 'visit-website CTA' ),
			'visit our site'                     => array( 2, 'visit-website CTA' ),
			'check out our website'              => array( 2, 'visit-website CTA' ),
			'view our website'                   => array( 2, 'visit-website CTA' ),
			'see our portfolio'                  => array( 2, 'portfolio pitch' ),
			'our portfolio'                      => array( 1, 'portfolio pitch' ),
			'schedule a demo'                    => array( 2, 'demo CTA' ),
			'book a demo'                        => array( 2, 'demo CTA' ),
			'hire us'                            => array( 2, 'hire-us pitch' ),
			'hire our team'                      => array( 3, 'hire-team pitch' ),
			'our services include'               => array( 2, 'services catalog' ),
			'we offer services'                  => array( 2, 'services catalog' ),
			'we provide services'                => array( 2, 'services catalog' ),
			'ecommerce development'              => array( 3, 'ecommerce development pitch' ),
			'e-commerce development'             => array( 3, 'ecommerce development pitch' ),
			'e commerce development'             => array( 3, 'ecommerce development pitch' ),
			'web development services'           => array( 3, 'web development pitch' ),
			'website development'                => array( 2, 'website development pitch' ),
			'web design services'                => array( 2, 'web design pitch' ),
			'wordpress development'              => array( 2, 'WordPress development pitch' ),
			'shopify development'                => array( 3, 'Shopify development pitch' ),
			'magento development'                => array( 2, 'Magento development pitch' ),
			'woocommerce'                        => array( 1, 'WooCommerce pitch' ),
			'custom software development'        => array( 3, 'custom software pitch' ),
			'mobile app development'             => array( 2, 'app development pitch' ),
			'app development'                    => array( 2, 'app development pitch' ),
			'software development company'       => array( 3, 'software company pitch' ),
			'it outsourcing'                     => array( 3, 'IT outsourcing pitch' ),
			'offshore development'               => array( 3, 'offshore development pitch' ),
			'dedicated developers'               => array( 2, 'dedicated developers pitch' ),
			'digital marketing services'         => array( 3, 'digital marketing pitch' ),
			'ppc management'                     => array( 2, 'PPC pitch' ),
			'social media marketing'             => array( 2, 'SMM pitch' ),
			'white label'                        => array( 2, 'white-label pitch' ),
			'flagged content'                    => array( 2, 'flagged-content spam marker' ),
		);

		foreach ( $patterns as $needle => $meta ) {
			if ( false !== strpos( $haystack, $needle ) ) {
				$score  += (int) $meta[0];
				$hits[] = (string) $meta[1];
			}
		}

		// Combo boost: sales CTA + SEO/marketing or link-building vocabulary.
		$has_seo_vocab = (
			false !== strpos( $haystack, 'seo' )
			|| false !== strpos( $haystack, 'ranking' )
			|| false !== strpos( $haystack, 'keywords' )
			|| false !== strpos( $haystack, 'backlink' )
			|| false !== strpos( $haystack, 'link opportunit' )
			|| false !== strpos( $haystack, 'content collaboration' )
			|| false !== strpos( $haystack, 'organic growth' )
			|| false !== strpos( $haystack, 'google rankings' )
		);
		$has_sales_cta = (
			false !== strpos( $haystack, 'reply' )
			|| false !== strpos( $haystack, 'let’s discuss' )
			|| false !== strpos( $haystack, "let's discuss" )
			|| false !== strpos( $haystack, 'contact us' )
			|| false !== strpos( $haystack, 'get in touch' )
			|| false !== strpos( $haystack, 'if this is of interest' )
			|| false !== strpos( $haystack, 'happy to share' )
			|| false !== strpos( $haystack, 'would you be open' )
			|| false !== strpos( $haystack, 'let me know if' )
			|| false !== strpos( $haystack, 'thought you might' )
		);

		if ( $has_seo_vocab && $has_sales_cta ) {
			$score += 2;
			$hits[] = 'SEO + sales CTA';
		}

		$vendor = self::score_vendor_solicitation_structure( $haystack );
		$score += (int) $vendor['score'];
		if ( ! empty( $vendor['hits'] ) ) {
			$hits = array_merge( $hits, $vendor['hits'] );
		}

		$hits  = array_values( array_unique( $hits ) );
		$label = implode( ', ', array_slice( $hits, 0, 3 ) );

		return array(
			'score' => $score,
			'label' => $label,
		);
	}

	/**
	 * Structural vendor solicitation: contact-card messages, sales mailboxes, brand/domain glue.
	 *
	 * Catches agency spam that is mostly “our URL + reach us at info@…” rather than SEO prose.
	 *
	 * @param string $haystack Lowercased combined submission text.
	 *
	 * @return array{score: int, hits: array<int, string>}
	 */
	private static function score_vendor_solicitation_structure( string $haystack ): array {
		$score = 0;
		$hits  = array();

		if ( '' === $haystack ) {
			return array(
				'score' => 0,
				'hits'  => array(),
			);
		}

		$has_url = self::count_urls( $haystack ) > 0;

		$has_contact_card_cta = (
			false !== strpos( $haystack, 'prefer email' )
			|| false !== strpos( $haystack, 'reach us at' )
			|| false !== strpos( $haystack, 'contact us at' )
			|| false !== strpos( $haystack, 'visit our website' )
			|| false !== strpos( $haystack, 'visit our site' )
			|| false !== strpos( $haystack, 'check out our website' )
			|| false !== strpos( $haystack, 'view our website' )
			|| false !== strpos( $haystack, 'our contact page' )
			|| false !== strpos( $haystack, 'get a quote' )
			|| false !== strpos( $haystack, 'request a quote' )
		);

		$has_service_pitch = (
			false !== strpos( $haystack, 'ecommerce' )
			|| false !== strpos( $haystack, 'e-commerce' )
			|| false !== strpos( $haystack, 'web development' )
			|| false !== strpos( $haystack, 'website development' )
			|| false !== strpos( $haystack, 'app development' )
			|| false !== strpos( $haystack, 'software development' )
			|| false !== strpos( $haystack, 'digital marketing' )
			|| false !== strpos( $haystack, 'shopify' )
			|| false !== strpos( $haystack, 'magento' )
			|| false !== strpos( $haystack, 'wordpress development' )
			|| false !== strpos( $haystack, 'web design' )
			|| false !== strpos( $haystack, 'it outsourcing' )
			|| false !== strpos( $haystack, 'offshore development' )
			|| false !== strpos( $haystack, 'dedicated developer' )
			|| false !== strpos( $haystack, 'white label' )
			|| false !== strpos( $haystack, 'ppc' )
			|| false !== strpos( $haystack, 'social media marketing' )
		);

		$has_sales_mailbox = (bool) preg_match(
			'/\b(info|sales|hello|hi|enquiry|inquiry|support|marketing|business|admin|office|contact|partners)@[a-z0-9.\-]+/i',
			$haystack
		);

		if ( $has_url && $has_contact_card_cta ) {
			$score += 3;
			$hits[] = 'URL + contact-card CTA';
		}

		if ( $has_url && $has_service_pitch ) {
			$score += 2;
			$hits[] = 'URL + service pitch';
		}

		if ( $has_sales_mailbox && $has_url ) {
			$score += 2;
			$hits[] = 'sales mailbox + URL';
		}

		if ( $has_sales_mailbox && $has_service_pitch ) {
			$score += 2;
			$hits[] = 'sales mailbox + service pitch';
		}

		if ( $has_contact_card_cta && $has_service_pitch ) {
			$score += 2;
			$hits[] = 'contact CTA + service pitch';
		}

		// Brand-like name glued to the vendor domain (e.g. recodecomsymn + recodecommerce.com).
		if ( preg_match_all( '/\b[a-z0-9._%+\-]+@([a-z0-9\-]+)\.[a-z]{2,}\b/i', $haystack, $matches ) ) {
			$compact = preg_replace( '/\s+/', '', $haystack );
			$compact = is_string( $compact ) ? $compact : $haystack;

			foreach ( $matches[1] as $domain_label ) {
				$domain_label = strtolower( (string) $domain_label );
				if ( strlen( $domain_label ) < 6 ) {
					continue;
				}

				$stem = substr( $domain_label, 0, min( 10, strlen( $domain_label ) ) );
				if ( strlen( $stem ) < 6 ) {
					continue;
				}

				if ( false !== strpos( $compact, $stem ) && ( $has_url || $has_service_pitch || $has_contact_card_cta ) ) {
					$score += 2;
					$hits[] = 'brand name matches vendor domain';
					break;
				}
			}
		}

		return array(
			'score' => $score,
			'hits'  => $hits,
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

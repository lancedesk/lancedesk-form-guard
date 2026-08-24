<?php
/**
 * Run representative spam samples against Layer 1 heuristics (CLI).
 *
 * Usage: php tools/spam-sample-check.php
 *
 * @package LanceDesk_Form_Guard
 */

declare( strict_types=1 );

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string       $text   Text.
	 * @param string|null  $domain Domain.
	 * @return string
	 */
	function __( string $text, $domain = null ): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

require_once dirname( __DIR__ ) . '/includes/class-ld-fg-heuristics.php';

/**
 * Print one escaped CLI line.
 *
 * @param string $line Line text.
 * @return void
 */
function ld_fg_cli_line( string $line ): void {
	echo esc_html( $line ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html() applied above.
}

/**
 * Print escaped sprintf output for CLI.
 *
 * @param string        $format Format string.
 * @param mixed         ...$args Arguments.
 * @return void
 */
function ld_fg_cli_printf( string $format, ...$args ): void {
	echo esc_html( sprintf( $format, ...$args ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html() applied above.
}

$ld_fg_settings = array(
	'heuristics' => array(
		'bbcode_url'          => 1,
		'url_count'           => 1,
		'honeypot'            => 1,
		'too_fast'            => 1,
		'suspicious_links'    => 1,
		'commercial_outreach' => 1,
	),
);

$ld_fg_samples = array(
	array(
		'label'   => 'Classic BBCode + link stuffing (user test sample)',
		'expect'  => 'block',
		'fields'  => array(
			'name'    => 'Best SEO Ranking 2026!!!',
			'email'   => 'promo@cheap-meds.biz',
			'message' => "Hello dear webmasters!!!\n\nI can boost your Google ranking to #1 in 24 hours. Visit http://spam-seo-rank.example/buy http://spam-seo-rank.example/cheap http://spam-seo-rank.example/now for 90% OFF viagra cialis casino loans.\n\n[url=http://spam-seo-rank.example]Click here[/url] [url=http://spam-seo-rank.example/2]More info[/url]\n\nTelegram me ASAP for bulk backlinks. Limited offer only today!!!",
		),
	),
	array(
		'label'   => 'SEO cold outreach audit pitch',
		'expect'  => 'block',
		'fields'  => array(
			'name'    => 'Mike from SEO Knights',
			'email'   => 'mike@seoknights-agency.com',
			'message' => "Hi,\n\nWe analyzed your website and found critical SEO issues. Your site is currently leaving money on the table with poorly optimized meta tags and you're not targeting the right keywords.\n\nOur digital marketing agency can help you rank with a full-scale SEO keyword strategy. Reply to this email for a free SEO audit.\n\nBest regards",
		),
	),
	array(
		'label'   => 'Shortener link spam (bit.ly)',
		'expect'  => 'block',
		'fields'  => array(
			// phpcs:ignore PluginCheck.CodeAnalysis.ShortURL.Found -- Intentional spam sample URL for heuristic unit test.
			'message' => 'Limited offer — see https://bit.ly/seo-deal-2026 today only.',
		),
	),
	array(
		'label'   => 'Image host spam (imgbb)',
		'expect'  => 'block',
		'fields'  => array(
			'message' => 'Portfolio here https://i.imgbb.com/abc123 and contact me for guest posts.',
		),
	),
	array(
		'label'   => 'Social handle dump',
		'expect'  => 'block',
		'fields'  => array(
			'message' => "|spamuser1\n|spamuser2\n|spamuser3\n|spamuser4\n|spamuser5",
		),
	),
	array(
		'label'   => 'Honeypot filled (bot)',
		'expect'  => 'block',
		'fields'  => array( 'message' => 'Normal looking message.' ),
		'extra'   => array( 'honeypot' => 'bot-filled-this' ),
	),
	array(
		'label'   => 'Too-fast submit (bot)',
		'expect'  => 'block',
		'fields'  => array( 'message' => 'Quick submit.' ),
		'extra'   => array( 'render_time' => 1000, 'submit_time' => 1001 ),
	),
	array(
		'label'   => 'Subtle generic outreach (2 URLs max — under link limit)',
		'expect'  => 'block',
		'fields'  => array(
			'name'    => 'Sarah',
			'email'   => 'sarah@marketing-firm.io',
			'message' => "Hello! I hope you're doing well. I'd love to discuss how we can help grow your business online. Would you be open to a quick call this week?",
		),
	),
	array(
		'label'   => 'Polite SEO mention without strong patterns',
		'expect'  => 'block',
		'fields'  => array(
			'message' => "Hi, we noticed your website could rank better on Google. We offer SEO services. Let's discuss if you're interested.",
		),
	),
	array(
		'label'   => 'Soft content collaboration / link opportunity pitch',
		'expect'  => 'block',
		'fields'  => array(
			'message' => 'I recently came across your website and wanted to get in touch about a possible content collaboration. We’re currently connecting with relevant websites and exploring opportunities that could be useful for both audiences.We also have a few established websites available for relevant content or link opportunities. Happy to share some examples if this is of interest.',
		),
	),
	array(
		'label'   => 'Legitimate church inquiry',
		'expect'  => 'allow',
		'fields'  => array(
			'name'    => 'John Smith',
			'email'   => 'john.smith@gmail.com',
			'message' => "Hi, I'd like to know your service times and whether you have a newcomers class. Thank you!",
		),
	),
	array(
		'label'   => 'Prayer request (real use case)',
		'expect'  => 'allow',
		'fields'  => array(
			'name'    => 'Mary',
			'email'   => 'mary@example.org',
			'message' => 'Please pray for my family during a difficult time. God bless.',
		),
	),
	array(
		'label'   => 'Enthusiastic but genuine volunteer note',
		'expect'  => 'allow',
		'fields'  => array(
			'message' => "I'm so excited to volunteer with your youth program! I've been looking for a church home and would love to help however I can.",
		),
	),
	array(
		'label'   => 'Fake address / suspicious format (AI-layer sample from logs)',
		'expect'  => 'allow_or_ai',
		'fields'  => array(
			'name'    => 'John Doe',
			'email'   => 'not-an-email',
			'message' => '123 Main St, Apt ###, Cityville 00000. Please send your media kit.',
		),
	),
	array(
		'label'   => 'Two links only (at threshold — should pass heuristics)',
		'expect'  => 'allow',
		'fields'  => array(
			'message' => 'Here are two references: https://example.org/page1 and https://example.org/page2 — thanks!',
		),
	),
	array(
		'label'   => 'Three links (over threshold)',
		'expect'  => 'block',
		'fields'  => array(
			'message' => 'Links: https://a.com https://b.com https://c.com',
		),
	),
);

ld_fg_cli_line( 'LanceDesk Form Guard — Layer 1 heuristic sample check' );
ld_fg_cli_line( str_repeat( '-', 72 ) );

$ld_fg_blocked = 0;
$ld_fg_allowed = 0;
$ld_fg_notes   = array();

foreach ( $ld_fg_samples as $ld_fg_sample ) {
	$ld_fg_payload = array_merge(
		array(
			'fields'      => $ld_fg_sample['fields'],
			'honeypot'    => '',
			'render_time' => 0,
			'submit_time' => time(),
		),
		$ld_fg_sample['extra'] ?? array()
	);

	$ld_fg_result  = LD_FG_Heuristics::evaluate( $ld_fg_payload, $ld_fg_settings );
	$ld_fg_is_spam = ( 'spam' === $ld_fg_result['verdict'] );

	if ( $ld_fg_is_spam ) {
		++$ld_fg_blocked;
	} else {
		++$ld_fg_allowed;
	}

	$ld_fg_expect = $ld_fg_sample['expect'];
	$ld_fg_status = 'OK';

	if ( 'block' === $ld_fg_expect && ! $ld_fg_is_spam ) {
		$ld_fg_status  = 'MISS — expected block';
		$ld_fg_notes[] = $ld_fg_sample['label'];
	} elseif ( 'allow' === $ld_fg_expect && $ld_fg_is_spam ) {
		$ld_fg_status  = 'FALSE POSITIVE';
		$ld_fg_notes[] = $ld_fg_sample['label'];
	}

	$ld_fg_icon = $ld_fg_is_spam ? 'BLOCKED' : 'ALLOWED';
	ld_fg_cli_printf(
		"[%s] %s\n    matched: %s | confidence: %s | reason: %s\n    expectation: %s → %s\n\n",
		$ld_fg_icon,
		$ld_fg_sample['label'],
		$ld_fg_result['matched'] ? (string) $ld_fg_result['matched'] : '—',
		(string) $ld_fg_result['confidence'],
		$ld_fg_result['reason'] ? (string) $ld_fg_result['reason'] : '(none)',
		$ld_fg_expect,
		$ld_fg_status
	);
}

ld_fg_cli_line( str_repeat( '-', 72 ) );
ld_fg_cli_printf(
	"Heuristics only: %d blocked, %d allowed (of %d samples)\n",
	$ld_fg_blocked,
	$ld_fg_allowed,
	count( $ld_fg_samples )
);

if ( ! empty( $ld_fg_notes ) ) {
	ld_fg_cli_line( "\nAttention:" );
	foreach ( $ld_fg_notes as $ld_fg_note ) {
		ld_fg_cli_line( ' - ' . $ld_fg_note );
	}
}

ld_fg_cli_line( "\nNote: Samples marked allow_or_ai need Layer 2 (AI enabled) or may slip with heuristics-only mode." );

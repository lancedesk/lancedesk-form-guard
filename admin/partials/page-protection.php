<?php
/**
 * Protection rules page content.
 *
 * @package LanceDesk_Form_Guard
 *
 * @var array<string,mixed> $ld_fg_settings Plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heuristics = isset( $ld_fg_settings['heuristics'] ) && is_array( $ld_fg_settings['heuristics'] )
	? $ld_fg_settings['heuristics']
	: array();
$default_action = (string) ( $ld_fg_settings['default_action'] ?? 'silent_discard' );
$ai_fail_mode   = (string) ( $ld_fg_settings['ai_fail_mode'] ?? 'fail_open' );
$ai_enabled     = ! empty( $ld_fg_settings['ai_enabled'] );
$block_pct      = (int) round( (float) ( $ld_fg_settings['confidence_block'] ?? 0.9 ) * 100 );
?>
<div class="flex flex-col w-full ld-fg-protection-page">
<div class="flex justify-between items-center gap-4 mb-6">
<h1 class="font-headline-lg text-headline-lg text-on-surface m-0"><?php esc_html_e( 'Protection Rules', 'lancedesk-form-guard' ); ?></h1>
<button type="button" class="ld-fg-save-protection bg-primary hover:bg-primary/90 text-on-primary font-label-md text-label-md px-6 py-3 rounded-lg flex items-center gap-2 transition-colors shadow-sm whitespace-nowrap">
<span class="ld-fg-icon material-symbols-outlined text-[18px]">save</span>
<?php esc_html_e( 'Save Changes', 'lancedesk-form-guard' ); ?>
</button>
</div>
<form id="ld-fg-protection-form" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
<div class="lg:col-span-5 space-y-6">
<div class="bg-surface-container rounded-xl shadow-sm p-6 relative overflow-hidden group hover:shadow-md transition-shadow">
<div class="flex items-center gap-3 mb-6 relative">
<div class="w-10 h-10 rounded-full bg-primary-container/20 flex items-center justify-center text-primary">
<span class="ld-fg-icon material-symbols-outlined">rule</span>
</div>
<div>
<h2 class="font-headline-md text-headline-md text-on-surface"><?php esc_html_e( 'Default Action', 'lancedesk-form-guard' ); ?></h2>
<p class="font-body-sm text-body-sm text-on-surface-variant"><?php esc_html_e( 'Behavior for positively identified threats.', 'lancedesk-form-guard' ); ?></p>
</div>
</div>
<div class="space-y-3 relative">
<?php
$actions = array(
	'silent_discard' => array( __( 'Silent Discard', 'lancedesk-form-guard' ), __( 'Drop malicious payloads immediately without user feedback.', 'lancedesk-form-guard' ) ),
	'soft_fail'      => array( __( 'Soft Fail', 'lancedesk-form-guard' ), __( 'Reject the submission with a generic "try again later" message.', 'lancedesk-form-guard' ) ),
	'quarantine'     => array( __( 'Quarantine', 'lancedesk-form-guard' ), __( 'Store the submission in logs for manual review before final rejection.', 'lancedesk-form-guard' ) ),
);
foreach ( $actions as $value => $labels ) :
	?>
<label class="flex items-start gap-3 p-4 rounded-lg cursor-pointer hover:bg-surface transition-colors border border-outline-variant/20 bg-surface/50">
<div class="flex items-center h-5 mt-0.5">
<input class="w-4 h-4 text-primary bg-surface border-outline focus:ring-primary focus:ring-2" name="default_action" type="radio" value="<?php echo esc_attr( $value ); ?>" <?php checked( $default_action, $value ); ?> />
</div>
<div class="flex-1">
<span class="block font-label-md text-label-md text-on-surface mb-0.5"><?php echo esc_html( $labels[0] ); ?></span>
<span class="block font-body-sm text-body-sm text-on-surface-variant"><?php echo esc_html( $labels[1] ); ?></span>
</div>
</label>
<?php endforeach; ?>
</div>
</div>
<div class="bg-surface-container rounded-xl shadow-sm p-6">
<div class="flex items-center gap-3 mb-6">
<div class="w-10 h-10 rounded-full bg-error-container/20 flex items-center justify-center text-error">
<span class="ld-fg-icon material-symbols-outlined">power_off</span>
</div>
<div>
<h2 class="font-headline-md text-headline-md text-on-surface"><?php esc_html_e( 'Provider Fallback', 'lancedesk-form-guard' ); ?></h2>
<p class="font-body-sm text-body-sm text-on-surface-variant"><?php esc_html_e( 'Action if AI analysis times out or fails.', 'lancedesk-form-guard' ); ?></p>
</div>
</div>
<div class="grid grid-cols-2 gap-4">
<label class="relative flex flex-col p-4 cursor-pointer rounded-lg border-2 <?php echo 'fail_open' === $ai_fail_mode ? 'border-primary bg-primary/5' : 'border-transparent bg-surface'; ?> transition-all text-center group">
<input class="sr-only" name="ai_fail_mode" type="radio" value="fail_open" <?php checked( $ai_fail_mode, 'fail_open' ); ?> />
<span class="ld-fg-icon material-symbols-outlined text-[24px] text-primary mb-2 mx-auto">lock_open</span>
<span class="font-label-md text-label-md text-on-surface"><?php esc_html_e( 'Fail Open', 'lancedesk-form-guard' ); ?></span>
<span class="font-body-sm text-body-sm text-on-surface-variant mt-1 text-[11px] leading-tight"><?php esc_html_e( 'Allow submission if rules pass', 'lancedesk-form-guard' ); ?></span>
</label>
<label class="relative flex flex-col p-4 cursor-pointer rounded-lg border-2 <?php echo 'fail_closed' === $ai_fail_mode ? 'border-primary bg-primary/5' : 'border-transparent bg-surface'; ?> transition-all text-center group">
<input class="sr-only" name="ai_fail_mode" type="radio" value="fail_closed" <?php checked( $ai_fail_mode, 'fail_closed' ); ?> />
<span class="ld-fg-icon material-symbols-outlined text-[24px] text-on-surface-variant mb-2 mx-auto">lock</span>
<span class="font-label-md text-label-md text-on-surface"><?php esc_html_e( 'Fail Closed', 'lancedesk-form-guard' ); ?></span>
<span class="font-body-sm text-body-sm text-on-surface-variant mt-1 text-[11px] leading-tight"><?php esc_html_e( 'Block submission entirely', 'lancedesk-form-guard' ); ?></span>
</label>
</div>
</div>
</div>
<div class="lg:col-span-7 space-y-6">
<div class="bg-surface rounded-xl shadow-sm border border-outline-variant/30 overflow-hidden">
<div class="px-6 py-5 border-b border-outline-variant/20 bg-surface-container-low flex justify-between items-center">
<div class="flex items-center gap-3">
<span class="ld-fg-icon material-symbols-outlined text-secondary">filter_alt</span>
<h2 class="font-headline-md text-headline-md text-on-surface"><?php esc_html_e( 'Layer 1: Deterministic Rules', 'lancedesk-form-guard' ); ?></h2>
</div>
<span class="bg-secondary-container text-on-secondary-container px-2 py-1 rounded text-[11px] font-bold tracking-wide uppercase"><?php esc_html_e( 'Fast Path', 'lancedesk-form-guard' ); ?></span>
</div>
<ul class="divide-y divide-outline-variant/10">
<?php
$rules = array(
	'honeypot'   => array( __( 'Invisible Honeypot Fields', 'lancedesk-form-guard' ), __( 'Injects a hidden field on protected forms. Bots that fill it are blocked instantly.', 'lancedesk-form-guard' ) ),
	'too_fast'   => array( __( 'Time-to-Submit Heuristic', 'lancedesk-form-guard' ), __( 'Records when the form was shown and blocks submissions faster than ~3 seconds.', 'lancedesk-form-guard' ) ),
	'url_count'  => array( __( 'URL Density Limit', 'lancedesk-form-guard' ), __( 'Block text areas containing an excessive ratio of hyperlinks.', 'lancedesk-form-guard' ) ),
	'bbcode_url' => array( __( 'BBCode / Markdown Injection', 'lancedesk-form-guard' ), __( 'Reject standard forum markup often used by spam networks.', 'lancedesk-form-guard' ) ),
);
foreach ( $rules as $key => $labels ) :
	$on = ! empty( $heuristics[ $key ] );
	?>
<li class="flex items-center justify-between p-4 hover:bg-surface-bright transition-colors">
<div class="flex-1 pr-4">
<p class="font-label-md text-label-md text-on-surface"><?php echo esc_html( $labels[0] ); ?></p>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5"><?php echo esc_html( $labels[1] ); ?></p>
</div>
<label class="relative inline-flex items-center cursor-pointer">
<input class="ld-fg-heuristic sr-only peer" name="heuristics[<?php echo esc_attr( $key ); ?>]" type="checkbox" value="1" <?php checked( $on ); ?> />
<div class="w-11 h-6 bg-surface-variant rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
</label>
</li>
<?php endforeach; ?>
</ul>
</div>
<div class="bg-surface rounded-xl shadow-md border border-outline-variant/30 overflow-hidden relative">
<div class="px-6 py-5 border-b border-outline-variant/20 bg-surface/80 backdrop-blur-sm flex justify-between items-start relative z-10">
<div>
<div class="flex items-center gap-3 mb-1">
<span class="ld-fg-icon material-symbols-outlined text-tertiary">smart_toy</span>
<h2 class="font-headline-md text-headline-md text-on-surface"><?php esc_html_e( 'Layer 2: AI Semantic Analysis', 'lancedesk-form-guard' ); ?></h2>
</div>
<p class="font-body-sm text-body-sm text-on-surface-variant"><?php esc_html_e( 'Deep inspection for contextually inappropriate or highly evasive spam.', 'lancedesk-form-guard' ); ?></p>
</div>
<label class="relative inline-flex items-center cursor-pointer mt-1">
<input class="ld-fg-ai-enabled sr-only peer" name="ai_enabled" type="checkbox" value="1" <?php checked( $ai_enabled ); ?> />
<div class="w-11 h-6 bg-surface-variant rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-tertiary"></div>
</label>
</div>
<div class="p-6 relative z-10 bg-surface/40">
<div>
<div class="flex justify-between items-end mb-4">
<div>
<p class="font-label-md text-label-md text-on-surface"><?php esc_html_e( 'Confidence Threshold', 'lancedesk-form-guard' ); ?></p>
<p class="font-body-sm text-body-sm text-on-surface-variant text-[12px] mt-0.5"><?php esc_html_e( 'Required AI certainty before rejecting payload.', 'lancedesk-form-guard' ); ?></p>
</div>
<span class="font-headline-md text-headline-md text-tertiary tabular-nums" id="ld-fg-threshold-val"><?php echo esc_html( (string) $block_pct ); ?>%</span>
</div>
<div class="relative w-full h-2 bg-surface-container-highest rounded-full overflow-hidden">
<div class="absolute top-0 left-0 h-full bg-tertiary rounded-full ld-fg-threshold-bar" style="width: <?php echo esc_attr( (string) $block_pct ); ?>%;"></div>
<input class="ld-fg-confidence-block absolute top-0 left-0 w-full h-full opacity-0 cursor-pointer" name="confidence_block" type="range" min="50" max="99" value="<?php echo esc_attr( (string) $block_pct ); ?>" />
</div>
</div>
</div>
</div>
</div>
</form>
</div>

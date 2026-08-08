<?php
/**
 * Dashboard page content.
 *
 * @package LanceDesk_Form_Guard
 *
 * @var array<string,mixed> $ld_fg_settings Plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$stats         = LD_FG_Logger::stats_7d( 7 );
$top_reasons   = LD_FG_Logger::top_reasons( 7, 4 );
$recent_logs   = LD_FG_Logger::query_recent( 4, array() );
$chart_data    = LD_FG_Logger::daily_chart_data( 7 );
$max_chart     = 1;

foreach ( $chart_data as $day ) {
	$max_chart = max( $max_chart, (int) $day['heuristic'] + (int) $day['ai'] );
}

// Setup wizard only until dismissed — or until all steps are complete (then persist dismiss).
$plugin         = LD_FG_Plugin::instance();
$has_adapter    = ! empty( $plugin->get_available_adapters() );
$has_protected  = ! empty( $ld_fg_settings['protected_forms'] );
$has_provider   = false;
$has_test       = LD_FG_Logger::has_any();

foreach ( array( 'groq', 'openai', 'anthropic' ) as $pid ) {
	if ( ! empty( $ld_fg_settings['providers'][ $pid ]['api_key'] ) ) {
		$has_provider = true;
		break;
	}
}

$setup_complete = $has_adapter && $has_provider && $has_protected && $has_test;

if ( $setup_complete && empty( $ld_fg_settings['setup_dismissed'] ) ) {
	LD_FG_Settings::update( array( 'setup_dismissed' => 1 ) );
	$ld_fg_settings['setup_dismissed'] = 1;
}

$show_setup = empty( $ld_fg_settings['setup_dismissed'] );

if ( $show_setup ) {
	require LD_FG_PLUGIN_DIR . 'admin/partials/part-setup-checklist.php';
	return;
}
?>
<div class="flex flex-col w-full gap-5 font-body-lg text-on-surface">
<div class="grid grid-cols-1 md:grid-cols-4 gap-5">
<div class="bg-surface-container-lowest rounded-lg p-6 flex flex-col justify-between shadow-sm border border-outline-variant/30">
<div class="flex items-center justify-between mb-2">
<span class="text-body-sm font-label-md text-on-surface-variant font-medium"><?php esc_html_e( 'Blocked (7d)', 'lancedesk-form-guard' ); ?></span>
<span class="ld-fg-icon material-symbols-outlined text-error text-[20px]" style="font-variation-settings: 'FILL' 1;">gpp_bad</span>
</div>
<div class="flex items-baseline gap-2">
<span class="text-hero-title font-hero-title text-on-surface"><?php echo esc_html( number_format_i18n( (int) $stats['blocked'] ) ); ?></span>
</div>
</div>
<div class="bg-surface-container-lowest rounded-lg p-6 flex flex-col justify-between shadow-sm border border-outline-variant/30">
<div class="flex items-center justify-between mb-2">
<span class="text-body-sm font-label-md text-on-surface-variant font-medium"><?php esc_html_e( 'Allowed (7d)', 'lancedesk-form-guard' ); ?></span>
<span class="ld-fg-icon material-symbols-outlined text-[#46b450] text-[20px]" style="font-variation-settings: 'FILL' 1;">check_circle</span>
</div>
<div class="flex items-baseline gap-2">
<span class="text-hero-title font-hero-title text-on-surface"><?php echo esc_html( number_format_i18n( (int) $stats['allowed'] ) ); ?></span>
</div>
</div>
<div class="bg-surface-container-lowest rounded-lg p-6 flex flex-col justify-between shadow-sm border border-outline-variant/30">
<div class="flex items-center justify-between mb-2">
<span class="text-body-sm font-label-md text-on-surface-variant font-medium"><?php esc_html_e( 'Quarantined', 'lancedesk-form-guard' ); ?></span>
<span class="ld-fg-icon material-symbols-outlined text-[#dba617] text-[20px]" style="font-variation-settings: 'FILL' 1;">inventory_2</span>
</div>
<div class="flex items-baseline gap-2">
<span class="text-hero-title font-hero-title text-on-surface"><?php echo esc_html( number_format_i18n( (int) $stats['quarantined'] ) ); ?></span>
<span class="text-body-sm font-body-sm text-on-surface-variant"><?php esc_html_e( 'requires review', 'lancedesk-form-guard' ); ?></span>
</div>
</div>
<div class="bg-surface-container-lowest rounded-lg p-6 flex flex-col justify-between shadow-sm border border-outline-variant/30">
<div class="flex items-center justify-between mb-2">
<span class="text-body-sm font-label-md text-on-surface-variant font-medium"><?php esc_html_e( 'AI Calls Saved', 'lancedesk-form-guard' ); ?></span>
<span class="ld-fg-icon material-symbols-outlined text-primary text-[20px]" style="font-variation-settings: 'FILL' 1;">smart_toy</span>
</div>
<div class="flex items-baseline gap-2">
<span class="text-hero-title font-hero-title text-on-surface"><?php echo esc_html( number_format_i18n( (int) $stats['heuristic_saved'] ) ); ?></span>
<span class="text-body-sm font-body-sm text-on-surface-variant"><?php esc_html_e( 'by heuristics', 'lancedesk-form-guard' ); ?></span>
</div>
</div>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
<div class="lg:col-span-2 bg-surface-container-lowest rounded-lg shadow-sm border border-outline-variant/30 p-6 flex flex-col">
<div class="flex items-center justify-between mb-6">
<h2 class="text-headline-md font-headline-md text-on-surface"><?php esc_html_e( '7-Day Block Activity', 'lancedesk-form-guard' ); ?></h2>
<div class="flex gap-4">
<div class="flex items-center gap-1.5 text-body-sm font-body-sm text-on-surface-variant">
<div class="w-3 h-3 rounded bg-primary"></div> <?php esc_html_e( 'Heuristic Blocks', 'lancedesk-form-guard' ); ?>
</div>
<div class="flex items-center gap-1.5 text-body-sm font-body-sm text-on-surface-variant">
<div class="w-3 h-3 rounded bg-secondary-container"></div> <?php esc_html_e( 'AI Blocks', 'lancedesk-form-guard' ); ?>
</div>
</div>
</div>
<div class="flex-1 min-h-[250px] relative w-full">
<canvas id="ld-fg-block-chart" height="250" aria-label="<?php esc_attr_e( '7-day block activity chart', 'lancedesk-form-guard' ); ?>"></canvas>
</div>
</div>
<div class="bg-surface-container-lowest rounded-lg shadow-sm border border-outline-variant/30 p-6 flex flex-col">
<h2 class="text-headline-md font-headline-md text-on-surface mb-6"><?php esc_html_e( 'Top Block Reasons', 'lancedesk-form-guard' ); ?></h2>
<div class="flex flex-col gap-5 flex-1 justify-center">
<?php if ( empty( $top_reasons ) ) : ?>
<p class="text-body-sm text-on-surface-variant"><?php esc_html_e( 'No block reasons recorded yet.', 'lancedesk-form-guard' ); ?></p>
<?php else : ?>
<?php
$top_max = max( 1, (int) ( $top_reasons[0]['count'] ?? 1 ) );
foreach ( $top_reasons as $reason_row ) :
	$pct = min( 100, (int) round( ( (int) $reason_row['count'] / $top_max ) * 100 ) );
	?>
<div>
<div class="flex justify-between text-body-sm font-body-sm mb-1.5">
<span class="text-on-surface font-medium truncate pr-2"><?php echo esc_html( $reason_row['reason'] ); ?></span>
<span class="text-on-surface-variant font-label-md"><?php echo esc_html( (string) $reason_row['count'] ); ?></span>
</div>
<div class="w-full h-2 bg-surface-variant rounded-full overflow-hidden">
<div class="h-full bg-primary rounded-full" style="width: <?php echo esc_attr( (string) $pct ); ?>%;"></div>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</div>
<div class="bg-surface-container-lowest rounded-lg shadow-sm border border-outline-variant/30 flex flex-col overflow-hidden">
<div class="p-6 border-b border-surface-variant flex justify-between items-center bg-surface/30">
<h2 class="text-headline-md font-headline-md text-on-surface"><?php esc_html_e( 'Recent Blocks', 'lancedesk-form-guard' ); ?></h2>
<a class="text-body-sm font-label-md text-primary hover:text-primary-container transition-colors" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-logs' ) ); ?>"><?php esc_html_e( 'View All Logs', 'lancedesk-form-guard' ); ?></a>
</div>
<div class="w-full overflow-x-auto">
<table class="w-full text-left border-collapse min-w-[800px]">
<thead>
<tr class="border-b border-surface-variant bg-surface-container-low text-body-sm font-label-md text-on-surface-variant">
<th class="py-3 px-6 font-medium w-[120px]"><?php esc_html_e( 'Time', 'lancedesk-form-guard' ); ?></th>
<th class="py-3 px-6 font-medium"><?php esc_html_e( 'Form Target', 'lancedesk-form-guard' ); ?></th>
<th class="py-3 px-6 font-medium w-[100px]"><?php esc_html_e( 'Layer', 'lancedesk-form-guard' ); ?></th>
<th class="py-3 px-6 font-medium w-[120px]"><?php esc_html_e( 'Action', 'lancedesk-form-guard' ); ?></th>
<th class="py-3 px-6 font-medium"><?php esc_html_e( 'Reason Snippet', 'lancedesk-form-guard' ); ?></th>
</tr>
</thead>
<tbody class="text-body-sm font-body-sm text-on-surface">
<?php if ( empty( $recent_logs ) ) : ?>
<tr><td colspan="5" class="py-6 px-6 text-on-surface-variant text-center"><?php esc_html_e( 'No recent blocks.', 'lancedesk-form-guard' ); ?></td></tr>
<?php else : ?>
<?php foreach ( $recent_logs as $log ) : ?>
<?php
	$is_quarantine = ( 'quarantine' === $log['action'] || 'quarantined' === $log['status'] );
	$layer_label   = ( 'ai' === $log['layer'] ) ? __( 'AI API', 'lancedesk-form-guard' ) : __( 'Heuristic', 'lancedesk-form-guard' );
	/* translators: %s: human-readable time difference, e.g. "2 mins". */
	$time_ago = sprintf( __( '%s ago', 'lancedesk-form-guard' ), human_time_diff( strtotime( (string) $log['created_at'] ), (int) current_time( 'timestamp' ) ) );
?>
<tr class="border-b border-surface-variant hover:bg-surface-container-low/50 transition-colors group">
<td class="py-3 px-6 text-on-surface-variant"><?php echo esc_html( $time_ago ); ?></td>
<td class="py-3 px-6 font-medium"><?php echo esc_html( $log['form_title'] ? $log['form_title'] : $log['form_id'] ); ?></td>
<td class="py-3 px-6">
<?php if ( 'ai' === $log['layer'] ) : ?>
<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-secondary-container/20 text-on-secondary-container text-[11px] font-label-md"><?php echo esc_html( $layer_label ); ?></span>
<?php else : ?>
<span class="inline-flex items-center px-2 py-0.5 rounded-full bg-surface-variant text-on-surface-variant text-[11px] font-label-md"><?php echo esc_html( $layer_label ); ?></span>
<?php endif; ?>
</td>
<td class="py-3 px-6">
<?php if ( $is_quarantine ) : ?>
<span class="inline-flex items-center gap-1 text-[#dba617] font-medium"><span class="ld-fg-icon material-symbols-outlined text-[14px]">inventory_2</span> <?php esc_html_e( 'Quarantine', 'lancedesk-form-guard' ); ?></span>
<?php else : ?>
<span class="inline-flex items-center gap-1 text-error font-medium"><span class="ld-fg-icon material-symbols-outlined text-[14px]">block</span> <?php esc_html_e( 'Blocked', 'lancedesk-form-guard' ); ?></span>
<?php endif; ?>
</td>
<td class="py-3 px-6 truncate max-w-[250px] text-on-surface-variant font-mono text-[12px]" title="<?php echo esc_attr( (string) $log['reason'] ); ?>"><?php echo esc_html( wp_trim_words( $log['snippet'] ? $log['snippet'] : $log['reason'], 8, '...' ) ); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
<div class="flex items-center justify-end text-body-sm font-body-sm text-on-surface-variant/70 gap-2 mt-2">
<span class="ld-fg-icon material-symbols-outlined text-[16px]">info</span>
<span><?php esc_html_e( 'IPs are stored as hashes. Message bodies are truncated for privacy.', 'lancedesk-form-guard' ); ?></span>
</div>
</div>

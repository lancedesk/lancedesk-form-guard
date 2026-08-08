<?php
/**
 * Security logs page content.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View template locals.

$logs  = LD_FG_Logger::query_recent( 25 );
$total = count( $logs );
?>
<div class="flex flex-col w-full ld-fg-logs-page">
<div class="flex items-center justify-between mb-6">
<h1 class="font-headline-lg text-headline-lg text-on-surface m-0"><?php esc_html_e( 'Security Logs', 'lancedesk-form-guard' ); ?></h1>
</div>
<div class="bg-surface-container rounded-xl shadow-sm mb-margin-lg p-margin-md flex flex-wrap items-end gap-margin-md">
<div class="flex-1 min-w-[200px]">
<label class="block font-label-md text-label-md text-on-surface-variant mb-unit"><?php esc_html_e( 'Search', 'lancedesk-form-guard' ); ?></label>
<div class="relative">
<span class="ld-fg-icon material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline-variant text-[20px]">search</span>
<input class="ld-fg-logs-search w-full bg-surface-container-lowest font-body-sm text-body-sm text-on-surface !pl-12 pr-3 py-2 rounded-lg border-none ring-1 ring-outline-variant focus:ring-2 focus:ring-primary outline-none transition-shadow placeholder:text-outline-variant" placeholder="<?php esc_attr_e( 'Search IP, Email, Form...', 'lancedesk-form-guard' ); ?>" type="text"/>
</div>
</div>
</div>
<div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden flex flex-col">
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse ld-fg-logs-table">
<thead>
<tr class="bg-surface-container-low text-on-surface-variant font-label-md text-label-md uppercase tracking-wider">
<th class="px-margin-md py-3 font-semibold"><?php esc_html_e( 'Date / Time', 'lancedesk-form-guard' ); ?></th>
<th class="px-margin-md py-3 font-semibold"><?php esc_html_e( 'Form Source', 'lancedesk-form-guard' ); ?></th>
<th class="px-margin-md py-3 font-semibold"><?php esc_html_e( 'Verdict', 'lancedesk-form-guard' ); ?></th>
<th class="px-margin-md py-3 font-semibold"><?php esc_html_e( 'Detection Layer', 'lancedesk-form-guard' ); ?></th>
<th class="px-margin-md py-3 font-semibold text-right"><?php esc_html_e( 'Confidence', 'lancedesk-form-guard' ); ?></th>
<th class="px-margin-md py-3 font-semibold"><?php esc_html_e( 'Reason Snapshot', 'lancedesk-form-guard' ); ?></th>
<th class="px-margin-md py-3 font-semibold w-12 text-center"><?php esc_html_e( 'Actions', 'lancedesk-form-guard' ); ?></th>
</tr>
</thead>
<tbody class="font-body-sm text-body-sm text-on-surface">
<?php if ( empty( $logs ) ) : ?>
<tr><td colspan="7" class="px-margin-md py-8 text-center text-on-surface-variant"><?php esc_html_e( 'No log entries yet.', 'lancedesk-form-guard' ); ?></td></tr>
<?php else : ?>
<?php foreach ( $logs as $log ) :
	$conf_pct = (int) round( (float) $log['confidence'] * 100 );
	$verdict  = (string) $log['verdict'];
	?>
<tr class="hover:bg-surface-container-lowest transition-colors border-t border-outline-variant/30 group ld-fg-log-row" data-log-id="<?php echo esc_attr( (string) $log['id'] ); ?>">
<td class="px-margin-md py-3 whitespace-nowrap">
<div class="font-medium"><?php echo esc_html( mysql2date( 'M j, H:i:s', $log['created_at'] ) ); ?></div>
</td>
<td class="px-margin-md py-3 whitespace-nowrap">
<div class="flex items-center gap-2">
<span class="ld-fg-icon material-symbols-outlined text-outline text-[16px]">contact_page</span>
<?php echo esc_html( $log['form_title'] ?: $log['form_id'] ); ?>
</div>
</td>
<td class="px-margin-md py-3 whitespace-nowrap">
<?php if ( 'quarantined' === $verdict ) : ?>
<span class="inline-flex items-center px-2 py-1 rounded bg-tertiary-container text-on-tertiary-container font-label-md text-[11px] uppercase tracking-wide"><?php esc_html_e( 'Quarantined', 'lancedesk-form-guard' ); ?></span>
<?php elseif ( 'allowed' === $verdict || 'clean' === $verdict ) : ?>
<span class="inline-flex items-center px-2 py-1 rounded bg-[#e8f5e9] text-[#2e7d32] font-label-md text-[11px] uppercase tracking-wide"><?php esc_html_e( 'Allowed', 'lancedesk-form-guard' ); ?></span>
<?php else : ?>
<span class="inline-flex items-center px-2 py-1 rounded bg-error-container text-on-error-container font-label-md text-[11px] uppercase tracking-wide"><?php esc_html_e( 'Blocked', 'lancedesk-form-guard' ); ?></span>
<?php endif; ?>
</td>
<td class="px-margin-md py-3 whitespace-nowrap">
<div class="flex items-center gap-1.5">
<span class="ld-fg-icon material-symbols-outlined text-primary text-[14px]"><?php echo 'ai' === $log['layer'] ? 'psychology' : 'rule'; ?></span>
<?php echo esc_html( 'ai' === $log['layer'] ? __( 'AI Analysis', 'lancedesk-form-guard' ) : __( 'Heuristic', 'lancedesk-form-guard' ) ); ?>
</div>
</td>
<td class="px-margin-md py-3 whitespace-nowrap text-right">
<span class="font-medium"><?php echo esc_html( (string) $conf_pct ); ?>%</span>
</td>
<td class="px-margin-md py-3">
<div class="truncate max-w-[200px] text-on-surface-variant" title="<?php echo esc_attr( $log['reason'] ); ?>"><?php echo esc_html( wp_trim_words( $log['reason'], 6, '...' ) ); ?></div>
</td>
<td class="px-margin-md py-3 text-center">
<button type="button" class="ld-fg-log-view text-outline hover:text-primary transition-colors opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-surface-container" data-log-id="<?php echo esc_attr( (string) $log['id'] ); ?>" title="<?php esc_attr_e( 'View Details', 'lancedesk-form-guard' ); ?>">
<span class="ld-fg-icon material-symbols-outlined text-[20px]">visibility</span>
</button>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="bg-surface-container-low border-t border-outline-variant/30 px-margin-md py-3 flex items-center justify-between">
<div class="font-body-sm text-body-sm text-on-surface-variant">
<?php
/* translators: 1: start number, 2: end number, 3: total entries. */
echo esc_html( sprintf( __( 'Showing %1$d to %2$d of %3$d entries', 'lancedesk-form-guard' ), $total > 0 ? 1 : 0, $total, $total ) );
?>
</div>
</div>
</div>
<?php require LD_FG_PLUGIN_DIR . 'admin/partials/part-log-drawer.php'; ?>
</div>

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

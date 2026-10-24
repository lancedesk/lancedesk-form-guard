<?php
/**
 * Security logs page content (FG-L1b layout).
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View template locals.

$per_page = 25;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filters.
$paged = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filters.
$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filters.
$verdict_filter = isset( $_GET['verdict'] ) ? sanitize_key( wp_unslash( (string) $_GET['verdict'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filters.
$layer_filter = isset( $_GET['layer'] ) ? sanitize_key( wp_unslash( (string) $_GET['layer'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only list filters.
$date_range = isset( $_GET['date_range'] ) ? sanitize_key( wp_unslash( (string) $_GET['date_range'] ) ) : '';

$filter = array();
if ( '' !== $search ) {
	$filter['search'] = $search;
}
if ( in_array( $verdict_filter, array( 'blocked', 'allowed', 'quarantined' ), true ) ) {
	$filter['verdict'] = $verdict_filter;
}
if ( in_array( $layer_filter, array( 'heuristic', 'ai', 'none' ), true ) ) {
	$filter['layer'] = $layer_filter;
}
if ( in_array( $date_range, array( 'today', 'yesterday', '7days' ), true ) ) {
	$filter['date_range'] = $date_range;
}

$total       = LD_FG_Logger::count( $filter );
$total_pages = max( 1, (int) ceil( $total / $per_page ) );
$paged       = min( $paged, $total_pages );
$offset      = ( $paged - 1 ) * $per_page;
$filter['offset'] = $offset;

$logs      = LD_FG_Logger::query_recent( $per_page, $filter );
$showing   = count( $logs );
$from      = $total > 0 ? ( $offset + 1 ) : 0;
$to        = $total > 0 ? ( $offset + $showing ) : 0;
$unread    = LD_FG_Logger::count_unread( get_current_user_id() );
$page_stats = LD_FG_Logger::logs_page_stats();
$read_ids  = array();

if ( ! empty( $logs ) ) {
	$read_ids = array_flip(
		LD_FG_Logger::get_read_ids(
			get_current_user_id(),
			array_map(
				static function ( array $log ): int {
					return (int) ( $log['id'] ?? 0 );
				},
				$logs
			)
		)
	);
}

$base_args = array(
	'page' => 'ld-fg-logs',
);
if ( '' !== $search ) {
	$base_args['s'] = $search;
}
if ( '' !== $verdict_filter ) {
	$base_args['verdict'] = $verdict_filter;
}
if ( '' !== $layer_filter ) {
	$base_args['layer'] = $layer_filter;
}
if ( '' !== $date_range ) {
	$base_args['date_range'] = $date_range;
}

$logs_url = static function ( array $extra = array() ) use ( $base_args ): string {
	return add_query_arg( array_merge( $base_args, $extra ), admin_url( 'admin.php' ) );
};

$export_url = wp_nonce_url(
	add_query_arg(
		array_merge(
			$base_args,
			array(
				'action' => 'ld_fg_export_logs_csv',
			)
		),
		admin_url( 'admin-post.php' )
	),
	'ld_fg_export_logs_csv'
);

$today_label = sprintf(
	/* translators: %s: localized date (e.g. 24 Oct). */
	__( 'Today (%s)', 'lancedesk-form-guard' ),
	date_i18n( 'j M' )
);

$integration_labels = array(
	'wpforms' => __( 'WPForms', 'lancedesk-form-guard' ),
	'cf7'     => __( 'Contact Form 7', 'lancedesk-form-guard' ),
	'gravity' => __( 'Gravity Forms', 'lancedesk-form-guard' ),
	'ninja'   => __( 'Ninja Forms', 'lancedesk-form-guard' ),
	'quform'  => __( 'Quform', 'lancedesk-form-guard' ),
);
$action_labels = array(
	'silent_discard' => __( 'Silent Discard', 'lancedesk-form-guard' ),
	'soft_fail'      => __( 'Soft Fail', 'lancedesk-form-guard' ),
	'quarantine'     => __( 'Quarantined', 'lancedesk-form-guard' ),
	'allow'          => __( 'Allowed', 'lancedesk-form-guard' ),
);
$provider_labels = array(
	'groq'      => 'Groq',
	'openai'    => 'OpenAI',
	'anthropic' => 'Anthropic',
);

$unread_badge = $unread > 99 ? '99+' : (string) $unread;
?>
<div class="flex flex-col w-full ld-fg-logs-page">
<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between pb-6 gap-4">
<div class="flex flex-col">
<div class="flex items-center gap-3 flex-wrap">
<h1 class="font-hero-title text-hero-title text-on-surface tracking-tight m-0"><?php esc_html_e( 'Security Logs', 'lancedesk-form-guard' ); ?></h1>
<?php if ( $unread > 0 ) : ?>
<span class="inline-flex items-center px-2 py-0.5 rounded-full text-label-md font-label-md bg-error-container text-on-error-container font-semibold ld-fg-logs-unread-pill">
<?php
echo esc_html(
	sprintf(
		/* translators: %s: unread count. */
		__( '%s unread', 'lancedesk-form-guard' ),
		$unread_badge
	)
);
?>
</span>
<?php endif; ?>
</div>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-1 mb-0">
<?php esc_html_e( 'Real-time telemetry and rule enforcement stream for protected site endpoints.', 'lancedesk-form-guard' ); ?>
</p>
</div>
<div class="flex items-center gap-3 flex-wrap justify-end">
<a class="ld-fg-logs-export inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg bg-surface-container-lowest text-primary text-body-lg font-body-lg shadow-sm hover:bg-surface-container transition-colors no-underline<?php echo $total < 1 ? ' opacity-50 pointer-events-none' : ''; ?>" href="<?php echo esc_url( $export_url ); ?>"<?php echo $total < 1 ? ' aria-disabled="true"' : ''; ?>>
<span class="ld-fg-icon material-symbols-outlined text-[18px]" aria-hidden="true">download</span>
<?php esc_html_e( 'Export CSV', 'lancedesk-form-guard' ); ?>
</a>
<div class="relative ld-fg-logs-columns-wrap">
<button type="button" class="ld-fg-logs-columns-btn inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-surface-container-lowest text-on-surface-variant hover:text-on-surface hover:bg-surface-container transition-colors font-body-sm text-body-sm shadow-sm" aria-expanded="false" aria-haspopup="true" aria-controls="ld-fg-logs-columns-menu">
<span class="ld-fg-icon material-symbols-outlined text-[18px]" aria-hidden="true">tune</span>
<?php esc_html_e( 'Columns', 'lancedesk-form-guard' ); ?>
</button>
<div id="ld-fg-logs-columns-menu" class="ld-fg-logs-columns-menu hidden" role="menu" hidden>
<p class="ld-fg-logs-columns-menu__title"><?php esc_html_e( 'Show columns', 'lancedesk-form-guard' ); ?></p>
<label class="ld-fg-logs-columns-menu__item"><input type="checkbox" data-col="timestamp" checked /> <?php esc_html_e( 'Timestamp', 'lancedesk-form-guard' ); ?></label>
<label class="ld-fg-logs-columns-menu__item"><input type="checkbox" data-col="form" checked /> <?php esc_html_e( 'Protected Form', 'lancedesk-form-guard' ); ?></label>
<label class="ld-fg-logs-columns-menu__item"><input type="checkbox" data-col="verdict" checked /> <?php esc_html_e( 'Verdict', 'lancedesk-form-guard' ); ?></label>
<label class="ld-fg-logs-columns-menu__item"><input type="checkbox" data-col="layer" checked /> <?php esc_html_e( 'Layer', 'lancedesk-form-guard' ); ?></label>
<label class="ld-fg-logs-columns-menu__item"><input type="checkbox" data-col="confidence" checked /> <?php esc_html_e( 'Confidence', 'lancedesk-form-guard' ); ?></label>
<label class="ld-fg-logs-columns-menu__item"><input type="checkbox" data-col="reason" checked /> <?php esc_html_e( 'Reason &amp; Context', 'lancedesk-form-guard' ); ?></label>
</div>
</div>
<?php if ( $page_stats['total'] > 0 ) : ?>
<button type="button" class="ld-fg-delete-all-logs inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-error text-on-error text-body-sm font-medium shadow-sm hover:opacity-95 transition-all">
<span class="ld-fg-icon material-symbols-outlined text-[18px]" aria-hidden="true">delete_sweep</span>
<?php esc_html_e( 'Clear All Logs', 'lancedesk-form-guard' ); ?>
</button>
<?php endif; ?>
</div>
</div>

<?php if ( $page_stats['total'] > 0 ) : ?>
<!-- KPI strip -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
<div class="bg-surface-container-lowest p-4 rounded-lg shadow-sm flex items-center justify-between">
<div>
<span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider"><?php esc_html_e( 'Total Scanned Today', 'lancedesk-form-guard' ); ?></span>
<div class="font-headline-lg text-headline-lg text-on-surface font-semibold mt-0.5"><?php echo esc_html( number_format_i18n( (int) $page_stats['total_today'] ) ); ?></div>
</div>
<div class="w-12 h-12 rounded-lg bg-surface-container-low flex items-center justify-center text-primary">
<span class="ld-fg-icon material-symbols-outlined text-[24px]" aria-hidden="true">verified_user</span>
</div>
</div>
<div class="bg-surface-container-lowest p-4 rounded-lg shadow-sm flex items-center justify-between">
<div>
<span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider"><?php esc_html_e( 'Threats Blocked', 'lancedesk-form-guard' ); ?></span>
<div class="font-headline-lg text-headline-lg text-error font-semibold mt-0.5"><?php echo esc_html( number_format_i18n( (int) $page_stats['blocked_today'] ) ); ?></div>
</div>
<div class="w-12 h-12 rounded-lg bg-error-container/40 flex items-center justify-center text-error">
<span class="ld-fg-icon material-symbols-outlined text-[24px]" aria-hidden="true">gpp_bad</span>
</div>
</div>
<div class="bg-surface-container-lowest p-4 rounded-lg shadow-sm flex items-center justify-between">
<div>
<span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider"><?php esc_html_e( 'Quarantine Ratio', 'lancedesk-form-guard' ); ?></span>
<div class="font-headline-lg text-headline-lg text-tertiary-container font-semibold mt-0.5"><?php echo esc_html( number_format_i18n( (float) $page_stats['quarantine_ratio'], 1 ) ); ?>%</div>
</div>
<div class="w-12 h-12 rounded-lg bg-tertiary-fixed/40 flex items-center justify-center text-tertiary">
<span class="ld-fg-icon material-symbols-outlined text-[24px]" aria-hidden="true">policy</span>
</div>
</div>
<div class="bg-surface-container-lowest p-4 rounded-lg shadow-sm flex items-center justify-between">
<div>
<span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider"><?php esc_html_e( 'Avg Confidence', 'lancedesk-form-guard' ); ?></span>
<div class="font-headline-lg text-headline-lg text-secondary font-semibold mt-0.5"><?php echo esc_html( number_format_i18n( (float) $page_stats['avg_confidence'], 1 ) ); ?>%</div>
</div>
<div class="w-12 h-12 rounded-lg bg-secondary-fixed/50 flex items-center justify-center text-secondary">
<span class="ld-fg-icon material-symbols-outlined text-[24px]" aria-hidden="true">psychology</span>
</div>
</div>
</div>
<?php endif; ?>

<!-- Filter strip -->
<div class="bg-surface-container-lowest p-4 rounded-lg shadow-sm mb-4">
<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="flex flex-wrap items-center gap-3 ld-fg-logs-filters">
<input type="hidden" name="page" value="ld-fg-logs" />
<div class="relative flex-1 min-w-[220px]">
<span class="ld-fg-icon material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]" aria-hidden="true">search</span>
<input id="ld-fg-logs-search" name="s" value="<?php echo esc_attr( $search ); ?>" class="ld-fg-logs-search w-full pl-9 pr-3 py-1.5 rounded-lg bg-surface-container-low text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary shadow-inner border-0" placeholder="<?php esc_attr_e( 'Filter by keyword, form or reason…', 'lancedesk-form-guard' ); ?>" type="search"/>
</div>
<div class="w-40">
<label class="screen-reader-text" for="ld-fg-logs-verdict"><?php esc_html_e( 'Verdict', 'lancedesk-form-guard' ); ?></label>
<select id="ld-fg-logs-verdict" name="verdict" class="w-full px-3 py-1.5 rounded-lg bg-surface-container-low text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary border-0">
<option value="" <?php selected( $verdict_filter, '' ); ?>><?php esc_html_e( 'All Verdicts', 'lancedesk-form-guard' ); ?></option>
<option value="blocked" <?php selected( $verdict_filter, 'blocked' ); ?>><?php esc_html_e( 'Blocked Only', 'lancedesk-form-guard' ); ?></option>
<option value="quarantined" <?php selected( $verdict_filter, 'quarantined' ); ?>><?php esc_html_e( 'Quarantined', 'lancedesk-form-guard' ); ?></option>
<option value="allowed" <?php selected( $verdict_filter, 'allowed' ); ?>><?php esc_html_e( 'Allowed', 'lancedesk-form-guard' ); ?></option>
</select>
</div>
<div class="w-44">
<label class="screen-reader-text" for="ld-fg-logs-layer"><?php esc_html_e( 'Layer', 'lancedesk-form-guard' ); ?></label>
<select id="ld-fg-logs-layer" name="layer" class="w-full px-3 py-1.5 rounded-lg bg-surface-container-low text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary border-0">
<option value="" <?php selected( $layer_filter, '' ); ?>><?php esc_html_e( 'All Protection Layers', 'lancedesk-form-guard' ); ?></option>
<option value="heuristic" <?php selected( $layer_filter, 'heuristic' ); ?>><?php esc_html_e( 'Heuristic Rules', 'lancedesk-form-guard' ); ?></option>
<option value="ai" <?php selected( $layer_filter, 'ai' ); ?>><?php esc_html_e( 'AI Analysis Engine', 'lancedesk-form-guard' ); ?></option>
<option value="none" <?php selected( $layer_filter, 'none' ); ?>><?php esc_html_e( 'None / Pass', 'lancedesk-form-guard' ); ?></option>
</select>
</div>
<div class="w-40">
<label class="screen-reader-text" for="ld-fg-logs-date"><?php esc_html_e( 'Date range', 'lancedesk-form-guard' ); ?></label>
<select id="ld-fg-logs-date" name="date_range" class="w-full px-3 py-1.5 rounded-lg bg-surface-container-low text-on-surface font-body-sm text-body-sm focus:outline-none focus:ring-2 focus:ring-primary border-0">
<option value="" <?php selected( $date_range, '' ); ?>><?php esc_html_e( 'All Dates', 'lancedesk-form-guard' ); ?></option>
<option value="today" <?php selected( $date_range, 'today' ); ?>><?php echo esc_html( $today_label ); ?></option>
<option value="yesterday" <?php selected( $date_range, 'yesterday' ); ?>><?php esc_html_e( 'Yesterday', 'lancedesk-form-guard' ); ?></option>
<option value="7days" <?php selected( $date_range, '7days' ); ?>><?php esc_html_e( 'Last 7 Days', 'lancedesk-form-guard' ); ?></option>
</select>
</div>
<button class="px-4 py-1.5 rounded-lg bg-surface-container-high text-on-surface font-body-sm text-body-sm font-medium hover:bg-surface-variant transition-colors border-0 cursor-pointer" type="submit">
<?php esc_html_e( 'Filter', 'lancedesk-form-guard' ); ?>
</button>
<?php
$selects_dirty = ( '' !== $verdict_filter || '' !== $layer_filter || '' !== $date_range );
?>
<a class="ld-fg-logs-reset px-3 py-1.5 rounded-lg text-primary font-body-sm text-body-sm hover:underline no-underline<?php echo $selects_dirty ? '' : ' hidden'; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-logs' ) ); ?>"<?php echo $selects_dirty ? '' : ' hidden'; ?>>
<?php esc_html_e( 'Reset', 'lancedesk-form-guard' ); ?>
</a>
</form>
</div>

<!-- Table card -->
<div class="bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden flex flex-col ld-fg-logs-card">
<!-- Bulk toolbar -->
<div class="p-3 bg-surface-container-low flex flex-wrap items-center justify-between gap-3 ld-fg-logs-bulk-bar">
<div class="flex items-center gap-3 flex-wrap">
<div class="flex items-center gap-2">
<label class="screen-reader-text" for="ld-fg-bulk-action"><?php esc_html_e( 'Bulk actions', 'lancedesk-form-guard' ); ?></label>
<select id="ld-fg-bulk-action" class="ld-fg-bulk-action-select px-3 py-1.5 rounded-lg bg-surface-container-lowest text-on-surface font-body-sm text-body-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary border-0" <?php disabled( empty( $logs ) ); ?>>
<option value=""><?php esc_html_e( 'Bulk actions', 'lancedesk-form-guard' ); ?></option>
<option value="mark-read"><?php esc_html_e( 'Mark as read', 'lancedesk-form-guard' ); ?></option>
<option value="mark-unread"><?php esc_html_e( 'Mark as unread', 'lancedesk-form-guard' ); ?></option>
<option value="export-selected"><?php esc_html_e( 'Export selected entries', 'lancedesk-form-guard' ); ?></option>
<option value="trash"><?php esc_html_e( 'Delete selected', 'lancedesk-form-guard' ); ?></option>
</select>
<button type="button" id="ld-fg-bulk-apply" class="ld-fg-bulk-apply px-4 py-1.5 rounded-lg bg-primary-container text-on-primary font-body-sm text-body-sm font-medium shadow-sm hover:opacity-90 transition-all inline-flex items-center gap-1.5 border-0 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed" disabled>
<?php esc_html_e( 'Apply', 'lancedesk-form-guard' ); ?>
</button>
</div>
<div class="h-4 w-px bg-outline-variant/50 hidden sm:block ld-fg-bulk-divider"></div>
<div class="flex items-center gap-2 ld-fg-bulk-selection-meta<?php echo empty( $logs ) ? ' opacity-50' : ''; ?>">
<span class="ld-fg-bulk-selected-count inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-secondary-container/30 text-on-secondary-container font-label-md text-label-md font-semibold hidden">
<span class="ld-fg-icon material-symbols-outlined text-[14px]" aria-hidden="true">checklist</span>
<span class="ld-fg-bulk-selected-count-text">0 selected</span>
</span>
<button type="button" class="ld-fg-bulk-deselect text-primary font-body-sm text-body-sm hover:underline bg-transparent border-0 cursor-pointer p-0 hidden">
<?php esc_html_e( '(Deselect all)', 'lancedesk-form-guard' ); ?>
</button>
</div>
</div>
<div class="flex items-center gap-3">
<span class="text-on-surface-variant font-label-md text-label-md ld-fg-logs-viewing">
<?php
/* translators: 1: start, 2: end, 3: total. */
echo esc_html( sprintf( __( 'Viewing %1$d–%2$d of %3$d', 'lancedesk-form-guard' ), $from, $to, $total ) );
?>
</span>
<div class="flex items-center gap-1 relative">
<button type="button" class="ld-fg-logs-density p-1 rounded text-on-surface-variant hover:bg-surface-container-highest bg-transparent border-0 cursor-pointer" title="<?php esc_attr_e( 'Compact density', 'lancedesk-form-guard' ); ?>" aria-pressed="false">
<span class="ld-fg-icon material-symbols-outlined text-[18px]" aria-hidden="true">density_medium</span>
</button>
<button type="button" class="ld-fg-logs-columns-btn-secondary p-1 rounded text-on-surface-variant hover:bg-surface-container-highest bg-transparent border-0 cursor-pointer" title="<?php esc_attr_e( 'Display columns', 'lancedesk-form-guard' ); ?>" aria-expanded="false" aria-controls="ld-fg-logs-columns-menu">
<span class="ld-fg-icon material-symbols-outlined text-[18px]" aria-hidden="true">view_column</span>
</button>
</div>
</div>
</div>

<div class="w-full overflow-x-auto ld-fg-logs-table-wrap">
<table class="w-full text-left border-collapse ld-fg-logs-table">
<thead>
<tr class="bg-surface-container text-on-surface-variant font-label-md text-label-md uppercase tracking-wider select-none h-11 ld-fg-logs-table-head">
<th class="w-12 px-4 py-2 align-middle text-center" scope="col">
<input type="checkbox" class="ld-fg-logs-select-all w-4 h-4 rounded text-primary accent-primary-container cursor-pointer" aria-label="<?php esc_attr_e( 'Select all', 'lancedesk-form-guard' ); ?>" title="<?php esc_attr_e( 'Select all', 'lancedesk-form-guard' ); ?>" <?php disabled( empty( $logs ) ); ?> />
</th>
<th class="w-8 px-1 py-2 align-middle text-center" scope="col"><span class="screen-reader-text"><?php esc_html_e( 'Status', 'lancedesk-form-guard' ); ?></span></th>
<th class="px-3 py-2 align-middle whitespace-nowrap ld-fg-col-cell" data-col="timestamp" scope="col"><?php esc_html_e( 'Timestamp', 'lancedesk-form-guard' ); ?></th>
<th class="px-3 py-2 align-middle whitespace-nowrap ld-fg-col-cell" data-col="form" scope="col"><?php esc_html_e( 'Protected Form', 'lancedesk-form-guard' ); ?></th>
<th class="px-3 py-2 align-middle whitespace-nowrap ld-fg-col-cell" data-col="verdict" scope="col"><?php esc_html_e( 'Verdict', 'lancedesk-form-guard' ); ?></th>
<th class="px-3 py-2 align-middle whitespace-nowrap ld-fg-col-cell" data-col="layer" scope="col"><?php esc_html_e( 'Layer', 'lancedesk-form-guard' ); ?></th>
<th class="px-3 py-2 align-middle whitespace-nowrap ld-fg-col-cell" data-col="confidence" scope="col"><?php esc_html_e( 'Confidence', 'lancedesk-form-guard' ); ?></th>
<th class="px-3 py-2 align-middle ld-fg-col-cell" data-col="reason" scope="col"><?php esc_html_e( 'Reason &amp; Context', 'lancedesk-form-guard' ); ?></th>
<th class="w-20 px-4 py-2 align-middle text-center" scope="col"><?php esc_html_e( 'Actions', 'lancedesk-form-guard' ); ?></th>
</tr>
</thead>
<tbody class="font-body-sm text-body-sm text-on-surface">
<?php if ( empty( $logs ) ) : ?>
<tr class="ld-fg-logs-empty-row">
<td colspan="9" class="p-0 border-0">
<div class="flex flex-col items-center justify-center py-20 px-6 text-center">
<div class="relative mb-5 flex items-center justify-center">
<div class="w-20 h-20 rounded-full bg-surface-container-low flex items-center justify-center text-outline">
<span class="ld-fg-icon material-symbols-outlined text-[44px] text-outline" aria-hidden="true">shield</span>
</div>
<div class="absolute -bottom-1 -right-1 w-7 h-7 rounded-full bg-surface-container-lowest shadow-sm flex items-center justify-center">
<span class="ld-fg-icon material-symbols-outlined text-[#46b450] text-[18px]" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">check_circle</span>
</div>
</div>
<?php if ( $page_stats['total'] > 0 ) : ?>
<h2 class="font-headline-md text-headline-md text-on-surface mb-2 font-semibold mt-0"><?php esc_html_e( 'No matching log entries', 'lancedesk-form-guard' ); ?></h2>
<p class="font-body-lg text-body-lg text-on-surface-variant max-w-[480px] leading-relaxed mb-6">
<?php esc_html_e( 'Try clearing filters or broadening the date range to see more results.', 'lancedesk-form-guard' ); ?>
</p>
<div class="flex flex-col sm:flex-row items-center gap-3">
<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-container text-on-primary font-label-md text-label-md transition-colors no-underline shadow-sm" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-logs' ) ); ?>">
<?php esc_html_e( 'Reset filters', 'lancedesk-form-guard' ); ?>
</a>
</div>
<?php else : ?>
<h2 class="font-headline-md text-headline-md text-on-surface mb-2 font-semibold mt-0"><?php esc_html_e( 'No log entries yet', 'lancedesk-form-guard' ); ?></h2>
<p class="font-body-lg text-body-lg text-on-surface-variant max-w-[480px] leading-relaxed mb-6">
<?php esc_html_e( 'When form submissions are intercepted or analyzed by Form Guard, audit records will appear here in real time.', 'lancedesk-form-guard' ); ?>
</p>
<div class="flex flex-col sm:flex-row items-center gap-3">
<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-colors no-underline" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-protection' ) ); ?>">
<span class="ld-fg-icon material-symbols-outlined text-primary text-[18px]" aria-hidden="true">rule</span>
<?php esc_html_e( 'View Protection Rules', 'lancedesk-form-guard' ); ?>
</a>
<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-surface-container hover:bg-surface-container-high text-secondary font-label-md text-label-md transition-colors no-underline" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-integrations' ) ); ?>">
<span class="ld-fg-icon material-symbols-outlined text-[18px]" aria-hidden="true">hub</span>
<?php esc_html_e( 'Open Integrations', 'lancedesk-form-guard' ); ?>
</a>
</div>
<?php endif; ?>
</div>
</td>
</tr>
<?php else : ?>
<?php foreach ( $logs as $log ) :
	$conf_pct    = (int) round( (float) $log['confidence'] * 100 );
	$verdict     = (string) $log['verdict'];
	$layer       = (string) ( $log['layer'] ?? '' );
	$action      = (string) ( $log['action'] ?? '' );
	$integration = (string) ( $log['integration'] ?? '' );
	$form_title  = (string) ( $log['form_title'] ?: $log['form_id'] );
	$form_id     = (string) ( $log['form_id'] ?? '' );
	$date_label  = mysql2date( 'M j, Y - H:i:s', $log['created_at'] );
	$date_short  = mysql2date( 'M j H:i:s', $log['created_at'] );
	$ip_hash     = (string) ( $log['ip_hash'] ?? '' );
	$ip_short    = '' !== $ip_hash ? substr( $ip_hash, 0, 8 ) . '…' : '—';
	$meta        = array();
	if ( ! empty( $log['meta'] ) ) {
		$decoded = json_decode( (string) $log['meta'], true );
		$meta    = is_array( $decoded ) ? $decoded : array();
	}
	$provider_key = sanitize_key( (string) ( $meta['provider'] ?? '' ) );
	$fields       = isset( $meta['fields'] ) && is_array( $meta['fields'] ) ? $meta['fields'] : array();
	$snippet      = (string) ( $log['snippet'] ?? '' );
	$layer_label = 'ai' === $layer
		? __( 'AI Analysis', 'lancedesk-form-guard' )
		: ( 'none' === $layer ? __( 'None', 'lancedesk-form-guard' ) : __( 'Heuristic', 'lancedesk-form-guard' ) );
	$layer_icon  = 'ai' === $layer ? 'smart_toy' : ( 'none' === $layer ? 'verified' : 'rule' );
	$reason_full = (string) ( $log['reason'] ?? '' );
	$integration_label = $integration_labels[ $integration ] ?? ( $integration ? ucwords( str_replace( '_', ' ', $integration ) ) : '—' );
	$action_label      = $action_labels[ $action ] ?? ( $action ? ucwords( str_replace( '_', ' ', $action ) ) : '—' );
	$provider_label    = '' !== $provider_key
		? ( $provider_labels[ $provider_key ] ?? ucwords( $provider_key ) )
		: ( 'ai' === $layer ? '—' : __( 'Local', 'lancedesk-form-guard' ) );
	if ( 'quarantined' === $verdict ) {
		$verdict_label = __( 'Quarantined', 'lancedesk-form-guard' );
		$title_label   = __( 'Form Submission Quarantined', 'lancedesk-form-guard' );
		$verdict_icon  = 'shield';
		$conf_class    = 'text-tertiary';
	} elseif ( 'allowed' === $verdict || 'clean' === $verdict ) {
		$verdict_label = __( 'Allowed', 'lancedesk-form-guard' );
		$title_label   = __( 'Form Submission Allowed', 'lancedesk-form-guard' );
		$verdict_icon  = 'check_circle';
		$conf_class    = 'text-on-surface-variant';
	} else {
		$verdict_label = __( 'Blocked', 'lancedesk-form-guard' );
		$title_label   = __( 'Form Submission Blocked', 'lancedesk-form-guard' );
		$verdict_icon  = 'block';
		$conf_class    = 'text-error';
	}
	$payload = array(
		'id'            => (int) $log['id'],
		'verdict'       => $verdict,
		'verdict_label' => $verdict_label,
		'title'         => $title_label,
		'form'          => $form_title,
		'date'          => $date_label,
		'integration'   => $integration_label,
		'layer'         => $layer_label,
		'layer_key'     => $layer,
		'action'        => $action_label,
		'provider'      => $provider_label,
		'provider_key'  => $provider_key,
		'confidence'    => $conf_pct,
		'reason'        => $reason_full,
		'snippet'       => $snippet,
		'fields'        => $fields,
		'is_quarantine' => ( 'quarantined' === $verdict || 'quarantine' === $action ),
	);
	$is_read = isset( $read_ids[ (int) $log['id'] ] );
	$row_bg  = $is_read
		? 'bg-surface-container-lowest hover:bg-surface-container-low'
		: 'bg-surface-container-lowest hover:bg-surface-container-low';
	?>
<tr
	class="h-[52px] transition-colors group ld-fg-log-row<?php echo $is_read ? ' is-read' : ' is-unread'; ?> <?php echo esc_attr( $row_bg ); ?>"
	data-log-id="<?php echo esc_attr( (string) $log['id'] ); ?>"
	data-read="<?php echo $is_read ? '1' : '0'; ?>"
	data-log="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>"
	data-export-date="<?php echo esc_attr( $date_label ); ?>"
	data-export-form="<?php echo esc_attr( $form_title ); ?>"
	data-export-verdict="<?php echo esc_attr( $verdict_label ); ?>"
	data-export-layer="<?php echo esc_attr( $layer_label ); ?>"
	data-export-confidence="<?php echo esc_attr( (string) $conf_pct ); ?>"
	data-export-reason="<?php echo esc_attr( $reason_full ); ?>"
>
<td class="px-4 py-2 align-middle text-center ld-fg-log-check-cell" onclick="event.stopPropagation();">
<input type="checkbox" class="ld-fg-log-check w-4 h-4 rounded text-primary accent-primary-container cursor-pointer" value="<?php echo esc_attr( (string) $log['id'] ); ?>" />
</td>
<td class="px-1 py-2 align-middle text-center ld-fg-log-status-cell">
<?php if ( ! $is_read ) : ?>
<span class="w-2 h-2 rounded-full bg-primary inline-block ld-fg-log-unread-dot" title="<?php esc_attr_e( 'Unread', 'lancedesk-form-guard' ); ?>"></span>
<?php endif; ?>
</td>
<td class="px-3 py-2 align-middle whitespace-nowrap font-medium ld-fg-col-cell ld-fg-log-date-cell" data-col="timestamp">
<?php echo esc_html( $date_short ); ?>
<span class="block text-[11px] text-on-surface-variant font-normal">IP: <?php echo esc_html( $ip_short ); ?></span>
</td>
<td class="px-3 py-2 align-middle whitespace-nowrap ld-fg-col-cell ld-fg-log-form-cell" data-col="form">
<div class="flex items-center gap-1.5 font-medium ld-fg-log-form-inner">
<span class="ld-fg-icon material-symbols-outlined text-[16px] text-primary" aria-hidden="true">contact_page</span>
<span class="ld-fg-log-form-title"><?php echo esc_html( $form_title ); ?></span>
</div>
<?php if ( '' !== $form_id ) : ?>
<span class="text-[11px] text-on-surface-variant">ID: #<?php echo esc_html( $form_id ); ?></span>
<?php endif; ?>
</td>
<td class="px-3 py-2 align-middle whitespace-nowrap ld-fg-col-cell ld-fg-log-verdict-cell" data-col="verdict">
<?php if ( 'quarantined' === $verdict ) : ?>
<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-tertiary-fixed text-on-tertiary-fixed font-label-md text-label-md font-semibold">
<span class="ld-fg-icon material-symbols-outlined text-[14px]" aria-hidden="true"><?php echo esc_html( $verdict_icon ); ?></span>
<?php echo esc_html( strtoupper( $verdict_label ) ); ?>
</span>
<?php elseif ( 'allowed' === $verdict || 'clean' === $verdict ) : ?>
<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-secondary-fixed text-on-secondary-fixed-variant font-label-md text-label-md font-semibold">
<span class="ld-fg-icon material-symbols-outlined text-[14px]" aria-hidden="true"><?php echo esc_html( $verdict_icon ); ?></span>
<?php echo esc_html( strtoupper( $verdict_label ) ); ?>
</span>
<?php else : ?>
<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-error-container text-on-error-container font-label-md text-label-md font-semibold">
<span class="ld-fg-icon material-symbols-outlined text-[14px]" aria-hidden="true"><?php echo esc_html( $verdict_icon ); ?></span>
<?php echo esc_html( strtoupper( $verdict_label ) ); ?>
</span>
<?php endif; ?>
</td>
<td class="px-3 py-2 align-middle whitespace-nowrap ld-fg-col-cell ld-fg-log-detection-cell" data-col="layer">
<span class="inline-flex items-center gap-1 text-on-surface-variant ld-fg-log-layer-inner">
<span class="ld-fg-icon material-symbols-outlined text-[15px]" aria-hidden="true"><?php echo esc_html( $layer_icon ); ?></span>
<span class="ld-fg-log-layer-label"><?php echo esc_html( $layer_label ); ?></span>
</span>
</td>
<td class="px-3 py-2 align-middle whitespace-nowrap font-semibold ld-fg-col-cell ld-fg-log-confidence-cell <?php echo esc_attr( $conf_class ); ?>" data-col="confidence">
<?php echo esc_html( (string) $conf_pct ); ?>%
</td>
<td class="px-3 py-2 align-middle ld-fg-col-cell ld-fg-log-reason-cell" data-col="reason">
<span class="ld-fg-log-reason-text font-medium text-on-surface" title="<?php echo esc_attr( $reason_full ); ?>"><?php echo esc_html( $reason_full ); ?></span>
</td>
<td class="px-4 py-2 align-middle text-center whitespace-nowrap ld-fg-log-actions-cell">
<button type="button" class="ld-fg-log-view p-1 rounded text-on-surface-variant hover:text-primary hover:bg-surface-container bg-transparent border-0 cursor-pointer" data-log-id="<?php echo esc_attr( (string) $log['id'] ); ?>" title="<?php esc_attr_e( 'Inspect entry', 'lancedesk-form-guard' ); ?>">
<span class="ld-fg-icon material-symbols-outlined text-[18px]" aria-hidden="true">visibility</span>
</button>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- Footer -->
<div class="p-3 bg-surface-container-low flex flex-col md:flex-row items-center justify-between gap-3 text-body-sm font-body-sm">
<div class="text-on-surface-variant">
<?php
printf(
	/* translators: 1: start number, 2: end number, 3: total entries. */
	esc_html__( 'Showing %1$s to %2$s of %3$s entries', 'lancedesk-form-guard' ),
	'<span class="font-semibold text-on-surface">' . esc_html( (string) $from ) . '</span>',
	'<span class="font-semibold text-on-surface">' . esc_html( (string) $to ) . '</span>',
	'<span class="font-semibold text-on-surface">' . esc_html( (string) $total ) . '</span>'
);
?>
</div>
<?php if ( $total_pages > 1 ) : ?>
<nav class="ld-fg-logs-pagination flex items-center gap-1 select-none" aria-label="<?php esc_attr_e( 'Logs pagination', 'lancedesk-form-guard' ); ?>">
<?php if ( $paged > 1 ) : ?>
<a class="ld-fg-page-link px-2.5 py-1 rounded bg-surface-container-lowest text-on-surface hover:bg-surface-container no-underline" href="<?php echo esc_url( $logs_url( array( 'paged' => $paged - 1 ) ) ); ?>"><?php esc_html_e( '« Prev', 'lancedesk-form-guard' ); ?></a>
<?php else : ?>
<span class="px-2.5 py-1 rounded bg-surface-container-lowest text-on-surface-variant opacity-60"><?php esc_html_e( '« Prev', 'lancedesk-form-guard' ); ?></span>
<?php endif; ?>
<?php
$page_window = 2;
$start_page  = max( 1, $paged - $page_window );
$end_page    = min( $total_pages, $paged + $page_window );
if ( $start_page > 1 ) :
	?>
<a class="ld-fg-page-link px-3 py-1 rounded bg-surface-container-lowest text-on-surface hover:bg-surface-container no-underline" href="<?php echo esc_url( $logs_url( array( 'paged' => 1 ) ) ); ?>">1</a>
<?php if ( $start_page > 2 ) : ?>
<span class="px-1 text-on-surface-variant">…</span>
<?php endif; ?>
<?php endif; ?>
<?php for ( $i = $start_page; $i <= $end_page; $i++ ) : ?>
<?php if ( $i === $paged ) : ?>
<span class="px-3 py-1 rounded bg-primary-container text-on-primary font-semibold" aria-current="page"><?php echo esc_html( (string) $i ); ?></span>
<?php else : ?>
<a class="ld-fg-page-link px-3 py-1 rounded bg-surface-container-lowest text-on-surface hover:bg-surface-container no-underline" href="<?php echo esc_url( $logs_url( array( 'paged' => $i ) ) ); ?>"><?php echo esc_html( (string) $i ); ?></a>
<?php endif; ?>
<?php endfor; ?>
<?php if ( $end_page < $total_pages ) : ?>
<?php if ( $end_page < $total_pages - 1 ) : ?>
<span class="px-1 text-on-surface-variant">…</span>
<?php endif; ?>
<a class="ld-fg-page-link px-3 py-1 rounded bg-surface-container-lowest text-on-surface hover:bg-surface-container no-underline" href="<?php echo esc_url( $logs_url( array( 'paged' => $total_pages ) ) ); ?>"><?php echo esc_html( (string) $total_pages ); ?></a>
<?php endif; ?>
<?php if ( $paged < $total_pages ) : ?>
<a class="ld-fg-page-link px-2.5 py-1 rounded bg-surface-container-lowest text-on-surface hover:bg-surface-container no-underline" href="<?php echo esc_url( $logs_url( array( 'paged' => $paged + 1 ) ) ); ?>"><?php esc_html_e( 'Next »', 'lancedesk-form-guard' ); ?></a>
<?php else : ?>
<span class="px-2.5 py-1 rounded bg-surface-container-lowest text-on-surface-variant opacity-60"><?php esc_html_e( 'Next »', 'lancedesk-form-guard' ); ?></span>
<?php endif; ?>
</nav>
<?php else : ?>
<div></div>
<?php endif; ?>
<?php if ( $page_stats['total'] > 0 ) : ?>
<div class="flex items-center">
<button type="button" class="ld-fg-delete-all-logs text-error font-body-sm text-body-sm hover:underline font-medium inline-flex items-center gap-1 bg-transparent border-0 cursor-pointer p-0">
<span class="ld-fg-icon material-symbols-outlined text-[16px]" aria-hidden="true">delete_forever</span>
<?php esc_html_e( 'Delete all logs', 'lancedesk-form-guard' ); ?>
</button>
</div>
<?php else : ?>
<div></div>
<?php endif; ?>
</div>
</div>

<div class="mt-4 flex flex-wrap items-center justify-between text-label-md font-label-md text-on-surface-variant">
<div class="flex items-center gap-2">
<span class="w-2 h-2 rounded-full bg-secondary"></span>
<?php
printf(
	/* translators: %s: plugin version. */
	esc_html__( 'LanceDesk Form Guard v%s • Security log audit trail', 'lancedesk-form-guard' ),
	esc_html( LD_FG_VERSION )
);
?>
</div>
</div>

<?php require LD_FG_PLUGIN_DIR . 'admin/partials/part-log-drawer.php'; ?>
<?php require LD_FG_PLUGIN_DIR . 'admin/partials/part-log-delete-modal.php'; ?>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

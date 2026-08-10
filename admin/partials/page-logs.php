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
<?php
$integration_labels = array(
	'wpforms' => __( 'WPForms', 'lancedesk-form-guard' ),
	'cf7'     => __( 'Contact Form 7', 'lancedesk-form-guard' ),
	'gravity' => __( 'Gravity Forms', 'lancedesk-form-guard' ),
	'ninja'   => __( 'Ninja Forms', 'lancedesk-form-guard' ),
	'quform'  => __( 'Quform', 'lancedesk-form-guard' ),
);
$action_labels      = array(
	'silent_discard' => __( 'Silent Discard', 'lancedesk-form-guard' ),
	'soft_fail'      => __( 'Soft Fail', 'lancedesk-form-guard' ),
	'quarantine'     => __( 'Quarantined', 'lancedesk-form-guard' ),
	'allow'          => __( 'Allowed', 'lancedesk-form-guard' ),
);
$provider_labels    = array(
	'groq'      => 'Groq',
	'openai'    => 'OpenAI',
	'anthropic' => 'Anthropic',
);
?>
<?php foreach ( $logs as $log ) :
	$conf_pct    = (int) round( (float) $log['confidence'] * 100 );
	$verdict     = (string) $log['verdict'];
	$layer       = (string) ( $log['layer'] ?? '' );
	$action      = (string) ( $log['action'] ?? '' );
	$integration = (string) ( $log['integration'] ?? '' );
	$form_title  = (string) ( $log['form_title'] ?: $log['form_id'] );
	$date_label  = mysql2date( 'M j, Y - H:i:s', $log['created_at'] );
	$meta        = array();
	if ( ! empty( $log['meta'] ) ) {
		$decoded = json_decode( (string) $log['meta'], true );
		$meta    = is_array( $decoded ) ? $decoded : array();
	}
	$provider_key = sanitize_key( (string) ( $meta['provider'] ?? '' ) );
	$fields       = isset( $meta['fields'] ) && is_array( $meta['fields'] ) ? $meta['fields'] : array();
	$snippet      = (string) ( $log['snippet'] ?? '' );
	$layer_label  = 'ai' === $layer
		? __( 'AI Analysis', 'lancedesk-form-guard' )
		: ( 'none' === $layer ? __( 'None', 'lancedesk-form-guard' ) : __( 'Heuristic Analysis', 'lancedesk-form-guard' ) );
	$integration_label = $integration_labels[ $integration ] ?? ( $integration ? ucwords( str_replace( '_', ' ', $integration ) ) : '—' );
	$action_label      = $action_labels[ $action ] ?? ( $action ? ucwords( str_replace( '_', ' ', $action ) ) : '—' );
	$provider_label    = '' !== $provider_key
		? ( $provider_labels[ $provider_key ] ?? ucwords( $provider_key ) )
		: ( 'ai' === $layer ? '—' : __( 'Local', 'lancedesk-form-guard' ) );
	if ( 'quarantined' === $verdict ) {
		$verdict_label = __( 'Quarantined', 'lancedesk-form-guard' );
		$title_label   = __( 'Form Submission Quarantined', 'lancedesk-form-guard' );
	} elseif ( 'allowed' === $verdict || 'clean' === $verdict ) {
		$verdict_label = __( 'Allowed', 'lancedesk-form-guard' );
		$title_label   = __( 'Form Submission Allowed', 'lancedesk-form-guard' );
	} else {
		$verdict_label = __( 'Blocked', 'lancedesk-form-guard' );
		$title_label   = __( 'Form Submission Blocked', 'lancedesk-form-guard' );
	}
	$payload = array(
		'id'                => (int) $log['id'],
		'verdict'           => $verdict,
		'verdict_label'     => $verdict_label,
		'title'             => $title_label,
		'form'              => $form_title,
		'date'              => $date_label,
		'integration'       => $integration_label,
		'layer'             => $layer_label,
		'layer_key'         => $layer,
		'action'            => $action_label,
		'provider'          => $provider_label,
		'provider_key'      => $provider_key,
		'confidence'        => $conf_pct,
		'reason'            => (string) ( $log['reason'] ?? '' ),
		'snippet'           => $snippet,
		'fields'            => $fields,
		'is_quarantine'     => ( 'quarantined' === $verdict || 'quarantine' === $action ),
	);
	?>
<tr
	class="hover:bg-surface-container-lowest transition-colors border-t border-outline-variant/30 group ld-fg-log-row"
	data-log-id="<?php echo esc_attr( (string) $log['id'] ); ?>"
	data-log="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>"
>
<td class="px-margin-md py-3 whitespace-nowrap">
<div class="font-medium"><?php echo esc_html( mysql2date( 'M j, H:i:s', $log['created_at'] ) ); ?></div>
</td>
<td class="px-margin-md py-3 whitespace-nowrap">
<div class="flex items-center gap-2">
<span class="ld-fg-icon material-symbols-outlined text-outline text-[16px]" aria-hidden="true">contact_page</span>
<span><?php echo esc_html( $form_title ); ?></span>
</div>
</td>
<td class="px-margin-md py-3 whitespace-nowrap">
<?php if ( 'quarantined' === $verdict ) : ?>
<span class="inline-flex items-center px-2 py-1 rounded bg-tertiary-container text-on-tertiary-container font-label-md text-[11px] uppercase tracking-wide"><?php echo esc_html( $verdict_label ); ?></span>
<?php elseif ( 'allowed' === $verdict || 'clean' === $verdict ) : ?>
<span class="inline-flex items-center px-2 py-1 rounded bg-[#e8f5e9] text-[#2e7d32] font-label-md text-[11px] uppercase tracking-wide"><?php echo esc_html( $verdict_label ); ?></span>
<?php else : ?>
<span class="inline-flex items-center px-2 py-1 rounded bg-error-container text-on-error-container font-label-md text-[11px] uppercase tracking-wide"><?php echo esc_html( $verdict_label ); ?></span>
<?php endif; ?>
</td>
<td class="px-margin-md py-3 whitespace-nowrap">
<div class="flex items-center gap-1.5">
<span class="ld-fg-icon material-symbols-outlined text-primary text-[14px]" aria-hidden="true"><?php echo 'ai' === $layer ? 'psychology' : 'rule'; ?></span>
<?php echo esc_html( $layer_label ); ?>
</div>
</td>
<td class="px-margin-md py-3 whitespace-nowrap text-right">
<span class="font-medium"><?php echo esc_html( (string) $conf_pct ); ?>%</span>
</td>
<td class="px-margin-md py-3">
<div class="truncate max-w-[200px] text-on-surface-variant" title="<?php echo esc_attr( (string) $log['reason'] ); ?>"><?php echo esc_html( wp_trim_words( (string) $log['reason'], 6, '...' ) ); ?></div>
</td>
<td class="px-margin-md py-3 text-center">
<button type="button" class="ld-fg-log-view text-outline hover:text-primary transition-colors p-1 rounded hover:bg-surface-container" data-log-id="<?php echo esc_attr( (string) $log['id'] ); ?>" title="<?php esc_attr_e( 'View Details', 'lancedesk-form-guard' ); ?>">
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

<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

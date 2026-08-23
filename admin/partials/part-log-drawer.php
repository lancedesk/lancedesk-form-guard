<?php
/**
 * Log detail drawer markup (hidden by default).
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="ld-fg-drawer-overlay" class="fixed inset-0 bg-on-surface/20 z-40 backdrop-blur-[1px] transition-opacity duration-300 hidden opacity-0" aria-hidden="true"></div>
<div id="ld-fg-log-drawer" class="fixed top-[32px] right-0 h-[calc(100vh-32px)] w-[420px] bg-surface z-50 shadow-2xl border-l border-outline transform transition-transform duration-300 translate-x-full flex flex-col hidden" aria-hidden="true">
<div class="p-margin-lg border-b border-outline flex-shrink-0 bg-surface-container-lowest">
<div class="flex items-start justify-between mb-margin-sm">
<div class="flex items-center gap-margin-sm flex-wrap">
<span id="ld-fg-drawer-verdict-badge" class="px-2.5 py-1 rounded-full bg-error-container text-on-error-container font-label-md text-[11px] uppercase font-bold tracking-wider flex items-center gap-1">
<span class="ld-fg-icon material-symbols-outlined text-[14px]" style="font-variation-settings: 'FILL' 1;" data-icon>block</span>
<span data-label><?php esc_html_e( 'Blocked', 'lancedesk-form-guard' ); ?></span>
</span>
<span id="ld-fg-drawer-form" class="font-body-sm text-body-sm text-on-surface-variant bg-surface-container px-2 py-1 rounded"></span>
</div>
<button type="button" class="ld-fg-drawer-close ld-fg-drawer-close-icon" aria-label="<?php esc_attr_e( 'Close', 'lancedesk-form-guard' ); ?>">
<span class="ld-fg-icon material-symbols-outlined text-[20px]">close</span>
</button>
</div>
<h2 id="ld-fg-drawer-title" class="font-headline-md text-headline-md text-on-surface mb-1"><?php esc_html_e( 'Form Submission Blocked', 'lancedesk-form-guard' ); ?></h2>
<div id="ld-fg-drawer-date" class="font-body-sm text-body-sm text-on-surface-variant flex items-center gap-1.5">
<span class="ld-fg-icon material-symbols-outlined text-[14px]">calendar_today</span>
<span id="ld-fg-drawer-date-text"></span>
</div>
</div>
<div class="flex-1 overflow-y-auto p-margin-lg bg-surface">
<div class="grid grid-cols-2 gap-x-margin-md gap-y-margin-lg mb-margin-lg p-margin-md rounded-lg border border-outline bg-surface-container-low">
<div>
<div class="font-label-md text-label-md text-on-surface-variant mb-1 uppercase tracking-wider text-[10px]"><?php esc_html_e( 'Integration', 'lancedesk-form-guard' ); ?></div>
<div id="ld-fg-drawer-integration" class="font-body-sm text-body-sm text-on-surface font-medium flex items-center gap-2">
<span class="ld-fg-icon material-symbols-outlined text-[16px] text-primary">webhook</span>
<span data-value></span>
</div>
</div>
<div>
<div class="font-label-md text-label-md text-on-surface-variant mb-1 uppercase tracking-wider text-[10px]"><?php esc_html_e( 'Layer', 'lancedesk-form-guard' ); ?></div>
<div id="ld-fg-drawer-layer" class="font-body-sm text-body-sm text-on-surface font-medium flex items-center gap-2">
<span class="ld-fg-icon material-symbols-outlined text-[16px] text-tertiary" data-icon>smart_toy</span>
<span data-value></span>
</div>
</div>
<div>
<div class="font-label-md text-label-md text-on-surface-variant mb-1 uppercase tracking-wider text-[10px]"><?php esc_html_e( 'Action Taken', 'lancedesk-form-guard' ); ?></div>
<div id="ld-fg-drawer-action" class="font-body-sm text-body-sm text-on-surface font-medium"></div>
</div>
<div>
<div class="font-label-md text-label-md text-on-surface-variant mb-1 uppercase tracking-wider text-[10px]"><?php esc_html_e( 'Provider', 'lancedesk-form-guard' ); ?></div>
<div id="ld-fg-drawer-provider" class="font-body-sm text-body-sm text-on-surface font-medium flex items-center gap-2">
<span id="ld-fg-drawer-provider-badge" class="w-4 h-4 rounded-sm bg-surface-container flex items-center justify-center font-label-md text-[10px] border border-outline hidden"></span>
<span data-value></span>
</div>
</div>
<div class="col-span-2">
<div class="font-label-md text-label-md text-on-surface-variant mb-2 uppercase tracking-wider text-[10px] flex justify-between">
<span><?php esc_html_e( 'Confidence Score', 'lancedesk-form-guard' ); ?></span>
<span id="ld-fg-drawer-confidence-label" class="text-error font-semibold"></span>
</div>
<div class="w-full h-1.5 bg-surface-container-high rounded-full overflow-hidden">
<div id="ld-fg-drawer-confidence-bar" class="h-full bg-error rounded-full" style="width: 0%;"></div>
</div>
</div>
</div>
<div class="mb-margin-lg">
<h3 class="font-label-md text-label-md text-on-surface font-semibold mb-2 flex items-center gap-2 border-b border-outline pb-2">
<span class="ld-fg-icon material-symbols-outlined text-[18px] text-error">policy</span>
<?php esc_html_e( 'Detection Reason', 'lancedesk-form-guard' ); ?>
</h3>
<div id="ld-fg-drawer-reason" class="p-3 rounded border-l-4 border-error bg-error-container/10 font-body-sm text-body-sm text-on-surface"></div>
</div>
<div>
<h3 class="font-label-md text-label-md text-on-surface font-semibold mb-3 flex items-center gap-2 border-b border-outline pb-2">
<span class="ld-fg-icon material-symbols-outlined text-[18px] text-primary">data_object</span>
<?php esc_html_e( 'Captured Fields', 'lancedesk-form-guard' ); ?>
</h3>
<div id="ld-fg-drawer-fields" class="space-y-4"></div>
</div>
</div>
<div class="p-margin-md border-t border-outline bg-surface flex-shrink-0 flex items-center justify-between gap-margin-sm shadow-[0_-2px_4px_rgba(0,0,0,0.02)]">
<button type="button" class="ld-fg-drawer-close h-8 px-4 rounded bg-transparent border-0 text-on-surface-variant font-label-md text-label-md hover:bg-surface-container transition-colors"><?php esc_html_e( 'Close', 'lancedesk-form-guard' ); ?></button>
<div id="ld-fg-drawer-quarantine-actions" class="flex gap-margin-sm">
<button type="button" class="ld-fg-discard-quarantine h-8 px-4 rounded bg-surface border border-outline text-error font-label-md text-label-md hover:bg-error-container/10 transition-colors flex items-center gap-1.5">
<span class="ld-fg-icon material-symbols-outlined text-[16px]">delete</span>
<?php esc_html_e( 'Discard', 'lancedesk-form-guard' ); ?>
</button>
<button type="button" class="ld-fg-release-quarantine h-8 px-4 rounded bg-primary text-on-primary font-label-md text-label-md hover:bg-primary/90 transition-colors shadow-sm flex items-center gap-1.5">
<span class="ld-fg-icon material-symbols-outlined text-[16px]">check_circle</span>
<?php esc_html_e( 'Release', 'lancedesk-form-guard' ); ?>
</button>
</div>
</div>
</div>

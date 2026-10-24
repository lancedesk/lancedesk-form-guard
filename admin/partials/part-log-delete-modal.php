<?php
/**
 * Delete security log(s) confirm modal (FG-L1d).
 *
 * Supports delete-all and delete-single via data-* copy switches in admin JS.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div
	class="ld-fg-delete-modal-overlay fixed inset-0 z-[100] hidden"
	id="ld-fg-delete-modal-overlay"
	hidden
	aria-hidden="true"
	data-mode="all"
	data-log-id=""
	data-title-all="<?php echo esc_attr__( 'Delete all security logs?', 'lancedesk-form-guard' ); ?>"
	data-body-all="<?php echo esc_attr__( 'This permanently removes every log entry across all forms. This action cannot be undone and audit history will be permanently lost.', 'lancedesk-form-guard' ); ?>"
	data-confirm-all="<?php echo esc_attr__( 'Delete all logs', 'lancedesk-form-guard' ); ?>"
	data-title-single="<?php echo esc_attr__( 'Delete this security log?', 'lancedesk-form-guard' ); ?>"
	data-body-single="<?php echo esc_attr__( 'This permanently removes this log entry. This action cannot be undone.', 'lancedesk-form-guard' ); ?>"
	data-confirm-single="<?php echo esc_attr__( 'Delete log', 'lancedesk-form-guard' ); ?>"
>
<div class="ld-fg-delete-modal relative w-full max-w-[460px] bg-white rounded-lg shadow-2xl overflow-hidden" role="dialog" aria-modal="true" aria-labelledby="ld-fg-delete-modal-title">
<div class="h-1 w-full bg-[#d63638]"></div>
<div class="p-6">
<div class="flex items-start gap-4">
<div class="flex-shrink-0 w-11 h-11 rounded-full bg-[#fee2e2] flex items-center justify-center text-[#d63638]">
<span class="ld-fg-icon material-symbols-outlined text-[24px]" aria-hidden="true">warning</span>
</div>
<div class="flex-1 min-w-0">
<h2 id="ld-fg-delete-modal-title" class="ld-fg-delete-modal-title text-[18px] leading-[24px] font-bold text-[#1d2327] mb-2 mt-0 font-headline-md">
<?php esc_html_e( 'Delete all security logs?', 'lancedesk-form-guard' ); ?>
</h2>
<p class="ld-fg-delete-modal-body text-[14px] leading-[20px] text-[#646970] font-body-lg mb-4">
<?php esc_html_e( 'This permanently removes every log entry across all forms. This action cannot be undone and audit history will be permanently lost.', 'lancedesk-form-guard' ); ?>
</p>
<div class="rounded-md bg-[#fcf9e8] p-3 text-[13px] leading-[18px] text-[#614800] flex items-start gap-2.5">
<span class="ld-fg-icon material-symbols-outlined text-[#8f6d00] text-[18px] flex-shrink-0 mt-0.5" aria-hidden="true">info</span>
<div>
<strong class="font-semibold text-[#483700]"><?php esc_html_e( 'Note:', 'lancedesk-form-guard' ); ?></strong>
<?php esc_html_e( ' Ongoing form protection and rule enforcement will remain active.', 'lancedesk-form-guard' ); ?>
</div>
</div>
</div>
</div>
</div>
<div class="bg-[#f6f7f7] px-5 py-4 flex items-center justify-between gap-3 flex-wrap">
<div class="flex items-center gap-1.5 text-[12px] text-[#646970]">
<span class="ld-fg-icon material-symbols-outlined text-[16px]" aria-hidden="true">shield</span>
<span><?php echo esc_html( sprintf( /* translators: %s: version. */ __( 'Form Guard v%s', 'lancedesk-form-guard' ), LD_FG_VERSION ) ); ?></span>
</div>
<div class="flex items-center gap-3">
<button type="button" class="ld-fg-delete-modal-cancel px-4 py-1.5 rounded bg-white text-[#2c3338] text-[13px] font-medium shadow-sm hover:bg-[#f0f0f1] border-0 cursor-pointer">
<?php esc_html_e( 'Cancel', 'lancedesk-form-guard' ); ?>
</button>
<button type="button" class="ld-fg-delete-modal-confirm px-4 py-1.5 rounded bg-[#d63638] text-white text-[13px] font-medium shadow-sm hover:bg-[#b32d2e] border-0 cursor-pointer inline-flex items-center gap-1.5">
<span class="ld-fg-icon material-symbols-outlined text-[16px]" aria-hidden="true">delete_forever</span>
<span class="ld-fg-delete-modal-confirm-label"><?php esc_html_e( 'Delete all logs', 'lancedesk-form-guard' ); ?></span>
</button>
</div>
</div>
</div>
</div>

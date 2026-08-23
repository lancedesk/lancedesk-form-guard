<?php
/**
 * Forms empty state.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="flex flex-col w-full h-full justify-center items-center">
<div class="flex flex-col items-center justify-center max-w-md mx-auto text-center px-4">
<div class="relative mb-6">
<div class="absolute inset-0 bg-primary/10 rounded-full blur-xl transform scale-150"></div>
<div class="w-24 h-24 rounded-full bg-surface-container flex items-center justify-center relative shadow-sm mx-auto">
<span class="ld-fg-icon material-symbols-outlined text-[48px] text-primary" style="font-variation-settings: 'FILL' 1;">list_alt_add</span>
</div>
</div>
<h2 class="font-headline-lg text-headline-lg text-on-surface mb-2"><?php esc_html_e( 'No compatible form plugins detected', 'lancedesk-form-guard' ); ?></h2>
<p class="font-body-lg text-body-lg text-on-surface-variant mb-8 max-w-sm"><?php esc_html_e( 'Install a supported form plugin to protect contact forms here. WordPress post comments can be protected without a form plugin — see Settings → WordPress Core.', 'lancedesk-form-guard' ); ?></p>
<div class="flex flex-col sm:flex-row items-center gap-4 w-full justify-center">
<a class="bg-primary text-on-primary font-label-md text-label-md px-6 py-3 rounded-lg shadow-sm hover:bg-primary/90 transition-colors flex items-center justify-center min-w-[200px]" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-integrations' ) ); ?>">
<?php esc_html_e( 'Open Integrations', 'lancedesk-form-guard' ); ?>
<span class="ld-fg-icon material-symbols-outlined ml-2 text-[18px]">arrow_forward</span>
</a>
</div>
</div>
</div>

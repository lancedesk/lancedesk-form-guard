<?php
/**
 * Settings page content.
 *
 * @package LanceDesk_Form_Guard
 *
 * @var array<string,mixed> $ld_fg_settings Plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$enabled           = ! empty( $ld_fg_settings['enabled'] );
$retention         = (int) ( $ld_fg_settings['retention_days'] ?? 90 );
$delete_uninstall  = ! empty( $ld_fg_settings['delete_on_uninstall'] );
$privacy_ack       = ! empty( $ld_fg_settings['privacy_ack'] );
?>
<div class="flex flex-col w-full">
<div class="flex justify-between items-center mb-6">
<h1 class="font-headline-lg text-headline-lg text-on-surface m-0"><?php esc_html_e( 'Settings', 'lancedesk-form-guard' ); ?></h1>
</div>
<form class="space-y-margin-lg" id="ld-fg-settings-form">
<div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden transition-all duration-300 hover:shadow-md">
<div class="px-margin-lg py-margin-md border-b border-outline-variant/50 bg-surface-container-lowest flex items-center justify-between">
<div>
<h2 class="font-headline-md text-headline-md text-on-surface"><?php esc_html_e( 'Global Configuration', 'lancedesk-form-guard' ); ?></h2>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-unit"><?php esc_html_e( 'Core operational settings for form protection.', 'lancedesk-form-guard' ); ?></p>
</div>
<span class="ld-fg-icon material-symbols-outlined text-outline-variant text-[28px]" style="font-variation-settings: 'FILL' 1;">tune</span>
</div>
<div class="p-margin-lg space-y-margin-lg">
<div class="flex items-start justify-between p-margin-md bg-surface-container-low rounded-lg border border-outline-variant/30">
<div class="flex-1 pr-margin-md">
<label class="font-headline-lg text-[18px] leading-tight text-on-surface cursor-pointer select-none" for="ld-fg-master-toggle"><?php esc_html_e( 'Enable Form Guard Protection', 'lancedesk-form-guard' ); ?></label>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-unit"><?php esc_html_e( 'Master switch to activate or deactivate spam and abuse protection across all connected forms.', 'lancedesk-form-guard' ); ?></p>
</div>
<label class="relative inline-flex items-center h-6 mt-1 cursor-pointer" for="ld-fg-master-toggle">
<input class="sr-only peer" id="ld-fg-master-toggle" name="enabled" type="checkbox" value="1" <?php checked( $enabled ); ?> />
<div class="relative w-11 h-6 bg-outline-variant rounded-full peer peer-focus:ring-2 peer-focus:ring-primary/20 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-outline-variant after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary pointer-events-none" aria-hidden="true"></div>
</label>
</div>
<div class="space-y-unit">
<label class="block font-label-md text-label-md text-on-surface" for="ld-fg-log-retention"><?php esc_html_e( 'Log Retention Period', 'lancedesk-form-guard' ); ?></label>
<div class="relative w-full max-w-xs">
<select class="w-full appearance-none bg-surface-container-lowest border border-outline-variant rounded-lg px-margin-md py-2.5 font-body-lg text-body-lg text-on-surface focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-colors cursor-pointer" id="ld-fg-log-retention" name="retention_days">
<option value="30" <?php selected( $retention, 30 ); ?>><?php esc_html_e( '30 Days', 'lancedesk-form-guard' ); ?></option>
<option value="90" <?php selected( $retention, 90 ); ?>><?php esc_html_e( '90 Days', 'lancedesk-form-guard' ); ?></option>
<option value="180" <?php selected( $retention, 180 ); ?>><?php esc_html_e( '180 Days', 'lancedesk-form-guard' ); ?></option>
</select>
<div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-outline">
<span class="ld-fg-icon material-symbols-outlined text-[20px]">expand_more</span>
</div>
</div>
<p class="font-body-sm text-body-sm text-on-surface-variant"><?php esc_html_e( 'Duration to keep security logs before automatic deletion.', 'lancedesk-form-guard' ); ?></p>
</div>
<div class="flex items-start mt-margin-md">
<div class="flex items-center h-5">
<input class="w-4 h-4 text-primary bg-surface-container-lowest border-outline-variant rounded focus:ring-primary/20 focus:ring-2 cursor-pointer transition-colors" id="ld-fg-delete-on-uninstall" name="delete_on_uninstall" type="checkbox" value="1" <?php checked( $delete_uninstall ); ?> />
</div>
<div class="ml-3 text-sm">
<label class="font-label-md text-label-md text-on-surface cursor-pointer select-none" for="ld-fg-delete-on-uninstall"><?php esc_html_e( 'Delete logs on uninstall', 'lancedesk-form-guard' ); ?></label>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-0.5"><?php esc_html_e( 'Remove all stored data when removing the plugin from this site.', 'lancedesk-form-guard' ); ?></p>
</div>
</div>
</div>
</div>
<div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm overflow-hidden relative">
<div class="px-margin-lg py-margin-md border-b border-outline-variant/50 bg-surface-container-lowest relative z-10">
<h2 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
<span class="ld-fg-icon material-symbols-outlined text-primary text-[20px]">policy</span>
<?php esc_html_e( 'Privacy & Data Processing', 'lancedesk-form-guard' ); ?>
</h2>
</div>
<div class="p-margin-lg space-y-margin-md relative z-10">
<div class="p-margin-md bg-secondary-container/20 rounded-lg border border-secondary-container/50">
<h3 class="font-label-md text-label-md text-on-surface mb-unit"><?php esc_html_e( 'AI Processing Acknowledgement', 'lancedesk-form-guard' ); ?></h3>
<p class="font-body-sm text-body-sm text-on-surface-variant mb-margin-sm leading-relaxed"><?php esc_html_e( 'By enabling form protection, submission data may be securely transmitted to configured AI providers for spam analysis. Only necessary form fields are processed. We recommend reviewing your privacy policy to ensure compliance with local data regulations regarding third-party processing.', 'lancedesk-form-guard' ); ?></p>
<div class="flex items-start mt-4 pt-4 border-t border-secondary-container/30">
<div class="flex items-center h-5">
<input class="w-4 h-4 text-primary bg-surface-container-lowest border-outline-variant rounded focus:ring-primary/20 focus:ring-2 cursor-pointer transition-colors" id="ld-fg-privacy-ack" name="privacy_ack" type="checkbox" value="1" <?php checked( $privacy_ack ); ?> required />
</div>
<div class="ml-3 text-sm">
<label class="font-label-md text-label-md text-on-surface cursor-pointer select-none" for="ld-fg-privacy-ack"><?php esc_html_e( 'I acknowledge that submission data will be processed by external AI providers.', 'lancedesk-form-guard' ); ?></label>
</div>
</div>
</div>
</div>
</div>
<div class="flex items-center justify-end pt-margin-sm gap-margin-sm sticky bottom-container-padding bg-surface/80 backdrop-blur-md p-margin-md rounded-xl border border-outline-variant/50 shadow-sm z-20">
<div class="text-body-sm font-body-sm text-primary opacity-0 transition-opacity duration-300 mr-auto flex items-center gap-2" id="ld-fg-save-status">
<span class="ld-fg-icon material-symbols-outlined text-[16px]">check_circle</span>
<?php esc_html_e( 'Settings saved successfully', 'lancedesk-form-guard' ); ?>
</div>
<button class="px-6 py-2 font-label-md text-label-md text-on-primary bg-primary hover:bg-on-primary-fixed-variant rounded-lg shadow-sm hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-surface flex items-center gap-2 group" id="ld-fg-save-btn" type="submit">
<span><?php esc_html_e( 'Save Settings', 'lancedesk-form-guard' ); ?></span>
<span class="ld-fg-icon material-symbols-outlined text-[18px] group-hover:translate-x-0.5 transition-transform">save</span>
</button>
</div>
</form>
</div>

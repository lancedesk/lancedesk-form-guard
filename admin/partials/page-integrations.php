<?php
/**
 * Integrations page content.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin   = LD_FG_Plugin::instance();
$adapters = array(
	'wpforms' => array( 'icon' => 'description', 'class' => LD_FG_Adapter_WPForms::class ),
	'cf7'     => array( 'icon' => 'mail', 'class' => LD_FG_Adapter_CF7::class ),
	'gravity' => array( 'icon' => 'layers', 'class' => LD_FG_Adapter_Gravity::class ),
	'quform'  => array( 'icon' => 'edit_note', 'class' => LD_FG_Adapter_Quform::class ),
	'ninja'   => array( 'icon' => 'bolt', 'class' => LD_FG_Adapter_Ninja::class ),
);
?>
<div class="flex flex-col w-full">
<div class="mb-margin-lg">
<h1 class="font-hero-title text-hero-title text-on-surface mb-margin-sm"><?php esc_html_e( 'Integrations', 'lancedesk-form-guard' ); ?></h1>
<p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl"><?php esc_html_e( 'Form Guard hooks your installed form plugins before notification emails are sent.', 'lancedesk-form-guard' ); ?></p>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
<?php foreach ( $adapters as $slug => $meta ) : ?>
<?php
/** @var LD_FG_Form_Adapter $adapter */
$adapter     = $plugin->get_adapters()[ $slug ] ?? null;
$available   = $adapter && $adapter->is_available();
$forms       = $available ? $adapter->list_forms() : array();
$form_count  = count( $forms );
$card_class  = $available ? 'bg-surface-container rounded-lg p-margin-md shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow' : 'bg-surface-container/50 rounded-lg p-margin-md relative overflow-hidden opacity-75';
?>
<div class="<?php echo esc_attr( $card_class ); ?>">
<div class="flex justify-between items-start mb-margin-md">
<div class="w-12 h-12 <?php echo $available ? 'bg-primary-container text-on-primary-container' : 'bg-surface-variant/50 text-outline'; ?> rounded-full flex items-center justify-center shrink-0">
<span class="ld-fg-icon material-symbols-outlined text-[24px]"><?php echo esc_html( $meta['icon'] ); ?></span>
</div>
<?php if ( $available && $form_count > 0 ) : ?>
<span class="bg-surface-tint/10 text-surface-tint font-label-md text-label-md px-2 py-1 rounded"><?php esc_html_e( 'Active', 'lancedesk-form-guard' ); ?></span>
<?php elseif ( $available ) : ?>
<span class="bg-outline-variant/30 text-on-surface-variant font-label-md text-label-md px-2 py-1 rounded"><?php esc_html_e( 'Installed - Inactive', 'lancedesk-form-guard' ); ?></span>
<?php else : ?>
<span class="bg-outline-variant/20 text-outline font-label-md text-label-md px-2 py-1 rounded"><?php esc_html_e( 'Not installed', 'lancedesk-form-guard' ); ?></span>
<?php endif; ?>
</div>
<h3 class="font-headline-md text-headline-md <?php echo $available ? 'text-on-surface' : 'text-outline'; ?> mb-unit"><?php echo esc_html( $adapter ? $adapter->get_label() : ucfirst( $slug ) ); ?></h3>
<p class="font-body-sm text-body-sm <?php echo $available ? 'text-on-surface-variant' : 'text-outline'; ?> mb-margin-md">
<?php
if ( ! $available ) {
	esc_html_e( 'Plugin missing', 'lancedesk-form-guard' );
} elseif ( $form_count > 0 ) {
	/* translators: %d: number of forms. */
	echo esc_html( sprintf( _n( '%d form detected', '%d forms detected', $form_count, 'lancedesk-form-guard' ), $form_count ) );
} else {
	esc_html_e( 'No active forms', 'lancedesk-form-guard' );
}
?>
</p>
<?php if ( $available && $form_count > 0 ) : ?>
<a class="bg-primary text-on-primary font-label-md text-label-md px-4 py-2 rounded-lg hover:bg-primary-fixed-variant transition-colors w-full inline-block text-center" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-forms' ) ); ?>"><?php esc_html_e( 'Manage forms', 'lancedesk-form-guard' ); ?></a>
<?php elseif ( $available ) : ?>
<a class="bg-transparent text-primary font-label-md text-label-md px-4 py-2 rounded-lg hover:bg-surface-variant transition-colors w-full inline-block text-center" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-forms' ) ); ?>"><?php esc_html_e( 'Activate Integration', 'lancedesk-form-guard' ); ?></a>
<?php else : ?>
<button type="button" class="bg-transparent text-outline font-label-md text-label-md px-4 py-2 rounded-lg cursor-not-allowed w-full" disabled><?php esc_html_e( 'Unavailable', 'lancedesk-form-guard' ); ?></button>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
</div>

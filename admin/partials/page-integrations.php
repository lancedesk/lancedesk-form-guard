<?php
/**
 * Integrations page content.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View template locals.

$plugin            = LD_FG_Plugin::instance();
$core_adapter      = $plugin->get_adapters()['core'] ?? null;
$core_comments_on  = ! empty( $ld_fg_settings['protect_core_comments'] );
$adapters          = array(
	'wpforms' => array( 'icon' => 'description' ),
	'cf7'     => array( 'icon' => 'mail' ),
	'gravity' => array( 'icon' => 'layers' ),
	'quform'  => array( 'icon' => 'edit_note' ),
	'ninja'   => array( 'icon' => 'bolt' ),
);

$installed = array();
$missing   = array();

foreach ( $adapters as $slug => $meta ) {
	/** @var LD_FG_Form_Adapter|null $adapter */
	$adapter = $plugin->get_adapters()[ $slug ] ?? null;
	if ( ! $adapter ) {
		continue;
	}

	if ( $adapter->is_available() ) {
		$installed[ $slug ] = array(
			'adapter' => $adapter,
			'icon'    => $meta['icon'],
			'forms'   => $adapter->list_forms(),
		);
	} else {
		$missing[] = $adapter->get_label();
	}
}
?>
<div class="flex flex-col w-full">
<div class="flex justify-between items-center mb-6">
<h1 class="font-headline-lg text-headline-lg text-on-surface m-0"><?php esc_html_e( 'Integrations', 'lancedesk-form-guard' ); ?></h1>
</div>
<p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mb-6"><?php esc_html_e( 'Form Guard hooks your installed form plugins — and native WordPress comment forms — before notification emails are sent or comments are published.', 'lancedesk-form-guard' ); ?></p>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter mb-gutter">
<?php if ( $core_adapter instanceof LD_FG_Form_Adapter ) : ?>
<div class="bg-surface-container rounded-lg p-margin-md shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow border border-primary/20">
<div class="flex justify-between items-start mb-margin-md">
<div class="w-12 h-12 bg-primary-container text-on-primary-container rounded-full flex items-center justify-center shrink-0">
<span class="ld-fg-icon material-symbols-outlined text-[24px]">forum</span>
</div>
<?php if ( $core_comments_on ) : ?>
<span class="bg-surface-tint/10 text-surface-tint font-label-md text-label-md px-2 py-1 rounded"><?php esc_html_e( 'Active', 'lancedesk-form-guard' ); ?></span>
<?php else : ?>
<span class="bg-outline-variant/30 text-on-surface-variant font-label-md text-label-md px-2 py-1 rounded"><?php esc_html_e( 'Installed', 'lancedesk-form-guard' ); ?></span>
<?php endif; ?>
</div>
<h3 class="font-headline-md text-headline-md text-on-surface mb-unit"><?php echo esc_html( $core_adapter->get_label() ); ?></h3>
<p class="font-body-sm text-body-sm text-on-surface-variant mb-margin-md"><?php esc_html_e( 'Post comment forms on your theme. Toggle protection in Settings — no per-form list.', 'lancedesk-form-guard' ); ?></p>
<a class="ld-fg-btn-primary font-label-md text-label-md px-4 py-2 rounded-lg w-full inline-block text-center" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-settings' ) ); ?>"><?php esc_html_e( 'Configure in Settings', 'lancedesk-form-guard' ); ?></a>
</div>
<?php endif; ?>
</div>

<?php if ( empty( $installed ) ) : ?>
<div class="bg-surface-container-lowest rounded-xl border border-outline-variant shadow-sm p-margin-lg max-w-2xl">
<div class="flex items-start gap-4">
<div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center shrink-0">
<span class="ld-fg-icon material-symbols-outlined text-[24px] text-primary" style="font-variation-settings: 'FILL' 1;">extension_off</span>
</div>
<div>
<h2 class="font-headline-md text-headline-md text-on-surface m-0 mb-unit"><?php esc_html_e( 'No third-party form plugins found', 'lancedesk-form-guard' ); ?></h2>
<p class="font-body-sm text-body-sm text-on-surface-variant mb-margin-md"><?php esc_html_e( 'Install and activate one of these plugins, then return here to connect it. WordPress comment protection is available above without any extra plugin.', 'lancedesk-form-guard' ); ?></p>
<ul class="font-body-sm text-body-sm text-on-surface list-disc pl-5 space-y-1 m-0">
<?php foreach ( $missing as $name ) : ?>
<li><?php echo esc_html( $name ); ?></li>
<?php endforeach; ?>
</ul>
</div>
</div>
</div>
<?php else : ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
<?php foreach ( $installed as $slug => $row ) : ?>
<?php
$adapter    = $row['adapter'];
$form_count = count( $row['forms'] );
?>
<div class="bg-surface-container rounded-lg p-margin-md shadow-sm relative overflow-hidden group hover:shadow-md transition-shadow">
<div class="flex justify-between items-start mb-margin-md">
<div class="w-12 h-12 bg-primary-container text-on-primary-container rounded-full flex items-center justify-center shrink-0">
<span class="ld-fg-icon material-symbols-outlined text-[24px]"><?php echo esc_html( $row['icon'] ); ?></span>
</div>
<?php if ( $form_count > 0 ) : ?>
<span class="bg-surface-tint/10 text-surface-tint font-label-md text-label-md px-2 py-1 rounded"><?php esc_html_e( 'Active', 'lancedesk-form-guard' ); ?></span>
<?php else : ?>
<span class="bg-outline-variant/30 text-on-surface-variant font-label-md text-label-md px-2 py-1 rounded"><?php esc_html_e( 'Installed', 'lancedesk-form-guard' ); ?></span>
<?php endif; ?>
</div>
<h3 class="font-headline-md text-headline-md text-on-surface mb-unit"><?php echo esc_html( $adapter->get_label() ); ?></h3>
<p class="font-body-sm text-body-sm text-on-surface-variant mb-margin-md">
<?php
if ( $form_count > 0 ) {
	/* translators: %d: number of forms. */
	echo esc_html( sprintf( _n( '%d form detected', '%d forms detected', $form_count, 'lancedesk-form-guard' ), $form_count ) );
} else {
	esc_html_e( 'No active forms', 'lancedesk-form-guard' );
}
?>
</p>
<?php if ( $form_count > 0 ) : ?>
<a class="ld-fg-btn-primary font-label-md text-label-md px-4 py-2 rounded-lg w-full inline-block text-center" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-forms' ) ); ?>"><?php esc_html_e( 'Manage forms', 'lancedesk-form-guard' ); ?></a>
<?php else : ?>
<a class="ld-fg-btn-ghost font-label-md text-label-md px-4 py-2 rounded-lg w-full inline-block text-center" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-forms' ) ); ?>"><?php esc_html_e( 'View forms', 'lancedesk-form-guard' ); ?></a>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php if ( ! empty( $missing ) ) : ?>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-6 mb-0">
<?php
echo esc_html(
	sprintf(
		/* translators: %s: comma-separated plugin names. */
		__( 'Also supports %s when installed.', 'lancedesk-form-guard' ),
		wp_sprintf( '%l', $missing )
	)
);
?>
</p>
<?php endif; ?>
<?php endif; ?>
</div>

<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

<?php
/**
 * Forms page content.
 *
 * @package LanceDesk_Form_Guard
 *
 * @var array<string,mixed> $ld_fg_settings Plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin    = LD_FG_Plugin::instance();
$available = $plugin->get_available_adapters();
$protected = isset( $ld_fg_settings['protected_forms'] ) ? (array) $ld_fg_settings['protected_forms'] : array();
$all_forms = array();

foreach ( $available as $slug => $adapter ) {
	foreach ( $adapter->list_forms() as $form ) {
		$key         = $slug . ':' . $form['id'];
		$all_forms[] = array(
			'integration' => $slug,
			'label'       => $adapter->get_label(),
			'id'          => $form['id'],
			'title'       => $form['title'],
			'edit_url'    => $form['edit_url'],
			'protected'   => in_array( $key, $protected, true ),
		);
	}
}

if ( empty( $available ) ) :
	require LD_FG_PLUGIN_DIR . 'admin/partials/part-forms-empty.php';
	return;
endif;
?>
<div class="flex flex-col w-full">
<div class="flex justify-between items-center mb-6">
<h1 class="font-headline-lg text-headline-lg text-on-surface m-0"><?php esc_html_e( 'Forms', 'lancedesk-form-guard' ); ?></h1>
</div>
<div class="bg-surface-container-lowest shadow-sm rounded-lg flex flex-col">
<div class="p-margin-lg bg-surface flex flex-col md:flex-row gap-4 justify-between items-start md:items-center rounded-t-lg">
<div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
<div class="relative">
<select class="ld-fg-filter-integration ld-fg-select bg-surface-container-lowest border border-outline-variant text-on-surface font-body-sm text-body-sm px-3 py-1.5 pr-9 rounded-md focus:outline-none focus:ring-2 focus:ring-primary h-[32px]">
<option value="all"><?php esc_html_e( 'All Integrations', 'lancedesk-form-guard' ); ?></option>
<?php foreach ( $available as $slug => $adapter ) : ?>
<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $adapter->get_label() ); ?></option>
<?php endforeach; ?>
</select>
<span class="ld-fg-icon material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 text-outline pointer-events-none text-[16px]" aria-hidden="true">expand_more</span>
</div>
</div>
<div class="flex items-center gap-3 w-full md:w-auto">
<div class="relative flex-1 md:w-64">
<span class="ld-fg-icon material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none" aria-hidden="true">search</span>
<input class="ld-fg-forms-search w-full bg-surface-container-lowest border border-outline-variant text-on-surface font-body-sm text-body-sm !pl-12 pr-3 py-1.5 rounded-md focus:outline-none focus:ring-2 focus:ring-primary h-[32px]" placeholder="<?php esc_attr_e( 'Search forms...', 'lancedesk-form-guard' ); ?>" type="text"/>
</div>
</div>
</div>
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-surface-container-lowest">
<th class="p-3 pl-margin-lg font-label-md text-label-md text-on-surface-variant font-medium border-b-outline-variant"><?php esc_html_e( 'Form Name', 'lancedesk-form-guard' ); ?></th>
<th class="p-3 font-label-md text-label-md text-on-surface-variant font-medium border-b-outline-variant"><?php esc_html_e( 'Integration', 'lancedesk-form-guard' ); ?></th>
<th class="p-3 font-label-md text-label-md text-on-surface-variant font-medium border-b-outline-variant"><?php esc_html_e( 'Protected', 'lancedesk-form-guard' ); ?></th>
</tr>
</thead>
<tbody class="font-body-sm text-body-sm">
<?php if ( empty( $all_forms ) ) : ?>
<tr><td colspan="3" class="p-6 text-center text-on-surface-variant"><?php esc_html_e( 'No forms found.', 'lancedesk-form-guard' ); ?></td></tr>
<?php else : ?>
<?php foreach ( $all_forms as $i => $form ) : ?>
<tr class="group hover:bg-surface transition-colors ld-fg-form-row" data-integration="<?php echo esc_attr( $form['integration'] ); ?>" data-title="<?php echo esc_attr( strtolower( $form['title'] ) ); ?>">
<td class="p-3 pl-margin-lg border-b-surface-variant font-medium text-primary">
<?php if ( $form['edit_url'] ) : ?>
<a class="hover:underline" href="<?php echo esc_url( $form['edit_url'] ); ?>"><?php echo esc_html( $form['title'] ); ?></a>
<?php else : ?>
<?php echo esc_html( $form['title'] ); ?>
<?php endif; ?>
</td>
<td class="p-3 border-b-surface-variant text-on-surface-variant"><?php echo esc_html( $form['label'] ); ?></td>
<td class="p-3 border-b-surface-variant">
<label class="relative inline-flex items-center cursor-pointer">
<input type="checkbox" class="ld-fg-toggle-form sr-only peer" data-integration="<?php echo esc_attr( $form['integration'] ); ?>" data-form-id="<?php echo esc_attr( (string) $form['id'] ); ?>" <?php checked( $form['protected'] ); ?> />
<div class="relative w-9 h-5 bg-outline rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
</label>
</td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
<div class="p-4 bg-surface rounded-b-lg flex justify-between items-center text-on-surface-variant font-body-sm text-body-sm">
<span><?php echo esc_html( sprintf(
	/* translators: %d: item count. */
	_n( '%d item', '%d items', count( $all_forms ), 'lancedesk-form-guard' ),
	count( $all_forms )
) ); ?></span>
</div>
</div>
</div>

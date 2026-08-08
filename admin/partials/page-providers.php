<?php
/**
 * AI Providers page content.
 *
 * @package LanceDesk_Form_Guard
 *
 * @var array<string,mixed> $ld_fg_settings Plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View template locals.

$providers = array(
	'groq'      => array( 'label' => 'Groq', 'letter' => 'G', 'models' => array( 'llama-3.1-8b-instant', 'llama3-70b-8192', 'mixtral-8x7b-32768' ) ),
	'openai'    => array( 'label' => 'OpenAI', 'letter' => 'O', 'models' => array( 'gpt-4o-mini', 'gpt-4o' ) ),
	'anthropic' => array( 'label' => 'Anthropic', 'letter' => 'A', 'models' => array( 'claude-3-5-haiku-latest', 'claude-3-5-sonnet-latest' ) ),
);
$order = isset( $ld_fg_settings['provider_order'] ) ? (array) $ld_fg_settings['provider_order'] : array( 'groq', 'openai', 'anthropic' );
$priority = 1;
?>
<div class="flex flex-col w-full">
<div class="flex justify-between items-center mb-6">
<h1 class="font-headline-lg text-headline-lg text-on-surface m-0"><?php esc_html_e( 'AI Providers', 'lancedesk-form-guard' ); ?></h1>
</div>
<p class="text-body-sm font-body-sm text-on-surface-variant flex items-center gap-2 mb-6">
<span class="ld-fg-icon material-symbols-outlined text-[16px]">lock</span>
<?php esc_html_e( 'API keys are encrypted at rest using AES-256.', 'lancedesk-form-guard' ); ?>
</p>
<div class="flex flex-col gap-margin-md" id="ld-fg-provider-list">
<?php foreach ( $order as $pid ) :
	if ( empty( $providers[ $pid ] ) ) {
		continue;
	}
	$meta     = $providers[ $pid ];
	$config   = isset( $ld_fg_settings['providers'][ $pid ] ) ? $ld_fg_settings['providers'][ $pid ] : array();
	$stored   = (string) ( $config['api_key'] ?? '' );
	$has_key  = '' !== $stored;
	$display  = $has_key ? ( LD_FG_Crypto::is_encrypted( $stored ) ? 'gsk_**************************************' : substr( $stored, 0, 4 ) . '**************************************' ) : '';
	$model    = (string) ( $config['model'] ?? $meta['models'][0] );
	$enabled  = ! empty( $config['enabled'] );
	?>
<div class="ld-fg-provider-card bg-surface-container-lowest rounded-lg p-margin-md flex flex-col sm:flex-row gap-margin-md shadow-sm items-start sm:items-center relative overflow-hidden group transition-all duration-200 hover:shadow-md" data-provider="<?php echo esc_attr( $pid ); ?>">
<div class="absolute left-0 top-0 bottom-0 w-8 bg-surface-container-low flex items-center justify-center cursor-grab active:cursor-grabbing text-outline opacity-50 group-hover:opacity-100 transition-opacity">
<span class="ld-fg-icon material-symbols-outlined text-[20px]">drag_indicator</span>
</div>
<div class="pl-8 flex-1 w-full <?php echo $has_key ? '' : 'opacity-70 hover:opacity-100 transition-opacity'; ?>">
<div class="flex items-center justify-between mb-margin-sm">
<div class="flex items-center gap-margin-sm">
<div class="w-8 h-8 rounded-full bg-[#f3f4f6] flex items-center justify-center shrink-0">
<span class="text-body-sm font-body-sm font-bold text-on-surface"><?php echo esc_html( $meta['letter'] ); ?></span>
</div>
<h2 class="text-headline-md font-headline-md text-on-surface"><?php echo esc_html( $meta['label'] ); ?></h2>
<?php if ( $has_key && $enabled ) : ?>
<span class="px-2 py-1 rounded bg-[#dcfce7] text-[#166534] text-label-md font-label-md"><?php esc_html_e( 'Connected', 'lancedesk-form-guard' ); ?></span>
<?php else : ?>
<span class="px-2 py-1 rounded bg-surface-variant text-on-surface-variant text-label-md font-label-md"><?php esc_html_e( 'Not Configured', 'lancedesk-form-guard' ); ?></span>
<?php endif; ?>
</div>
<span class="text-label-md font-label-md text-on-surface-variant px-2 py-1 rounded bg-surface-container uppercase tracking-wider">
<?php
/* translators: %d: provider priority number. */
echo esc_html( sprintf( __( 'Priority %d', 'lancedesk-form-guard' ), $priority ) );
?>
</span>
</div>
<div class="flex flex-col sm:flex-row gap-margin-md w-full">
<div class="flex-1 w-full">
<label class="block text-label-md font-label-md text-on-surface-variant mb-unit"><?php esc_html_e( 'API Key', 'lancedesk-form-guard' ); ?></label>
<div class="relative w-full">
<input class="ld-fg-provider-api-key w-full bg-surface-container-lowest rounded-lg h-10 px-3 text-body-sm font-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary shadow-sm border border-outline-variant" type="<?php echo $has_key ? 'password' : 'text'; ?>" <?php echo $has_key ? 'readonly' : ''; ?> value="<?php echo esc_attr( $display ); ?>" placeholder="<?php echo esc_attr( 'groq' === $pid ? 'gsk-...' : ( 'openai' === $pid ? 'sk-...' : 'sk-ant-...' ) ); ?>" />
</div>
</div>
<div class="w-full sm:w-64 shrink-0">
<label class="block text-label-md font-label-md text-on-surface-variant mb-unit"><?php esc_html_e( 'Default Model', 'lancedesk-form-guard' ); ?></label>
<select class="ld-fg-provider-model w-full bg-surface-container-lowest rounded-lg h-10 px-3 text-body-sm font-body-sm text-on-surface focus:outline-none focus:ring-2 focus:ring-primary shadow-sm border border-outline-variant appearance-none cursor-pointer" <?php echo $has_key ? '' : 'disabled'; ?>>
<?php foreach ( $meta['models'] as $mid ) : ?>
<option value="<?php echo esc_attr( $mid ); ?>" <?php selected( $model, $mid ); ?>><?php echo esc_html( $mid ); ?></option>
<?php endforeach; ?>
</select>
</div>
</div>
<div class="mt-margin-sm flex justify-end gap-2">
<?php if ( $has_key ) : ?>
<button type="button" class="ld-fg-test-provider flex items-center gap-2 text-body-sm font-body-sm text-secondary hover:text-secondary-container transition-colors py-1 px-3 rounded hover:bg-secondary-fixed/50" data-provider="<?php echo esc_attr( $pid ); ?>">
<span class="ld-fg-icon material-symbols-outlined text-[16px]">sync</span>
<?php esc_html_e( 'Test Connection', 'lancedesk-form-guard' ); ?>
</button>
<?php else : ?>
<button type="button" class="ld-fg-save-provider-key bg-primary text-on-primary text-body-sm font-body-sm py-1.5 px-4 rounded-lg shadow-sm hover:bg-primary/90 transition-colors" data-provider="<?php echo esc_attr( $pid ); ?>">
<?php esc_html_e( 'Save Key', 'lancedesk-form-guard' ); ?>
</button>
<?php endif; ?>
<div class="ld-fg-provider-test-result text-body-sm" hidden></div>
</div>
</div>
</div>
<?php
	++$priority;
endforeach;
?>
</div>
</div>

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

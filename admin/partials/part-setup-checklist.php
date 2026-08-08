<?php
/**
 * First-run setup checklist (dashboard).
 *
 * @package LanceDesk_Form_Guard
 *
 * @var array<string,mixed> $ld_fg_settings Plugin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugin        = LD_FG_Plugin::instance();
$has_adapter   = ! empty( $plugin->get_available_adapters() );
$protected     = isset( $ld_fg_settings['protected_forms'] ) ? (array) $ld_fg_settings['protected_forms'] : array();
$has_protected = ! empty( $protected );
$has_provider  = false;

foreach ( array( 'groq', 'openai', 'anthropic' ) as $pid ) {
	if ( ! empty( $ld_fg_settings['providers'][ $pid ]['api_key'] ) ) {
		$has_provider = true;
		break;
	}
}

// Active step = first incomplete (provider before forms).
$step2_active = $has_adapter && ! $has_provider;
$step3_active = $has_adapter && $has_provider && ! $has_protected;
?>
<div class="flex flex-col w-full max-w-4xl mx-auto">
<div class="mb-margin-lg">
<h1 class="font-headline-lg text-headline-lg text-on-surface"><?php esc_html_e( 'Welcome to Form Guard', 'lancedesk-form-guard' ); ?></h1>
<p class="font-body-lg text-body-lg text-on-surface-variant mt-unit"><?php esc_html_e( 'Let\'s get your forms protected against spam and malicious inputs.', 'lancedesk-form-guard' ); ?></p>
</div>
<div class="bg-surface-container-lowest rounded-xl shadow-sm p-margin-lg">
<div class="flex flex-col gap-margin-md">

<?php // Step 1: form plugin. ?>
<div class="flex items-start gap-margin-md p-margin-md <?php echo $has_adapter ? 'bg-surface-container rounded-lg' : 'bg-primary-fixed/20 rounded-lg relative overflow-hidden group'; ?> transition-colors duration-200">
<?php if ( ! $has_adapter ) : ?><div class="absolute inset-0 bg-primary-fixed/10 group-hover:bg-primary-fixed/20 transition-colors duration-300"></div><?php endif; ?>
<div class="mt-1 flex-shrink-0 w-6 h-6 rounded-full <?php echo $has_adapter ? 'bg-primary' : 'bg-surface-container-lowest shadow-sm border border-primary-fixed'; ?> flex items-center justify-center relative z-10">
<span class="ld-fg-icon material-symbols-outlined text-[16px] <?php echo $has_adapter ? 'text-on-primary font-bold' : 'text-primary'; ?>"><?php echo $has_adapter ? 'check' : 'looks_one'; ?></span>
</div>
<div class="flex-grow relative z-10">
<h3 class="font-headline-md text-headline-md text-on-surface <?php echo $has_adapter ? 'line-through opacity-70' : ''; ?>"><?php esc_html_e( 'Install and activate a form plugin', 'lancedesk-form-guard' ); ?></h3>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-1 <?php echo $has_adapter ? 'opacity-70' : ''; ?>"><?php echo $has_adapter ? esc_html__( 'Great job! We detected an active form plugin on your site.', 'lancedesk-form-guard' ) : esc_html__( 'Install WPForms, Contact Form 7, Gravity Forms, Quform, or Ninja Forms.', 'lancedesk-form-guard' ); ?></p>
<?php if ( ! $has_adapter ) : ?>
<a class="mt-3 bg-primary hover:bg-primary/90 text-on-primary font-label-md text-label-md px-4 py-2 rounded-lg transition-colors shadow-sm inline-flex items-center" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-integrations' ) ); ?>">
<span class="ld-fg-icon material-symbols-outlined text-[18px] mr-2">hub</span>
<?php esc_html_e( 'View Integrations', 'lancedesk-form-guard' ); ?>
</a>
<?php endif; ?>
</div>
</div>

<?php // Step 2: AI provider (before forms). ?>
<div class="flex items-start gap-margin-md p-margin-md <?php echo $has_provider ? 'bg-surface-container rounded-lg' : ( $step2_active ? 'bg-primary-fixed/20 rounded-lg relative overflow-hidden group' : 'hover:bg-surface-container-low rounded-lg' ); ?> transition-colors duration-200">
<?php if ( $step2_active ) : ?><div class="absolute inset-0 bg-primary-fixed/10 group-hover:bg-primary-fixed/20 transition-colors duration-300"></div><?php endif; ?>
<div class="mt-1 flex-shrink-0 w-6 h-6 rounded-full <?php echo $has_provider ? 'bg-primary' : ( $step2_active ? 'bg-surface-container-lowest shadow-sm border border-primary-fixed' : 'bg-surface-container-highest' ); ?> flex items-center justify-center relative z-10">
<span class="ld-fg-icon material-symbols-outlined text-[16px] <?php echo $has_provider ? 'text-on-primary font-bold' : ( $step2_active ? 'text-primary' : 'text-on-surface-variant' ); ?>"><?php echo $has_provider ? 'check' : 'looks_two'; ?></span>
</div>
<div class="flex-grow relative z-10">
<h3 class="font-headline-md text-headline-md text-on-surface <?php echo $has_provider ? 'line-through opacity-70' : ''; ?>"><?php esc_html_e( 'Add an AI provider', 'lancedesk-form-guard' ); ?></h3>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-1 <?php echo $has_provider ? 'opacity-70' : ''; ?>"><?php echo $has_provider ? esc_html__( 'Provider connected. Ambiguous submissions can be classified with AI.', 'lancedesk-form-guard' ) : esc_html__( 'Connect Groq, OpenAI, or Anthropic so AI can review cases heuristics leave unclear.', 'lancedesk-form-guard' ); ?></p>
<?php if ( ! $has_provider ) : ?>
<a class="mt-3 bg-primary hover:bg-primary/90 text-on-primary font-label-md text-label-md px-4 py-2 rounded-lg transition-colors shadow-sm inline-flex items-center" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-providers' ) ); ?>">
<span class="ld-fg-icon material-symbols-outlined text-[18px] mr-2">smart_toy</span>
<?php esc_html_e( 'Add AI Provider', 'lancedesk-form-guard' ); ?>
</a>
<?php endif; ?>
</div>
</div>

<?php // Step 3: protect forms. ?>
<div class="flex items-start gap-margin-md p-margin-md <?php echo $has_protected ? 'bg-surface-container rounded-lg' : ( $step3_active ? 'bg-primary-fixed/20 rounded-lg relative overflow-hidden group' : 'hover:bg-surface-container-low rounded-lg' ); ?> transition-colors duration-200">
<?php if ( $step3_active ) : ?><div class="absolute inset-0 bg-primary-fixed/10 group-hover:bg-primary-fixed/20 transition-colors duration-300"></div><?php endif; ?>
<div class="mt-1 flex-shrink-0 w-6 h-6 rounded-full <?php echo $has_protected ? 'bg-primary' : ( $step3_active ? 'bg-surface-container-lowest shadow-sm border border-primary-fixed' : 'bg-surface-container-highest' ); ?> flex items-center justify-center relative z-10">
<span class="ld-fg-icon material-symbols-outlined text-[16px] <?php echo $has_protected ? 'text-on-primary font-bold' : ( $step3_active ? 'text-primary' : 'text-on-surface-variant' ); ?>"><?php echo $has_protected ? 'check' : 'looks_3'; ?></span>
</div>
<div class="flex-grow relative z-10">
<h3 class="font-headline-md text-headline-md text-on-surface <?php echo $has_protected ? 'line-through opacity-70' : ''; ?>"><?php esc_html_e( 'Protect at least one form', 'lancedesk-form-guard' ); ?></h3>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-1 <?php echo $has_protected ? 'opacity-70' : ''; ?>"><?php esc_html_e( 'Choose which forms get Layer 1 heuristics and AI review before notification emails go out.', 'lancedesk-form-guard' ); ?></p>
<?php if ( ! $has_protected ) : ?>
<a class="mt-3 <?php echo $has_provider ? 'bg-primary hover:bg-primary/90 text-on-primary' : 'bg-surface-container-highest text-on-surface-variant'; ?> font-label-md text-label-md px-4 py-2 rounded-lg transition-colors shadow-sm inline-flex items-center" href="<?php echo esc_url( admin_url( 'admin.php?page=ld-fg-forms' ) ); ?>">
<span class="ld-fg-icon material-symbols-outlined text-[18px] mr-2">security</span>
<?php esc_html_e( 'Configure Protection', 'lancedesk-form-guard' ); ?>
</a>
<?php if ( ! $has_provider ) : ?>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-2"><?php esc_html_e( 'You can protect forms with local heuristics only, but connecting an AI provider first is recommended.', 'lancedesk-form-guard' ); ?></p>
<?php endif; ?>
<?php endif; ?>
</div>
</div>

<?php // Step 4: test. ?>
<div class="flex items-start gap-margin-md p-margin-md hover:bg-surface-container-low rounded-lg transition-colors duration-200">
<div class="mt-1 flex-shrink-0 w-6 h-6 rounded-full bg-surface-container-highest flex items-center justify-center">
<span class="ld-fg-icon material-symbols-outlined text-[16px] text-on-surface-variant">looks_4</span>
</div>
<div class="flex-grow">
<h3 class="font-headline-md text-headline-md text-on-surface"><?php esc_html_e( 'Send a test submission', 'lancedesk-form-guard' ); ?></h3>
<p class="font-body-sm text-body-sm text-on-surface-variant mt-1"><?php esc_html_e( 'Verify that Form Guard is correctly intercepting and analyzing form entries.', 'lancedesk-form-guard' ); ?></p>
</div>
</div>

</div>
</div>
<div class="mt-margin-lg flex justify-end">
<button type="button" class="ld-fg-dismiss-setup text-primary hover:text-primary/80 font-label-md text-label-md bg-transparent px-4 py-2 rounded-lg transition-colors inline-flex items-center">
<?php esc_html_e( 'Skip Setup', 'lancedesk-form-guard' ); ?>
<span class="ld-fg-icon material-symbols-outlined text-[18px] ml-1">arrow_forward</span>
</button>
</div>
</div>

<?php
/**
 * Admin layout shell (Stitch sidebar + header).
 *
 * @package LanceDesk_Form_Guard
 *
 * @var string              $ld_fg_page_key  Current nav key.
 * @var array<string,array> $ld_fg_nav_items Sidebar navigation.
 * @var string              $ld_fg_partial   Content partial path.
 * @var array<string,mixed> $ld_fg_settings  Plugin settings.
 * @var bool                $ld_fg_enabled   Whether protection is enabled.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_classes = 'bg-primary-container text-on-primary-container border-l-4 border-l-primary';
$nav_base       = 'flex items-center px-4 py-2 text-[14px] text-[#f0f0f1] hover:bg-primary-container/20 hover:text-on-primary-container transition-colors';

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View template variables.
?>
<div class="wrap ld-fg-wrap">
	<?php settings_errors(); ?>
</div>
<div class="ld-fg-admin-wrap bg-[#f0f0f1] font-body-lg text-on-surface">
	<aside class="ld-fg-admin-sidebar w-48 bg-[#2c3338] flex flex-col flex-shrink-0" aria-label="<?php esc_attr_e( 'Form Guard navigation', 'lancedesk-form-guard' ); ?>">
		<div class="px-4 h-12 flex items-center mb-2 bg-[#1d2327]">
			<span class="ld-fg-icon material-symbols-outlined text-[#a7aaad] mr-2" aria-hidden="true">shield</span>
			<span class="text-[14px] font-semibold text-white truncate"><?php esc_html_e( 'LanceDesk', 'lancedesk-form-guard' ); ?></span>
		</div>
		<nav class="flex-1 space-y-0.5 overflow-y-auto">
			<?php foreach ( $ld_fg_nav_items as $key => $item ) : ?>
				<?php
				$is_active = ( $key === $ld_fg_page_key );
				$url       = admin_url( 'admin.php?page=' . $item['slug'] );
				$classes   = $is_active
					? 'flex items-center justify-between px-4 py-2 transition-colors group ' . $active_classes
					: ( 'dashboard' === $key ? $nav_base . ' justify-between group' : $nav_base );
				?>
				<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
					<?php if ( 'dashboard' === $key ) : ?>
					<div class="flex items-center">
						<span class="ld-fg-icon material-symbols-outlined mr-3 text-[20px]" aria-hidden="true"><?php echo esc_html( (string) $item['icon'] ); ?></span>
						<?php echo esc_html( (string) $item['label'] ); ?>
					</div>
						<?php if ( $ld_fg_enabled ) : ?>
					<span class="px-1.5 py-0.5 rounded-full bg-[#46b450] text-[10px] text-white font-bold leading-none"><?php esc_html_e( 'ON', 'lancedesk-form-guard' ); ?></span>
						<?php endif; ?>
					<?php else : ?>
					<span class="ld-fg-icon material-symbols-outlined mr-3 text-[20px]" aria-hidden="true"><?php echo esc_html( (string) $item['icon'] ); ?></span>
						<?php echo esc_html( (string) $item['label'] ); ?>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>
	</aside>

	<div class="ld-fg-admin-main flex-1 min-w-0 flex flex-col">
		<header class="ld-fg-admin-header h-10 bg-[#1d2327] flex items-center justify-between px-margin-md flex-shrink-0">
			<button type="button" class="ld-fg-refresh bg-transparent border-0 p-0 cursor-pointer leading-none" aria-label="<?php esc_attr_e( 'Refresh', 'lancedesk-form-guard' ); ?>" onclick="window.location.reload();">
				<span class="ld-fg-icon material-symbols-outlined text-[#a7aaad] text-[18px]" aria-hidden="true">refresh</span>
			</button>
			<span class="text-[13px] text-white font-medium"><?php esc_html_e( 'LanceDesk Form Guard', 'lancedesk-form-guard' ); ?></span>
		</header>

		<main class="ld-fg-admin-content relative flex-1 px-container-padding py-container-padding">
			<?php require $ld_fg_partial; ?>
		</main>

		<footer class="ld-fg-admin-footer">
			<p>
				<?php
				/* translators: %s: plugin version number. */
				echo esc_html( sprintf( __( 'LanceDesk Form Guard v%s', 'lancedesk-form-guard' ), LD_FG_VERSION ) );
				?>
			</p>
		</footer>
	</div>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

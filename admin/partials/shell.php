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
 * @var int                 $ld_fg_logs_unread Unread security log count for current user.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View template variables.

ob_start();
settings_errors();
$ld_fg_notices = trim( (string) ob_get_clean() );
if ( '' !== $ld_fg_notices ) {
	echo '<div class="wrap ld-fg-wrap">' . $ld_fg_notices . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP settings_errors HTML.
}
?>
<div class="ld-fg-admin-wrap">
	<aside class="ld-fg-admin-sidebar" aria-label="<?php esc_attr_e( 'Form Guard navigation', 'lancedesk-form-guard' ); ?>">
		<div class="ld-fg-admin-sidebar__brand">
			<span class="ld-fg-icon material-symbols-outlined" aria-hidden="true">shield</span>
			<span class="ld-fg-admin-sidebar__brand-text"><?php esc_html_e( 'LanceDesk', 'lancedesk-form-guard' ); ?></span>
		</div>
		<nav class="ld-fg-admin-sidebar__nav">
			<?php foreach ( $ld_fg_nav_items as $key => $item ) : ?>
				<?php
				$is_active = ( $key === $ld_fg_page_key );
				$url       = admin_url( 'admin.php?page=' . $item['slug'] );
				$classes   = 'ld-fg-nav-link' . ( $is_active ? ' is-active' : '' );
				?>
				<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $is_active ? ' aria-current="page"' : ''; ?>>
					<span class="ld-fg-icon material-symbols-outlined" aria-hidden="true"><?php echo esc_html( (string) $item['icon'] ); ?></span>
					<span class="ld-fg-nav-link__label"><?php echo esc_html( (string) $item['label'] ); ?></span>
					<?php if ( 'dashboard' === $key && $ld_fg_enabled ) : ?>
						<span class="ld-fg-nav-link__badge"><?php esc_html_e( 'ON', 'lancedesk-form-guard' ); ?></span>
					<?php endif; ?>
					<?php if ( 'logs' === $key ) : ?>
						<?php
						$ld_fg_unread_label = $ld_fg_logs_unread > 99 ? '99+' : (string) $ld_fg_logs_unread;
						$ld_fg_unread_aria  = sprintf(
							/* translators: %d: unread log entry count. */
							_n( '%d unread log entry', '%d unread log entries', $ld_fg_logs_unread, 'lancedesk-form-guard' ),
							$ld_fg_logs_unread
						);
						?>
						<span
							id="ld-fg-logs-unread-badge"
							class="ld-fg-nav-link__badge ld-fg-nav-link__badge--count<?php echo $ld_fg_logs_unread > 0 ? '' : ' hidden'; ?>"
							aria-label="<?php echo esc_attr( $ld_fg_unread_aria ); ?>"
						><?php echo esc_html( $ld_fg_unread_label ); ?></span>
					<?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>
	</aside>

	<div class="ld-fg-admin-main">
		<main class="ld-fg-admin-content">
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

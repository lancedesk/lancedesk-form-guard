<?php
/**
 * Plugin Name:       LanceDesk Form Guard
 * Plugin URI:        https://github.com/lancedesk/lancedesk-form-guard
 * Description:       Protects contact forms and WordPress comments with heuristics and optional AI before emails send or comments are saved.
 * Version:           0.1.6
 * Author:            Lance Desk
 * Author URI:        https://lancedesk.com
 * Text Domain:       lancedesk-form-guard
 * Domain Path:       /languages
 * Requires at least: 6.4
 * Tested up to:      7.1
 * Requires PHP:      8.0
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package LanceDesk_Form_Guard
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LD_FG_VERSION', '0.1.6' );
define( 'LD_FG_PLUGIN_FILE', __FILE__ );
define( 'LD_FG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LD_FG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LD_FG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'LD_FG_PLUGIN_SLUG', 'lancedesk-form-guard' );
define( 'LD_FG_TEXT_DOMAIN', 'lancedesk-form-guard' );
define( 'LD_FG_PREFIX', 'ld_fg' );
define( 'LD_FG_CLASS_PREFIX', 'LD_FG_' );

/**
 * Return a cache-busting version string for a bundled asset.
 *
 * @param string $relative_path Path relative to plugin root.
 *
 * @return string
 */
function ld_fg_asset_version( string $relative_path ): string {
	$path = LD_FG_PLUGIN_DIR . ltrim( $relative_path, '/' );

	if ( is_readable( $path ) ) {
		return (string) filemtime( $path );
	}

	return LD_FG_VERSION;
}

/**
 * Show admin notice when requirements are not met.
 *
 * @param string $message Plain message (will be escaped).
 *
 * @return void
 */
function ld_fg_dependency_notice( string $message ): void {
	add_action(
		'admin_notices',
		static function () use ( $message ): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( $message )
			);
		}
	);
}

/**
 * Verify PHP and WordPress version requirements.
 *
 * @return bool True when all requirements are met.
 */
function ld_fg_requirements_met(): bool {
	if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
		ld_fg_dependency_notice(
			__( 'LanceDesk Form Guard requires PHP 8.0 or higher.', 'lancedesk-form-guard' )
		);
		return false;
	}

	global $wp_version;

	if ( version_compare( $wp_version, '6.4', '<' ) ) {
		ld_fg_dependency_notice(
			__( 'LanceDesk Form Guard requires WordPress 6.4 or higher.', 'lancedesk-form-guard' )
		);
		return false;
	}

	return true;
}

require_once LD_FG_PLUGIN_DIR . 'includes/class-ld-fg-db.php';
require_once LD_FG_PLUGIN_DIR . 'includes/class-ld-fg-activator.php';

register_activation_hook( __FILE__, array( 'LD_FG_Activator', 'activate' ) );
add_action( 'plugins_loaded', array( 'LD_FG_DB', 'maybe_upgrade' ), 5 );

/**
 * Initialize the plugin after all plugins are loaded.
 *
 * @return void
 */
function ld_fg_init_plugin(): void {
	if ( ! ld_fg_requirements_met() ) {
		return;
	}

	require_once LD_FG_PLUGIN_DIR . 'includes/class-ld-fg-plugin.php';
	LD_FG_Plugin::instance();
}

add_action( 'plugins_loaded', 'ld_fg_init_plugin' );

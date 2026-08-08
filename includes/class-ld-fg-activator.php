<?php
/**
 * Plugin activation handler.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs on plugin activation.
 */
final class LD_FG_Activator {

	/**
	 * Activate plugin: tables and default options.
	 *
	 * @return void
	 */
	public static function activate(): void {
		LD_FG_DB::create_tables();
		LD_FG_DB::seed_default_options();

		if ( false === get_option( 'ld_fg_installed_at', false ) ) {
			add_option( 'ld_fg_installed_at', time() );
		}

		flush_rewrite_rules();
	}
}

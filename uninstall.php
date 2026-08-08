<?php
/**
 * Plugin uninstall handler.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Uninstall script scope.

require_once plugin_dir_path( __FILE__ ) . 'includes/class-ld-fg-db.php';

$settings = get_option( 'ld_fg_settings', array() );

if ( is_array( $settings ) && ! empty( $settings['delete_on_uninstall'] ) ) {
	LD_FG_DB::drop_tables();

	global $wpdb;

	$like = $wpdb->esc_like( 'ld_fg_' ) . '%';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
			$like,
			$wpdb->esc_like( '_transient_ld_fg_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_ld_fg_' ) . '%'
		)
	);
}

// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

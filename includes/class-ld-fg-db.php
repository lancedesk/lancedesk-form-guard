<?php
/**
 * Database schema and table helpers.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Database operations for custom tables.
 */
final class LD_FG_DB {

	/**
	 * Current schema version stored in options.
	 */
	public const DB_VERSION = '1.1.0';

	/**
	 * Return fully qualified table names.
	 *
	 * @return array<string, string>
	 */
	public static function get_tables(): array {
		global $wpdb;

		return array(
			'logs'      => $wpdb->prefix . 'ld_fg_logs',
			'log_reads' => $wpdb->prefix . 'ld_fg_log_reads',
		);
	}

	/**
	 * Return a whitelisted custom table name.
	 *
	 * @param string $key Table key.
	 *
	 * @return string
	 */
	public static function table_name( string $key ): string {
		$tables = self::get_tables();

		return isset( $tables[ $key ] ) ? $tables[ $key ] : '';
	}

	/**
	 * Create or upgrade custom tables via dbDelta.
	 *
	 * @return void
	 */
	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$tables  = self::get_tables();
		$charset = $wpdb->get_charset_collate();
		$logs    = $tables['logs'];

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- dbDelta requires raw CREATE TABLE strings.
		$sql_logs = "CREATE TABLE {$logs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			created_at DATETIME NOT NULL,
			integration VARCHAR(64) NOT NULL DEFAULT '',
			form_id VARCHAR(64) NOT NULL DEFAULT '',
			form_title VARCHAR(255) NOT NULL DEFAULT '',
			verdict VARCHAR(32) NOT NULL DEFAULT '',
			layer VARCHAR(32) NOT NULL DEFAULT '',
			confidence DECIMAL(4,3) NOT NULL DEFAULT 0.000,
			reason TEXT NOT NULL,
			action VARCHAR(32) NOT NULL DEFAULT '',
			ip_hash VARCHAR(64) NOT NULL DEFAULT '',
			email_hash VARCHAR(64) NOT NULL DEFAULT '',
			snippet TEXT NOT NULL,
			meta LONGTEXT NULL,
			status VARCHAR(32) NOT NULL DEFAULT 'active',
			PRIMARY KEY  (id),
			KEY created_at (created_at),
			KEY integration (integration),
			KEY form_id (form_id),
			KEY verdict (verdict),
			KEY status (status)
		) {$charset};";
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		dbDelta( $sql_logs );

		$log_reads = $tables['log_reads'];

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared -- dbDelta requires raw CREATE TABLE strings.
		$sql_reads = "CREATE TABLE {$log_reads} (
			log_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			read_at DATETIME NOT NULL,
			PRIMARY KEY  (log_id, user_id),
			KEY user_id (user_id),
			KEY read_at (read_at)
		) {$charset};";
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		dbDelta( $sql_reads );

		update_option( 'ld_fg_db_version', self::DB_VERSION );
	}

	/**
	 * Run dbDelta when the bundled schema version increases.
	 *
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$installed = (string) get_option( 'ld_fg_db_version', '0' );

		if ( version_compare( $installed, self::DB_VERSION, '>=' ) ) {
			return;
		}

		self::create_tables();
	}

	/**
	 * Drop all plugin tables.
	 *
	 * @return void
	 */
	public static function drop_tables(): void {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );
		}

		delete_option( 'ld_fg_db_version' );
	}

	/**
	 * Set default plugin options on first activation.
	 *
	 * @return void
	 */
	public static function seed_default_options(): void {
		if ( false !== get_option( 'ld_fg_settings', false ) ) {
			return;
		}

		add_option(
			'ld_fg_settings',
			array(
				'enabled'               => 0,
				'retention_days'        => 90,
				'delete_on_uninstall'   => 0,
				'privacy_ack'           => 0,
				'default_action'        => 'silent_discard',
				'ai_fail_mode'          => 'fail_open',
				'heuristics'            => array(
					'bbcode_url'          => 1,
					'url_count'           => 1,
					'honeypot'            => 1,
					'too_fast'            => 1,
					'suspicious_links'    => 1,
					'commercial_outreach' => 1,
				),
				'ai_enabled'            => 0,
				'confidence_quarantine' => 0.7,
				'confidence_block'      => 0.9,
				'protected_forms'          => array(),
				'protect_all'              => array(),
				'protect_core_comments'    => 1,
				'protect_core_registration' => 0,
				'providers'             => array(
					'groq'      => array(
						'enabled' => 0,
						'api_key' => '',
						'model'   => 'llama-3.1-8b-instant',
					),
					'openai'    => array(
						'enabled' => 0,
						'api_key' => '',
						'model'   => 'gpt-4o-mini',
					),
					'anthropic' => array(
						'enabled' => 0,
						'api_key' => '',
						'model'   => 'claude-3-5-haiku-latest',
					),
				),
				'provider_order'        => array( 'groq', 'openai', 'anthropic' ),
				'setup_dismissed'       => 0,
			)
		);
	}
}

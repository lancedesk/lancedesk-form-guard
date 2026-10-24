<?php
/**
 * Admin area functionality.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers admin menus, assets, settings, and AJAX handlers.
 */
final class LD_FG_Admin {

	/**
	 * Top-level menu slug.
	 */
	public const MENU_SLUG = 'ld-fg';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );

		add_action( 'wp_ajax_ld_fg_toggle_form', array( $this, 'ajax_toggle_form' ) );
		add_action( 'wp_ajax_ld_fg_test_provider', array( $this, 'ajax_test_provider' ) );
		add_action( 'wp_ajax_ld_fg_release_quarantine', array( $this, 'ajax_release_quarantine' ) );
		add_action( 'wp_ajax_ld_fg_discard_quarantine', array( $this, 'ajax_discard_quarantine' ) );
		add_action( 'wp_ajax_ld_fg_mark_log_read', array( $this, 'ajax_mark_log_read' ) );
		add_action( 'wp_ajax_ld_fg_bulk_logs', array( $this, 'ajax_bulk_logs' ) );
		add_action( 'wp_ajax_ld_fg_delete_logs', array( $this, 'ajax_delete_logs' ) );
		add_action( 'wp_ajax_ld_fg_dismiss_setup', array( $this, 'ajax_dismiss_setup' ) );
		add_action( 'wp_ajax_ld_fg_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'admin_post_ld_fg_export_logs_csv', array( $this, 'export_logs_csv' ) );
	}

	/**
	 * Mark Form Guard admin screens for CSS that must apply before :has() paints.
	 *
	 * @param string $classes Body classes.
	 *
	 * @return string
	 */
	public function admin_body_class( string $classes ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page detection.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';

		if ( '' !== $page && ( 'ld-fg' === $page || 0 === strpos( $page, 'ld-fg-' ) ) ) {
			$classes .= ' ld-fg-screen';
		}

		return $classes;
	}

	/**
	 * Admin navigation items for the unified sidebar.
	 *
	 * @return array<string, array{slug: string, label: string, icon: string}>
	 */
	public static function get_nav_items(): array {
		return array(
			'dashboard'     => array(
				'slug'  => 'ld-fg',
				'label' => __( 'Dashboard', 'lancedesk-form-guard' ),
				'icon'  => 'dashboard',
			),
			'integrations'  => array(
				'slug'  => 'ld-fg-integrations',
				'label' => __( 'Integrations', 'lancedesk-form-guard' ),
				'icon'  => 'hub',
			),
			'forms'         => array(
				'slug'  => 'ld-fg-forms',
				'label' => __( 'Forms', 'lancedesk-form-guard' ),
				'icon'  => 'list_alt',
			),
			'protection'    => array(
				'slug'  => 'ld-fg-protection',
				'label' => __( 'Protection', 'lancedesk-form-guard' ),
				'icon'  => 'security',
			),
			'providers'     => array(
				'slug'  => 'ld-fg-providers',
				'label' => __( 'AI Providers', 'lancedesk-form-guard' ),
				'icon'  => 'smart_toy',
			),
			'logs'          => array(
				'slug'  => 'ld-fg-logs',
				'label' => __( 'Logs', 'lancedesk-form-guard' ),
				'icon'  => 'history',
			),
			'settings'      => array(
				'slug'  => 'ld-fg-settings',
				'label' => __( 'Settings', 'lancedesk-form-guard' ),
				'icon'  => 'settings',
			),
		);
	}

	/**
	 * Register settings for the options API.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'ld_fg_settings_group',
			LD_FG_Settings::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => LD_FG_Settings::defaults(),
			)
		);
	}

	/**
	 * Sanitize settings on save.
	 *
	 * @param mixed $input Raw input.
	 *
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ): array {
		if ( ! is_array( $input ) ) {
			return LD_FG_Settings::get();
		}

		$output = LD_FG_Settings::get();

		// Partial AJAX saves only include some keys — never reset omitted fields to defaults.
		if ( array_key_exists( 'enabled', $input ) ) {
			$output['enabled'] = ! empty( $input['enabled'] ) ? 1 : 0;
		}
		if ( array_key_exists( 'keep_logs', $input ) ) {
			$output['keep_logs'] = ! empty( $input['keep_logs'] ) ? 1 : 0;
		}
		if ( array_key_exists( 'retention_days', $input ) ) {
			$days = absint( $input['retention_days'] );
			$output['retention_days'] = in_array( $days, array( 15, 30, 90, 180 ), true ) ? $days : 90;
		}
		if ( array_key_exists( 'delete_on_uninstall', $input ) ) {
			$output['delete_on_uninstall'] = ! empty( $input['delete_on_uninstall'] ) ? 1 : 0;
		}
		if ( array_key_exists( 'privacy_ack', $input ) ) {
			$output['privacy_ack'] = ! empty( $input['privacy_ack'] ) ? 1 : 0;
		}
		if ( array_key_exists( 'setup_dismissed', $input ) ) {
			$output['setup_dismissed'] = ! empty( $input['setup_dismissed'] ) ? 1 : 0;
		}
		if ( array_key_exists( 'ai_enabled', $input ) ) {
			$output['ai_enabled'] = ! empty( $input['ai_enabled'] ) ? 1 : 0;
		}

		$allowed_actions = array( 'silent_discard', 'soft_fail', 'quarantine' );
		if ( isset( $input['default_action'] ) && in_array( $input['default_action'], $allowed_actions, true ) ) {
			$output['default_action'] = $input['default_action'];
		}

		$allowed_fail = array( 'fail_open', 'fail_closed' );
		if ( isset( $input['ai_fail_mode'] ) && in_array( $input['ai_fail_mode'], $allowed_fail, true ) ) {
			$output['ai_fail_mode'] = $input['ai_fail_mode'];
		}

		if ( isset( $input['confidence_quarantine'] ) ) {
			$output['confidence_quarantine'] = max( 0.0, min( 1.0, (float) $input['confidence_quarantine'] ) );
		}

		if ( isset( $input['confidence_block'] ) ) {
			$output['confidence_block'] = max( 0.0, min( 1.0, (float) $input['confidence_block'] ) );
		}

		if ( isset( $input['heuristics'] ) && is_array( $input['heuristics'] ) ) {
			$keys = array( 'bbcode_url', 'url_count', 'honeypot', 'too_fast', 'suspicious_links', 'commercial_outreach', 'gibberish' );
			foreach ( $keys as $key ) {
				$output['heuristics'][ $key ] = ! empty( $input['heuristics'][ $key ] ) ? 1 : 0;
			}
		}

		if ( isset( $input['providers'] ) && is_array( $input['providers'] ) ) {
			foreach ( array( 'groq', 'openai', 'anthropic' ) as $pid ) {
				if ( empty( $input['providers'][ $pid ] ) || ! is_array( $input['providers'][ $pid ] ) ) {
					continue;
				}

				$inc = $input['providers'][ $pid ];
				$existing = isset( $output['providers'][ $pid ] ) ? $output['providers'][ $pid ] : array();

				$output['providers'][ $pid ]['enabled'] = ! empty( $inc['enabled'] ) ? 1 : 0;
				$output['providers'][ $pid ]['model']   = isset( $inc['model'] )
					? sanitize_text_field( wp_unslash( (string) $inc['model'] ) )
					: ( $existing['model'] ?? '' );

				$new_key = isset( $inc['api_key'] ) ? sanitize_text_field( wp_unslash( (string) $inc['api_key'] ) ) : '';
				if ( '' !== $new_key && ! str_contains( $new_key, '***' ) ) {
					$output['providers'][ $pid ]['api_key'] = $new_key;
				}
			}
		}

		if ( isset( $input['provider_order'] ) && is_array( $input['provider_order'] ) ) {
			$output['provider_order'] = array_values(
				array_intersect(
					array_map( 'sanitize_key', $input['provider_order'] ),
					array( 'groq', 'openai', 'anthropic' )
				)
			);
		}

		// register_setting hooks this into sanitize_option_{$key}, so update_option()
		// also runs through here — must preserve list fields or toggles never save.
		if ( array_key_exists( 'protected_forms', $input ) && is_array( $input['protected_forms'] ) ) {
			$output['protected_forms'] = array_values(
				array_filter(
					array_map(
						static function ( $item ): string {
							return sanitize_text_field( (string) $item );
						},
						$input['protected_forms']
					),
					static function ( string $item ): bool {
						return '' !== $item;
					}
				)
			);
		}

		if ( array_key_exists( 'protect_all', $input ) && is_array( $input['protect_all'] ) ) {
			$protect_all = array();
			foreach ( $input['protect_all'] as $slug => $on ) {
				$slug = sanitize_key( (string) $slug );
				if ( '' !== $slug ) {
					$protect_all[ $slug ] = ! empty( $on ) ? 1 : 0;
				}
			}
			$output['protect_all'] = $protect_all;
		}

		if ( array_key_exists( 'protect_core_comments', $input ) ) {
			$output['protect_core_comments'] = ! empty( $input['protect_core_comments'] ) ? 1 : 0;
		}

		if ( array_key_exists( 'protect_core_registration', $input ) ) {
			$output['protect_core_registration'] = ! empty( $input['protect_core_registration'] ) ? 1 : 0;
		}

		// Guidelines 7 & 9: no third-party submission data without explicit privacy acknowledgment.
		if ( empty( $output['privacy_ack'] ) ) {
			$output['ai_enabled'] = 0;
		}

		return LD_FG_Settings::encrypt_provider_keys( $output );
	}

	/**
	 * Register admin menu and submenus.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			esc_html__( 'LanceDesk Form Guard', 'lancedesk-form-guard' ),
			esc_html__( 'Form Guard', 'lancedesk-form-guard' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-shield',
			58
		);

		$pages = array(
			'integrations' => array( __( 'Integrations', 'lancedesk-form-guard' ), 'render_integrations' ),
			'forms'        => array( __( 'Forms', 'lancedesk-form-guard' ), 'render_forms' ),
			'protection'   => array( __( 'Protection Rules', 'lancedesk-form-guard' ), 'render_protection' ),
			'providers'    => array( __( 'AI Providers', 'lancedesk-form-guard' ), 'render_providers' ),
			'logs'         => array( __( 'Security Logs', 'lancedesk-form-guard' ), 'render_logs' ),
			'settings'     => array( __( 'Settings', 'lancedesk-form-guard' ), 'render_settings' ),
		);

		foreach ( $pages as $key => $page ) {
			add_submenu_page(
				self::MENU_SLUG,
				$page[0],
				self::get_nav_items()[ $key ]['label'],
				'manage_options',
				'ld-fg-' . $key,
				array( $this, $page[1] )
			);
		}
	}

	/**
	 * Enqueue admin assets on plugin pages.
	 *
	 * @param string $hook_suffix Current admin hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if ( false === strpos( $hook_suffix, 'ld-fg' ) && false === strpos( $hook_suffix, 'form-guard' ) ) {
			return;
		}

		wp_enqueue_style(
			'ld-fg-admin',
			LD_FG_PLUGIN_URL . 'admin/css/ld-fg-admin.css',
			array(),
			ld_fg_asset_version( 'admin/css/ld-fg-admin.css' )
		);

		$is_dashboard = ( 'toplevel_page_ld-fg' === $hook_suffix );

		if ( $is_dashboard ) {
			wp_enqueue_script(
				'chart-js',
				LD_FG_PLUGIN_URL . 'admin/js/vendor/chart.umd.min.js',
				array(),
				ld_fg_asset_version( 'admin/js/vendor/chart.umd.min.js' ),
				true
			);
		}

		$deps = $is_dashboard ? array( 'jquery', 'chart-js' ) : array( 'jquery' );

		wp_enqueue_script(
			'ld-fg-admin',
			LD_FG_PLUGIN_URL . 'admin/js/ld-fg-admin.js',
			$deps,
			ld_fg_asset_version( 'admin/js/ld-fg-admin.js' ),
			true
		);

		wp_localize_script(
			'ld-fg-admin',
			'ldFgAdmin',
			array(
				'nonce'   => wp_create_nonce( 'ld_fg_admin' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'fieldLabel'         => __( 'Field', 'lancedesk-form-guard' ),
					'fieldPrefix'        => __( 'Field:', 'lancedesk-form-guard' ),
					'flaggedContent'     => __( 'Flagged Content', 'lancedesk-form-guard' ),
					'capturedContent'    => __( 'Captured content', 'lancedesk-form-guard' ),
					'noCapturedFields'   => __( 'No captured fields for this entry.', 'lancedesk-form-guard' ),
					'submissionBlocked'  => __( 'Form Submission Blocked', 'lancedesk-form-guard' ),
				),
			)
		);

		if ( $is_dashboard ) {
			wp_localize_script(
				'ld-fg-admin',
				'ldFgDashboard',
				array(
					'chartData' => LD_FG_Logger::daily_chart_data( 7 ),
				)
			);
		}
	}

	/**
	 * Render page inside shell.
	 *
	 * @param string $page_key     Nav key.
	 * @param string $partial_file Partial filename.
	 *
	 * @return void
	 */
	private function render_page( string $page_key, string $partial_file ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'lancedesk-form-guard' ) );
		}

		$this->maybe_enable_when_forms_protected();

		$ld_fg_page_key   = $page_key;
		$ld_fg_nav_items  = self::get_nav_items();
		$ld_fg_partial    = LD_FG_PLUGIN_DIR . 'admin/partials/' . $partial_file;
		$ld_fg_settings   = LD_FG_Settings::get();
		$ld_fg_enabled    = ! empty( $ld_fg_settings['enabled'] );
		$ld_fg_logs_unread = LD_FG_Logger::count_unread( get_current_user_id() );

		if ( ! file_exists( $ld_fg_partial ) ) {
			wp_die( esc_html__( 'Admin view not found.', 'lancedesk-form-guard' ) );
		}

		require LD_FG_PLUGIN_DIR . 'admin/partials/shell.php';
	}

	/**
	 * If any forms are marked protected but the master switch is off, turn it on.
	 * Otherwise the engine silently skips (no logs / no blocks).
	 *
	 * @return void
	 */
	private function maybe_enable_when_forms_protected(): void {
		$settings  = LD_FG_Settings::get();
		$protected = isset( $settings['protected_forms'] ) ? (array) $settings['protected_forms'] : array();
		$core_on   = ! empty( $settings['protect_core_comments'] ) || ! empty( $settings['protect_core_registration'] );

		if ( empty( $settings['enabled'] ) && ( ! empty( $protected ) || $core_on ) ) {
			LD_FG_Settings::update( array( 'enabled' => 1 ) );
		}
	}

	/** @return void */
	public function render_dashboard(): void {
		$this->render_page( 'dashboard', 'page-dashboard.php' );
	}

	/** @return void */
	public function render_integrations(): void {
		$this->render_page( 'integrations', 'page-integrations.php' );
	}

	/** @return void */
	public function render_forms(): void {
		$this->render_page( 'forms', 'page-forms.php' );
	}

	/** @return void */
	public function render_protection(): void {
		$this->render_page( 'protection', 'page-protection.php' );
	}

	/** @return void */
	public function render_providers(): void {
		$this->render_page( 'providers', 'page-providers.php' );
	}

	/** @return void */
	public function render_logs(): void {
		$this->render_page( 'logs', 'page-logs.php' );
	}

	/** @return void */
	public function render_settings(): void {
		$this->render_page( 'settings', 'page-settings.php' );
	}

	/**
	 * AJAX: toggle form protection.
	 *
	 * @return void
	 */
	public function ajax_toggle_form(): void {
		if ( ! check_ajax_referer( 'ld_fg_admin', 'nonce', false ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token. Refresh the page and try again.', 'lancedesk-form-guard' ) ), 403 );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lancedesk-form-guard' ) ), 403 );
		}

		$integration = isset( $_POST['integration'] ) ? sanitize_key( wp_unslash( (string) $_POST['integration'] ) ) : '';
		$form_id     = isset( $_POST['form_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['form_id'] ) ) : '';

		$raw_flag = '';
		foreach ( array( 'protect', 'is_protected', 'protected' ) as $flag_key ) {
			if ( isset( $_POST[ $flag_key ] ) ) {
				$raw_flag = strtolower( trim( sanitize_text_field( wp_unslash( (string) $_POST[ $flag_key ] ) ) ) );
				break;
			}
		}

		// Accept common truthy encodings from jQuery/fetch.
		$is_protected = in_array( $raw_flag, array( '1', 'true', 'yes', 'on' ), true );

		if ( '' === $integration || '' === $form_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid form.', 'lancedesk-form-guard' ),
				),
				400
			);
		}

		$key  = $integration . ':' . $form_id;
		$opt  = get_option( LD_FG_Settings::OPTION_KEY, array() );
		$list = ( is_array( $opt ) && isset( $opt['protected_forms'] ) && is_array( $opt['protected_forms'] ) )
			? array_values( array_map( 'strval', $opt['protected_forms'] ) )
			: array();

		if ( $is_protected ) {
			if ( ! in_array( $key, $list, true ) ) {
				$list[] = $key;
			}
		} else {
			$list = array_values(
				array_filter(
					$list,
					static function ( string $item ) use ( $key ): bool {
						return $item !== $key;
					}
				)
			);
		}

		if ( ! LD_FG_Settings::set_protected_forms( $list ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not save protection state.', 'lancedesk-form-guard' ) ), 500 );
		}

		// Protecting a form should turn the master switch on — otherwise the engine
		// silently no-ops (no logs, no blocks) and setup step 4 never completes.
		if ( $is_protected ) {
			$current = LD_FG_Settings::get();
			if ( empty( $current['enabled'] ) ) {
				LD_FG_Settings::update( array( 'enabled' => 1 ) );
			}
		}

		wp_send_json_success(
			array(
				'protect'         => $is_protected ? 1 : 0,
				'key'             => $key,
				'protected_forms' => $list,
				'enabled'         => ! empty( LD_FG_Settings::get()['enabled'] ) ? 1 : 0,
			)
		);
	}

	/**
	 * AJAX: test AI provider connection.
	 *
	 * @return void
	 */
	public function ajax_test_provider(): void {
		check_ajax_referer( 'ld_fg_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lancedesk-form-guard' ) ) );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( (string) $_POST['provider'] ) ) : '';
		$api_key  = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['api_key'] ) ) : '';
		$model    = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['model'] ) ) : '';

		if ( '' === $provider ) {
			wp_send_json_error( array( 'message' => __( 'Invalid provider.', 'lancedesk-form-guard' ) ) );
		}

		if ( '' === $api_key || str_contains( $api_key, '***' ) ) {
			$settings = LD_FG_Settings::decrypt_provider_keys( LD_FG_Settings::get() );
			$api_key  = (string) ( $settings['providers'][ $provider ]['api_key'] ?? '' );
		}

		$result = LD_FG_AI_Router::test_connection( $provider, $api_key, $model );

		if ( empty( $result['success'] ) ) {
			wp_send_json_error( array( 'message' => $result['message'] ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: release quarantined log.
	 *
	 * @return void
	 */
	public function ajax_release_quarantine(): void {
		check_ajax_referer( 'ld_fg_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lancedesk-form-guard' ) ) );
		}

		$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;

		if ( ! LD_FG_Logger::update_status( $log_id, 'released' ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not release entry.', 'lancedesk-form-guard' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: discard quarantined log.
	 *
	 * @return void
	 */
	public function ajax_discard_quarantine(): void {
		check_ajax_referer( 'ld_fg_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lancedesk-form-guard' ) ) );
		}

		$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;

		if ( ! LD_FG_Logger::update_status( $log_id, 'discarded' ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not discard entry.', 'lancedesk-form-guard' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: mark a security log entry as read for the current user.
	 *
	 * @return void
	 */
	public function ajax_mark_log_read(): void {
		check_ajax_referer( 'ld_fg_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lancedesk-form-guard' ) ) );
		}

		$log_id = isset( $_POST['log_id'] ) ? absint( $_POST['log_id'] ) : 0;

		if ( ! LD_FG_Logger::mark_read( $log_id, get_current_user_id() ) ) {
			wp_send_json_error( array( 'message' => __( 'Could not mark entry as read.', 'lancedesk-form-guard' ) ) );
		}

		wp_send_json_success();
	}

	/**
	 * AJAX: bulk mark read / unread for selected log IDs.
	 *
	 * @return void
	 */
	public function ajax_bulk_logs(): void {
		check_ajax_referer( 'ld_fg_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lancedesk-form-guard' ) ) );
		}

		$bulk = isset( $_POST['bulk'] ) ? sanitize_key( wp_unslash( (string) $_POST['bulk'] ) ) : '';
		$ids  = $this->parse_log_ids_from_request();

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No log entries selected.', 'lancedesk-form-guard' ) ) );
		}

		$user_id = get_current_user_id();

		if ( 'mark-read' === $bulk ) {
			$n = LD_FG_Logger::mark_read_ids( $ids, $user_id );
			wp_send_json_success(
				array(
					'updated' => $n,
					/* translators: %d: number of entries. */
					'message' => sprintf( __( 'Marked %d entries as read.', 'lancedesk-form-guard' ), $n ),
				)
			);
		}

		if ( 'mark-unread' === $bulk ) {
			$n = LD_FG_Logger::mark_unread_ids( $ids, $user_id );
			wp_send_json_success(
				array(
					'updated' => $n,
					/* translators: %d: number of entries. */
					'message' => sprintf( __( 'Marked %d entries as unread.', 'lancedesk-form-guard' ), $n ),
				)
			);
		}

		wp_send_json_error( array( 'message' => __( 'Unknown bulk action.', 'lancedesk-form-guard' ) ) );
	}

	/**
	 * Stream filtered security logs as CSV download.
	 *
	 * @return void
	 */
	public function export_logs_csv(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'lancedesk-form-guard' ), 403 );
		}

		check_admin_referer( 'ld_fg_export_logs_csv' );

		$filter = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce checked above.
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$verdict = isset( $_GET['verdict'] ) ? sanitize_key( wp_unslash( (string) $_GET['verdict'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$layer = isset( $_GET['layer'] ) ? sanitize_key( wp_unslash( (string) $_GET['layer'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date_range = isset( $_GET['date_range'] ) ? sanitize_key( wp_unslash( (string) $_GET['date_range'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ids_raw = isset( $_GET['ids'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ids'] ) ) : '';

		if ( '' !== $search ) {
			$filter['search'] = $search;
		}
		if ( in_array( $verdict, array( 'blocked', 'allowed', 'quarantined' ), true ) ) {
			$filter['verdict'] = $verdict;
		}
		if ( in_array( $layer, array( 'heuristic', 'ai', 'none' ), true ) ) {
			$filter['layer'] = $layer;
		}
		if ( in_array( $date_range, array( 'today', 'yesterday', '7days' ), true ) ) {
			$filter['date_range'] = $date_range;
		}
		if ( '' !== $ids_raw ) {
			$filter['ids'] = array_map( 'absint', explode( ',', $ids_raw ) );
		}

		$filename = 'lancedesk-form-guard-logs-' . gmdate( 'Y-m-d-His' ) . '.csv';
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming download to php://output.
		$out = fopen( 'php://output', 'w' );
		if ( false === $out ) {
			wp_die( esc_html__( 'Could not open export stream.', 'lancedesk-form-guard' ) );
		}

		fprintf( $out, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		fputcsv( $out, array( 'ID', 'Date', 'Form', 'Form ID', 'Verdict', 'Layer', 'Confidence', 'Reason', 'Snippet', 'Integration' ) );

		$offset = 0;
		$chunk  = 200;
		do {
			$filter['offset'] = $offset;
			$rows             = LD_FG_Logger::query_recent( $chunk, $filter );
			foreach ( $rows as $row ) {
				fputcsv(
					$out,
					array(
						(int) ( $row['id'] ?? 0 ),
						(string) ( $row['created_at'] ?? '' ),
						(string) ( $row['form_title'] ?? '' ),
						(string) ( $row['form_id'] ?? '' ),
						(string) ( $row['verdict'] ?? '' ),
						(string) ( $row['layer'] ?? '' ),
						(string) round( (float) ( $row['confidence'] ?? 0 ) * 100 ),
						(string) ( $row['reason'] ?? '' ),
						(string) ( $row['snippet'] ?? '' ),
						(string) ( $row['integration'] ?? '' ),
					)
				);
			}
			$offset += $chunk;
		} while ( count( $rows ) === $chunk );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $out );
		exit;
	}

	/**
	 * Parse log ID list from POST (JSON array or comma string).
	 *
	 * @return array<int, int>
	 */
	private function parse_log_ids_from_request(): array {
		$raw_ids = isset( $_POST['ids'] ) ? wp_unslash( $_POST['ids'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Caller verifies nonce; sanitized below.

		if ( is_string( $raw_ids ) ) {
			$decoded = json_decode( $raw_ids, true );
			$raw_ids = is_array( $decoded ) ? $decoded : array_filter( array_map( 'trim', explode( ',', $raw_ids ) ) );
		}

		if ( ! is_array( $raw_ids ) ) {
			return array();
		}

		return array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $raw_ids ),
					static function ( int $id ): bool {
						return $id > 0;
					}
				)
			)
		);
	}

	/**
	 * AJAX: permanently delete security log rows.
	 *
	 * @return void
	 */
	public function ajax_delete_logs(): void {
		check_ajax_referer( 'ld_fg_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lancedesk-form-guard' ) ) );
		}

		$delete_all = ! empty( $_POST['delete_all'] );

		if ( $delete_all ) {
			$deleted = LD_FG_Logger::delete_all();
			wp_send_json_success(
				array(
					'deleted' => $deleted,
					/* translators: %d: number of deleted log entries. */
					'message' => sprintf( __( 'Deleted %d log entries.', 'lancedesk-form-guard' ), $deleted ),
				)
			);
		}

		$ids = $this->parse_log_ids_from_request();

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No log entries selected.', 'lancedesk-form-guard' ) ) );
		}

		$deleted = LD_FG_Logger::delete_ids( $ids );

		if ( $deleted < 1 ) {
			wp_send_json_error( array( 'message' => __( 'Could not delete log entries.', 'lancedesk-form-guard' ) ) );
		}

		wp_send_json_success(
			array(
				'deleted' => $deleted,
				/* translators: %d: number of deleted log entries. */
				'message' => sprintf( __( 'Deleted %d log entries.', 'lancedesk-form-guard' ), $deleted ),
			)
		);
	}

	/**
	 * AJAX: dismiss first-run setup.
	 *
	 * @return void
	 */
	public function ajax_dismiss_setup(): void {
		check_ajax_referer( 'ld_fg_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lancedesk-form-guard' ) ) );
		}

		LD_FG_Settings::update( array( 'setup_dismissed' => 1 ) );

		wp_send_json_success();
	}

	/**
	 * AJAX: save settings from admin UI.
	 *
	 * @return void
	 */
	public function ajax_save_settings(): void {
		check_ajax_referer( 'ld_fg_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lancedesk-form-guard' ) ) );
		}

		// JSON object/string from admin UI; every value is sanitized in sanitize_settings().
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_settings() after json_decode.
		$raw = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : array();

		if ( is_string( $raw ) ) {
			$decoded = json_decode( $raw, true );
			$raw     = is_array( $decoded ) ? $decoded : array();
		}

		if ( ! is_array( $raw ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings payload.', 'lancedesk-form-guard' ) ) );
		}

		$sanitized = $this->sanitize_settings( $raw );
		update_option( LD_FG_Settings::OPTION_KEY, $sanitized );

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'lancedesk-form-guard' ) ) );
	}
}

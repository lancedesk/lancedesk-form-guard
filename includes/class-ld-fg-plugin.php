<?php
/**
 * Main plugin bootstrap.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin singleton.
 */
final class LD_FG_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var LD_FG_Plugin|null
	 */
	private static ?LD_FG_Plugin $instance = null;

	/**
	 * Registered form adapters.
	 *
	 * @var array<string, LD_FG_Form_Adapter>
	 */
	private array $adapters = array();

	/**
	 * Get singleton instance.
	 *
	 * @return LD_FG_Plugin
	 */
	public static function instance(): LD_FG_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->register_adapters();

		if ( is_admin() ) {
			require_once LD_FG_PLUGIN_DIR . 'admin/class-ld-fg-admin.php';
			new LD_FG_Admin();
		}

		require_once LD_FG_PLUGIN_DIR . 'public/class-ld-fg-public.php';
		new LD_FG_Frontend();
	}

	/**
	 * Load core class files.
	 *
	 * @return void
	 */
	private function load_dependencies(): void {
		require_once LD_FG_PLUGIN_DIR . 'includes/class-ld-fg-crypto.php';
		require_once LD_FG_PLUGIN_DIR . 'includes/class-ld-fg-settings.php';
		require_once LD_FG_PLUGIN_DIR . 'includes/class-ld-fg-logger.php';
		require_once LD_FG_PLUGIN_DIR . 'includes/class-ld-fg-heuristics.php';
		require_once LD_FG_PLUGIN_DIR . 'includes/class-ld-fg-ai-router.php';
		require_once LD_FG_PLUGIN_DIR . 'includes/class-ld-fg-engine.php';

		require_once LD_FG_PLUGIN_DIR . 'includes/adapters/class-ld-fg-adapter-wpforms.php';
		require_once LD_FG_PLUGIN_DIR . 'includes/adapters/class-ld-fg-adapter-cf7.php';
		require_once LD_FG_PLUGIN_DIR . 'includes/adapters/class-ld-fg-adapter-gravity.php';
		require_once LD_FG_PLUGIN_DIR . 'includes/adapters/class-ld-fg-adapter-quform.php';
		require_once LD_FG_PLUGIN_DIR . 'includes/adapters/class-ld-fg-adapter-ninja.php';
		require_once LD_FG_PLUGIN_DIR . 'includes/adapters/class-ld-fg-adapter-core.php';
	}

	/**
	 * Register adapters and their hooks on plugins_loaded.
	 *
	 * @return void
	 */
	private function register_adapters(): void {
		require_once LD_FG_PLUGIN_DIR . 'includes/adapters/interface-ld-fg-form-adapter.php';

		$this->adapters = array(
			'core'    => new LD_FG_Adapter_Core(),
			'wpforms' => new LD_FG_Adapter_WPForms(),
			'cf7'     => new LD_FG_Adapter_CF7(),
			'gravity' => new LD_FG_Adapter_Gravity(),
			'quform'  => new LD_FG_Adapter_Quform(),
			'ninja'   => new LD_FG_Adapter_Ninja(),
		);

		// Merge external adapters + register hooks after other plugins load.
		add_action(
			'init',
			function (): void {
				/**
				 * Filter registered Form Guard adapters (add custom integrations).
				 *
				 * @param array<string, LD_FG_Form_Adapter> $adapters Adapters keyed by slug.
				 */
				$extra = apply_filters( 'ld_fg_adapters', array() );
				if ( is_array( $extra ) ) {
					foreach ( $extra as $slug => $adapter ) {
						if ( $adapter instanceof LD_FG_Form_Adapter ) {
							$this->adapters[ sanitize_key( (string) $slug ) ] = $adapter;
						}
					}
				}

				foreach ( $this->adapters as $adapter ) {
					if ( $adapter->is_available() ) {
						$adapter->register_hooks();
					}
				}
			},
			20
		);
	}

	/**
	 * Get all adapters.
	 *
	 * @return array<string, LD_FG_Form_Adapter>
	 */
	public function get_adapters(): array {
		return $this->adapters;
	}

	/**
	 * Get available adapters only.
	 *
	 * @return array<string, LD_FG_Form_Adapter>
	 */
	public function get_available_adapters(): array {
		return array_filter(
			$this->adapters,
			static function ( LD_FG_Form_Adapter $adapter ): bool {
				return $adapter->is_available();
			}
		);
	}
}

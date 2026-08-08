<?php
/**
 * Front-end honeypot + render-time field injection.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects Layer-1 trap fields into protected forms and enqueues helpers.
 */
final class LD_FG_Frontend {

	/**
	 * Whether assets were enqueued this request.
	 *
	 * @var bool
	 */
	private bool $assets_enqueued = false;

	/**
	 * Bootstrap front-end hooks.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_assets' ), 20 );
		add_action( 'init', array( $this, 'register_form_hooks' ), 30 );
	}

	/**
	 * Whether injection should run at all for this site request.
	 *
	 * @return bool
	 */
	public function is_injection_enabled(): bool {
		$settings = LD_FG_Settings::get();

		if ( empty( $settings['enabled'] ) ) {
			return false;
		}

		$heuristics = isset( $settings['heuristics'] ) && is_array( $settings['heuristics'] )
			? $settings['heuristics']
			: array();

		return ! empty( $heuristics['honeypot'] ) || ! empty( $heuristics['too_fast'] );
	}

	/**
	 * Register per-integration form output hooks.
	 *
	 * @return void
	 */
	public function register_form_hooks(): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( ! $this->is_injection_enabled() ) {
			return;
		}

		// Contact Form 7.
		add_filter( 'wpcf7_form_elements', array( $this, 'inject_cf7' ), 20 );

		// WPForms.
		add_action( 'wpforms_frontend_output', array( $this, 'inject_wpforms' ), 10, 5 );

		// Gravity Forms.
		add_filter( 'gform_get_form_filter', array( $this, 'inject_gravity' ), 20, 2 );

		// Quform.
		add_filter( 'quform_form', array( $this, 'inject_quform' ), 20, 2 );

		// Ninja Forms — markup after open tag when available; JS covers AJAX payload.
		add_action( 'ninja_forms_display_after_open_form_tag', array( $this, 'inject_ninja' ), 10, 1 );
		add_action( 'ninja_forms_display_after_fields', array( $this, 'inject_ninja' ), 10, 1 );
	}

	/**
	 * Enqueue CSS/JS when a supported form plugin is active and injection is on.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets(): void {
		if ( $this->assets_enqueued || ! $this->is_injection_enabled() ) {
			return;
		}

		$plugin   = LD_FG_Plugin::instance();
		$active   = $plugin->get_available_adapters();
		$settings = LD_FG_Settings::get();

		if ( empty( $active ) ) {
			return;
		}

		$heuristics = isset( $settings['heuristics'] ) && is_array( $settings['heuristics'] )
			? $settings['heuristics']
			: array();

		$protected = array();
		foreach ( (array) ( $settings['protected_forms'] ?? array() ) as $key ) {
			$key = (string) $key;
			if ( '' === $key || ! str_contains( $key, ':' ) ) {
				continue;
			}
			[ $slug, $id ] = array_pad( explode( ':', $key, 2 ), 2, '' );
			if ( '' === $slug || '' === $id ) {
				continue;
			}
			if ( ! isset( $protected[ $slug ] ) ) {
				$protected[ $slug ] = array();
			}
			$protected[ $slug ][] = (string) $id;
		}

		// protect_all integrations.
		foreach ( (array) ( $settings['protect_all'] ?? array() ) as $slug => $on ) {
			if ( empty( $on ) ) {
				continue;
			}
			$protected[ (string) $slug ] = array( '*' );
		}

		if ( empty( $protected ) ) {
			return;
		}

		$this->assets_enqueued = true;

		wp_enqueue_style(
			'ld-fg-frontend',
			LD_FG_PLUGIN_URL . 'public/css/ld-fg-frontend.css',
			array(),
			ld_fg_asset_version( 'public/css/ld-fg-frontend.css' )
		);

		wp_enqueue_script(
			'ld-fg-frontend',
			LD_FG_PLUGIN_URL . 'public/js/ld-fg-frontend.js',
			array(),
			ld_fg_asset_version( 'public/js/ld-fg-frontend.js' ),
			true
		);

		wp_localize_script(
			'ld-fg-frontend',
			'ldFgFront',
			array(
				'honeypot'  => ! empty( $heuristics['honeypot'] ),
				'tooFast'   => ! empty( $heuristics['too_fast'] ),
				'protected' => $protected,
				'now'       => time(),
			)
		);
	}

	/**
	 * CF7: append trap fields inside the form.
	 *
	 * @param string $elements Form HTML elements.
	 *
	 * @return string
	 */
	public function inject_cf7( string $elements ): string {
		$form = class_exists( 'WPCF7_ContactForm', false ) ? WPCF7_ContactForm::get_current() : null;
		if ( ! $form || ! method_exists( $form, 'id' ) ) {
			return $elements;
		}

		if ( ! LD_FG_Settings::is_form_protected( 'cf7', (string) $form->id() ) ) {
			return $elements;
		}

		$this->maybe_enqueue_assets();

		return $elements . $this->fields_html( 'cf7', (string) $form->id() );
	}

	/**
	 * WPForms: print trap fields during front-end output.
	 *
	 * @param array<string, mixed> $form_data Form data.
	 * @param mixed                ...$args   Extra WPForms args (unused).
	 *
	 * @return void
	 */
	public function inject_wpforms( $form_data, ...$args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		if ( ! is_array( $form_data ) ) {
			return;
		}

		$form_id = isset( $form_data['id'] ) ? (string) $form_data['id'] : '';
		if ( '' === $form_id || ! LD_FG_Settings::is_form_protected( 'wpforms', $form_id ) ) {
			return;
		}

		$this->maybe_enqueue_assets();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fields_html escapes.
		echo $this->fields_html( 'wpforms', $form_id );
	}

	/**
	 * Gravity Forms: insert traps before closing form tag.
	 *
	 * @param string               $form_string Form HTML.
	 * @param array<string, mixed> $form        Form array.
	 *
	 * @return string
	 */
	public function inject_gravity( string $form_string, $form ): string {
		$form_id = is_array( $form ) && isset( $form['id'] ) ? (string) $form['id'] : '';
		if ( '' === $form_id || ! LD_FG_Settings::is_form_protected( 'gravity', $form_id ) ) {
			return $form_string;
		}

		$this->maybe_enqueue_assets();
		$fields = $this->fields_html( 'gravity', $form_id );

		if ( false !== stripos( $form_string, '</form>' ) ) {
			return (string) preg_replace( '/<\/form>/i', $fields . '</form>', $form_string, 1 );
		}

		return $form_string . $fields;
	}

	/**
	 * Quform: append traps to form HTML when filter provides it.
	 *
	 * @param mixed $form Form HTML or object.
	 * @param mixed $form_object Optional form object.
	 *
	 * @return mixed
	 */
	public function inject_quform( $form, $form_object = null ) {
		$form_id = '';

		if ( is_object( $form_object ) && method_exists( $form_object, 'getId' ) ) {
			$form_id = (string) $form_object->getId();
		} elseif ( is_object( $form ) && method_exists( $form, 'getId' ) ) {
			$form_id = (string) $form->getId();
		}

		if ( '' === $form_id || ! LD_FG_Settings::is_form_protected( 'quform', $form_id ) ) {
			return $form;
		}

		if ( ! is_string( $form ) ) {
			return $form;
		}

		$this->maybe_enqueue_assets();
		$fields = $this->fields_html( 'quform', $form_id );

		if ( false !== stripos( $form, '</form>' ) ) {
			return (string) preg_replace( '/<\/form>/i', $fields . '</form>', $form, 1 );
		}

		return $form . $fields;
	}

	/**
	 * Ninja Forms: echo traps into the form markup.
	 *
	 * @param int|string $form_id Form ID.
	 *
	 * @return void
	 */
	public function inject_ninja( $form_id ): void {
		$form_id = (string) $form_id;
		if ( '' === $form_id || ! LD_FG_Settings::is_form_protected( 'ninja', $form_id ) ) {
			return;
		}

		$this->maybe_enqueue_assets();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fields_html escapes.
		echo $this->fields_html( 'ninja', $form_id );
	}

	/**
	 * Build honeypot + render-time markup.
	 *
	 * @param string $integration Integration slug.
	 * @param string $form_id     Form ID.
	 *
	 * @return string
	 */
	public function fields_html( string $integration, string $form_id ): string {
		$settings   = LD_FG_Settings::get();
		$heuristics = isset( $settings['heuristics'] ) && is_array( $settings['heuristics'] )
			? $settings['heuristics']
			: array();

		$uid   = 'ld-fg-' . sanitize_html_class( $integration ) . '-' . sanitize_html_class( $form_id );
		$parts = array(
			'<div class="ld-fg-traps" data-ld-fg-integration="' . esc_attr( $integration ) . '" data-ld-fg-form="' . esc_attr( $form_id ) . '">',
		);

		if ( ! empty( $heuristics['too_fast'] ) ) {
			$parts[] = '<input type="hidden" name="ld_fg_rt" class="ld-fg-rt" value="' . esc_attr( (string) time() ) . '" autocomplete="off" />';
		}

		if ( ! empty( $heuristics['honeypot'] ) ) {
			$parts[] = '<div class="ld-fg-hp" aria-hidden="true">';
			$parts[] = '<label for="' . esc_attr( $uid . '-hp' ) . '">' . esc_html__( 'Leave this field empty', 'lancedesk-form-guard' ) . '</label>';
			$parts[] = '<input type="text" name="ld_fg_hp" id="' . esc_attr( $uid . '-hp' ) . '" value="" tabindex="-1" autocomplete="off" />';
			$parts[] = '</div>';
		}

		$parts[] = '</div>';

		return implode( '', $parts );
	}
}

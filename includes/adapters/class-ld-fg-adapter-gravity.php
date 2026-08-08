<?php
/**
 * Gravity Forms adapter.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once LD_FG_PLUGIN_DIR . 'includes/adapters/interface-ld-fg-form-adapter.php';

/**
 * Gravity Forms integration.
 */
final class LD_FG_Adapter_Gravity implements LD_FG_Form_Adapter {

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'gravity';
	}

	/**
	 * @inheritDoc
	 */
	public function is_available(): bool {
		return class_exists( 'GFForms', false ) || class_exists( 'GFAPI', false );
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return 'Gravity Forms';
	}

	/**
	 * @inheritDoc
	 */
	public function list_forms(): array {
		if ( ! $this->is_available() || ! class_exists( 'GFAPI', false ) ) {
			return array();
		}

		$items = GFAPI::get_forms();
		$forms = array();

		if ( ! is_array( $items ) ) {
			return array();
		}

		foreach ( $items as $form ) {
			if ( empty( $form['id'] ) ) {
				continue;
			}

			$forms[] = array(
				'id'       => (string) $form['id'],
				'title'    => (string) ( $form['title'] ?? '' ),
				'edit_url' => admin_url( 'admin.php?page=gf_edit_forms&id=' . absint( $form['id'] ) ),
			);
		}

		return $forms;
	}

	/**
	 * @inheritDoc
	 */
	public function register_hooks(): void {
		add_filter( 'gform_validation', array( $this, 'handle_validation' ), 5 );
	}

	/**
	 * Gravity Forms validation hook.
	 *
	 * @param array<string, mixed> $validation_result Validation result.
	 *
	 * @return array<string, mixed>
	 */
	public function handle_validation( array $validation_result ): array {
		$context = $this->extract( $validation_result );

		if ( null === $context ) {
			return $validation_result;
		}

		$result = LD_FG_Engine::evaluate( $context );

		if ( ! empty( $result['block'] ) ) {
			$this->apply_block( $validation_result, (string) $result['action'] );
			$validation_result['is_valid'] = false;
		}

		return $validation_result;
	}

	/**
	 * @inheritDoc
	 */
	public function extract( $source ): ?array {
		if ( ! is_array( $source ) || empty( $source['form'] ) ) {
			return null;
		}

		$form    = $source['form'];
		$form_id = isset( $form['id'] ) ? (string) $form['id'] : '';
		$fields  = array();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Intercept runs inside gform_validation after GF nonce checks.
		if ( ! empty( $source['form']['fields'] ) && is_array( $source['form']['fields'] ) ) {
			foreach ( $source['form']['fields'] as $field ) {
				$input = 'input_' . ( is_object( $field ) ? $field->id : ( $field['id'] ?? '' ) );
				if ( isset( $_POST[ $input ] ) ) {
					$fields[] = sanitize_text_field( wp_unslash( (string) $_POST[ $input ] ) );
				}
			}
		}

		$context = array(
			'integration' => $this->get_slug(),
			'form_id'     => $form_id,
			'form_title'  => (string) ( $form['title'] ?? '' ),
			'fields'      => $fields,
			'honeypot'    => isset( $_POST['ld_fg_hp'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ld_fg_hp'] ) ) : '',
			'render_time' => isset( $_POST['ld_fg_rt'] ) ? absint( $_POST['ld_fg_rt'] ) : 0,
			'submit_time' => time(),
			'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
			'email'       => isset( $_POST['input_1'] ) ? sanitize_email( wp_unslash( (string) $_POST['input_1'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return $context;
	}

	/**
	 * @inheritDoc
	 */
	public function apply_block( $source, string $action ): void {
		if ( ! is_array( $source ) ) {
			return;
		}

		if ( 'soft_fail' === $action ) {
			$source['is_valid'] = false;
			if ( ! empty( $source['form']['fields'][0] ) ) {
				$field = $source['form']['fields'][0];
				$field->failed_validation  = true;
				$field->validation_message = __( 'Something went wrong. Please try again later.', 'lancedesk-form-guard' );
			}
		}
	}
}

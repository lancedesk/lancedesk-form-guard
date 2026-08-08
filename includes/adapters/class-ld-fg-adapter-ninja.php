<?php
/**
 * Ninja Forms adapter.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once LD_FG_PLUGIN_DIR . 'includes/adapters/interface-ld-fg-form-adapter.php';

/**
 * Ninja Forms integration.
 *
 * Hooks before action processing (email, save, success) via ninja_forms_submit_data.
 */
final class LD_FG_Adapter_Ninja implements LD_FG_Form_Adapter {

	/**
	 * Pending block action keyed by form ID for submission_actions filter.
	 *
	 * @var array<string, string>
	 */
	private array $pending_blocks = array();

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'ninja';
	}

	/**
	 * @inheritDoc
	 */
	public function is_available(): bool {
		return class_exists( 'Ninja_Forms', false ) || function_exists( 'Ninja_Forms' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return 'Ninja Forms';
	}

	/**
	 * @inheritDoc
	 */
	public function list_forms(): array {
		if ( ! $this->is_available() || ! function_exists( 'Ninja_Forms' ) ) {
			return array();
		}

		try {
			$items = Ninja_Forms()->form()->get_forms();
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- API may throw on bad installs.
			return array();
		}

		if ( ! is_array( $items ) ) {
			return array();
		}

		$forms = array();

		foreach ( $items as $form ) {
			if ( ! is_object( $form ) || ! method_exists( $form, 'get_id' ) ) {
				continue;
			}

			$form_id = (string) $form->get_id();
			$title   = method_exists( $form, 'get_setting' )
				? (string) $form->get_setting( 'title' )
				: '';

			if ( '' === $title && method_exists( $form, 'get_settings' ) ) {
				$settings = $form->get_settings();
				$title    = is_array( $settings ) ? (string) ( $settings['title'] ?? '' ) : '';
			}

			$forms[] = array(
				'id'       => $form_id,
				'title'    => $title ? $title : sprintf(
					/* translators: %s: form ID. */
					__( 'Ninja Form #%s', 'lancedesk-form-guard' ),
					$form_id
				),
				'edit_url' => admin_url( 'admin.php?page=ninja-forms&form_id=' . rawurlencode( $form_id ) ),
			);
		}

		usort(
			$forms,
			static function ( array $a, array $b ): int {
				return strcasecmp( $a['title'], $b['title'] );
			}
		);

		return $forms;
	}

	/**
	 * @inheritDoc
	 */
	public function register_hooks(): void {
		add_filter( 'ninja_forms_submit_data', array( $this, 'handle_submit_data' ), 5 );
		add_filter( 'ninja_forms_submission_actions', array( $this, 'filter_submission_actions' ), 5, 2 );
	}

	/**
	 * Evaluate submission before Ninja Forms runs email/save/success actions.
	 *
	 * @param array<string, mixed> $form_data Submission payload.
	 *
	 * @return array<string, mixed>
	 */
	public function handle_submit_data( array $form_data ): array {
		$context = $this->extract( $form_data );

		if ( null === $context ) {
			return $form_data;
		}

		$result = LD_FG_Engine::evaluate( $context );

		if ( empty( $result['block'] ) ) {
			return $form_data;
		}

		$action  = (string) $result['action'];
		$form_id = (string) ( $form_data['id'] ?? '' );

		if ( '' !== $form_id ) {
			$this->pending_blocks[ $form_id ] = $action;
		}

		// Soft fail: surface a generic error. Silent discard / quarantine: no errors —
		// keep success actions, strip mail in filter_submission_actions().
		if ( 'soft_fail' === $action ) {
			return $this->with_block_errors( $form_data, $action );
		}

		return $form_data;
	}

	/**
	 * Strip notification (and related) actions when a block is pending.
	 *
	 * @param array<int, mixed>    $actions   Registered submission actions.
	 * @param array<string, mixed> $form_data Submission payload.
	 *
	 * @return array<int, mixed>
	 */
	public function filter_submission_actions( array $actions, $form_data ): array {
		$form_id = is_array( $form_data ) ? (string) ( $form_data['id'] ?? '' ) : '';

		if ( '' === $form_id || empty( $this->pending_blocks[ $form_id ] ) ) {
			return $actions;
		}

		$action = $this->pending_blocks[ $form_id ];
		unset( $this->pending_blocks[ $form_id ] );

		// Always drop outbound email. Soft fail already halted via field errors.
		// Silent discard keeps successmessage/redirect so AJAX finishes like a real send.
		$strip_types = array( 'email', 'emailadmin', 'emailuser' );

		if ( 'soft_fail' === $action ) {
			$strip_types = array_merge( $strip_types, array( 'successmessage', 'redirect', 'save' ) );
		} else {
			// Silent / quarantine: no mail, no DB save of spam — still show thank-you.
			$strip_types = array_merge( $strip_types, array( 'save' ) );
		}

		$filtered = array();

		foreach ( $actions as $item ) {
			$type = '';

			if ( is_array( $item ) ) {
				$type = strtolower( (string) ( $item['settings']['type'] ?? $item['type'] ?? '' ) );
			} elseif ( is_object( $item ) && method_exists( $item, 'get_setting' ) ) {
				$type = strtolower( (string) $item->get_setting( 'type' ) );
			} elseif ( is_object( $item ) && method_exists( $item, 'get_type' ) ) {
				$type = strtolower( (string) $item->get_type() );
			}

			if ( '' !== $type && in_array( $type, $strip_types, true ) ) {
				continue;
			}

			$filtered[] = $item;
		}

		return $filtered;
	}

	/**
	 * @inheritDoc
	 */
	public function extract( $source ): ?array {
		if ( ! is_array( $source ) ) {
			return null;
		}

		$form_id = isset( $source['id'] ) ? (string) $source['id'] : '';
		$fields  = isset( $source['fields'] ) && is_array( $source['fields'] ) ? $source['fields'] : array();
		$flat    = array();
		$email   = '';
		$title   = '';

		if ( ! empty( $source['settings'] ) && is_array( $source['settings'] ) ) {
			$title = (string) ( $source['settings']['title'] ?? '' );
		}

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$value = $field['value'] ?? '';

			if ( is_array( $value ) ) {
				$value = implode( ', ', array_map( 'strval', $value ) );
			}

			$value = trim( (string) $value );

			if ( '' === $value ) {
				continue;
			}

			$flat[] = $value;

			$type = strtolower( (string) ( $field['type'] ?? '' ) );
			$key  = strtolower( (string) ( $field['key'] ?? '' ) );

			if ( '' === $email && ( 'email' === $type || false !== strpos( $key, 'email' ) ) ) {
				$email = sanitize_email( $value );
			}
		}

		return array(
			'integration' => $this->get_slug(),
			'form_id'     => $form_id,
			'form_title'  => $title,
			'fields'      => $flat,
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Intercept runs after Ninja Forms AJAX nonce validation.
			'honeypot'    => isset( $_POST['ld_fg_hp'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ld_fg_hp'] ) ) : '',
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Intercept runs after Ninja Forms AJAX nonce validation.
			'render_time' => isset( $_POST['ld_fg_rt'] ) ? absint( $_POST['ld_fg_rt'] ) : 0,
			'submit_time' => time(),
			'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
			'email'       => $email,
		);
	}

	/**
	 * @inheritDoc
	 *
	 * Blocking for Ninja Forms is applied in handle_submit_data() (array return).
	 */
	public function apply_block( $source, string $action ): void {
		// no-op — see with_block_errors().
	}

	/**
	 * Attach field errors so Ninja Forms halts action processing (soft fail only).
	 *
	 * @param array<string, mixed> $form_data Submission payload.
	 * @param string               $action    Engine action.
	 *
	 * @return array<string, mixed>
	 */
	private function with_block_errors( array $form_data, string $action ): array {
		$field_id = $this->first_field_id( $form_data );

		if ( null === $field_id ) {
			return $form_data;
		}

		if ( ! isset( $form_data['errors'] ) || ! is_array( $form_data['errors'] ) ) {
			$form_data['errors'] = array();
		}

		if ( ! isset( $form_data['errors']['fields'] ) || ! is_array( $form_data['errors']['fields'] ) ) {
			$form_data['errors']['fields'] = array();
		}

		if ( 'soft_fail' === $action ) {
			$form_data['errors']['fields'][ $field_id ] = __( 'Something went wrong. Please try again later.', 'lancedesk-form-guard' );
		}

		return $form_data;
	}

	/**
	 * First numeric field ID in the submission (for error attachment).
	 *
	 * @param array<string, mixed> $form_data Submission payload.
	 *
	 * @return int|string|null
	 */
	private function first_field_id( array $form_data ) {
		if ( empty( $form_data['fields'] ) || ! is_array( $form_data['fields'] ) ) {
			return null;
		}

		foreach ( $form_data['fields'] as $key => $field ) {
			if ( is_array( $field ) && isset( $field['id'] ) ) {
				return $field['id'];
			}

			if ( is_numeric( $key ) ) {
				return $key;
			}
		}

		return null;
	}
}

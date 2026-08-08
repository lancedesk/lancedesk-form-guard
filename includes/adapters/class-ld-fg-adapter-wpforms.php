<?php
/**
 * WPForms adapter.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once LD_FG_PLUGIN_DIR . 'includes/adapters/interface-ld-fg-form-adapter.php';

/**
 * WPForms integration.
 */
final class LD_FG_Adapter_WPForms implements LD_FG_Form_Adapter {

	/**
	 * Form IDs that should complete successfully but never send notification emails.
	 *
	 * @var array<int, true>
	 */
	private array $silent_form_ids = array();

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'wpforms';
	}

	/**
	 * @inheritDoc
	 */
	public function is_available(): bool {
		return function_exists( 'wpforms' ) || class_exists( 'WPForms', false );
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return 'WPForms';
	}

	/**
	 * @inheritDoc
	 */
	public function list_forms(): array {
		if ( ! $this->is_available() ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'      => 'wpforms',
				'post_status'    => array( 'publish' ),
				'posts_per_page' => 200,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$forms = array();

		foreach ( $posts as $post ) {
			$forms[] = array(
				'id'       => (string) $post->ID,
				'title'    => $post->post_title,
				'edit_url' => get_edit_post_link( $post->ID, 'raw' ) ?: '',
			);
		}

		return $forms;
	}

	/**
	 * @inheritDoc
	 */
	public function register_hooks(): void {
		// After field validation/formatting — $fields have value keys; errors stop entry + notifications.
		// Docs: https://wpforms.com/developers/wpforms_process/
		add_action( 'wpforms_process', array( $this, 'handle_process' ), 5, 3 );
		add_filter( 'wpforms_entry_email', array( $this, 'maybe_skip_all_emails' ), 10, 4 );
		add_filter( 'wpforms_entry_email_process', array( $this, 'maybe_skip_email_process' ), 10, 4 );
	}

	/**
	 * Intercept WPForms submission after fields are validated and formatted.
	 *
	 * @param array<int|string, mixed> $fields    Sanitized field values.
	 * @param array<string, mixed>     $entry     Raw entry / POST payload.
	 * @param array<string, mixed>     $form_data Form settings and ID.
	 *
	 * @return void
	 */
	public function handle_process( $fields, $entry, $form_data ): void {
		if ( ! is_array( $fields ) || ! is_array( $form_data ) ) {
			return;
		}

		$form_id = isset( $form_data['id'] ) ? (string) $form_data['id'] : '';
		if ( '' === $form_id && is_array( $entry ) && isset( $entry['id'] ) ) {
			$form_id = (string) $entry['id'];
		}

		$context = $this->extract(
			array(
				'fields'    => $fields,
				'entry'     => is_array( $entry ) ? $entry : array(),
				'form_data' => $form_data,
				'form_id'   => $form_id,
			)
		);

		if ( null === $context ) {
			return;
		}

		$result = LD_FG_Engine::evaluate( $context );

		if ( empty( $result['block'] ) ) {
			return;
		}

		$action = (string) $result['action'];

		if ( 'soft_fail' === $action ) {
			$this->apply_block( $form_data, $action );
			return;
		}

		// Silent discard / quarantine: let confirmation UI complete; block notification emails.
		if ( $form_id !== '' ) {
			$this->silent_form_ids[ (int) $form_id ] = true;
		}
	}

	/**
	 * Skip all WPForms notification emails for silently discarded submissions.
	 *
	 * @param bool                     $send      Whether to send.
	 * @param array<int|string, mixed> $fields    Fields.
	 * @param array<string, mixed>     $entry     Entry.
	 * @param array<string, mixed>     $form_data Form data.
	 *
	 * @return bool
	 */
	public function maybe_skip_all_emails( $send, $fields, $entry, $form_data ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		return $this->should_send_wpforms_email( (bool) $send, $form_data );
	}

	/**
	 * Skip a single WPForms notification for silently discarded submissions.
	 *
	 * @param bool                     $process         Whether to process.
	 * @param array<int|string, mixed> $fields          Fields.
	 * @param array<string, mixed>     $form_data       Form data.
	 * @param int|string               $notification_id Notification ID.
	 *
	 * @return bool
	 */
	public function maybe_skip_email_process( $process, $fields, $form_data, $notification_id ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
		return $this->should_send_wpforms_email( (bool) $process, $form_data );
	}

	/**
	 * @param bool                     $default   Default send flag.
	 * @param array<string, mixed>|mixed $form_data Form data.
	 *
	 * @return bool
	 */
	private function should_send_wpforms_email( bool $default, $form_data ): bool {
		$form_id = is_array( $form_data ) ? (int) ( $form_data['id'] ?? 0 ) : 0;

		if ( $form_id > 0 && isset( $this->silent_form_ids[ $form_id ] ) ) {
			return false;
		}

		return $default;
	}

	/**
	 * @inheritDoc
	 */
	public function extract( $source ): ?array {
		if ( ! is_array( $source ) ) {
			return null;
		}

		$form_id = (string) ( $source['form_id'] ?? '' );
		$fields  = isset( $source['fields'] ) && is_array( $source['fields'] ) ? $source['fields'] : array();

		$flat = array();

		foreach ( $fields as $field ) {
			if ( is_array( $field ) && array_key_exists( 'value', $field ) ) {
				$value = $field['value'];
				if ( is_array( $value ) ) {
					$flat[] = implode( ', ', array_map( 'strval', $value ) );
				} elseif ( '' !== (string) $value ) {
					$flat[] = (string) $value;
				}
				continue;
			}

			if ( is_scalar( $field ) && '' !== (string) $field ) {
				$flat[] = (string) $field;
			}
		}

		$title = '';
		if ( ! empty( $source['form_data']['settings']['form_title'] ) ) {
			$title = (string) $source['form_data']['settings']['form_title'];
		} elseif ( $form_id ) {
			$post_title = get_the_title( (int) $form_id );
			$title      = is_string( $post_title ) ? $post_title : '';
		}

		return array(
			'integration' => $this->get_slug(),
			'form_id'     => $form_id,
			'form_title'  => $title,
			'fields'      => $flat,
			'honeypot'    => isset( $_POST['ld_fg_hp'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ld_fg_hp'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'render_time' => isset( $_POST['ld_fg_rt'] ) ? absint( $_POST['ld_fg_rt'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'submit_time' => time(),
			'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
			'email'       => $this->find_email( $fields ),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function apply_block( $source, string $action ): void {
		$form_id = 0;
		if ( is_array( $source ) ) {
			$form_id = (int) ( $source['id'] ?? 0 );
		}

		if ( $form_id <= 0 || ! function_exists( 'wpforms' ) || ! isset( wpforms()->process ) ) {
			return;
		}

		if ( 'soft_fail' === $action ) {
			wpforms()->process->errors[ $form_id ]['header'] = __( 'Something went wrong. Please try again later.', 'lancedesk-form-guard' );
		}
	}

	/**
	 * Find email field value.
	 *
	 * @param array<int|string, mixed> $fields WPForms fields.
	 *
	 * @return string
	 */
	private function find_email( array $fields ): string {
		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			if ( isset( $field['type'] ) && 'email' === $field['type'] && ! empty( $field['value'] ) ) {
				return sanitize_email( (string) $field['value'] );
			}
		}

		return '';
	}
}

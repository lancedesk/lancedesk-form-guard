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
		add_action( 'wpforms_process_before', array( $this, 'handle_process_before' ), 5, 2 );
	}

	/**
	 * Intercept WPForms submission.
	 *
	 * @param array<string, mixed> $fields Form fields.
	 * @param array<string, mixed> $entry  Entry data.
	 *
	 * @return void
	 */
	public function handle_process_before( array $fields, array $entry ): void {
		$form_id = isset( $entry['id'] ) ? (string) $entry['id'] : '';

		$context = $this->extract(
			array(
				'fields'  => $fields,
				'entry'   => $entry,
				'form_id' => $form_id,
			)
		);

		if ( null === $context ) {
			return;
		}

		$result = LD_FG_Engine::evaluate( $context );

		if ( ! empty( $result['block'] ) ) {
			$this->apply_block( $entry, (string) $result['action'] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function extract( $source ): ?array {
		if ( ! is_array( $source ) ) {
			return null;
		}

		$form_id = (string) ( $source['form_id'] ?? '' );
		$entry   = isset( $source['entry'] ) && is_array( $source['entry'] ) ? $source['entry'] : array();
		$fields  = isset( $source['fields'] ) && is_array( $source['fields'] ) ? $source['fields'] : array();

		$flat = array();

		foreach ( $fields as $field ) {
			if ( is_array( $field ) && isset( $field['value'] ) ) {
				$flat[] = is_array( $field['value'] ) ? implode( ', ', $field['value'] ) : (string) $field['value'];
			}
		}

		$title = $form_id ? get_the_title( (int) $form_id ) : '';

		return array(
			'integration' => $this->get_slug(),
			'form_id'     => $form_id,
			'form_title'  => is_string( $title ) ? $title : '',
			'fields'      => $flat,
			'honeypot'    => isset( $_POST['ld_fg_hp'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ld_fg_hp'] ) ) : '',
			'render_time' => isset( $_POST['ld_fg_rt'] ) ? absint( $_POST['ld_fg_rt'] ) : 0,
			'submit_time' => time(),
			'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
			'email'       => $this->find_email( $fields ),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function apply_block( $source, string $action ): void {
		if ( 'soft_fail' === $action ) {
			wpforms()->process->errors[ (int) ( $source['id'] ?? 0 ) ]['header'] = __( 'Something went wrong. Please try again later.', 'lancedesk-form-guard' );
		}

		// silent_discard and quarantine stop processing by setting a generic error when soft_fail; otherwise unset success.
		if ( 'soft_fail' !== $action && isset( wpforms()->process ) ) {
			wpforms()->process->errors[ (int) ( $source['id'] ?? 0 ) ]['header'] = ' ';
		}
	}

	/**
	 * Find email field value.
	 *
	 * @param array<int, mixed> $fields WPForms fields.
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

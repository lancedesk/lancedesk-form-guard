<?php
/**
 * Contact Form 7 adapter.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once LD_FG_PLUGIN_DIR . 'includes/adapters/interface-ld-fg-form-adapter.php';

/**
 * Contact Form 7 integration.
 */
final class LD_FG_Adapter_CF7 implements LD_FG_Form_Adapter {

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'cf7';
	}

	/**
	 * @inheritDoc
	 */
	public function is_available(): bool {
		return defined( 'WPCF7_VERSION' ) || class_exists( 'WPCF7_ContactForm', false );
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return 'Contact Form 7';
	}

	/**
	 * @inheritDoc
	 */
	public function list_forms(): array {
		if ( ! $this->is_available() || ! class_exists( 'WPCF7_ContactForm', false ) ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'      => 'wpcf7_contact_form',
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
		add_filter( 'wpcf7_validate', array( $this, 'handle_validate' ), 5, 2 );
	}

	/**
	 * Validate CF7 submission.
	 *
	 * @param bool                 $valid  Current validity.
	 * @param array<string, mixed> $tags   Form tags.
	 *
	 * @return bool
	 */
	public function handle_validate( $valid, $tags ) {
		$submission = class_exists( 'WPCF7_Submission', false ) ? WPCF7_Submission::get_instance() : null;

		if ( ! $submission ) {
			return $valid;
		}

		$context = $this->extract( $submission );

		if ( null === $context ) {
			return $valid;
		}

		$result = LD_FG_Engine::evaluate( $context );

		if ( ! empty( $result['block'] ) ) {
			$this->apply_block( $submission, (string) $result['action'] );

			return false;
		}

		return $valid;
	}

	/**
	 * @inheritDoc
	 */
	public function extract( $source ): ?array {
		if ( ! is_object( $source ) || ! method_exists( $source, 'get_posted_data' ) ) {
			return null;
		}

		$data    = $source->get_posted_data();
		$form_id = method_exists( $source, 'get_contact_form' ) && $source->get_contact_form()
			? (string) $source->get_contact_form()->id()
			: '';

		$title = $form_id ? get_the_title( (int) $form_id ) : '';
		$flat  = is_array( $data ) ? array_values( array_map( 'strval', $data ) ) : array();

		return array(
			'integration' => $this->get_slug(),
			'form_id'     => $form_id,
			'form_title'  => is_string( $title ) ? $title : '',
			'fields'      => $flat,
			'honeypot'    => isset( $_POST['ld_fg_hp'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ld_fg_hp'] ) ) : '',
			'render_time' => isset( $_POST['ld_fg_rt'] ) ? absint( $_POST['ld_fg_rt'] ) : 0,
			'submit_time' => time(),
			'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
			'email'       => isset( $data['your-email'] ) ? sanitize_email( (string) $data['your-email'] ) : '',
		);
	}

	/**
	 * @inheritDoc
	 */
	public function apply_block( $source, string $action ): void {
		if ( ! is_object( $source ) || ! method_exists( $source, 'set_status' ) ) {
			return;
		}

		if ( 'soft_fail' === $action ) {
			$source->set_status( 'validation_failed' );
			if ( method_exists( $source, 'set_response' ) ) {
				$source->set_response( __( 'Something went wrong. Please try again later.', 'lancedesk-form-guard' ) );
			}
		} else {
			$source->set_status( 'aborted' );
		}
	}
}

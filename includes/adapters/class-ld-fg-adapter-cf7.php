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
	 * Skip outbound mail for this request (silent discard / quarantine).
	 *
	 * @var bool
	 */
	private bool $skip_mail = false;

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
		add_filter( 'wpcf7_skip_mail', array( $this, 'maybe_skip_mail' ), 10, 2 );
	}

	/**
	 * Validate CF7 submission.
	 *
	 * Soft fail → validation error. Silent discard / quarantine → allow success UI, skip mail.
	 *
	 * @param mixed                $result Validation result (WPCF7_Validation in modern CF7).
	 * @param array<string, mixed> $tags   Form tags.
	 *
	 * @return mixed
	 */
	public function handle_validate( $result, $tags ) {
		$submission = class_exists( 'WPCF7_Submission', false ) ? WPCF7_Submission::get_instance() : null;

		if ( ! $submission ) {
			return $result;
		}

		$context = $this->extract( $submission );

		if ( null === $context ) {
			return $result;
		}

		$eval = LD_FG_Engine::evaluate( $context );

		if ( empty( $eval['block'] ) ) {
			return $result;
		}

		$action = (string) $eval['action'];

		if ( 'soft_fail' === $action ) {
			$this->apply_soft_fail( $result, $tags, $submission );
			return $result;
		}

		// Silent discard / quarantine: thank-you UI, no email.
		$this->skip_mail = true;

		return $result;
	}

	/**
	 * Skip CF7 mail when silent discard flagged this request.
	 *
	 * @param bool   $skip         Current skip flag.
	 * @param object $contact_form Contact form.
	 *
	 * @return bool
	 */
	public function maybe_skip_mail( $skip, $contact_form ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return (bool) $skip || $this->skip_mail;
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
			'honeypot'    => isset( $_POST['ld_fg_hp'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ld_fg_hp'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'render_time' => isset( $_POST['ld_fg_rt'] ) ? absint( $_POST['ld_fg_rt'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'submit_time' => time(),
			'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
			'email'       => isset( $data['your-email'] ) ? sanitize_email( (string) $data['your-email'] ) : '',
		);
	}

	/**
	 * @inheritDoc
	 */
	public function apply_block( $source, string $action ): void {
		// Soft fail handled in handle_validate(); silent uses skip_mail.
	}

	/**
	 * Mark submission as a soft validation failure with a generic message.
	 *
	 * @param mixed                $result     Validation result.
	 * @param array<int, mixed>    $tags       Form tags.
	 * @param object               $submission Submission instance.
	 *
	 * @return void
	 */
	private function apply_soft_fail( $result, $tags, $submission ): void {
		$message = __( 'Something went wrong. Please try again later.', 'lancedesk-form-guard' );

		if ( is_object( $result ) && method_exists( $result, 'invalidate' ) && is_array( $tags ) ) {
			foreach ( $tags as $tag ) {
				if ( is_object( $tag ) ) {
					$result->invalidate( $tag, $message );
					break;
				}
			}
		}

		if ( method_exists( $submission, 'set_status' ) ) {
			$submission->set_status( 'validation_failed' );
		}
		if ( method_exists( $submission, 'set_response' ) ) {
			$submission->set_response( $message );
		}
	}
}

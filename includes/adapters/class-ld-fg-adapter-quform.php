<?php
/**
 * Quform adapter.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once LD_FG_PLUGIN_DIR . 'includes/adapters/interface-ld-fg-form-adapter.php';

/**
 * Quform integration.
 */
final class LD_FG_Adapter_Quform implements LD_FG_Form_Adapter {

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'quform';
	}

	/**
	 * @inheritDoc
	 */
	public function is_available(): bool {
		return class_exists( 'Quform', false ) || defined( 'QUFORM_VERSION' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return 'Quform';
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
				'post_type'      => 'quform',
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
		add_filter( 'quform_pre_process', array( $this, 'handle_pre_process' ), 5, 2 );
	}

	/**
	 * Quform pre-process hook.
	 *
	 * @param bool                 $continue Continue processing.
	 * @param Quform_Form|null     $form     Form object.
	 *
	 * @return bool
	 */
	public function handle_pre_process( $continue, $form ) {
		$context = $this->extract(
			array(
				'form' => $form,
			)
		);

		if ( null === $context ) {
			return $continue;
		}

		$result = LD_FG_Engine::evaluate( $context );

		if ( ! empty( $result['block'] ) ) {
			$this->apply_block( $form, (string) $result['action'] );

			return false;
		}

		return $continue;
	}

	/**
	 * @inheritDoc
	 */
	public function extract( $source ): ?array {
		if ( ! is_array( $source ) || empty( $source['form'] ) ) {
			return null;
		}

		$form = $source['form'];
		$id   = is_object( $form ) && method_exists( $form, 'getId' ) ? (string) $form->getId() : '';
		$name = is_object( $form ) && method_exists( $form, 'getName' ) ? (string) $form->getName() : '';

		$fields = array();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Intercept runs after Quform has validated the submission.
		foreach ( $_POST as $key => $value ) {
			if ( 0 === strpos( (string) $key, 'quform_' ) && is_string( $value ) ) {
				$fields[] = sanitize_text_field( wp_unslash( $value ) );
			}
		}

		$context = array(
			'integration' => $this->get_slug(),
			'form_id'     => $id,
			'form_title'  => $name,
			'fields'      => $fields,
			'honeypot'    => isset( $_POST['ld_fg_hp'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ld_fg_hp'] ) ) : '',
			'render_time' => isset( $_POST['ld_fg_rt'] ) ? absint( $_POST['ld_fg_rt'] ) : 0,
			'submit_time' => time(),
			'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
			'email'       => '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return $context;
	}

	/**
	 * @inheritDoc
	 */
	public function apply_block( $source, string $action ): void {
		if ( is_object( $source ) && method_exists( $source, 'addError' ) && 'soft_fail' === $action ) {
			$source->addError( __( 'Something went wrong. Please try again later.', 'lancedesk-form-guard' ) );
		}
	}
}

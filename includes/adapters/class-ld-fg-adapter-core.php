<?php
/**
 * WordPress Core (comments) adapter.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once LD_FG_PLUGIN_DIR . 'includes/adapters/interface-ld-fg-form-adapter.php';

/**
 * Native WordPress comment form integration.
 */
final class LD_FG_Adapter_Core implements LD_FG_Form_Adapter {

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'core';
	}

	/**
	 * @inheritDoc
	 */
	public function is_available(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return __( 'WordPress Core', 'lancedesk-form-guard' );
	}

	/**
	 * @inheritDoc
	 */
	public function list_forms(): array {
		return array();
	}

	/**
	 * @inheritDoc
	 */
	public function register_hooks(): void {
		add_filter( 'preprocess_comment', array( $this, 'handle_preprocess_comment' ), 5 );
	}

	/**
	 * Run protection on native comment submissions.
	 *
	 * @param array<string, mixed> $commentdata Comment data.
	 *
	 * @return array<string, mixed>
	 */
	public function handle_preprocess_comment( array $commentdata ): array {
		if ( ! empty( $commentdata['comment_type'] ) && 'comment' !== (string) $commentdata['comment_type'] ) {
			return $commentdata;
		}

		$context = $this->extract( $commentdata );

		if ( null === $context ) {
			return $commentdata;
		}

		$eval = LD_FG_Engine::evaluate( $context );

		if ( empty( $eval['block'] ) ) {
			return $commentdata;
		}

		$this->abort_comment_submission( $commentdata, (string) $eval['action'] );

		return $commentdata;
	}

	/**
	 * Stop WordPress from saving a blocked comment (log only lives in Form Guard).
	 *
	 * Setting comment_approved to spam is not enough — moderators and other filters
	 * can still auto-approve. Exit the request before wp_new_comment() runs.
	 *
	 * @param array<string, mixed> $commentdata Comment data.
	 * @param string               $action      Block action from the engine.
	 *
	 * @return never
	 */
	private function abort_comment_submission( array $commentdata, string $action ): void {
		if ( 'soft_fail' === $action ) {
			wp_die(
				esc_html__( 'Something went wrong. Please try again later.', 'lancedesk-form-guard' ),
				esc_html__( 'Comment not accepted', 'lancedesk-form-guard' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}

		$post_id = isset( $commentdata['comment_post_ID'] ) ? absint( $commentdata['comment_post_ID'] ) : 0;
		$parent  = isset( $commentdata['comment_parent'] ) ? absint( $commentdata['comment_parent'] ) : 0;

		if ( $parent ) {
			$location = get_comment_link( $parent );
		} elseif ( $post_id ) {
			$location = get_permalink( $post_id );
		} else {
			$location = home_url( '/' );
		}

		if ( ! is_string( $location ) || '' === $location ) {
			$location = home_url( '/' );
		}

		// Mirror a normal successful comment POST so the visitor (or bot) does not retry.
		$location = add_query_arg( 'comment-posted', 'true', $location );

		/**
		 * Filters the redirect URL after Form Guard discards a blocked comment.
		 *
		 * @param string               $location    Redirect URL.
		 * @param array<string, mixed> $commentdata Comment data (not saved to wp_comments).
		 * @param string               $action      Block action slug.
		 */
		$location = apply_filters( 'ld_fg_comment_discard_redirect', $location, $commentdata, $action );

		wp_safe_redirect( $location );
		exit;
	}

	/**
	 * @inheritDoc
	 */
	public function extract( $source ): ?array {
		if ( ! is_array( $source ) ) {
			return null;
		}

		$author  = isset( $source['comment_author'] ) ? sanitize_text_field( (string) $source['comment_author'] ) : '';
		$email   = isset( $source['comment_author_email'] ) ? sanitize_email( (string) $source['comment_author_email'] ) : '';
		$url     = isset( $source['comment_author_url'] ) ? esc_url_raw( (string) $source['comment_author_url'] ) : '';
		$content = isset( $source['comment_content'] ) ? wp_strip_all_tags( (string) $source['comment_content'] ) : '';

		$fields = array(
			'author'  => $author,
			'email'   => $email,
			'url'     => $url,
			'content' => $content,
		);

		$post_id = isset( $source['comment_post_ID'] ) ? absint( $source['comment_post_ID'] ) : 0;
		$title   = $post_id ? get_the_title( $post_id ) : '';

		return array(
			'integration' => $this->get_slug(),
			'form_id'     => 'comment',
			'form_title'  => __( 'WordPress Comments', 'lancedesk-form-guard' ),
			'fields'      => $fields,
			'honeypot'    => isset( $_POST['ld_fg_hp'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ld_fg_hp'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'render_time' => isset( $_POST['ld_fg_rt'] ) ? absint( $_POST['ld_fg_rt'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'submit_time' => time(),
			'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
			'email'       => $email,
			'meta'        => array(
				'post_id'   => $post_id,
				'post_title' => is_string( $title ) ? $title : '',
			),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function apply_block( $source, string $action ): void {
		// Blocking is handled in handle_preprocess_comment().
	}
}

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
 *
 * Forms live in the Quform DB tables (via Quform_Repository), not as a WP post type.
 * Submission interception uses quform_pre_validate (after field values are set).
 */
final class LD_FG_Adapter_Quform implements LD_FG_Form_Adapter {

	/**
	 * POST keys that are Quform plumbing, not user field content.
	 *
	 * @var array<int, string>
	 */
	private const META_POST_KEYS = array(
		'quform_form_id',
		'quform_submit',
		'quform_current_page_id',
		'quform_uid',
		'quform_captcha_unique_id',
	);

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
		return defined( 'QUFORM_VERSION' ) || class_exists( 'Quform', false );
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
		$repository = $this->get_repository();

		if ( ! $repository || ! method_exists( $repository, 'all' ) ) {
			return array();
		}

		// Non-trashed forms (active + inactive). Quform::all( null ) = no active filter.
		$rows = $repository->all( null, 'name', 'ASC' );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$forms = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || empty( $row['id'] ) ) {
				continue;
			}

			$id = (string) $row['id'];

			$forms[] = array(
				'id'       => $id,
				'title'    => isset( $row['name'] ) ? (string) $row['name'] : ( 'Form ' . $id ),
				'edit_url' => admin_url( 'admin.php?page=quform.forms&sp=edit&id=' . rawurlencode( $id ) ),
			);
		}

		return $forms;
	}

	/**
	 * @inheritDoc
	 */
	public function register_hooks(): void {
		// After setValues() so field content is available on the form object.
		add_filter( 'quform_pre_validate', array( $this, 'handle_pre_validate' ), 5, 2 );
	}

	/**
	 * Quform pre-validate: return a non-empty result array to short-circuit processing.
	 *
	 * @param array            $result Existing result (empty to continue).
	 * @param Quform_Form|null $form   Form object.
	 *
	 * @return array
	 */
	public function handle_pre_validate( $result, $form ) {
		if ( ! is_array( $result ) ) {
			$result = array();
		}

		// Another filter already decided — leave it alone.
		if ( ! empty( $result ) ) {
			return $result;
		}

		$context = $this->extract(
			array(
				'form' => $form,
			)
		);

		if ( null === $context ) {
			return $result;
		}

		$eval = LD_FG_Engine::evaluate( $context );

		if ( empty( $eval['block'] ) ) {
			return $result;
		}

		return $this->block_response( $form, (string) $eval['action'] );
	}

	/**
	 * @inheritDoc
	 */
	public function extract( $source ): ?array {
		if ( ! is_array( $source ) || empty( $source['form'] ) || ! is_object( $source['form'] ) ) {
			return null;
		}

		$form = $source['form'];
		$id   = method_exists( $form, 'getId' ) ? (string) $form->getId() : '';

		$fields = $this->extract_form_values( $form );
		// Always merge POST: getValues() can miss nested/composite keys while name/email
		// still populate $fields — which previously skipped the POST fallback entirely.
		$fields = array_merge( $fields, $this->extract_post_fields() );

		$email = '';
		foreach ( $fields as $value ) {
			if ( is_email( $value ) ) {
				$email = sanitize_email( $value );
				break;
			}
		}

		return array(
			'integration' => $this->get_slug(),
			'form_id'     => $id,
			'form_title'  => $this->resolve_form_title( $form, $id ),
			'fields'      => $fields,
			'honeypot'    => isset( $_POST['ld_fg_hp'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ld_fg_hp'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'render_time' => isset( $_POST['ld_fg_rt'] ) ? absint( $_POST['ld_fg_rt'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Missing
			'submit_time' => time(),
			'ip'          => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '',
			'email'       => $email,
		);
	}

	/**
	 * @inheritDoc
	 */
	public function apply_block( $source, string $action ): void {
		// Blocking is handled via filter return value in handle_pre_validate().
	}

	/**
	 * Resolve Quform form display name for logs (not the numeric id).
	 *
	 * Quform_Form has config( 'name' ), not getName().
	 *
	 * @param object $form Form object.
	 * @param string $id   Form id fallback.
	 *
	 * @return string
	 */
	private function resolve_form_title( object $form, string $id ): string {
		if ( method_exists( $form, 'config' ) ) {
			$name = trim( (string) $form->config( 'name', '' ) );
			if ( '' !== $name ) {
				return $name;
			}
			$title = trim( (string) $form->config( 'title', '' ) );
			if ( '' !== $title ) {
				return wp_strip_all_tags( $title );
			}
		}

		$repository = $this->get_repository();
		if ( $repository && method_exists( $repository, 'find' ) && '' !== $id ) {
			$row = $repository->find( (int) $id );
			if ( is_array( $row ) && ! empty( $row['name'] ) ) {
				return (string) $row['name'];
			}
		}

		return '' !== $id ? ( 'Form ' . $id ) : '';
	}

	/**
	 * Collect values from the Quform form object.
	 *
	 * @param object $form Form object.
	 *
	 * @return array<string, string>
	 */
	private function extract_form_values( object $form ): array {
		$fields = array();

		if ( method_exists( $form, 'getRecursiveIterator' ) ) {
			foreach ( $form->getRecursiveIterator() as $element ) {
				if ( ! is_object( $element ) || ! method_exists( $element, 'getName' ) ) {
					continue;
				}
				// Skip non-field containers (Page/Group/etc.).
				if ( ! method_exists( $element, 'getValue' ) && ! method_exists( $element, 'getValueText' ) ) {
					continue;
				}

				$key = (string) $element->getName();
				if ( '' === $key || 0 !== strpos( $key, 'quform_' ) ) {
					continue;
				}

				$value = '';
				if ( method_exists( $element, 'getValueText' ) ) {
					$value = $element->getValueText( ' ' );
				} elseif ( method_exists( $element, 'getValue' ) ) {
					$raw = $element->getValue();
					if ( is_array( $raw ) ) {
						$value = implode( ' ', array_map( 'strval', $raw ) );
					} else {
						$value = (string) $raw;
					}
				}

				$value = $this->sanitize_field_value( $value );
				if ( '' === $value ) {
					continue;
				}

				$fields[ $this->sanitize_field_key( $key ) ] = $value;
			}

			return $fields;
		}

		if ( method_exists( $form, 'getValues' ) ) {
			$values = $form->getValues();
			if ( is_array( $values ) ) {
				foreach ( $values as $key => $value ) {
					if ( is_array( $value ) ) {
						$value = implode( ' ', array_map( 'strval', $value ) );
					}
					$value = $this->sanitize_field_value( (string) $value );
					if ( '' === $value ) {
						continue;
					}
					$fields[ $this->sanitize_field_key( (string) $key ) ] = $value;
				}
			}
		}

		return $fields;
	}

	/**
	 * Flatten quform_* POST values (including nested arrays) into string fields.
	 *
	 * @return array<string, string>
	 */
	private function extract_post_fields(): array {
		$fields = array();

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Runs inside Quform's submission pipeline.
		foreach ( $_POST as $key => $value ) {
			$key = (string) $key;
			if ( 0 !== strpos( $key, 'quform_' ) ) {
				continue;
			}
			if ( in_array( $key, self::META_POST_KEYS, true ) ) {
				continue;
			}

			$this->flatten_post_value( $fields, $key, $value );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return $fields;
	}

	/**
	 * Recursively flatten a POST value into $fields.
	 *
	 * @param array<string, string> $fields Accumulator.
	 * @param string                $key    Field key.
	 * @param mixed                 $value  Raw POST value.
	 */
	private function flatten_post_value( array &$fields, string $key, $value ): void {
		if ( is_array( $value ) ) {
			foreach ( $value as $child_key => $child_value ) {
				$child_name = is_int( $child_key )
					? $key . '_' . $child_key
					: $key . '_' . (string) $child_key;
				$this->flatten_post_value( $fields, $child_name, $child_value );
			}
			return;
		}

		if ( ! is_scalar( $value ) ) {
			return;
		}

		$text = $this->sanitize_field_value( wp_unslash( (string) $value ) );
		if ( '' === $text ) {
			return;
		}

		$fields[ $this->sanitize_field_key( $key ) ] = $text;
	}

	/**
	 * Sanitize a Quform field key for storage/logging.
	 *
	 * @param string $key Raw key.
	 *
	 * @return string
	 */
	private function sanitize_field_key( string $key ): string {
		return sanitize_text_field( $key );
	}

	/**
	 * Sanitize user field content while keeping multi-line message bodies intact.
	 *
	 * @param string $value Raw value.
	 *
	 * @return string
	 */
	private function sanitize_field_value( string $value ): string {
		$value = trim( sanitize_textarea_field( $value ) );
		return $value;
	}

	/**
	 * Build Quform processor response for a blocked submission.
	 *
	 * @param mixed  $form   Form object.
	 * @param string $action Block action slug.
	 *
	 * @return array<string, mixed>
	 */
	private function block_response( $form, string $action ): array {
		if ( 'soft_fail' === $action ) {
			return array(
				'type'  => 'error',
				'error' => array(
					'enabled' => true,
					'title'   => '',
					'content' => __( 'Something went wrong. Please try again later.', 'lancedesk-form-guard' ),
				),
			);
		}

		// Silent discard / quarantine: thank-you UI, no entry save, no notification emails.
		$confirmation = array(
			'type'            => 'message',
			'message'         => '',
			'messageIcon'     => '',
			'messagePosition' => 'above',
			'messageTimeout'  => '10',
			'hideForm'        => false,
			'resetForm'       => 'reset',
		);

		if ( is_object( $form ) && method_exists( $form, 'setConfirmation' ) && method_exists( $form, 'getConfirmation' ) ) {
			$form->setConfirmation();
			$conf = $form->getConfirmation();
			if ( is_object( $conf ) && method_exists( $conf, 'getData' ) ) {
				$data = $conf->getData();
				if ( is_array( $data ) && ! empty( $data ) ) {
					$confirmation = $data;
				}
			}
		}

		return array(
			'type'         => 'success',
			'confirmation' => $confirmation,
		);
	}

	/**
	 * Resolve Quform_Repository from the Quform service container.
	 *
	 * @return object|null
	 */
	private function get_repository() {
		if ( ! $this->is_available() ) {
			return null;
		}

		if ( function_exists( 'quform' ) ) {
			$repository = quform( 'repository' );
			if ( is_object( $repository ) ) {
				return $repository;
			}
		}

		if ( class_exists( 'Quform', false ) && method_exists( 'Quform', 'getService' ) ) {
			$repository = Quform::getService( 'repository' );
			if ( is_object( $repository ) ) {
				return $repository;
			}
		}

		return null;
	}
}

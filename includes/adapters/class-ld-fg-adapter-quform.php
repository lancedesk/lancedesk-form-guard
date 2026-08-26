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
		'quform_form_uid',
		'quform_captcha_unique_id',
		'quform_csrf_token',
		'quform_loaded',
		'quform_ajax',
		'quform_count',
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

		$extracted = $this->extract_form_values( $form );
		$fields    = isset( $extracted['fields'] ) && is_array( $extracted['fields'] ) ? $extracted['fields'] : array();
		$honeypot  = isset( $extracted['honeypot'] ) ? (string) $extracted['honeypot'] : '';

		// Always merge POST: getValues() can miss nested/composite keys while name/email
		// still populate $fields — which previously skipped the POST fallback entirely.
		$post = $this->extract_post_fields( $id, $honeypot );
		$fields = array_merge( $fields, $post['fields'] );
		if ( '' === $honeypot && '' !== $post['honeypot'] ) {
			$honeypot = $post['honeypot'];
		}

		// Our injected honeypot (if present on the page).
		if ( '' === $honeypot && isset( $_POST['ld_fg_hp'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$honeypot = sanitize_text_field( wp_unslash( (string) $_POST['ld_fg_hp'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

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
			'honeypot'    => $honeypot,
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
	 * Honeypot elements are excluded from fields and returned separately when filled.
	 *
	 * @param object $form Form object.
	 *
	 * @return array{fields: array<string, string>, honeypot: string}
	 */
	private function extract_form_values( object $form ): array {
		$fields   = array();
		$honeypot = '';

		if ( method_exists( $form, 'getRecursiveIterator' ) ) {
			foreach ( $form->getRecursiveIterator() as $element ) {
				if ( ! is_object( $element ) || ! method_exists( $element, 'getName' ) ) {
					continue;
				}
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

				if ( $this->is_honeypot_element( $element ) ) {
					if ( '' !== $value && '' === $honeypot ) {
						$honeypot = $value;
					}
					continue;
				}

				if ( '' === $value ) {
					continue;
				}

				$fields[ $this->sanitize_field_key( $key ) ] = $value;
			}

			return array(
				'fields'   => $fields,
				'honeypot' => $honeypot,
			);
		}

		if ( method_exists( $form, 'getValues' ) ) {
			$values = $form->getValues();
			if ( is_array( $values ) ) {
				$form_id = method_exists( $form, 'getId' ) ? (int) $form->getId() : 0;
				$hp_key  = $this->honeypot_post_key( $form_id );

				foreach ( $values as $key => $value ) {
					$key = (string) $key;
					if ( is_array( $value ) ) {
						$value = implode( ' ', array_map( 'strval', $value ) );
					}
					$value = $this->sanitize_field_value( (string) $value );
					if ( '' === $value ) {
						continue;
					}
					if ( '' !== $hp_key && $key === $hp_key ) {
						if ( '' === $honeypot ) {
							$honeypot = $value;
						}
						continue;
					}
					$fields[ $this->sanitize_field_key( $key ) ] = $value;
				}
			}
		}

		return array(
			'fields'   => $fields,
			'honeypot' => $honeypot,
		);
	}

	/**
	 * Flatten quform_* POST values (including nested arrays) into string fields.
	 *
	 * @param string $form_id       Form id.
	 * @param string $known_honeypot Already-detected honeypot value (if any).
	 *
	 * @return array{fields: array<string, string>, honeypot: string}
	 */
	private function extract_post_fields( string $form_id, string $known_honeypot = '' ): array {
		$fields   = array();
		$honeypot = $known_honeypot;
		$hp_key   = $this->honeypot_post_key( (int) $form_id );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Runs inside Quform's submission pipeline.
		foreach ( $_POST as $key => $value ) {
			$key = (string) $key;
			if ( 0 !== strpos( $key, 'quform_' ) ) {
				continue;
			}
			if ( $this->is_meta_post_key( $key ) ) {
				continue;
			}

			$flat = array();
			$this->flatten_post_value( $flat, $key, $value );

			foreach ( $flat as $flat_key => $flat_value ) {
				if ( '' !== $hp_key && ( $flat_key === $hp_key || 0 === strpos( $flat_key, $hp_key . '_' ) ) ) {
					if ( '' !== $flat_value && '' === $honeypot ) {
						$honeypot = $flat_value;
					}
					continue;
				}
				$fields[ $flat_key ] = $flat_value;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return array(
			'fields'   => $fields,
			'honeypot' => $honeypot,
		);
	}

	/**
	 * Whether a POST key is Quform plumbing (not user content).
	 *
	 * @param string $key POST key.
	 *
	 * @return bool
	 */
	private function is_meta_post_key( string $key ): bool {
		if ( in_array( $key, self::META_POST_KEYS, true ) ) {
			return true;
		}

		// Defensive: any future plumbing keys like quform_something_token.
		if ( preg_match( '/^quform_(csrf|loaded|ajax|count|uid|form_uid|form_id|submit|current_page)/', $key ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether a Quform element is the built-in honeypot.
	 *
	 * @param object $element Element object.
	 *
	 * @return bool
	 */
	private function is_honeypot_element( object $element ): bool {
		if ( class_exists( 'Quform_Element_Honeypot', false ) && $element instanceof Quform_Element_Honeypot ) {
			return true;
		}

		if ( method_exists( $element, 'config' ) && 'honeypot' === (string) $element->config( 'type' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Deterministic Quform honeypot POST name for a form id.
	 *
	 * Mirrors Quform_Form_Factory: md5('hp_id_{id}') → element id → quform_{formId}_{elementId}.
	 *
	 * @param int $form_id Form id.
	 *
	 * @return string Empty when form id is invalid.
	 */
	private function honeypot_post_key( int $form_id ): string {
		if ( $form_id < 1 ) {
			return '';
		}

		$element_id = ( hexdec( substr( md5( 'hp_id_' . $form_id ), 0, 6 ) ) % 900000 ) + 100000;

		return 'quform_' . $form_id . '_' . $element_id;
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

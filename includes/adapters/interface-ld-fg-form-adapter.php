<?php
/**
 * Form plugin adapter contract.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for third-party form plugin integrations.
 */
interface LD_FG_Form_Adapter {

	/**
	 * Integration slug.
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Whether the form plugin is available.
	 *
	 * @return bool
	 */
	public function is_available(): bool;

	/**
	 * Human-readable label.
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * List forms for admin UI.
	 *
	 * @return array<int, array{id: string, title: string, edit_url: string}>
	 */
	public function list_forms(): array;

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks(): void;

	/**
	 * Extract normalized submission from hook context.
	 *
	 * @param mixed $source Hook-specific source object/array.
	 *
	 * @return array<string, mixed>|null
	 */
	public function extract( $source ): ?array;

	/**
	 * Apply block action to the submission flow.
	 *
	 * @param mixed  $source Hook-specific source.
	 * @param string $action Action slug from engine.
	 *
	 * @return void
	 */
	public function apply_block( $source, string $action ): void;
}

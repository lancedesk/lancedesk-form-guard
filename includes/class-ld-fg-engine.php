<?php
/**
 * Form submission protection orchestrator.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs Layer 1 heuristics then Layer 2 AI and applies action.
 */
final class LD_FG_Engine {

	/**
	 * Evaluate a submission and return decision.
	 *
	 * @param array<string, mixed> $context Integration context: integration, form_id, form_title, fields, honeypot, render_time, submit_time, ip, email.
	 *
	 * @return array{block: bool, action: string, verdict: array<string, mixed>, log_id: int}
	 */
	public static function evaluate( array $context ): array {
		$settings = LD_FG_Settings::decrypt_provider_keys( LD_FG_Settings::get() );

		$integration = sanitize_key( (string) ( $context['integration'] ?? '' ) );
		$form_id     = sanitize_text_field( (string) ( $context['form_id'] ?? '' ) );

		$allow = array(
			'block'   => false,
			'action'  => 'allow',
			'verdict' => array(
				'verdict'    => 'clean',
				'confidence' => 0.0,
				'reason'     => '',
				'layer'      => '',
			),
			'log_id'  => 0,
		);

		if ( empty( $settings['enabled'] ) || ! LD_FG_Settings::is_form_protected( $integration, $form_id ) ) {
			return $allow;
		}

		$payload = array(
			'fields'       => $context['fields'] ?? array(),
			'honeypot'     => $context['honeypot'] ?? '',
			'render_time'  => $context['render_time'] ?? 0,
			'submit_time'  => $context['submit_time'] ?? time(),
		);

		$verdict = LD_FG_Heuristics::evaluate( $payload, $settings );

		if ( 'spam' === $verdict['verdict'] ) {
			$action = self::map_action( $settings, $verdict['confidence'], true );

			return self::finalize( $context, $verdict, $action, $settings );
		}

		if ( ! empty( $settings['ai_enabled'] ) ) {
			$content = self::combine_fields( $payload['fields'] );
			$ai      = LD_FG_AI_Router::classify( $content, $settings );

			if ( 'spam' === $ai['verdict'] && $ai['confidence'] > 0 ) {
				$action = self::map_action( $settings, $ai['confidence'], false );

				return self::finalize( $context, $ai, $action, $settings );
			}

			if ( empty( $ai['provider'] ) && 'fail_closed' === ( $settings['ai_fail_mode'] ?? 'fail_open' ) ) {
				$verdict = array(
					'verdict'    => 'spam',
					'confidence' => 0.5,
					'reason'     => __( 'AI provider unavailable (fail closed).', 'lancedesk-form-guard' ),
					'layer'      => 'ai',
				);
				$action  = self::map_action( $settings, 0.9, false );

				return self::finalize( $context, $verdict, $action, $settings );
			}
		}

		$allow['log_id'] = LD_FG_Logger::insert(
			array(
				'integration' => $integration,
				'form_id'     => $form_id,
				'form_title'  => (string) ( $context['form_title'] ?? '' ),
				'verdict'     => 'allowed',
				'layer'       => 'none',
				'confidence'  => 0.0,
				'reason'      => __( 'Passed all checks.', 'lancedesk-form-guard' ),
				'action'      => 'allow',
				'ip_hash'     => LD_FG_Logger::hash_ip( (string) ( $context['ip'] ?? '' ) ),
				'email_hash'  => LD_FG_Logger::hash_email( (string) ( $context['email'] ?? '' ) ),
				'snippet'     => self::snippet( $payload['fields'] ),
				'meta'        => array( 'fields' => $payload['fields'] ),
			)
		);

		return $allow;
	}

	/**
	 * Map confidence to block/quarantine/allow action.
	 *
	 * @param array<string, mixed> $settings   Settings.
	 * @param float                $confidence Confidence score.
	 * @param bool                 $heuristic  Whether from heuristics.
	 *
	 * @return string
	 */
	private static function map_action( array $settings, float $confidence, bool $heuristic ): string {
		$block_threshold      = isset( $settings['confidence_block'] ) ? (float) $settings['confidence_block'] : 0.9;
		$quarantine_threshold = isset( $settings['confidence_quarantine'] ) ? (float) $settings['confidence_quarantine'] : 0.7;

		if ( $heuristic || $confidence >= $block_threshold ) {
			return (string) ( $settings['default_action'] ?? 'silent_discard' );
		}

		if ( $confidence >= $quarantine_threshold ) {
			return 'quarantine';
		}

		return (string) ( $settings['default_action'] ?? 'silent_discard' );
	}

	/**
	 * Build final response and write log.
	 *
	 * @param array<string, mixed> $context  Submission context.
	 * @param array<string, mixed> $verdict  Verdict array.
	 * @param string               $action   Action slug.
	 * @param array<string, mixed> $settings Settings.
	 *
	 * @return array{block: bool, action: string, verdict: array<string, mixed>, log_id: int}
	 */
	private static function finalize( array $context, array $verdict, string $action, array $settings ): array {
		$integration = sanitize_key( (string) ( $context['integration'] ?? '' ) );
		$form_id     = sanitize_text_field( (string) ( $context['form_id'] ?? '' ) );
		$fields      = isset( $context['fields'] ) && is_array( $context['fields'] ) ? $context['fields'] : array();

		$stored_verdict = 'quarantine' === $action ? 'quarantined' : 'blocked';
		$block          = in_array( $action, array( 'silent_discard', 'soft_fail', 'quarantine' ), true );

		$log_id = LD_FG_Logger::insert(
			array(
				'integration' => $integration,
				'form_id'     => $form_id,
				'form_title'  => (string) ( $context['form_title'] ?? '' ),
				'verdict'     => $stored_verdict,
				'layer'       => (string) ( $verdict['layer'] ?? 'heuristic' ),
				'confidence'  => (float) ( $verdict['confidence'] ?? 0.0 ),
				'reason'      => (string) ( $verdict['reason'] ?? '' ),
				'action'      => $action,
				'ip_hash'     => LD_FG_Logger::hash_ip( (string) ( $context['ip'] ?? '' ) ),
				'email_hash'  => LD_FG_Logger::hash_email( (string) ( $context['email'] ?? '' ) ),
				'snippet'     => self::snippet( $fields ),
				'meta'        => array(
					'fields'   => $fields,
					'provider' => $verdict['provider'] ?? '',
				),
				'status'      => 'quarantine' === $action ? 'active' : 'active',
			)
		);

		return array(
			'block'   => $block,
			'action'  => $action,
			'verdict' => $verdict,
			'log_id'  => $log_id,
		);
	}

	/**
	 * Combine field values to text.
	 *
	 * @param array<string, mixed> $fields Fields.
	 *
	 * @return string
	 */
	private static function combine_fields( array $fields ): string {
		$parts = array();

		foreach ( $fields as $value ) {
			if ( is_string( $value ) ) {
				$parts[] = $value;
			} elseif ( is_array( $value ) ) {
				$parts[] = implode( ' ', array_map( 'strval', $value ) );
			}
		}

		return implode( "\n", $parts );
	}

	/**
	 * Truncate snippet for logs.
	 *
	 * @param array<string, mixed> $fields Fields.
	 *
	 * @return string
	 */
	private static function snippet( array $fields ): string {
		$text = self::combine_fields( $fields );

		if ( strlen( $text ) > 200 ) {
			return substr( $text, 0, 197 ) . '...';
		}

		return $text;
	}
}

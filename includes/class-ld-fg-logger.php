<?php
/**
 * Log persistence and analytics queries.
 *
 * @package LanceDesk_Form_Guard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Security log CRUD and reporting.
 */
final class LD_FG_Logger {

	/**
	 * Insert a log entry.
	 *
	 * @param array<string, mixed> $data Log fields.
	 *
	 * @return int Insert ID or 0 on failure.
	 */
	public static function insert( array $data ): int {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'logs' );

		if ( '' === $table ) {
			return 0;
		}

		$now = current_time( 'mysql' );

		$row = array(
			'created_at'  => $now,
			'integration' => sanitize_key( (string) ( $data['integration'] ?? '' ) ),
			'form_id'     => sanitize_text_field( (string) ( $data['form_id'] ?? '' ) ),
			'form_title'  => sanitize_text_field( (string) ( $data['form_title'] ?? '' ) ),
			'verdict'     => sanitize_key( (string) ( $data['verdict'] ?? '' ) ),
			'layer'       => sanitize_key( (string) ( $data['layer'] ?? '' ) ),
			'confidence'  => isset( $data['confidence'] ) ? (float) $data['confidence'] : 0.0,
			'reason'      => sanitize_textarea_field( (string) ( $data['reason'] ?? '' ) ),
			'action'      => sanitize_key( (string) ( $data['action'] ?? '' ) ),
			'ip_hash'     => sanitize_text_field( (string) ( $data['ip_hash'] ?? '' ) ),
			'email_hash'  => sanitize_text_field( (string) ( $data['email_hash'] ?? '' ) ),
			'snippet'     => sanitize_textarea_field( (string) ( $data['snippet'] ?? '' ) ),
			'meta'        => isset( $data['meta'] ) ? wp_json_encode( $data['meta'] ) : '',
			'status'      => sanitize_key( (string) ( $data['status'] ?? 'active' ) ),
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			$table,
			$row,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return false === $result ? 0 : (int) $wpdb->insert_id;
	}

	/**
	 * Whether any security log rows exist (used by setup checklist step 4).
	 *
	 * @return bool
	 */
	public static function has_any(): bool {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'logs' );

		if ( '' === $table ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM %i LIMIT 1', $table )
		);

		return null !== $exists && '' !== $exists;
	}

	/**
	 * Query recent log rows.
	 *
	 * @param int                  $limit  Max rows.
	 * @param array<string, mixed> $filter Optional filters: verdict, layer, search.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function query_recent( int $limit = 20, array $filter = array() ): array {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'logs' );

		if ( '' === $table ) {
			return array();
		}

		$limit   = max( 1, min( 500, $limit ) );
		$where   = array( '1=1' );
		$params  = array( $table );
		$allowed = array( 'blocked', 'allowed', 'quarantined', 'spam', 'clean' );

		if ( ! empty( $filter['verdict'] ) && in_array( $filter['verdict'], $allowed, true ) ) {
			$where[]  = 'verdict = %s';
			$params[] = $filter['verdict'];
		}

		if ( ! empty( $filter['layer'] ) ) {
			$where[]  = 'layer = %s';
			$params[] = sanitize_key( (string) $filter['layer'] );
		}

		if ( ! empty( $filter['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( (string) $filter['search'] ) ) . '%';
			$where[]  = '(reason LIKE %s OR snippet LIKE %s OR form_title LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$params[] = $limit;
		$sql      = 'SELECT * FROM %i WHERE ' . implode( ' AND ', $where ) . ' ORDER BY created_at DESC, id DESC LIMIT %d';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $where fragments are fixed whitelist SQL; values bound via $wpdb->prepare().
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Count logs by verdict for the last N days.
	 *
	 * @param int $days Number of days.
	 *
	 * @return array<string, int>
	 */
	public static function stats_7d( int $days = 7 ): array {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'logs' );

		if ( '' === $table ) {
			return array(
				'blocked'          => 0,
				'allowed'          => 0,
				'quarantined'      => 0,
				'heuristic_saved'  => 0,
			);
		}

		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . absint( $days ) . ' days', current_time( 'timestamp' ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT verdict, layer, COUNT(*) AS total FROM %i WHERE created_at >= %s GROUP BY verdict, layer",
				$table,
				$since
			),
			ARRAY_A
		);

		$stats = array(
			'blocked'         => 0,
			'allowed'         => 0,
			'quarantined'     => 0,
			'heuristic_saved' => 0,
		);

		if ( ! is_array( $rows ) ) {
			return $stats;
		}

		foreach ( $rows as $row ) {
			$verdict = (string) ( $row['verdict'] ?? '' );
			$layer   = (string) ( $row['layer'] ?? '' );
			$total   = (int) ( $row['total'] ?? 0 );

			if ( 'blocked' === $verdict ) {
				$stats['blocked'] += $total;
			} elseif ( 'allowed' === $verdict || 'clean' === $verdict ) {
				$stats['allowed'] += $total;
			} elseif ( 'quarantined' === $verdict ) {
				$stats['quarantined'] += $total;
			}

			if ( 'heuristic' === $layer && in_array( $verdict, array( 'blocked', 'spam' ), true ) ) {
				$stats['heuristic_saved'] += $total;
			}
		}

		return $stats;
	}

	/**
	 * Top block reasons for the last N days.
	 *
	 * @param int $days  Days window.
	 * @param int $limit Max reasons.
	 *
	 * @return array<int, array{reason: string, count: int}>
	 */
	public static function top_reasons( int $days = 7, int $limit = 5 ): array {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'logs' );

		if ( '' === $table ) {
			return array();
		}

		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-' . absint( $days ) . ' days', current_time( 'timestamp' ) ) );
		$limit = max( 1, min( 20, $limit ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT reason, COUNT(*) AS total FROM %i WHERE created_at >= %s AND verdict IN ('blocked','quarantined','spam') AND reason != '' GROUP BY reason ORDER BY total DESC LIMIT %d",
				$table,
				$since,
				$limit
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$out = array();

		foreach ( $rows as $row ) {
			$out[] = array(
				'reason' => (string) ( $row['reason'] ?? '' ),
				'count'  => (int) ( $row['total'] ?? 0 ),
			);
		}

		return $out;
	}

	/**
	 * Daily block counts for chart (heuristic vs AI).
	 *
	 * @param int $days Number of days.
	 *
	 * @return array<int, array{date: string, label: string, heuristic: int, ai: int}>
	 */
	public static function daily_chart_data( int $days = 7 ): array {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'logs' );

		if ( '' === $table ) {
			return array();
		}

		$days  = max( 1, min( 30, $days ) );
		$since = gmdate( 'Y-m-d 00:00:00', strtotime( '-' . ( $days - 1 ) . ' days', current_time( 'timestamp' ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS day, layer, COUNT(*) AS total FROM %i WHERE created_at >= %s AND verdict IN ('blocked','quarantined','spam') GROUP BY day, layer ORDER BY day ASC",
				$table,
				$since
			),
			ARRAY_A
		);

		$buckets = array();

		for ( $i = $days - 1; $i >= 0; $i-- ) {
			$ts    = strtotime( '-' . $i . ' days', current_time( 'timestamp' ) );
			$date  = gmdate( 'Y-m-d', $ts );
			$label = gmdate( 'D', $ts );

			$buckets[ $date ] = array(
				'date'      => $date,
				'label'     => $label,
				'heuristic' => 0,
				'ai'        => 0,
			);
		}

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$day = (string) ( $row['day'] ?? '' );

				if ( ! isset( $buckets[ $day ] ) ) {
					continue;
				}

				$layer = (string) ( $row['layer'] ?? '' );
				$total = (int) ( $row['total'] ?? 0 );

				if ( 'ai' === $layer ) {
					$buckets[ $day ]['ai'] += $total;
				} else {
					$buckets[ $day ]['heuristic'] += $total;
				}
			}
		}

		return array_values( $buckets );
	}

	/**
	 * Update log status (release/discard quarantine).
	 *
	 * @param int    $log_id Log ID.
	 * @param string $status New status.
	 *
	 * @return bool
	 */
	public static function update_status( int $log_id, string $status ): bool {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'logs' );

		if ( '' === $table || $log_id <= 0 ) {
			return false;
		}

		$allowed = array( 'active', 'released', 'discarded' );

		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			$table,
			array( 'status' => $status ),
			array( 'id' => $log_id ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Hash IP for privacy-safe storage.
	 *
	 * @param string $ip Raw IP.
	 *
	 * @return string
	 */
	public static function hash_ip( string $ip ): string {
		if ( '' === $ip ) {
			return '';
		}

		return hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
	}

	/**
	 * Hash email for privacy-safe storage.
	 *
	 * @param string $email Raw email.
	 *
	 * @return string
	 */
	public static function hash_email( string $email ): string {
		$email = strtolower( trim( $email ) );

		if ( '' === $email ) {
			return '';
		}

		return hash_hmac( 'sha256', $email, wp_salt( 'auth' ) );
	}
}

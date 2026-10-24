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
	 * Build WHERE clause fragments and bind params for log filters.
	 *
	 * @param array<string, mixed> $filter Optional filters: verdict, layer, search, date_range, ids.
	 *
	 * @return array{0: array<int, string>, 1: array<int, mixed>}
	 */
	private static function build_filter_clause( array $filter ): array {
		global $wpdb;

		$where   = array( '1=1' );
		$params  = array();
		$allowed = array( 'blocked', 'allowed', 'quarantined', 'spam', 'clean' );

		if ( ! empty( $filter['verdicts'] ) && is_array( $filter['verdicts'] ) ) {
			$verdicts = array_values(
				array_intersect(
					array_map( 'sanitize_key', $filter['verdicts'] ),
					$allowed
				)
			);
			if ( ! empty( $verdicts ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $verdicts ), '%s' ) );
				$where[]      = "verdict IN ({$placeholders})";
				foreach ( $verdicts as $verdict ) {
					$params[] = $verdict;
				}
			}
		} elseif ( ! empty( $filter['verdict'] ) && in_array( $filter['verdict'], $allowed, true ) ) {
			$where[]  = 'verdict = %s';
			$params[] = $filter['verdict'];
		}

		if ( ! empty( $filter['layer'] ) ) {
			$layer = sanitize_key( (string) $filter['layer'] );
			if ( in_array( $layer, array( 'heuristic', 'ai', 'none' ), true ) ) {
				$where[]  = 'layer = %s';
				$params[] = $layer;
			}
		}

		if ( ! empty( $filter['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( (string) $filter['search'] ) ) . '%';
			$where[]  = '(reason LIKE %s OR snippet LIKE %s OR form_title LIKE %s OR form_id LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$date_range = isset( $filter['date_range'] ) ? sanitize_key( (string) $filter['date_range'] ) : '';
		if ( in_array( $date_range, array( 'today', 'yesterday', '7days' ), true ) ) {
			$now_ts = current_time( 'timestamp' );
			$today  = gmdate( 'Y-m-d 00:00:00', $now_ts );

			if ( 'today' === $date_range ) {
				$where[]  = 'created_at >= %s';
				$params[] = $today;
			} elseif ( 'yesterday' === $date_range ) {
				$yesterday = gmdate( 'Y-m-d 00:00:00', strtotime( '-1 day', $now_ts ) );
				$where[]   = 'created_at >= %s AND created_at < %s';
				$params[]  = $yesterday;
				$params[]  = $today;
			} else {
				$since    = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days', $now_ts ) );
				$where[]  = 'created_at >= %s';
				$params[] = $since;
			}
		}

		if ( ! empty( $filter['ids'] ) && is_array( $filter['ids'] ) ) {
			$ids = array_values(
				array_unique(
					array_filter(
						array_map( 'absint', $filter['ids'] ),
						static function ( int $id ): bool {
							return $id > 0;
						}
					)
				)
			);
			if ( ! empty( $ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
				$where[]      = "id IN ({$placeholders})";
				foreach ( $ids as $id ) {
					$params[] = $id;
				}
			}
		}

		return array( $where, $params );
	}

	/**
	 * Count log rows matching optional filters.
	 *
	 * @param array<string, mixed> $filter Optional filters: verdict, layer, search.
	 *
	 * @return int
	 */
	public static function count( array $filter = array() ): int {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'logs' );

		if ( '' === $table ) {
			return 0;
		}

		list( $where, $params ) = self::build_filter_clause( $filter );
		$params                 = array_merge( array( $table ), $params );
		$sql                    = 'SELECT COUNT(*) FROM %i WHERE ' . implode( ' AND ', $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $where fragments are fixed whitelist SQL; values bound via $wpdb->prepare().
		$count = $wpdb->get_var( $wpdb->prepare( $sql, ...$params ) );

		return max( 0, (int) $count );
	}

	/**
	 * Query recent log rows.
	 *
	 * @param int                  $limit  Max rows.
	 * @param array<string, mixed> $filter Optional filters: verdict, layer, search, offset.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function query_recent( int $limit = 20, array $filter = array() ): array {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'logs' );

		if ( '' === $table ) {
			return array();
		}

		$limit  = max( 1, min( 500, $limit ) );
		$offset = isset( $filter['offset'] ) ? max( 0, (int) $filter['offset'] ) : 0;

		list( $where, $params ) = self::build_filter_clause( $filter );
		$params                 = array_merge( array( $table ), $params, array( $limit, $offset ) );
		$sql                    = 'SELECT * FROM %i WHERE ' . implode( ' AND ', $where ) . ' ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $where fragments are fixed whitelist SQL; values bound via $wpdb->prepare().
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$params ), ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Permanently delete log rows by ID (also clears per-user read markers).
	 *
	 * @param array<int, int> $ids Log IDs.
	 *
	 * @return int Number of deleted log rows.
	 */
	public static function delete_ids( array $ids ): int {
		global $wpdb;

		$logs_table  = LD_FG_DB::table_name( 'logs' );
		$reads_table = LD_FG_DB::table_name( 'log_reads' );

		if ( '' === $logs_table ) {
			return 0;
		}

		$ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $ids ),
					static function ( int $id ): bool {
						return $id > 0;
					}
				)
			)
		);

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		if ( '' !== $reads_table ) {
			$read_params = array_merge( array( $reads_table ), $ids );
			$read_sql    = "DELETE FROM %i WHERE log_id IN ({$placeholders})";
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- IN placeholders are %d; table via %i.
			$wpdb->query( $wpdb->prepare( $read_sql, ...$read_params ) );
		}

		$params = array_merge( array( $logs_table ), $ids );
		$sql    = "DELETE FROM %i WHERE id IN ({$placeholders})";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- IN placeholders are %d; table via %i.
		$deleted = $wpdb->query( $wpdb->prepare( $sql, ...$params ) );

		return false === $deleted ? 0 : (int) $deleted;
	}

	/**
	 * Delete every security log (and read markers).
	 *
	 * @return int Number of deleted log rows.
	 */
	public static function delete_all(): int {
		global $wpdb;

		$logs_table  = LD_FG_DB::table_name( 'logs' );
		$reads_table = LD_FG_DB::table_name( 'log_reads' );

		if ( '' === $logs_table ) {
			return 0;
		}

		$total = self::count();

		if ( '' !== $reads_table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $reads_table ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $logs_table ) );

		return $total;
	}

	/**
	 * Purge logs older than the configured retention window.
	 *
	 * @param int $days Retention days (rows older than this are removed).
	 *
	 * @return int Number of deleted log rows.
	 */
	public static function purge_older_than( int $days ): int {
		global $wpdb;

		$logs_table = LD_FG_DB::table_name( 'logs' );
		$days       = max( 1, min( 3650, $days ) );

		if ( '' === $logs_table ) {
			return 0;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $days . ' days', current_time( 'timestamp' ) ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE created_at < %s',
				$logs_table,
				$cutoff
			)
		);

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			return 0;
		}

		return self::delete_ids( array_map( 'absint', $ids ) );
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
	 * Mark a log entry as read for the current admin user.
	 *
	 * @param int $log_id  Log ID.
	 * @param int $user_id WordPress user ID.
	 *
	 * @return bool
	 */
	public static function mark_read( int $log_id, int $user_id ): bool {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'log_reads' );

		if ( '' === $table || $log_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->replace(
			$table,
			array(
				'log_id'  => $log_id,
				'user_id' => $user_id,
				'read_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Mark many log entries as read for a user.
	 *
	 * @param array<int, int> $log_ids Log IDs.
	 * @param int             $user_id WordPress user ID.
	 *
	 * @return int Number of rows marked.
	 */
	public static function mark_read_ids( array $log_ids, int $user_id ): int {
		$marked = 0;
		foreach ( $log_ids as $log_id ) {
			if ( self::mark_read( (int) $log_id, $user_id ) ) {
				++$marked;
			}
		}
		return $marked;
	}

	/**
	 * Mark a log entry as unread for a user (remove read marker).
	 *
	 * @param int $log_id  Log ID.
	 * @param int $user_id WordPress user ID.
	 *
	 * @return bool
	 */
	public static function mark_unread( int $log_id, int $user_id ): bool {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'log_reads' );

		if ( '' === $table || $log_id <= 0 || $user_id <= 0 ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table,
			array(
				'log_id'  => $log_id,
				'user_id' => $user_id,
			),
			array( '%d', '%d' )
		);

		return false !== $result;
	}

	/**
	 * Mark many log entries as unread for a user.
	 *
	 * @param array<int, int> $log_ids Log IDs.
	 * @param int             $user_id WordPress user ID.
	 *
	 * @return int Number of rows unmarked.
	 */
	public static function mark_unread_ids( array $log_ids, int $user_id ): int {
		$marked = 0;
		foreach ( $log_ids as $log_id ) {
			if ( self::mark_unread( (int) $log_id, $user_id ) ) {
				++$marked;
			}
		}
		return $marked;
	}

	/**
	 * Aggregate counts for the Logs KPI strip (today + lifetime ratios).
	 *
	 * @return array{total_today: int, blocked_today: int, quarantine_ratio: float, avg_confidence: float, blocked: int, allowed: int, quarantined: int, total: int}
	 */
	public static function logs_page_stats(): array {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'logs' );
		$empty = array(
			'total_today'      => 0,
			'blocked_today'    => 0,
			'quarantine_ratio' => 0.0,
			'avg_confidence'   => 0.0,
			'blocked'          => 0,
			'allowed'          => 0,
			'quarantined'      => 0,
			'total'            => 0,
		);

		if ( '' === $table ) {
			return $empty;
		}

		$today_start = gmdate( 'Y-m-d 00:00:00', current_time( 'timestamp' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_today = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM %i WHERE created_at >= %s',
				$table,
				$today_start
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$blocked_today = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i WHERE created_at >= %s AND verdict = 'blocked'",
				$table,
				$today_start
			)
		);

		$total       = self::count();
		$blocked     = self::count( array( 'verdict' => 'blocked' ) );
		$allowed     = self::count( array( 'verdict' => 'allowed' ) );
		$quarantined = self::count( array( 'verdict' => 'quarantined' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$avg = $wpdb->get_var( $wpdb->prepare( 'SELECT AVG(confidence) FROM %i', $table ) );

		return array(
			'total_today'      => max( 0, $total_today ),
			'blocked_today'    => max( 0, $blocked_today ),
			'quarantine_ratio' => $total > 0 ? round( ( $quarantined / $total ) * 100, 1 ) : 0.0,
			'avg_confidence'   => round( (float) $avg * 100, 1 ),
			'blocked'          => $blocked,
			'allowed'          => $allowed,
			'quarantined'      => $quarantined,
			'total'            => $total,
		);
	}

	/**
	 * Return log IDs the user has already read.
	 *
	 * @param int             $user_id WordPress user ID.
	 * @param array<int, int> $log_ids Candidate log IDs.
	 *
	 * @return array<int, int>
	 */
	public static function get_read_ids( int $user_id, array $log_ids ): array {
		global $wpdb;

		$table = LD_FG_DB::table_name( 'log_reads' );

		if ( '' === $table || $user_id <= 0 || empty( $log_ids ) ) {
			return array();
		}

		$log_ids = array_values(
			array_filter(
				array_map( 'absint', $log_ids ),
				static function ( int $id ): bool {
					return $id > 0;
				}
			)
		);

		if ( empty( $log_ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $log_ids ), '%d' ) );
		$params       = array_merge( array( $table, $user_id ), $log_ids );
		$sql          = "SELECT log_id FROM %i WHERE user_id = %d AND log_id IN ({$placeholders})";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- IN placeholders are %d; table via %i.
		$rows = $wpdb->get_col( $wpdb->prepare( $sql, ...$params ) );

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map( 'absint', $rows );
	}

	/**
	 * Count log entries the user has not opened yet.
	 *
	 * @param int $user_id WordPress user ID.
	 *
	 * @return int
	 */
	public static function count_unread( int $user_id ): int {
		global $wpdb;

		$logs_table  = LD_FG_DB::table_name( 'logs' );
		$reads_table = LD_FG_DB::table_name( 'log_reads' );

		if ( '' === $logs_table || '' === $reads_table || $user_id <= 0 ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM %i AS l LEFT JOIN %i AS r ON r.log_id = l.id AND r.user_id = %d WHERE r.log_id IS NULL",
				$logs_table,
				$reads_table,
				$user_id
			)
		);

		return max( 0, (int) $count );
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

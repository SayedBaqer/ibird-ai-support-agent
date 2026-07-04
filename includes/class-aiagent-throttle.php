<?php
defined( 'ABSPATH' ) || exit;

class AIAgent_Throttle {

	// ── Check all limits before calling LLM ───────────────────────────────────

	/**
	 * Returns true when it's safe to proceed; false when a limit is hit.
	 * Pass $session_token and $customer_key (user ID or IP).
	 */
	public static function check( string $session_token, string $customer_key ): bool {
		$settings = aiagent_settings();

		// Global circuit breaker.
		if ( self::global_count() >= (int) $settings['daily_cap'] ) {
			return false;
		}

		// Per-session cap.
		if ( self::session_count( $session_token ) >= (int) $settings['session_cap'] ) {
			return false;
		}

		// Per-customer daily cap.
		if ( self::customer_count( $customer_key ) >= (int) $settings['per_customer_cap'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Increment all counters after a successful LLM round-trip.
	 * $cost = number of LLM requests made this turn (classify + reply = 2, etc.)
	 */
	public static function increment( string $session_token, string $customer_key, int $cost = 1 ): void {
		self::inc( 'global',   'global',        $cost );
		self::inc( 'session',  $session_token,  1 );
		self::inc( 'customer', $customer_key,   1 );
	}

	// ── Counters ──────────────────────────────────────────────────────────────

	public static function global_count(): int {
		return self::get_count( 'global', 'global' );
	}

	public static function session_count( string $session_token ): int {
		return self::get_count( 'session', $session_token );
	}

	public static function customer_count( string $customer_key ): int {
		return self::get_count( 'customer', $customer_key );
	}

	// ── Internal ──────────────────────────────────────────────────────────────

	private static function today(): string {
		return gmdate( 'Y-m-d' );
	}

	private static function get_count( string $scope, string $key ): int {
		global $wpdb;
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT count FROM {$wpdb->prefix}aiagent_usage_counters WHERE scope=%s AND `key`=%s AND date=%s",
			$scope, $key, self::today()
		) );
		return (int) $count;
	}

	private static function inc( string $scope, string $key, int $amount ): void {
		global $wpdb;
		$table = "{$wpdb->prefix}aiagent_usage_counters";
		$today = self::today();

		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE scope=%s AND `key`=%s AND date=%s",
			$scope, $key, $today
		) );

		if ( $exists ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$table} SET count = count + %d WHERE scope=%s AND `key`=%s AND date=%s",
				$amount, $scope, $key, $today
			) );
		} else {
			$wpdb->insert(
				$table,
				[ 'scope' => $scope, 'key' => $key, 'date' => $today, 'count' => $amount ],
				[ '%s', '%s', '%s', '%d' ]
			);
		}
	}

	// ── Customer key helper ───────────────────────────────────────────────────

	public static function customer_key(): string {
		$user_id = get_current_user_id();
		if ( $user_id > 0 ) {
			return 'u:' . $user_id;
		}
		// phpcs:ignore WordPress.VIP.ServerVariables
		$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
		return 'ip:' . $ip;
	}
}

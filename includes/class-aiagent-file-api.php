<?php
defined( 'ABSPATH' ) || exit;

/**
 * Gemini File API — upload once, reference by URI.
 *
 * Why this exists:
 *  - PHP regex cannot reliably extract text from FlateDecode-compressed PDF streams.
 *  - base64 inline-data image uploads are blocked by many hosts' loopback restrictions.
 *  - Gemini's File API accepts PDF, images, video, audio up to 2 GB at no storage cost;
 *    files live for 48 h and can be referenced in multiple generateContent calls.
 *  - Native text inside PDFs (not scanned) is extracted FREE — zero token charge.
 *  - Scanned PDFs are understood via Gemini's vision (each page treated as an image).
 *
 * Token-saving design:
 *  - Upload the file ONCE → store the returned URI in the DB with an expiry timestamp.
 *  - Any subsequent call that references the same URI re-uses the already-uploaded bytes
 *    (Gemini keeps it on their side for 48 h).
 *  - Gemini 2.5 Flash has implicit context caching: repeated tokens at the start of a
 *    request are auto-cached after the first call — system prompt + policy rules benefit
 *    from this with no extra code.
 */
class AIAgent_File_API {

	private static string $upload_base = 'https://generativelanguage.googleapis.com/upload/v1beta';
	private static string $api_base    = 'https://generativelanguage.googleapis.com/v1beta';

	// ── Upload ────────────────────────────────────────────────────────────────

	/**
	 * Upload a local file to the Gemini File API.
	 *
	 * Uses a two-step resumable upload so it works for any file size supported
	 * by the API (up to 2 GB; PDFs capped at 50 MB, images are much smaller).
	 *
	 * @param string $file_path   Absolute local path to the file.
	 * @param string $mime_type   e.g. 'application/pdf', 'image/jpeg'.
	 * @param string $display_name  Human-readable label (defaults to basename).
	 * @return array { file_uri: string, file_name: string, expires_at: string, error: bool, message: string }
	 */
	public static function upload( string $file_path, string $mime_type, string $display_name = '' ): array {
		$empty = [ 'file_uri' => '', 'file_name' => '', 'expires_at' => '', 'error' => true, 'message' => '' ];

		$settings = aiagent_settings();
		$api_key  = $settings['api_key'] ?? '';
		if ( $api_key === '' ) {
			$empty['message'] = 'No Gemini API key configured.';
			return $empty;
		}

		if ( ! file_exists( $file_path ) ) {
			$empty['message'] = 'File not found: ' . $file_path;
			return $empty;
		}

		$file_size = filesize( $file_path );
		if ( $display_name === '' ) {
			$display_name = basename( $file_path );
		}

		// ── Step 1: initiate resumable upload ────────────────────────────────
		$init_url  = self::$upload_base . '/files?key=' . rawurlencode( $api_key );
		$init_resp = wp_remote_post( $init_url, [
			'headers' => [
				'X-Goog-Upload-Protocol'       => 'resumable',
				'X-Goog-Upload-Command'        => 'start',
				'X-Goog-Upload-Header-Content-Length' => (string) $file_size,
				'X-Goog-Upload-Header-Content-Type'   => $mime_type,
				'Content-Type'                 => 'application/json',
			],
			'body'    => wp_json_encode( [ 'file' => [ 'display_name' => $display_name ] ] ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $init_resp ) ) {
			$empty['message'] = $init_resp->get_error_message();
			self::log( 'upload-init', $empty['message'] );
			return $empty;
		}

		$upload_url = wp_remote_retrieve_header( $init_resp, 'x-goog-upload-url' );
		if ( $upload_url === '' ) {
			$empty['message'] = 'No upload URL in File API init response.';
			self::log( 'upload-init', $empty['message'] . ' Body: ' . wp_remote_retrieve_body( $init_resp ) );
			return $empty;
		}

		// ── Step 2: send file bytes ───────────────────────────────────────────
		$file_data = file_get_contents( $file_path );
		if ( $file_data === false ) {
			$empty['message'] = 'Could not read file.';
			return $empty;
		}

		$upload_resp = wp_remote_post( $upload_url, [
			'headers' => [
				'Content-Length'        => (string) $file_size,
				'X-Goog-Upload-Offset'  => '0',
				'X-Goog-Upload-Command' => 'upload, finalize',
			],
			'body'    => $file_data,
			'timeout' => 120,
		] );

		unset( $file_data ); // Free memory.

		if ( is_wp_error( $upload_resp ) ) {
			$empty['message'] = $upload_resp->get_error_message();
			self::log( 'upload-bytes', $empty['message'] );
			return $empty;
		}

		$code = wp_remote_retrieve_response_code( $upload_resp );
		$data = json_decode( wp_remote_retrieve_body( $upload_resp ), true );

		if ( $code !== 200 || empty( $data['file']['uri'] ) ) {
			$empty['message'] = $data['error']['message'] ?? wp_remote_retrieve_body( $upload_resp );
			self::log( 'upload-bytes', "HTTP {$code}: " . $empty['message'] );
			return $empty;
		}

		$file_uri  = $data['file']['uri'];
		$file_name = $data['file']['name'] ?? '';
		$state     = $data['file']['state'] ?? 'ACTIVE';
		$expires   = gmdate( 'Y-m-d H:i:s', time() + 47 * HOUR_IN_SECONDS ); // 48 h minus buffer.

		// If still processing (large files), poll briefly.
		if ( $state !== 'ACTIVE' && $file_name !== '' ) {
			$active = self::wait_active( $file_name, $api_key );
			if ( ! $active ) {
				$empty['message'] = 'Gemini File API: file did not become ACTIVE in time.';
				return $empty;
			}
		}

		return [
			'file_uri'   => $file_uri,
			'file_name'  => $file_name,
			'expires_at' => $expires,
			'error'      => false,
			'message'    => '',
		];
	}

	/**
	 * Poll until the file state becomes ACTIVE (max $max_seconds).
	 */
	public static function wait_active( string $file_name, string $api_key, int $max_seconds = 45 ): bool {
		$deadline = time() + $max_seconds;
		while ( time() < $deadline ) {
			sleep( 3 );
			$url      = self::$api_base . '/' . ltrim( $file_name, '/' ) . '?key=' . rawurlencode( $api_key );
			$response = wp_remote_get( $url, [ 'timeout' => 10 ] );
			if ( is_wp_error( $response ) ) continue;
			$file_data = json_decode( wp_remote_retrieve_body( $response ), true );
			$state     = $file_data['state'] ?? '';
			if ( $state === 'ACTIVE' ) return true;
			if ( $state === 'FAILED' ) return false;
		}
		return false;
	}

	// ── Analyze ───────────────────────────────────────────────────────────────

	/**
	 * Ask Gemini about an already-uploaded file, referenced by its URI.
	 *
	 * Uses `fileData` in the parts array — no base64, no loopback fetch.
	 * Gemini reads the file from its own storage.
	 * Native PDF text is NOT counted in input tokens.
	 *
	 * @param string $file_uri   URI returned by upload() (e.g. "https://...v1beta/files/abc").
	 * @param string $mime_type  MIME type of the uploaded file.
	 * @param string $prompt     What to ask.
	 * @param array  $opts       Optional: thinking (bool), thinking_budget (int), model (string).
	 * @return array { description: string, error: bool }
	 */
	public static function analyze( string $file_uri, string $mime_type, string $prompt, array $opts = [] ): array {
		$settings = aiagent_settings();
		$api_key  = $settings['api_key'] ?? '';
		if ( $api_key === '' || $file_uri === '' ) {
			return [ 'description' => '', 'error' => true ];
		}

		$model   = $opts['model'] ?? $settings['model_reply'];
		$url     = self::$api_base . "/models/{$model}:generateContent?key=" . rawurlencode( $api_key );
		$max_tokens = (int) ( $opts['max_output_tokens'] ?? 8192 );
		$payload = [
			'contents' => [ [
				'role'  => 'user',
				'parts' => [
					[ 'fileData' => [ 'mimeType' => $mime_type, 'fileUri' => $file_uri ] ],
					[ 'text'     => $prompt ],
				],
			] ],
			'generationConfig' => [ 'temperature' => 0.1, 'maxOutputTokens' => $max_tokens ],
		];

		if ( ! empty( $opts['thinking'] ) ) {
			$budget = (int) ( $opts['thinking_budget'] ?? 2048 );
			$payload['generationConfig']['thinkingConfig'] = [ 'thinkingBudget' => $budget ];
		}

		$timeout  = ! empty( $opts['thinking'] ) ? 180 : 90;
		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( $payload ),
			'timeout' => $timeout,
		] );

		if ( is_wp_error( $response ) ) {
			self::log( 'analyze', $response->get_error_message() );
			return [ 'description' => '', 'error' => true ];
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 || empty( $data['candidates'][0] ) ) {
			self::log( 'analyze', "HTTP {$code}: " . wp_remote_retrieve_body( $response ) );
			return [ 'description' => '', 'error' => true ];
		}

		$text = '';
		foreach ( $data['candidates'][0]['content']['parts'] ?? [] as $part ) {
			if ( isset( $part['text'] ) && empty( $part['thought'] ) ) {
				$text .= $part['text'];
			}
		}

		return [ 'description' => trim( $text ), 'error' => false ];
	}

	// ── Context Caching ───────────────────────────────────────────────────────

	/**
	 * Create a Gemini context cache from text content.
	 *
	 * Cached content lives on Gemini's servers for the specified TTL.
	 * Callers reference it by cache_name in generateContent — Gemini bills
	 * cached tokens at ~25% of the normal input rate (≈75% savings).
	 *
	 * Minimum: 2 048 tokens (≈ 1 500 words). We skip creation silently
	 * if the text is too short — callers fall back to plain RAG.
	 *
	 * @param string $text         The manual / knowledge text to cache.
	 * @param string $model        Which Gemini model will read this cache.
	 * @param array  $system       Optional system instruction strings.
	 * @param int    $ttl_seconds  How long to keep the cache (default 1 h, max 1 h on free tier).
	 * @return array { cache_name: string, expires_at: string, error: bool }
	 */
	public static function create_cache( string $text, string $model = '', array $system = [], int $ttl_seconds = 3600 ): array {
		$empty    = [ 'cache_name' => '', 'expires_at' => '', 'error' => true ];
		$settings = aiagent_settings();
		$api_key  = $settings['api_key'] ?? '';
		if ( $api_key === '' || $text === '' ) return $empty;

		if ( $model === '' ) $model = $settings['model_reply'];

		$payload = [
			'model'    => "models/{$model}",
			'contents' => [ [
				'role'  => 'user',
				'parts' => [ [ 'text' => $text ] ],
			] ],
			'ttl' => $ttl_seconds . 's',
		];

		if ( ! empty( $system ) ) {
			$payload['systemInstruction'] = [
				'parts' => array_map( fn( $s ) => [ 'text' => $s ], $system ),
			];
		}

		$url      = self::$api_base . '/cachedContents?key=' . rawurlencode( $api_key );
		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( $payload ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) {
			self::log( 'cache-create', $response->get_error_message() );
			return $empty;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 || empty( $data['name'] ) ) {
			// Too short or other error — not fatal, caller falls back to RAG.
			self::log( 'cache-create', "HTTP {$code}: " . wp_remote_retrieve_body( $response ) );
			return $empty;
		}

		$expires_at = gmdate( 'Y-m-d H:i:s', time() + $ttl_seconds - 60 ); // Buffer of 60 s.
		return [ 'cache_name' => $data['name'], 'expires_at' => $expires_at, 'error' => false ];
	}

	/**
	 * Extend the TTL of an existing cache (prevents it from expiring mid-session).
	 */
	public static function refresh_cache( string $cache_name, int $ttl_seconds = 3600 ): bool {
		$settings = aiagent_settings();
		$api_key  = $settings['api_key'] ?? '';
		if ( $api_key === '' || $cache_name === '' ) return false;

		$url      = self::$api_base . '/' . ltrim( $cache_name, '/' ) . '?key=' . rawurlencode( $api_key );
		$response = wp_remote_request( $url, [
			'method'  => 'PATCH',
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [ 'ttl' => $ttl_seconds . 's' ] ),
			'timeout' => 15,
		] );
		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}

	/**
	 * Delete a context cache before it expires naturally.
	 */
	public static function delete_cache( string $cache_name ): bool {
		$settings = aiagent_settings();
		$api_key  = $settings['api_key'] ?? '';
		if ( $api_key === '' || $cache_name === '' ) return false;

		$url      = self::$api_base . '/' . ltrim( $cache_name, '/' ) . '?key=' . rawurlencode( $api_key );
		$response = wp_remote_request( $url, [ 'method' => 'DELETE', 'timeout' => 15 ] );
		return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
	}

	/**
	 * Retrieve the stored cache entry for a product model.
	 * Returns null if no cache exists or it has expired.
	 */
	public static function get_model_cache( string $model_name ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT `value` FROM {$wpdb->prefix}aiagent_reference_data WHERE `type` = 'manual_cache' AND `key` = %s LIMIT 1",
			$model_name
		) );
		if ( ! $row ) return null;
		$entry = json_decode( $row->value, true );
		if ( empty( $entry['cache_name'] ) || empty( $entry['cache_expires_at'] ) ) return null;
		// Consider expired 2 min early to avoid serving an expiring cache.
		if ( strtotime( $entry['cache_expires_at'] ) < ( time() + 120 ) ) return null;
		return $entry;
	}

	/**
	 * Save (or update) the manual cache entry for a product model.
	 * Stores cache_name, file_uri, and their expiry timestamps.
	 */
	public static function save_model_cache( string $model_name, array $entry ): void {
		global $wpdb;
		$wpdb->replace(
			$wpdb->prefix . 'aiagent_reference_data',
			[
				'type'       => 'manual_cache',
				'key'        => $model_name,
				'value'      => wp_json_encode( $entry ),
				'created_at' => current_time( 'mysql' ),
			],
			[ '%s', '%s', '%s', '%s' ]
		);
	}

	// ── File delete ───────────────────────────────────────────────────────────

	/**
	 * Delete a file from Gemini File API storage.
	 */
	public static function delete( string $file_name ): bool {
		$settings = aiagent_settings();
		$api_key  = $settings['api_key'] ?? '';
		if ( $api_key === '' || $file_name === '' ) return false;

		$url      = self::$api_base . '/' . ltrim( $file_name, '/' ) . '?key=' . rawurlencode( $api_key );
		$response = wp_remote_request( $url, [ 'method' => 'DELETE', 'timeout' => 15 ] );
		$code     = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
		return $code === 200;
	}

	private static function log( string $ctx, string $msg ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions
			error_log( "[AIAgent FileAPI:{$ctx}] {$msg}" );
		}
	}
}

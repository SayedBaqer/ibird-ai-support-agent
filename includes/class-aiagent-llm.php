<?php
defined( 'ABSPATH' ) || exit;

/**
 * LLM client with provider router.
 *
 * Provider priority:
 *  1. Gemini (gemini-2.5-flash for reply, gemini-2.5-flash-lite for classify, gemini-embedding-2 for embed)
 *     ↳ Supports: Google Search grounding, thinking mode (both Gemini-only).
 *  2. Groq  (llama-3.3-70b-versatile) — OpenAI-compatible, generous free tier
 *  3. Cerebras (llama-3.3-70b) — OpenAI-compatible, fastest inference
 *  4. Graceful fallback message (never raw errors to the customer)
 *
 * Embedding stays Gemini-only — very generous free tier, no fallback needed.
 * PII hard rule: never in any prompt sent to any provider.
 */
class AIAgent_LLM {

	private static string $gemini_base    = 'https://generativelanguage.googleapis.com/v1beta';
	private static string $groq_base      = 'https://api.groq.com/openai/v1';
	private static string $cerebras_base  = 'https://api.cerebras.ai/v1';

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Generate a grounded reply. Tries Gemini → Groq → Cerebras.
	 *
	 * @param array  $messages  Gemini-format content array.
	 * @param array  $system    System instruction strings.
	 * @param array  $tools     Gemini-format tool declarations.
	 * @param string $lang      'en' | 'ar' — for fallback message.
	 * @param array  $options   Optional Gemini-specific flags (Groq/Cerebras ignore them):
	 *   'search'          bool — enable Google Search grounding (Gemini searches the web).
	 *   'thinking'        bool — enable thinking mode (Gemini reasons before responding).
	 *   'thinking_budget' int  — thinking token budget 0–8192 (default from settings).
	 * @return array {reply, tool_calls, error, provider, grounding}
	 */
	public static function reply( array $messages, array $system = [], array $tools = [], string $lang = 'en', array $options = [] ): array {
		$settings = aiagent_settings();

		// Resolve options: caller wins; fall back to saved settings.
		$search   = $options['search']          ?? self::setting_bool( 'enable_search_grounding' );
		$thinking = $options['thinking']        ?? self::setting_bool( 'enable_thinking' );
		$t_budget = (int) ( $options['thinking_budget'] ?? ( $settings['thinking_budget'] ?? 1024 ) );
		$g_opts   = [ 'search' => $search, 'thinking' => $thinking, 'thinking_budget' => max( 0, $t_budget ) ];

		// ── Try Gemini ────────────────────────────────────────────────────────
		if ( $settings['api_key'] !== '' ) {
			$result = self::gemini_reply( $messages, $system, $tools, $lang, null, $g_opts );
			if ( ! $result['error'] ) {
				return $result + [ 'provider' => 'gemini' ];
			}
			self::log( 'router', 'Gemini reply failed — trying Groq.' );
		}

		// ── Try Groq (no search/thinking support) ─────────────────────────────
		$groq_key = $settings['groq_api_key'] ?? '';
		if ( $groq_key !== '' ) {
			$result = self::openai_compat_reply(
				self::$groq_base, $groq_key,
				$settings['groq_model'] ?? 'llama-3.3-70b-versatile',
				$messages, $system, $tools, $lang
			);
			if ( ! $result['error'] ) {
				return $result + [ 'provider' => 'groq' ];
			}
			self::log( 'router', 'Groq reply failed — trying Cerebras.' );
		}

		// ── Try Cerebras ──────────────────────────────────────────────────────
		$cerebras_key = $settings['cerebras_api_key'] ?? '';
		if ( $cerebras_key !== '' ) {
			$result = self::openai_compat_reply(
				self::$cerebras_base, $cerebras_key,
				$settings['cerebras_model'] ?? 'llama-3.3-70b',
				$messages, $system, $tools, $lang
			);
			if ( ! $result['error'] ) {
				return $result + [ 'provider' => 'cerebras' ];
			}
			self::log( 'router', 'Cerebras reply failed — graceful fallback.' );
		}

		return self::fallback( $lang ) + [ 'provider' => 'fallback' ];
	}

	/**
	 * Cheap single-turn classification. Tries Gemini → Groq → Cerebras.
	 * Never uses search or thinking (too slow / costly for classification).
	 */
	public static function classify( string $prompt, string $lang = 'en' ): string {
		$settings = aiagent_settings();
		$messages = [ [ 'role' => 'user', 'parts' => [ [ 'text' => $prompt ] ] ] ];

		if ( $settings['api_key'] !== '' ) {
			// Classification only needs a short label — cap at 256 tokens to cut cost.
			$result = self::gemini_reply( $messages, [], [], $lang, $settings['model_classify'], [ 'max_output_tokens' => 256 ] );
			if ( ! $result['error'] ) return trim( $result['reply'] );
		}

		$groq_key = $settings['groq_api_key'] ?? '';
		if ( $groq_key !== '' ) {
			$result = self::openai_compat_reply( self::$groq_base, $groq_key, $settings['groq_model'] ?? 'llama-3.3-70b-versatile', $messages, [], [], $lang );
			if ( ! $result['error'] ) return trim( $result['reply'] );
		}

		$cerebras_key = $settings['cerebras_api_key'] ?? '';
		if ( $cerebras_key !== '' ) {
			$result = self::openai_compat_reply( self::$cerebras_base, $cerebras_key, $settings['cerebras_model'] ?? 'llama-3.3-70b', $messages, [], [], $lang );
			if ( ! $result['error'] ) return trim( $result['reply'] );
		}

		return '';
	}

	/**
	 * Analyze an image with Gemini Vision.
	 *
	 * Reads the image bytes directly from disk when an attachment_id is given
	 * (avoids a self-HTTP "loopback" request, which many hosts block or which
	 * fails on local/self-signed setups). Falls back to fetching $image_url
	 * over HTTP when no local attachment is available.
	 *
	 * @param string $image_url     Publicly accessible URL of the image (fallback).
	 * @param string $prompt        What to ask Gemini about the image.
	 * @param array  $opts          Optional: same search/thinking opts as reply().
	 * @param int    $attachment_id Optional WP media attachment ID — read from disk if given.
	 * @return array ['description'=>string, 'error'=>bool]
	 */
	public static function vision_analyze( string $image_url, string $prompt, array $opts = [], int $attachment_id = 0 ): array {
		$settings = aiagent_settings();
		$api_key  = $settings['api_key'] ?? '';
		if ( $api_key === '' ) return [ 'description' => '', 'error' => true ];

		$model   = $opts['model'] ?? $settings['model_reply'];
		$api_url = self::$gemini_base . "/models/{$model}:generateContent?key=" . rawurlencode( $api_key );

		// ── Path 1: caller already uploaded via File API → use fileData (best) ──
		// No base64, no loopback — Gemini reads the file from its own storage.
		if ( ! empty( $opts['gemini_file_uri'] ) && ! empty( $opts['gemini_file_mime'] ) ) {
			$payload = [
				'contents' => [ [
					'role'  => 'user',
					'parts' => [
						[ 'fileData' => [ 'mimeType' => $opts['gemini_file_mime'], 'fileUri' => $opts['gemini_file_uri'] ] ],
						[ 'text'     => $prompt ],
					],
				] ],
				'generationConfig' => [ 'temperature' => 0.2, 'maxOutputTokens' => 2048 ],
			];
			if ( ! empty( $opts['thinking'] ) ) {
				$payload['generationConfig']['thinkingConfig'] = [ 'thinkingBudget' => (int) ( $opts['thinking_budget'] ?? 1024 ) ];
			}
			return self::run_vision_payload( $api_url, $payload, $opts );
		}

		// ── Path 2: read from disk (disk-read: no loopback, faster than HTTP) ──
		$img_body = '';
		$mime     = '';
		if ( $attachment_id > 0 ) {
			$local_path = get_attached_file( $attachment_id );
			if ( $local_path && file_exists( $local_path ) ) {
				$img_body = file_get_contents( $local_path );
				$mime     = get_post_mime_type( $attachment_id ) ?: '';
			}
		}

		// ── Path 3: fallback HTTP fetch (last resort) ─────────────────────────
		if ( $img_body === '' ) {
			$img_resp = wp_remote_get( $image_url, [ 'timeout' => 20, 'sslverify' => false ] );
			if ( is_wp_error( $img_resp ) ) {
				self::log( 'vision', 'Image fetch error: ' . $img_resp->get_error_message() );
				// ── Path 4: auto-upload to File API as last-resort workaround ──
				return self::vision_via_file_api( $image_url, $prompt, $opts, $api_key );
			}
			$code = wp_remote_retrieve_response_code( $img_resp );
			if ( $code !== 200 ) {
				self::log( 'vision', "Image fetch HTTP {$code} for {$image_url}" );
				return self::vision_via_file_api( $image_url, $prompt, $opts, $api_key );
			}
			$img_body = wp_remote_retrieve_body( $img_resp );
			$mime     = wp_remote_retrieve_header( $img_resp, 'content-type' ) ?: '';
		}

		if ( $img_body === '' ) return [ 'description' => '', 'error' => true ];

		$mime = $mime ?: 'image/jpeg';
		$mime = strtolower( trim( explode( ';', $mime )[0] ) );
		if ( ! in_array( $mime, [ 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' ], true ) ) {
			$mime = 'image/jpeg';
		}

		$payload = [
			'contents' => [ [
				'role'  => 'user',
				'parts' => [
					[ 'inlineData' => [ 'mimeType' => $mime, 'data' => base64_encode( $img_body ) ] ],
					[ 'text'       => $prompt ],
				],
			] ],
			'generationConfig' => [ 'temperature' => 0.2, 'maxOutputTokens' => 2048 ],
		];
		if ( ! empty( $opts['thinking'] ) ) {
			$payload['generationConfig']['thinkingConfig'] = [ 'thinkingBudget' => (int) ( $opts['thinking_budget'] ?? 1024 ) ];
		}
		return self::run_vision_payload( $api_url, $payload, $opts );
	}

	private static function run_vision_payload( string $url, array $payload, array $opts ): array {
		$timeout  = ! empty( $opts['thinking'] ) ? 90 : 60;
		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( $payload ),
			'timeout' => $timeout,
		] );
		if ( is_wp_error( $response ) ) {
			self::log( 'vision', $response->get_error_message() );
			return [ 'description' => '', 'error' => true ];
		}
		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code !== 200 || empty( $data['candidates'][0] ) ) {
			self::log( 'vision', "HTTP {$code}: " . wp_remote_retrieve_body( $response ) );
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

	/**
	 * Last-resort: when both disk-read and HTTP fetch fail, try uploading the
	 * image to Gemini File API and analyzing via file URI.
	 * This handles cases where WordPress can't reach its own media URL at all.
	 */
	private static function vision_via_file_api( string $image_url, string $prompt, array $opts, string $api_key ): array {
		// We can't read from disk at this point; attempt a direct download ignoring WP HTTP.
		$ch = curl_init( $image_url );
		if ( ! $ch ) return [ 'description' => '', 'error' => true ];
		curl_setopt_array( $ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 20,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
		] );
		$body = curl_exec( $ch );
		$mime = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE ) ?: 'image/jpeg';
		curl_close( $ch );
		if ( ! $body ) return [ 'description' => '', 'error' => true ];

		// Write to a temp file, upload to File API, analyze, delete.
		$tmp = wp_tempnam( 'aiagent_img' );
		file_put_contents( $tmp, $body );
		$uploaded = AIAgent_File_API::upload( $tmp, strtolower( trim( explode( ';', $mime )[0] ) ), basename( $image_url ) );
		@unlink( $tmp );
		if ( $uploaded['error'] || $uploaded['file_uri'] === '' ) return [ 'description' => '', 'error' => true ];

		$result = AIAgent_File_API::analyze( $uploaded['file_uri'], $uploaded['file_uri'] ? ( $opts['mime'] ?? 'image/jpeg' ) : 'image/jpeg', $prompt, $opts );
		AIAgent_File_API::delete( $uploaded['file_name'] );
		return $result;
	}

	/**
	 * Embed text — Gemini only (generous free tier, no fallback needed).
	 * Returns float[] or [] on failure.
	 */
	public static function embed( string $text ): array {
		$settings = aiagent_settings();
		$api_key  = $settings['api_key'] ?? '';
		if ( $api_key === '' ) return [];

		$model = $settings['model_embed'];
		$url   = self::$gemini_base . "/models/{$model}:embedContent?key=" . rawurlencode( $api_key );

		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( [
				'model'   => "models/{$model}",
				'content' => [ 'parts' => [ [ 'text' => $text ] ] ],
			] ),
			'timeout' => 20,
		] );

		if ( is_wp_error( $response ) ) { self::log( 'embed', $response->get_error_message() ); return []; }

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code !== 200 || empty( $data['embedding']['values'] ) ) {
			self::log( 'embed', "HTTP {$code}: " . wp_remote_retrieve_body( $response ) );
			return [];
		}

		return $data['embedding']['values'];
	}

	// ── Gemini backend ────────────────────────────────────────────────────────

	private static function gemini_reply(
		array $messages,
		array $system,
		array $tools,
		string $lang,
		?string $model_override = null,
		array $opts = []       // search, thinking, thinking_budget
	): array {
		$settings = aiagent_settings();
		$api_key  = $settings['api_key'] ?? '';
		$model    = $model_override ?? $settings['model_reply'];

		$use_search   = (bool) ( $opts['search']   ?? false );
		$use_thinking = (bool) ( $opts['thinking']  ?? false );
		$t_budget     = (int)  ( $opts['thinking_budget'] ?? 1024 );

		$url     = self::$gemini_base . "/models/{$model}:generateContent?key=" . rawurlencode( $api_key );
		$payload = [ 'contents' => $messages ];

		// Context cache: if caller supplies a cache_name, reference it here.
		// Gemini bills cached tokens at ~25% of normal rate — ~75% savings.
		// When a cache is used, the cached content (manual text) must NOT also
		// be injected into $messages or $system — it's already on Gemini's side.
		if ( ! empty( $opts['cache_name'] ) ) {
			$payload['cachedContent'] = $opts['cache_name'];
		}

		if ( ! empty( $system ) ) {
			$payload['systemInstruction'] = [
				'parts' => array_map( fn( $s ) => [ 'text' => $s ], $system ),
			];
		}

		// ── Tools: function declarations + optional Google Search grounding ──
		$tool_list = [];
		if ( ! empty( $tools ) ) {
			$tool_list[] = [ 'functionDeclarations' => $tools ];
		}
		if ( $use_search ) {
			// Google Search grounding — Gemini searches the web when it needs to.
			// The model decides when to use it; we don't force it.
			$tool_list[] = [ 'googleSearch' => (object) [] ];
		}
		if ( ! empty( $tool_list ) ) {
			$payload['tools'] = $tool_list;
			if ( ! empty( $tools ) ) {
				// functionCallingConfig only applies to function declarations.
				$payload['toolConfig'] = [ 'functionCallingConfig' => [ 'mode' => 'AUTO' ] ];
			}
		}

		// ── Generation config — token budget by request type ─────────────────
		// Support answers rarely exceed 400 tokens; capping at 512 reduces output
		// cost vs the old 2048 default. Thinking mode needs headroom for reasoning.
		$default_max  = $use_thinking ? 8192 : 512;
		$max_tokens   = (int) ( $opts['max_output_tokens'] ?? $default_max );
		$gen_config   = [ 'temperature' => 0.4, 'maxOutputTokens' => $max_tokens ];
		if ( $use_thinking && $t_budget > 0 ) {
			$gen_config['thinkingConfig'] = [ 'thinkingBudget' => $t_budget ];
		}
		$payload['generationConfig'] = $gen_config;

		// Thinking + search need more time — extend timeout.
		$timeout = 30;
		if ( $use_thinking ) $timeout = 90;
		elseif ( $use_search ) $timeout = 45;

		$response = wp_remote_post( $url, [
			'headers' => [ 'Content-Type' => 'application/json' ],
			'body'    => wp_json_encode( $payload ),
			'timeout' => $timeout,
		] );

		if ( is_wp_error( $response ) ) {
			self::log( 'gemini', $response->get_error_message() );
			return self::error_result();
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code === 429 ) { self::log( 'gemini', 'Rate limited.' ); return self::error_result(); }

		if ( $code !== 200 || empty( $data['candidates'][0] ) ) {
			self::log( 'gemini', "HTTP {$code}: {$body}" );
			return self::error_result();
		}

		$parts      = $data['candidates'][0]['content']['parts'] ?? [];
		$text_parts = [];
		$tool_calls = null;

		foreach ( $parts as $part ) {
			if ( isset( $part['functionCall'] ) ) {
				$tool_calls[] = $part['functionCall'];
			} elseif ( isset( $part['text'] ) && empty( $part['thought'] ) ) {
				// Skip thought parts (internal reasoning) — only return the final reply text.
				$text_parts[] = $part['text'];
			}
		}

		// Extract grounding metadata (web sources Gemini cited, if search was used).
		$grounding = null;
		if ( $use_search ) {
			$meta     = $data['candidates'][0]['groundingMetadata'] ?? [];
			$chunks   = $meta['groundingChunks'] ?? [];
			$supports = $meta['groundingSupports'] ?? [];
			if ( ! empty( $chunks ) ) {
				$grounding = [
					'sources' => array_map( function ( $c ) {
						return [
							'title' => $c['web']['title'] ?? '',
							'uri'   => $c['web']['uri']   ?? '',
						];
					}, $chunks ),
					'supports' => $supports,
				];
			}
		}

		return [
			'reply'      => implode( '', $text_parts ),
			'tool_calls' => $tool_calls,
			'error'      => false,
			'grounding'  => $grounding,
		];
	}

	// ── OpenAI-compatible backend (Groq / Cerebras) ───────────────────────────

	private static function openai_compat_reply(
		string $base_url,
		string $api_key,
		string $model,
		array $messages,
		array $system,
		array $tools,
		string $lang
	): array {
		$oai_messages = [];

		if ( ! empty( $system ) ) {
			$oai_messages[] = [ 'role' => 'system', 'content' => implode( "\n", $system ) ];
		}

		foreach ( $messages as $msg ) {
			$role    = $msg['role'] === 'model' ? 'assistant' : 'user';
			$parts   = $msg['parts'] ?? [];
			$content = '';
			foreach ( $parts as $part ) {
				if ( isset( $part['text'] ) ) {
					$content .= $part['text'];
				} elseif ( isset( $part['functionResponse'] ) ) {
					$content .= '[Tool result: ' . wp_json_encode( $part['functionResponse']['response'] ?? '' ) . ']';
				}
			}
			if ( $content !== '' ) $oai_messages[] = [ 'role' => $role, 'content' => $content ];
		}

		$payload = [
			'model'       => $model,
			'messages'    => $oai_messages,
			'temperature' => 0.4,
			'max_tokens'  => 1024,
		];

		if ( ! empty( $tools ) ) {
			$payload['tools'] = array_map( fn( $t ) => [
				'type'     => 'function',
				'function' => [
					'name'        => $t['name'],
					'description' => $t['description'] ?? '',
					'parameters'  => $t['parameters'] ?? [ 'type' => 'object', 'properties' => [] ],
				],
			], $tools );
			$payload['tool_choice'] = 'auto';
		}

		$response = wp_remote_post( $base_url . '/chat/completions', [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			],
			'body'    => wp_json_encode( $payload ),
			'timeout' => 30,
		] );

		if ( is_wp_error( $response ) ) { self::log( 'oai-compat', $response->get_error_message() ); return self::error_result(); }

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code === 429 ) { self::log( 'oai-compat', 'Rate limited.' ); return self::error_result(); }

		if ( $code !== 200 || empty( $data['choices'][0] ) ) {
			self::log( 'oai-compat', "HTTP {$code}: {$body}" );
			return self::error_result();
		}

		$message    = $data['choices'][0]['message'] ?? [];
		$reply      = $message['content'] ?? '';
		$tool_calls = null;

		if ( ! empty( $message['tool_calls'] ) ) {
			$tool_calls = array_map( function ( $tc ) {
				$args = $tc['function']['arguments'] ?? '{}';
				return [
					'name' => $tc['function']['name'],
					'args' => json_decode( $args, true ) ?? [],
				];
			}, $message['tool_calls'] );
		}

		return [
			'reply'      => $reply,
			'tool_calls' => $tool_calls,
			'error'      => false,
			'grounding'  => null,
		];
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	private static function fallback( string $lang ): array {
		$settings = aiagent_settings();
		$key      = 'fallback_message_' . $lang;
		$msg      = $settings[ $key ] ?? (
			$lang === 'ar'
				? 'شكراً للتواصل معنا! سيتواصل فريقنا معك قريباً.'
				: 'Thank you for reaching out! Our team will follow up with you shortly.'
		);
		return [ 'reply' => $msg, 'tool_calls' => null, 'error' => true, 'grounding' => null ];
	}

	private static function error_result(): array {
		return [ 'reply' => '', 'tool_calls' => null, 'error' => true, 'grounding' => null ];
	}

	private static function setting_bool( string $key ): bool {
		$v = aiagent_settings()[ $key ] ?? false;
		return $v === true || $v === '1' || $v === 1;
	}

	private static function log( string $ctx, string $msg ): void {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions
			error_log( "[AIAgent LLM:{$ctx}] {$msg}" );
		}
	}
}

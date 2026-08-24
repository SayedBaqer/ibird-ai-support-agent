<?php
defined( 'ABSPATH' ) || exit;

/**
 * RAG layer — chunk/embed storage and similarity retrieval.
 * Abstracted so a vector backend can replace the PHP cosine implementation later.
 */
class AIAgent_RAG {

	/**
	 * Reserved "model" value for manual content that applies across every product
	 * (battery care, resets, connectivity, packaging, etc.) — ingested and searched
	 * through the exact same manual_chunks pipeline as a specific model.
	 */
	const COMMON_MODEL = '__common__';

	// ── Similarity ────────────────────────────────────────────────────────────

	public static function cosine_similarity( array $a, array $b ): float {
		if ( empty( $a ) || empty( $b ) || count( $a ) !== count( $b ) ) {
			return 0.0;
		}
		$dot = 0.0;
		$ma  = 0.0;
		$mb  = 0.0;
		$n   = count( $a );
		for ( $i = 0; $i < $n; $i++ ) {
			$dot += $a[ $i ] * $b[ $i ];
			$ma  += $a[ $i ] * $a[ $i ];
			$mb  += $b[ $i ] * $b[ $i ];
		}
		if ( $ma === 0.0 || $mb === 0.0 ) {
			return 0.0;
		}
		return $dot / ( sqrt( $ma ) * sqrt( $mb ) );
	}

	// ── Taught examples ───────────────────────────────────────────────────────

	/**
	 * Embed a question and store/update a taught example row.
	 * Returns the row id or false on failure.
	 */
	public static function embed_taught_example( int $example_id ): bool {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT id, question, solution FROM {$wpdb->prefix}aiagent_taught_examples WHERE id = %d",
			$example_id
		) );

		if ( ! $row ) {
			return false;
		}

		// Embed question + solution together for better retrieval.
		$text      = $row->question . "\n" . $row->solution;
		$embedding = AIAgent_LLM::embed( $text );

		if ( empty( $embedding ) ) {
			return false;
		}

		$wpdb->update(
			"{$wpdb->prefix}aiagent_taught_examples",
			[ 'embedding' => wp_json_encode( $embedding ) ],
			[ 'id'        => $example_id ],
			[ '%s' ], [ '%d' ]
		);

		return true;
	}

	/**
	 * Find the closest taught examples to a question.
	 * Uses hybrid retrieval: MySQL FULLTEXT/LIKE pre-filter → cosine re-rank.
	 * Scores are boosted by confidence (admin can demote noisy examples).
	 * Returns array of ['id', 'question', 'solution', 'score'] sorted desc.
	 *
	 * @param string $lang  Optional — filters to rows tagged 'en', 'ar', or 'both'.
	 */
	public static function search_taught_examples( string $question, int $limit = 3, string $lang = '' ): array {
		global $wpdb;

		$q_embedding = AIAgent_LLM::embed( $question );
		if ( empty( $q_embedding ) ) {
			return [];
		}

		$lang_sql = ( $lang !== '' && in_array( $lang, [ 'en', 'ar' ], true ) )
			? $wpdb->prepare( "AND (language = %s OR language = 'both')", $lang )
			: '';

		// ── Step 1: keyword pre-filter (fast, indexed) ────────────────────────
		// Extracts meaningful words from the question for a FULLTEXT or LIKE filter.
		// This reduces the cosine comparison set from potentially thousands to ~50.
		$words = array_filter(
			preg_split( '/\s+/', preg_replace( '/[^\p{L}\p{N}\s]/u', ' ', $question ) ),
			fn( $w ) => mb_strlen( $w ) > 2
		);

		$rows = [];

		// Try FULLTEXT (requires ft_qa index — added in DB install).
		if ( ! empty( $words ) ) {
			$has_ft = $wpdb->get_var( "SHOW INDEX FROM {$wpdb->prefix}aiagent_taught_examples WHERE Key_name = 'ft_qa'" );
			if ( $has_ft ) {
				$ft_str = implode( ' ', array_map( fn( $w ) => '+' . $wpdb->esc_like( $w ), array_slice( $words, 0, 6 ) ) );
				$rows   = $wpdb->get_results( $wpdb->prepare(
					"SELECT id, question, solution, embedding, confidence, usage_count
					FROM {$wpdb->prefix}aiagent_taught_examples
					WHERE MATCH(question, solution) AGAINST(%s IN BOOLEAN MODE)
					AND embedding IS NOT NULL {$lang_sql}
					LIMIT 60",
					$ft_str
				) );
			}

			// Fallback: LIKE on first keyword (still cheaper than full-table cosine).
			if ( empty( $rows ) ) {
				$like = '%' . $wpdb->esc_like( $words[0] ) . '%';
				$rows = $wpdb->get_results( $wpdb->prepare(
					"SELECT id, question, solution, embedding, confidence, usage_count
					FROM {$wpdb->prefix}aiagent_taught_examples
					WHERE (question LIKE %s OR solution LIKE %s)
					AND embedding IS NOT NULL {$lang_sql}
					LIMIT 60",
					$like, $like
				) );
			}
		}

		// Final fallback: full scan (small KBs only — fine for < 500 examples).
		if ( empty( $rows ) ) {
			$rows = $wpdb->get_results(
				"SELECT id, question, solution, embedding, confidence, usage_count
				FROM {$wpdb->prefix}aiagent_taught_examples
				WHERE embedding IS NOT NULL {$lang_sql}"
			);
		}

		// ── Step 2: cosine re-rank ────────────────────────────────────────────
		$scored = [];
		foreach ( $rows as $row ) {
			$emb = json_decode( $row->embedding, true );
			if ( ! is_array( $emb ) ) {
				continue;
			}
			$sim = self::cosine_similarity( $q_embedding, $emb );
			if ( $sim >= 0.60 ) {
				// Boost score by confidence so higher-quality examples rank above noisy ones.
				$confidence = (float) ( $row->confidence ?? 1.0 );
				$scored[]   = [
					'id'         => (int) $row->id,
					'question'   => $row->question,
					'solution'   => $row->solution,
					'score'      => $sim * ( 0.80 + 0.20 * min( 1.0, $confidence ) ),
					'usage_count' => (int) $row->usage_count,
				];
			}
		}

		usort( $scored, fn( $a, $b ) => $b['score'] <=> $a['score'] );
		return array_slice( $scored, 0, $limit );
	}

	/**
	 * Check whether a near-duplicate question already exists in the knowledge base.
	 * Returns the existing row (id, question, solution) or null if no near-duplicate.
	 * Used to prevent knowledge base noise when auto-promoting or bulk-importing.
	 */
	public static function find_duplicate( string $question, float $threshold = 0.93 ): ?array {
		global $wpdb;

		$emb = AIAgent_LLM::embed( $question );
		if ( empty( $emb ) ) {
			return null;
		}

		$rows = $wpdb->get_results(
			"SELECT id, question, solution, embedding FROM {$wpdb->prefix}aiagent_taught_examples
			WHERE embedding IS NOT NULL ORDER BY created_at DESC LIMIT 300"
		);

		foreach ( $rows as $row ) {
			$stored = json_decode( $row->embedding, true );
			if ( ! is_array( $stored ) ) {
				continue;
			}
			if ( self::cosine_similarity( $emb, $stored ) >= $threshold ) {
				return [
					'id'       => (int) $row->id,
					'question' => $row->question,
					'solution' => $row->solution,
				];
			}
		}

		return null;
	}

	/**
	 * Increment usage_count and update last_used for a list of example IDs.
	 * Called every time the tools layer retrieves and uses examples in a reply.
	 */
	public static function increment_usage( array $ids ): void {
		if ( empty( $ids ) ) {
			return;
		}
		global $wpdb;
		$ids_in = implode( ',', array_map( 'absint', $ids ) );
		$wpdb->query(
			"UPDATE {$wpdb->prefix}aiagent_taught_examples
			SET usage_count = usage_count + 1, last_used = NOW()
			WHERE id IN ({$ids_in})"
		);
	}

	/**
	 * Lower the confidence of examples that were retrieved but led to escalation.
	 * Called by the escalate_to_human tool to decay quality of unhelpful examples.
	 */
	public static function decay_confidence( array $ids, float $factor = 0.85 ): void {
		if ( empty( $ids ) ) {
			return;
		}
		global $wpdb;
		$ids_in = implode( ',', array_map( 'absint', $ids ) );
		$wpdb->query(
			"UPDATE {$wpdb->prefix}aiagent_taught_examples
			SET confidence = GREATEST(0.1, confidence * {$factor})
			WHERE id IN ({$ids_in})"
		);
	}

	// ── Manual chunks ─────────────────────────────────────────────────────────

	/**
	 * Store a manual chunk immediately WITHOUT embedding (fast, no API call).
	 * Embeddings are filled in later by embed_pending_chunks().
	 * Returns the inserted row id or 0 on failure.
	 */
	public static function store_manual_chunk_bare(
		string $model,
		string $section_title,
		string $chunk,
		string $source_file
	): int {
		global $wpdb;
		$wpdb->insert(
			"{$wpdb->prefix}aiagent_manual_chunks",
			[
				'model'         => $model,
				'section_title' => $section_title,
				'chunk'         => $chunk,
				'embedding'     => null,
				'source_file'   => $source_file,
			],
			[ '%s', '%s', '%s', '%s', '%s' ]
		);
		return (int) $wpdb->insert_id;
	}

	/**
	 * Background job: embed all manual chunks that have no embedding yet.
	 * Processes up to $batch at a time to stay within PHP limits.
	 */
	public static function embed_pending_chunks( int $batch = 40 ): int {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, section_title, chunk FROM {$wpdb->prefix}aiagent_manual_chunks WHERE embedding IS NULL LIMIT %d",
			$batch
		) );
		$done = 0;
		foreach ( $rows as $row ) {
			$emb = AIAgent_LLM::embed( $row->section_title . "\n" . $row->chunk );
			if ( ! empty( $emb ) ) {
				$wpdb->update(
					"{$wpdb->prefix}aiagent_manual_chunks",
					[ 'embedding' => wp_json_encode( $emb ) ],
					[ 'id' => $row->id ],
					[ '%s' ], [ '%d' ]
				);
				$done++;
			}
		}
		// If there are more pending, reschedule.
		$remaining = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_manual_chunks WHERE embedding IS NULL" );
		if ( $remaining > 0 ) {
			wp_schedule_single_event( time() + 5, 'aiagent_embed_chunks_cron' );
		}
		return $done;
	}

	/**
	 * Background job: embed all taught examples that have no embedding yet.
	 */
	public static function embed_pending_examples( int $batch = 30 ): int {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}aiagent_taught_examples WHERE embedding IS NULL LIMIT %d",
			$batch
		) );
		$done = 0;
		foreach ( $rows as $row ) {
			if ( self::embed_taught_example( (int) $row->id ) ) {
				$done++;
			}
		}
		$remaining = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_taught_examples WHERE embedding IS NULL" );
		if ( $remaining > 0 ) {
			wp_schedule_single_event( time() + 5, 'aiagent_embed_examples_cron' );
		}
		return $done;
	}

	/**
	 * Embed and store a single manual chunk (original method — kept for compatibility).
	 */
	public static function store_manual_chunk(
		string $model,
		string $section_title,
		string $chunk,
		string $source_file
	): bool {
		global $wpdb;

		$embedding = AIAgent_LLM::embed( $section_title . "\n" . $chunk );
		if ( empty( $embedding ) ) {
			return false;
		}

		$wpdb->insert(
			"{$wpdb->prefix}aiagent_manual_chunks",
			[
				'model'         => $model,
				'section_title' => $section_title,
				'chunk'         => $chunk,
				'embedding'     => wp_json_encode( $embedding ),
				'source_file'   => $source_file,
			],
			[ '%s', '%s', '%s', '%s', '%s' ]
		);

		return (bool) $wpdb->insert_id;
	}

	/**
	 * Find the closest manual chunks for a model + question.
	 */
	public static function search_manual( string $question, string $model, int $limit = 3 ): array {
		global $wpdb;

		$q_embedding = AIAgent_LLM::embed( $question );
		if ( empty( $q_embedding ) ) {
			return [];
		}

		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT section_title, chunk, embedding FROM {$wpdb->prefix}aiagent_manual_chunks
			WHERE model = %s AND embedding IS NOT NULL",
			$model
		) );

		$scored = [];
		foreach ( $rows as $row ) {
			$emb = json_decode( $row->embedding, true );
			if ( ! is_array( $emb ) ) {
				continue;
			}
			$score = self::cosine_similarity( $q_embedding, $emb );
			if ( $score > 0.55 ) {
				$scored[] = [
					'section' => $row->section_title,
					'chunk'   => $row->chunk,
					'score'   => $score,
				];
			}
		}

		usort( $scored, fn( $a, $b ) => $b['score'] <=> $a['score'] );
		return array_slice( $scored, 0, $limit );
	}

	// ── PDF / text chunking ───────────────────────────────────────────────────

	/**
	 * Split plain text into overlapping chunks.
	 * Target: ~500 words per chunk, 50-word overlap to preserve context at boundaries.
	 */
	public static function chunk_text( string $text, int $chunk_words = 500, int $overlap = 50 ): array {
		// Normalise whitespace.
		$text  = preg_replace( '/\r\n|\r/', "\n", $text );
		$text  = preg_replace( '/\n{3,}/', "\n\n", $text );
		$words = preg_split( '/\s+/', trim( $text ), -1, PREG_SPLIT_NO_EMPTY );
		$total = count( $words );

		if ( $total === 0 ) {
			return [];
		}

		$chunks = [];
		$start  = 0;
		while ( $start < $total ) {
			$slice    = array_slice( $words, $start, $chunk_words );
			$chunks[] = implode( ' ', $slice );
			$start   += $chunk_words - $overlap;
		}

		return $chunks;
	}

	/**
	 * Simple PDF text extraction — decompresses FlateDecode content streams
	 * (the vast majority of real-world PDFs use stream compression) then picks
	 * out text between BT/ET markers. Works for most text-layer PDFs.
	 * For scanned/image PDFs the admin should paste text manually.
	 */
	public static function extract_pdf_text( string $file_path ): string {
		if ( ! file_exists( $file_path ) ) {
			return '';
		}

		$content = file_get_contents( $file_path );
		if ( $content === false ) {
			return '';
		}

		$blocks = [];

		// Pass 1: any content already in plain text (uncompressed streams).
		preg_match_all( '/BT\s+(.*?)\s+ET/s', $content, $plain );
		if ( ! empty( $plain[1] ) ) {
			$blocks = array_merge( $blocks, $plain[1] );
		}

		// Pass 2: decompress every FlateDecode stream and scan those too —
		// this is where almost all real PDF text actually lives.
		if ( preg_match_all( '/stream\r?\n(.*?)\r?\nendstream/s', $content, $streams ) ) {
			foreach ( $streams[1] as $raw ) {
				$decoded = @gzuncompress( $raw );
				if ( $decoded === false ) {
					// Some producers leave a couple of stray bytes before the zlib header.
					$decoded = @gzinflate( substr( $raw, 2 ) );
				}
				if ( $decoded === false || $decoded === '' ) {
					continue;
				}
				preg_match_all( '/BT\s+(.*?)\s+ET/s', $decoded, $m );
				if ( ! empty( $m[1] ) ) {
					$blocks = array_merge( $blocks, $m[1] );
				}
			}
		}

		if ( empty( $blocks ) ) {
			return '';
		}

		$text_parts = [];
		foreach ( $blocks as $block ) {
			// Pick out Tj / TJ string operands.
			preg_match_all( '/\(((?:[^()\\\\]|\\\\.)*)\)\s*Tj/', $block, $tj );
			preg_match_all( '/\[((?:[^\[\]]|\\\\.)*)\]\s*TJ/', $block, $TJ );

			foreach ( $tj[1] as $t ) {
				$text_parts[] = self::decode_pdf_string( $t );
			}
			foreach ( $TJ[1] as $t ) {
				// TJ arrays contain strings and numeric spacing.
				preg_match_all( '/\(((?:[^()\\\\]|\\\\.)*)\)/', $t, $strings );
				foreach ( $strings[1] as $s ) {
					$text_parts[] = self::decode_pdf_string( $s );
				}
			}
		}

		return implode( ' ', array_filter( $text_parts ) );
	}

	// ── Semantic cache ────────────────────────────────────────────────────────
	// Stores recent AI replies with embeddings. On each new question we embed it
	// and compare against the last 200 cached entries. If cosine > $threshold
	// (default 0.92 = almost identical meaning) the cached answer is returned
	// without calling the LLM — saving 20-40% of API calls for repetitive queries.
	// Only used for Mode A (product Q&A); Mode B always hits the LLM for fresh
	// context. Entries expire after data_retention_days (same as chat logs).

	/**
	 * Check whether a near-identical question was answered recently.
	 * Scoped by $model — Mode A can now give model-specific manual-grounded
	 * answers, so a cache hit for one product must never be served for another.
	 * Pass '' for the general (no product selected) bucket.
	 * Returns ['answer'=>string, 'score'=>float, 'id'=>int] or null on miss.
	 */
	public static function semantic_cache_get( string $question, string $model = '', float $threshold = 0.92 ): ?array {
		global $wpdb;

		$q_emb = AIAgent_LLM::embed( $question );
		if ( empty( $q_emb ) ) {
			return null;
		}

		$recent = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, question, answer, embedding FROM {$wpdb->prefix}aiagent_semantic_cache
			WHERE expires_at > NOW() AND model = %s ORDER BY created_at DESC LIMIT 200",
			$model
		) );

		$best       = null;
		$best_score = 0.0;

		foreach ( $recent as $row ) {
			$emb = json_decode( $row->embedding, true );
			if ( ! is_array( $emb ) ) {
				continue;
			}
			$score = self::cosine_similarity( $q_emb, $emb );
			if ( $score >= $threshold && $score > $best_score ) {
				$best_score = $score;
				$best       = [ 'answer' => $row->answer, 'score' => $score, 'id' => (int) $row->id ];
			}
		}

		// Increment hit counter on match.
		if ( $best !== null ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->prefix}aiagent_semantic_cache SET hits = hits + 1 WHERE id = %d",
				$best['id']
			) );
		}

		return $best;
	}

	/**
	 * Store a new Q→A pair in the semantic cache, scoped to $model (see
	 * semantic_cache_get). Expires according to the data_retention_days setting.
	 */
	public static function semantic_cache_set( string $question, string $answer, string $model = '' ): void {
		global $wpdb;

		$emb = AIAgent_LLM::embed( $question );
		if ( empty( $emb ) ) {
			return;
		}

		$days      = (int) ( aiagent_settings()['data_retention_days'] ?? 90 );
		$expires   = gmdate( 'Y-m-d H:i:s', strtotime( "+{$days} days" ) );

		$wpdb->insert(
			"{$wpdb->prefix}aiagent_semantic_cache",
			[
				'question'   => $question,
				'answer'     => $answer,
				'embedding'  => wp_json_encode( $emb ),
				'model'      => $model,
				'expires_at' => $expires,
			],
			[ '%s', '%s', '%s', '%s', '%s' ]
		);
	}

	private static function decode_pdf_string( string $s ): string {
		// Basic PDF octal and hex decode.
		$s = preg_replace_callback( '/\\\\([0-7]{1,3})/', function ( $m ) {
			return chr( octdec( $m[1] ) );
		}, $s );
		$s = str_replace( [ '\\n', '\\r', '\\t', '\\(', '\\)' ], [ "\n", "\r", "\t", '(', ')' ], $s );
		return trim( $s );
	}

	// ── Auto-categorise via LLM ───────────────────────────────────────────────

	/**
	 * Ask the LLM to categorise a Q&A pair and optionally propose a new category.
	 * Returns ['category_id' => int|null, 'proposed_name' => string|null, 'tags' => array, 'clean_question' => string, 'clean_solution' => string]
	 */
	public static function auto_categorise( string $question, string $solution, string $lang = 'en' ): array {
		global $wpdb;

		$existing = $wpdb->get_results(
			"SELECT id, name_en, name_ar FROM {$wpdb->prefix}aiagent_categories WHERE approved = 1 ORDER BY name_en ASC"
		);

		$cat_list = implode( ', ', array_map(
			fn( $c ) => "{$c->id}:{$c->name_en}",
			$existing
		) );

		$prompt = <<<PROMPT
You are a knowledge-base organiser for iBird (a product company in Bahrain).

Given the following Q&A pair, return a JSON object with these keys:
- "clean_question": a concise, rewritten version of the question (1 sentence)
- "clean_solution": a clear, rewritten solution/answer (keep technical accuracy)
- "category_id": the ID of the best matching existing category from the list (integer), or null if none fit
- "proposed_category": if category_id is null, propose a short new category name in English (max 4 words), else null
- "tags": array of 2-5 keyword strings

Existing categories (id:name): {$cat_list}

Question: {$question}
Solution: {$solution}

Return JSON only. No markdown fences.
PROMPT;

		$raw = AIAgent_LLM::classify( $prompt, $lang );

		// Strip any stray markdown fences.
		$raw = preg_replace( '/^```(?:json)?\s*/i', '', trim( $raw ) );
		$raw = preg_replace( '/```$/', '', trim( $raw ) );

		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return [
				'category_id'   => null,
				'proposed_name' => null,
				'tags'          => [],
				'clean_question' => $question,
				'clean_solution' => $solution,
			];
		}

		return [
			'category_id'    => isset( $data['category_id'] ) ? (int) $data['category_id'] : null,
			'proposed_name'  => $data['proposed_category'] ?? null,
			'tags'           => $data['tags'] ?? [],
			'clean_question' => $data['clean_question'] ?? $question,
			'clean_solution' => $data['clean_solution'] ?? $solution,
		];
	}
}

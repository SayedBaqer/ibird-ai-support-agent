<?php
defined( 'ABSPATH' ) || exit;

/**
 * RAG layer — chunk/embed storage and similarity retrieval.
 * Abstracted so a vector backend can replace the PHP cosine implementation later.
 */
class AIAgent_RAG {

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
	 * Returns array of ['question'=>, 'solution'=>, 'score'=>] sorted desc.
	 */
	public static function search_taught_examples( string $question, int $limit = 3 ): array {
		global $wpdb;

		$q_embedding = AIAgent_LLM::embed( $question );
		if ( empty( $q_embedding ) ) {
			return [];
		}

		// Fetch all rows that have an embedding. For large datasets this should be
		// replaced with a vector index, but fine for the current scale.
		$rows = $wpdb->get_results(
			"SELECT question, solution, embedding FROM {$wpdb->prefix}aiagent_taught_examples WHERE embedding IS NOT NULL"
		);

		$scored = [];
		foreach ( $rows as $row ) {
			$emb = json_decode( $row->embedding, true );
			if ( ! is_array( $emb ) ) {
				continue;
			}
			$score = self::cosine_similarity( $q_embedding, $emb );
			if ( $score > 0.65 ) {
				$scored[] = [
					'question' => $row->question,
					'solution' => $row->solution,
					'score'    => $score,
				];
			}
		}

		usort( $scored, fn( $a, $b ) => $b['score'] <=> $a['score'] );
		return array_slice( $scored, 0, $limit );
	}

	// ── Manual chunks ─────────────────────────────────────────────────────────

	/**
	 * Embed and store a single manual chunk.
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

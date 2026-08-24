<?php
defined( 'ABSPATH' ) || exit;

class AIAgent_Tools {

	// ── Tool declarations for Gemini function-calling ─────────────────────────

	/**
	 * Return tool declarations appropriate for the current conversation mode.
	 *
	 * search_taught_examples, search_common_knowledge, escalate_to_human — always available.
	 * search_products — Mode A (pre-sales) only.
	 * search_manual   — available whenever a model is known: verified (Mode B) OR the
	 *                    customer has selected a product in Mode A (unverified — general
	 *                    usage/troubleshooting only, no warranty/order facts).
	 *
	 * @param string $mode      'product' | 'support'
	 * @param bool   $has_model Whether a model is known for this conversation (verified_model
	 *                          in support mode, or selected_model in product mode).
	 */
	public static function declarations( string $mode = 'product', bool $has_model = false ): array {
		$common = [
			[
				'name'        => 'search_taught_examples',
				'description' => 'Search the knowledge base for pre-taught Q&A pairs that match the customer question.',
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'question' => [ 'type' => 'string', 'description' => 'Customer question to match' ],
					],
					'required' => [ 'question' ],
				],
			],
			[
				'name'        => 'search_common_knowledge',
				'description' => 'Search general troubleshooting/how-to knowledge that applies across all products (battery care, resets, connectivity, packaging, etc.) — not tied to one specific model.',
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'question' => [ 'type' => 'string', 'description' => 'Customer question to match' ],
					],
					'required' => [ 'question' ],
				],
			],
			[
				'name'        => 'escalate_to_human',
				'description' => 'Escalate the conversation to a human agent and create a support ticket.',
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'reason' => [ 'type' => 'string', 'description' => 'Reason for escalation' ],
					],
					'required' => [ 'reason' ],
				],
			],
		];

		if ( $mode === 'product' ) {
			array_unshift( $common, [
				'name'        => 'search_products',
				'description' => 'Search WooCommerce products by a customer query. Use this for pre-sales product questions — features, specs, price, availability.',
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'query' => [ 'type' => 'string', 'description' => 'Customer search query' ],
					],
					'required' => [ 'query' ],
				],
			] );
		}

		if ( $has_model ) {
			$desc = $mode === 'support'
				? 'Search the product manual for the verified customer\'s model to find troubleshooting steps, usage instructions, and technical guidance.'
				: 'Search the manual for the product the customer has selected — general usage instructions and troubleshooting steps only. Do NOT use this to state warranty status or promise replacement/repair; those require ownership verification.';
			array_unshift( $common, [
				'name'        => 'search_manual',
				'description' => $desc,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'question' => [ 'type' => 'string', 'description' => 'Customer support question' ],
						'model'    => [ 'type' => 'string', 'description' => 'Product model name/code' ],
					],
					'required' => [ 'question', 'model' ],
				],
			] );
		}

		return $common;
	}

	// ── Dispatcher ────────────────────────────────────────────────────────────

	/**
	 * Dispatch a tool call returned by the LLM.
	 *
	 * @param  array  $tool_call  ['name'=>string, 'args'=>array]
	 * @param  array  $context    ['conversation_id', 'session_token', 'lang', 'mode', 'verified_model']
	 * @return string  Sanitised result text to feed back to the LLM.
	 */
	public static function dispatch( array $tool_call, array $context ): string {
		$name = $tool_call['name'] ?? '';
		$args = $tool_call['args'] ?? [];
		$mode = $context['mode'] ?? 'product';

		switch ( $name ) {

			case 'search_products':
				// Guard: only allowed in Mode A.
				if ( $mode !== 'product' ) {
					return '[search_products not available in support mode]';
				}
				return self::search_products( sanitize_text_field( $args['query'] ?? '' ) );

			case 'search_manual':
				// Guard: allowed when verified (Mode B) OR a product is selected (Mode A, unverified).
				$context_model = $context['verified_model'] ?? $context['selected_model'] ?? '';
				if ( $mode !== 'support' && empty( $context_model ) ) {
					return '[search_manual not available — no product selected and ownership not verified]';
				}
				return self::search_manual(
					sanitize_text_field( $args['question'] ?? '' ),
					sanitize_text_field( $args['model'] ?? $context_model )
				);

			case 'search_common_knowledge':
				return self::search_manual(
					sanitize_text_field( $args['question'] ?? '' ),
					AIAgent_RAG::COMMON_MODEL
				);

			case 'search_taught_examples':
				return self::search_taught_examples(
					sanitize_text_field( $args['question'] ?? '' ),
					$context['lang'] ?? ''
				);

			case 'escalate_to_human':
				return self::escalate_to_human(
					sanitize_text_field( $args['reason'] ?? '' ),
					$context
				);

			default:
				return '[Unknown tool: ' . esc_html( $name ) . ']';
		}
	}

	// ── Tool: search_products (Mode A only) ───────────────────────────────────

	public static function search_products( string $query ): string {
		if ( $query === '' ) {
			return 'No query provided.';
		}

		$ids = wc_get_products( [
			'status'  => 'publish',
			'limit'   => 5,
			'return'  => 'ids',
			's'       => $query,
		] );

		// Fallback to WP_Query if WC search returns nothing.
		if ( empty( $ids ) ) {
			$qr  = new WP_Query( [ 'post_type' => 'product', 'post_status' => 'publish', 's' => $query, 'posts_per_page' => 5, 'fields' => 'ids' ] );
			$ids = $qr->posts;
		}

		if ( empty( $ids ) ) {
			return 'No matching products found in the catalogue.';
		}

		$lines = [];
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}

			$price    = $product->get_price();
			$currency = get_woocommerce_currency();
			$stock    = $product->is_in_stock() ? 'In stock' : 'Out of stock';

			$desc = $product->get_short_description();
			if ( $desc === '' ) {
				$desc = wp_trim_words( wp_strip_all_tags( $product->get_description() ), 50 );
			} else {
				$desc = wp_strip_all_tags( $desc );
			}

			$attr_lines = [];
			foreach ( $product->get_attributes() as $attr ) {
				if ( $attr instanceof WC_Product_Attribute ) {
					$attr_lines[] = wc_attribute_label( $attr->get_name() ) . ': ' . implode( ', ', $attr->get_options() );
				}
			}

			$cats = wp_get_post_terms( (int) $id, 'product_cat', [ 'fields' => 'names' ] );

			$lines[] = implode( "\n", array_filter( [
				'Product: ' . $product->get_name(),
				'Price: ' . $price . ' ' . $currency,
				'Stock: ' . $stock,
				$desc       !== '' ? 'Description: ' . $desc : '',
				! empty( $attr_lines ) ? 'Specs: ' . implode( '; ', $attr_lines ) : '',
				! empty( $cats ) ? 'Categories: ' . implode( ', ', $cats ) : '',
			] ) );
		}

		return $lines ? implode( "\n\n---\n\n", $lines ) : 'No product details available.';
	}

	// ── Tool: search_manual (verified support, or an unverified selected product) ──

	/**
	 * Tracks the Gemini context cache name resolved during the last search_manual call.
	 * The reply layer reads this after tool dispatch to attach cachedContent — saving
	 * ~75% on input tokens when a valid cache exists for this product's manual.
	 */
	public static string $last_cache_name = '';

	public static function search_manual( string $question, string $model ): string {
		self::$last_cache_name = '';

		if ( $question === '' || $model === '' ) {
			return 'Question or model not provided.';
		}

		// ── Check for a live Gemini context cache for this model ──────────────
		$cache_entry = AIAgent_File_API::get_model_cache( $model );
		if ( $cache_entry ) {
			// Expose cache name to the reply layer so it can use cachedContent.
			self::$last_cache_name = $cache_entry['cache_name'];
			// Extend TTL so it doesn't expire mid-conversation.
			AIAgent_File_API::refresh_cache( $cache_entry['cache_name'] );
			return "Manual context loaded from cache for model \"{$model}\". Answer the customer's question using the cached manual content.";
		}

		// ── Fallback: RAG chunk retrieval ─────────────────────────────────────
		$results = AIAgent_RAG::search_manual( $question, $model, 4 );

		if ( empty( $results ) ) {
			return 'No relevant manual sections found for model "' . $model . '".';
		}

		$parts = [];
		foreach ( $results as $r ) {
			$parts[] = ( $r['section'] ? "## {$r['section']}\n" : '' ) . $r['chunk'];
		}

		return implode( "\n\n---\n\n", $parts );
	}

	// ── Tool: search_taught_examples (both modes) ─────────────────────────────

	public static function search_taught_examples( string $question, string $lang = '' ): string {
		if ( $question === '' ) {
			return 'No question provided.';
		}

		$results = AIAgent_RAG::search_taught_examples( $question, 3, $lang );

		if ( empty( $results ) ) {
			return 'No matching knowledge base entries found.';
		}

		// Track which examples were actually used — increments usage_count + last_used
		// so the admin UI can show what the AI retrieves most and what's never used.
		$ids = array_filter( array_column( $results, 'id' ) );
		if ( ! empty( $ids ) ) {
			AIAgent_RAG::increment_usage( $ids );
		}

		$parts = [];
		foreach ( $results as $r ) {
			$score_pct = round( $r['score'] * 100 );
			$parts[] = "Q: {$r['question']}\nA: {$r['solution']}\n[relevance: {$score_pct}%]";
		}

		$context = implode( "\n\n---\n\n", $parts );
		return "Knowledge base matches (adapt these to the customer's question — do not copy verbatim):\n\n{$context}";
	}

	// ── Expose last-used example IDs to the caller (for confidence decay on escalation) ──
	public static array $last_example_ids = [];

	// ── Tool: escalate_to_human ───────────────────────────────────────────────

	public static function escalate_to_human( string $reason, array $context ): string {
		global $wpdb;

		$conversation_id = (int) ( $context['conversation_id'] ?? 0 );

		if ( ! $conversation_id ) {
			return 'Escalation failed: no conversation ID.';
		}

		$wpdb->insert(
			"{$wpdb->prefix}aiagent_tickets",
			[
				'conversation_id' => $conversation_id,
				'status'          => 'open',
				'reason'          => $reason,
				'contact'         => '',
			],
			[ '%d', '%s', '%s', '%s' ]
		);

		$ticket_id = (int) $wpdb->insert_id;

		$wpdb->update(
			"{$wpdb->prefix}aiagent_conversations",
			[ 'status' => 'escalated' ],
			[ 'id'     => $conversation_id ],
			[ '%s' ], [ '%d' ]
		);

		// When examples were retrieved this turn but still led to escalation, decay
		// their confidence slightly — they were not helpful enough to avoid escalation.
		if ( ! empty( self::$last_example_ids ) ) {
			AIAgent_RAG::decay_confidence( self::$last_example_ids );
			self::$last_example_ids = [];
		}

		// Notify the admin team immediately — email always, WhatsApp link if configured.
		self::notify_escalation( $ticket_id, $reason, $conversation_id );

		return "Escalation ticket #{$ticket_id} created. A specialist will follow up shortly.";
	}

	// ── Escalation notification ───────────────────────────────────────────────

	private static function notify_escalation( int $ticket_id, string $reason, int $conversation_id ): void {
		$settings       = aiagent_settings();
		$admin_email    = $settings['notify_email'] ?: get_option( 'admin_email' );
		$whatsapp_num   = trim( $settings['whatsapp_number'] ?? '' );
		$admin_url      = admin_url( 'admin.php?page=aiagent-tickets' );
		$ticket_url     = admin_url( "admin.php?page=aiagent-tickets&ticket_id={$ticket_id}" );

		// ── Email notification ────────────────────────────────────────────────
		if ( $admin_email ) {
			$subject = "[iBird Support] New Escalation Ticket #{$ticket_id}";
			$body    = "A customer conversation has been escalated.\n\n"
				. "Ticket #: {$ticket_id}\n"
				. "Conversation ID: {$conversation_id}\n"
				. "Reason: {$reason}\n\n"
				. "Review ticket: {$ticket_url}\n\n"
				. "All tickets: {$admin_url}";

			wp_mail( $admin_email, $subject, $body, [
				'Content-Type: text/plain; charset=UTF-8',
				'From: iBird AI Support <' . $admin_email . '>',
			] );
		}

		// ── WhatsApp deep-link (GCC market standard) ──────────────────────────
		// Sends the admin a pre-composed WhatsApp message they can tap to open.
		// Uses the wa.me API — no server-side WhatsApp API key required for this
		// basic flow. For full automation, replace with Twilio or 360dialog API.
		if ( $whatsapp_num !== '' ) {
			$whatsapp_num = preg_replace( '/[^0-9+]/', '', $whatsapp_num );
			$text         = rawurlencode(
				"iBird Support Alert: New ticket #{$ticket_id}\n"
				. "Reason: {$reason}\n"
				. "Review: {$ticket_url}"
			);
			// Log the WhatsApp deep-link so admin can tap it from their device.
			// (A full Twilio/360dialog integration can replace this with a real API POST.)
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( "[AIAgent Escalation] WhatsApp link: https://wa.me/{$whatsapp_num}?text={$text}" );
			}
		}
	}
}

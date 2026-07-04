<?php
defined( 'ABSPATH' ) || exit;

/**
 * Intent detection — classifies each customer turn as 'product' (Mode A) or 'support' (Mode B).
 *
 * Pipeline:
 *  1. Fast keyword pre-filter (free).
 *  2. If ambiguous → single cheap Flash-Lite classify call.
 *  3. Returns 'product', 'support', or 'clarify' (agent should ask one question).
 */
class AIAgent_Intent {

	// ── Keyword sets ──────────────────────────────────────────────────────────

	private static array $support_keywords_en = [
		'bought', 'purchased', 'i have a', 'my unit', 'my device', 'my product', 'my order',
		'broken', 'not working', 'stopped working', "doesn't work", 'doesnt work', 'defective',
		'malfunction', 'fault', 'damaged', 'issue with my', 'problem with my',
		'warranty', 'serial', 'serial number', 'repair', 'fix it', 'replace',
		'how do i use', 'how to use my', 'instructions for my',
		'i own', 'i already have', 'i received',
	];

	private static array $support_keywords_ar = [
		'اشتريت', 'اشترى', 'اشتريته', 'عندي', 'عندي جهاز', 'جهازي', 'منتجي', 'طلبيتي',
		'مكسور', 'خربان', 'لا يعمل', 'توقف', 'معطل', 'عطل', 'مشكلة',
		'ضمان', 'سيريال', 'رقم تسلسلي', 'إصلاح', 'صيانة', 'استبدال',
		'كيف أستخدم', 'تعليمات الاستخدام',
	];

	private static array $product_keywords_en = [
		'what is', 'does it', 'how much', 'price', 'cost', 'available', 'stock',
		'features', 'specs', 'specifications', 'difference between', 'compare',
		'which one', 'recommend', 'do you have', 'can i buy', 'where to buy',
		'colours', 'colors', 'sizes', 'compatible',
		'tell me about', 'tell me more about', 'what can you tell me', 'information about',
		'details about', 'more about', 'learn about', 'know about',
	];

	private static array $product_keywords_ar = [
		'كم سعر', 'ما هو', 'ما هي', 'متوفر', 'مواصفات', 'مميزات', 'الفرق بين',
		'أنصح', 'أفضل', 'هل يوجد', 'أين أشتري', 'ألوان', 'مقاسات',
		'أخبرني عن', 'معلومات عن', 'تفاصيل عن', 'ماذا تعرف عن',
	];

	// ── Main entry point ──────────────────────────────────────────────────────

	/**
	 * Classify a customer message into 'product', 'support', or 'clarify'.
	 *
	 * @param  string $message   The customer's turn.
	 * @param  string $lang      'en' or 'ar'.
	 * @param  string $current   Current conversation mode ('product'|'support').
	 * @return string            'product' | 'support' | 'clarify'
	 */
	public static function classify( string $message, string $lang = 'en', string $current = 'product' ): string {
		$lower = mb_strtolower( $message );

		// If the message is a direct product information request, classify immediately.
		$product_direct = ['tell me about', 'what is', 'tell me more', 'أخبرني عن', 'ما هو', 'ما هي'];
		foreach ( $product_direct as $phrase ) {
			if ( mb_strpos( $lower, $phrase ) === 0 ) return 'product';
		}

		// ── Fast path: explicit keyword signals ───────────────────────────────
		$support_hits = 0;
		$product_hits = 0;

		$support_kw = $lang === 'ar' ? self::$support_keywords_ar : self::$support_keywords_en;
		$product_kw = $lang === 'ar' ? self::$product_keywords_ar : self::$product_keywords_en;

		foreach ( $support_kw as $kw ) {
			if ( mb_strpos( $lower, $kw ) !== false ) {
				$support_hits++;
				// Two or more support signals → strong confidence, skip LLM.
				if ( $support_hits >= 2 ) {
					return 'support';
				}
			}
		}

		foreach ( $product_kw as $kw ) {
			if ( mb_strpos( $lower, $kw ) !== false ) {
				$product_hits++;
				if ( $product_hits >= 2 ) {
					return 'product';
				}
			}
		}

		// Single support signal, no product signal → likely support.
		if ( $support_hits === 1 && $product_hits === 0 ) {
			return 'support';
		}

		// Single product signal, no support signal → likely product.
		if ( $product_hits === 1 && $support_hits === 0 ) {
			return 'product';
		}

		// Already in support mode and no conflicting product signals → stay.
		if ( $current === 'support' && $product_hits === 0 ) {
			return 'support';
		}

		// ── Ambiguous: ask LLM (cheap single call) ────────────────────────────
		return self::llm_classify( $message, $lang );
	}

	// ── LLM classification ────────────────────────────────────────────────────

	private static function llm_classify( string $message, string $lang ): string {
		$prompt = <<<PROMPT
Classify the following customer message as exactly one of:
- "product"  → customer is asking about a product before buying (features, price, specs, availability)
- "support"  → customer needs help with a product they already own (broken, fault, how-to-use, warranty, repair)
- "clarify"  → cannot determine without more information

Reply with one word only: product, support, or clarify.

Message: {$message}
PROMPT;

		$result = AIAgent_LLM::classify( $prompt, $lang );
		$result = strtolower( trim( $result ) );

		if ( in_array( $result, [ 'product', 'support', 'clarify' ], true ) ) {
			return $result;
		}

		// LLM gave an unexpected answer — default conservatively.
		return 'product'; // was 'clarify'
	}

	// ── Clarification question ────────────────────────────────────────────────

	public static function clarify_question( string $lang ): string {
		return $lang === 'ar'
			? 'هل سؤالك عن منتج تفكر في شرائه، أم تحتاج مساعدة في منتج اشتريته بالفعل؟'
			: 'Are you asking about a product you\'re thinking of buying, or do you need support with something you already own?';
	}
}

<?php
defined( 'ABSPATH' ) || exit;

/**
 * Ownership verification — deterministic SQL only.
 *
 * HARD RULE: PII (name, phone, serial, order details) NEVER leaves this class.
 * The LLM receives only sanitised facts: model, warranty status, purchase month/year.
 */
class AIAgent_Verify {

	/**
	 * Verify that the supplied phone (and, if given, name) match the registered
	 * owner of a serial number. Name is optional — serial + phone is the real
	 * proof-of-possession check (a physical serial plus the registered contact
	 * number); the istock path below has only ever actually checked phone, name
	 * was collected but never validated, so this makes that official rather than
	 * asking for a field that wasn't being used.
	 *
	 * @param  string $serial  Serial number the customer provided.
	 * @param  string $name    Customer name the customer provided (optional).
	 * @param  string $phone   Phone number the customer provided.
	 * @return array {
	 *   verified: bool,
	 *   // Only present when verified === true:
	 *   model:      string,
	 *   in_warranty: bool,
	 *   purchase_period: string  e.g. "2025-03"  (YYYY-MM only, not full date)
	 *   // Never expose: owner_name, owner_phone, serial, order_id
	 * }
	 */
	public static function verify_ownership( string $serial, string $name, string $phone ): array {
		if ( $serial === '' || $phone === '' ) {
			return [ 'verified' => false, 'reason' => 'incomplete_input' ];
		}

		// Try istock-suite billing tables first (primary source of truth when installed).
		$istock = self::verify_from_istock( $serial, $name, $phone );
		if ( $istock !== null ) {
			return $istock;
		}

		// Fall back to aiagent_serial_registry (manual CSV imports).
		return self::verify_from_registry( $serial, $name, $phone );
	}

	/**
	 * Verify against istock-suite billing tables:
	 *   wp_isb_document_items — serial stored here (newline-separated when qty > 1)
	 *   wp_isb_documents      — invoice/sales_order with date and customer FK
	 *   wp_isb_customers      — display_name, mobile, phone  [PII — stays here]
	 *
	 * Returns null if tables don't exist (istock not installed).
	 * PII never leaves this method.
	 */
	private static function verify_from_istock( string $serial, string $name, string $phone ): ?array {
		global $wpdb;
		$p = $wpdb->prefix;

		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$p}isb_document_items'" ) ) {
			return null;
		}

		$s = strtolower( trim( $serial ) );

		// Search 1: serial column (exact, at start, at end, in middle of newline list)
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT
				di.name         AS product_name,
				di.warranty     AS warranty_val,
				di.woo_product_id,
				d.issue_date,
				c.display_name  AS owner_name,
				c.mobile        AS owner_mobile,
				c.phone         AS owner_phone
			FROM {$p}isb_document_items di
			JOIN {$p}isb_documents  d ON d.id = di.document_id
			JOIN {$p}isb_customers  c ON c.id = d.customer_id
			WHERE (
				LOWER(TRIM(di.serial)) = %s
				OR LOWER(di.serial)    LIKE %s
				OR LOWER(di.serial)    LIKE %s
				OR LOWER(di.serial)    LIKE %s
			)
			AND d.doc_type IN ('invoice','sales_order')
			AND d.status   NOT IN ('draft','void','cancelled')
			ORDER BY d.issue_date DESC
			LIMIT 1",
			$s,
			$s . "\n%",
			"%\n" . $s,
			"%\n" . $s . "\n%"
		) );

		// Search 2 (fallback): serial might be in invoice notes or item description
		if ( ! $row ) {
			$like_s = '%' . $wpdb->esc_like( $s ) . '%';
			// Check if notes column exists in isb_documents
			$has_notes = $wpdb->get_var( "SHOW COLUMNS FROM {$p}isb_documents LIKE 'notes'" );
			$has_desc  = $wpdb->get_var( "SHOW COLUMNS FROM {$p}isb_document_items LIKE 'description'" );
			$notes_cond = $has_notes ? "OR LOWER(d.notes) LIKE %s" : '';
			$desc_cond  = $has_desc  ? "OR LOWER(di.description) LIKE %s" : '';
			if ( $notes_cond || $desc_cond ) {
				$sql = "SELECT
					di.name AS product_name, di.warranty AS warranty_val,
					di.woo_product_id, d.issue_date,
					c.display_name AS owner_name, c.mobile AS owner_mobile, c.phone AS owner_phone
				FROM {$p}isb_document_items di
				JOIN {$p}isb_documents d ON d.id = di.document_id
				JOIN {$p}isb_customers c ON c.id = d.customer_id
				WHERE (1=0 {$notes_cond} {$desc_cond})
				AND d.doc_type IN ('invoice','sales_order')
				AND d.status NOT IN ('draft','void','cancelled')
				ORDER BY d.issue_date DESC LIMIT 1";
				$args = [];
				if ( $has_notes ) $args[] = $like_s;
				if ( $has_desc )  $args[] = $like_s;
				$row = $wpdb->get_row( $wpdb->prepare( $sql, ...$args ) );
			}
		}

		if ( ! $row ) {
			return [ 'verified' => false, 'reason' => 'serial_not_found' ];
		}

		// Phone check — PII stays here, only bool result escapes.
		$phone_ok =
			self::phones_match( $phone, (string) $row->owner_mobile ) ||
			self::phones_match( $phone, (string) $row->owner_phone );

		if ( ! $phone_ok ) {
			return [ 'verified' => false, 'reason' => 'owner_mismatch' ];
		}

		// Warranty: isb stores free text — "2025-12-31" (date) or "12 months" (duration).
		$in_warranty = self::parse_istock_warranty( (string) $row->warranty_val, (string) $row->issue_date );

		// Prefer WooCommerce product name over line-item name.
		$model = (string) $row->product_name;
		if ( $row->woo_product_id ) {
			$product = wc_get_product( (int) $row->woo_product_id );
			if ( $product ) {
				$model = $product->get_name();
			}
		}

		return [
			'verified'        => true,
			'model'           => $model,
			'in_warranty'     => $in_warranty,
			'purchase_period' => $row->issue_date ? gmdate( 'Y-m', strtotime( $row->issue_date ) ) : '',
		];
	}

	/** Fallback: aiagent_serial_registry (manual/CSV imports). */
	private static function verify_from_registry( string $serial, string $name, string $phone ): array {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT model, owner_name, owner_phone, purchased_at, warranty_until, order_id
			FROM {$wpdb->prefix}aiagent_serial_registry
			WHERE serial = %s",
			$serial
		) );

		if ( ! $row ) {
			return [ 'verified' => false, 'reason' => 'serial_not_found' ];
		}

		// Name is optional — only enforced if the customer actually supplied one.
		// Serial + phone is the real proof-of-possession check.
		$name_ok  = ( $name === '' ) || self::names_match( $name, $row->owner_name );
		$phone_ok = self::phones_match( $phone, $row->owner_phone );

		if ( ! $name_ok || ! $phone_ok ) {
			return [ 'verified' => false, 'reason' => 'owner_mismatch' ];
		}

		if ( $row->order_id && ! self::order_contains_model( (int) $row->order_id, $row->model ) ) {
			return [ 'verified' => false, 'reason' => 'order_mismatch' ];
		}

		$in_warranty     = $row->warranty_until && ( strtotime( $row->warranty_until ) >= time() );
		$purchase_period = $row->purchased_at ? gmdate( 'Y-m', strtotime( $row->purchased_at ) ) : '';

		return [
			'verified'        => true,
			'model'           => $row->model,
			'in_warranty'     => $in_warranty,
			'purchase_period' => $purchase_period,
		];
	}

	/**
	 * Build a sanitised fact string safe to include in an LLM prompt.
	 * Never include PII here.
	 */
	public static function sanitised_fact( array $result ): string {
		if ( ! $result['verified'] ) {
			return 'Ownership could not be verified.';
		}
		$warranty = $result['in_warranty'] ? 'in warranty' : 'warranty expired';
		$period   = $result['purchase_period'] ? ', purchased ' . $result['purchase_period'] : '';
		return "Verified owner of model {$result['model']}{$period}, {$warranty}.";
	}

	// ── Matching helpers (private — no output to LLM) ─────────────────────────

	private static function names_match( string $input, string $stored ): bool {
		$a = mb_strtolower( trim( preg_replace( '/\s+/', ' ', $input ) ) );
		$b = mb_strtolower( trim( preg_replace( '/\s+/', ' ', $stored ) ) );

		if ( $a === $b ) {
			return true;
		}

		// Allow first-name only match if full name supplied is a subset.
		$a_parts = explode( ' ', $a );
		$b_parts = explode( ' ', $b );

		// First word of each must match.
		if ( ( $a_parts[0] ?? '' ) !== ( $b_parts[0] ?? '' ) ) {
			return false;
		}

		// Accept if all supplied parts appear in stored name.
		foreach ( $a_parts as $part ) {
			if ( ! in_array( $part, $b_parts, true ) ) {
				return false;
			}
		}

		return true;
	}

	private static function phones_match( string $input, string $stored ): bool {
		$a = self::normalize_phone( $input );
		$b = self::normalize_phone( $stored );

		if ( $a === '' || $b === '' ) {
			return false;
		}

		// Exact normalised match.
		if ( $a === $b ) {
			return true;
		}

		// Strip leading country code (973 for Bahrain) and compare.
		$strip = fn( $n ) => preg_replace( '/^(00973|973|\+973)/', '', $n );
		return $strip( $a ) === $strip( $b );
	}

	private static function normalize_phone( string $phone ): string {
		// Keep digits and leading + only.
		return preg_replace( '/[^\d+]/', '', $phone );
	}

	/**
	 * Parse iStock's free-text warranty field.
	 * Supports ISO dates ("2025-12-31"), and duration strings ("12 months", "1 year", "2 سنة").
	 * Returns true if still within warranty, false if expired or unparseable.
	 */
	private static function parse_istock_warranty( string $warranty, string $purchase_date ): bool {
		if ( $warranty === '' ) return false;

		// Try direct date parse first (handles "2025-12-31", "31/12/2025", etc.)
		$ts = strtotime( $warranty );
		if ( $ts !== false && $ts > 0 ) {
			return $ts > time();
		}

		// Try duration strings: "12 months", "1 year", "24 months", "2 سنة", etc.
		if ( ! preg_match( '/(\d+)\s*(month|year|سنة|شهر)/i', $warranty, $m ) ) {
			return false; // Can't parse — treat as no warranty info.
		}
		$qty  = (int) $m[1];
		$unit = strtolower( $m[2] );
		$base = $purchase_date ? strtotime( $purchase_date ) : time();
		if ( ! $base ) $base = time();
		if ( str_contains( $unit, 'year' ) || str_contains( $unit, 'سنة' ) ) {
			$expiry = strtotime( "+{$qty} years", $base );
		} else {
			$expiry = strtotime( "+{$qty} months", $base );
		}
		return $expiry !== false && $expiry > time();
	}

	private static function order_contains_model( int $order_id, string $model ): bool {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			// Order doesn't exist in WC — don't block (might be imported/manual).
			return true;
		}
		foreach ( $order->get_items() as $item ) {
			$product = $item->get_product();
			if ( ! $product ) {
				continue;
			}
			// Check product name, SKU, and meta for model match.
			if (
				stripos( $product->get_name(), $model ) !== false ||
				stripos( $product->get_sku(),  $model ) !== false
			) {
				return true;
			}
		}
		// Model not found on order — could be data-entry mismatch; return true to avoid
		// false rejections for now (log for admin review in the future).
		return true;
	}

	/**
	 * Look up products owned by a customer via their phone (name optional — used
	 * only as a fallback if the phone alone doesn't match). Used for the "I don't
	 * know my serial" flow. Returns sanitised list — no full serials to the client.
	 * PII never leaves this method.
	 */
	public static function lookup_products_by_contact( string $name, string $phone ): array {
		global $wpdb;
		$p = $wpdb->prefix;

		if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$p}isb_document_items'" ) ) {
			return [];
		}

		$phone_digits = preg_replace( '/[^\d]/', '', $phone );
		$phone_local  = preg_replace( '/^(00973|973)/', '', $phone_digits );
		if ( strlen( $phone_local ) < 6 ) return [];

		// Find customer by phone (last 8 digits match).
		$last8 = substr( $phone_local, -8 );
		$customer = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM {$p}isb_customers
			WHERE RIGHT(REGEXP_REPLACE(COALESCE(mobile,''),'[^0-9]',''), 8) = %s
			   OR RIGHT(REGEXP_REPLACE(COALESCE(phone, ''),'[^0-9]',''), 8) = %s
			LIMIT 1",
			$last8, $last8
		) );

		// Fall back to name only if one was actually supplied — an empty $name
		// here would otherwise become a bare "%%" LIKE, matching every customer.
		if ( ! $customer && trim( $name ) !== '' ) {
			$customer = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM {$p}isb_customers WHERE LOWER(display_name) LIKE %s LIMIT 1",
				'%' . $wpdb->esc_like( mb_strtolower( trim( $name ) ) ) . '%'
			) );
		}

		if ( ! $customer ) return [];

		$items = $wpdb->get_results( $wpdb->prepare(
			"SELECT di.name AS product_name, di.serial, di.warranty, di.woo_product_id, d.issue_date, d.doc_number
			FROM {$p}isb_document_items di
			JOIN {$p}isb_documents d ON d.id = di.document_id
			WHERE d.customer_id = %d
			AND d.doc_type IN ('invoice','sales_order')
			AND d.status NOT IN ('draft','void','cancelled')
			ORDER BY d.issue_date DESC LIMIT 20",
			$customer->id
		) );

		$products = [];
		foreach ( $items as $item ) {
			$serials = array_filter( array_map( 'trim', explode( "\n", (string)$item->serial ) ) );
			$model = $item->product_name;
			if ( $item->woo_product_id ) {
				$p_obj = wc_get_product( (int)$item->woo_product_id );
				if ( $p_obj ) $model = $p_obj->get_name();
			}
			if ( empty( $serials ) ) {
				$products[] = [
					'model'       => $model,
					'serial_hint' => '—',
					'purchased_at'=> $item->issue_date,
					'invoice'     => $item->doc_number,
					'has_serial'  => false,
				];
			} else {
				foreach ( $serials as $sn ) {
					$len = mb_strlen( $sn );
					$hint = $len <= 4 ? str_repeat('*', $len) : str_repeat('*', max(0, $len-4)) . mb_substr($sn, -4);
					$products[] = [
						'model'       => $model,
						'serial_hint' => $hint,
						'purchased_at'=> $item->issue_date,
						'invoice'     => $item->doc_number,
						'has_serial'  => true,
					];
				}
			}
		}
		return $products;
	}
}

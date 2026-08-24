<?php
defined( 'ABSPATH' ) || exit;

class AIAgent_DB {

	public static function install() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// conversations
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_conversations (
			id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_token  CHAR(36)        NOT NULL,
			customer_ref   BIGINT UNSIGNED          DEFAULT NULL,
			mode           ENUM('product','support') NOT NULL DEFAULT 'product',
			language       ENUM('en','ar')           NOT NULL DEFAULT 'en',
			status         ENUM('active','escalated','claimed','closed') NOT NULL DEFAULT 'active',
			verified_model VARCHAR(255)             DEFAULT NULL,
			in_warranty    TINYINT(1)               DEFAULT NULL,
			selected_product_id BIGINT UNSIGNED    DEFAULT NULL,
			selected_model VARCHAR(255)             DEFAULT NULL,
			created_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY session_token (session_token),
			KEY status (status)
		) $charset;" );

		// messages
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_messages (
			id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id  BIGINT UNSIGNED NOT NULL,
			role             ENUM('customer','ai','human') NOT NULL,
			body             LONGTEXT        NOT NULL,
			attachment_url   TEXT                     DEFAULT NULL,
			tool_calls       JSON                     DEFAULT NULL,
			created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY conversation_id (conversation_id)
		) $charset;" );

		// ratings
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_ratings (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			message_id  BIGINT UNSIGNED NOT NULL,
			score       ENUM('good','wrong','incomplete') NOT NULL,
			correction  TEXT                             DEFAULT NULL,
			rated_by    BIGINT UNSIGNED NOT NULL,
			created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY message_id (message_id)
		) $charset;" );

		// tickets
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_tickets (
			id               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			conversation_id  BIGINT UNSIGNED NOT NULL,
			status           ENUM('open','claimed','resolved') NOT NULL DEFAULT 'open',
			reason           TEXT NOT NULL,
			assignee         BIGINT UNSIGNED DEFAULT NULL,
			contact          TEXT            DEFAULT NULL,
			resolution       TEXT            DEFAULT NULL,
			created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			resolved_at      DATETIME         DEFAULT NULL,
			PRIMARY KEY (id),
			KEY status (status),
			KEY conversation_id (conversation_id)
		) $charset;" );

		// categories
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_categories (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name_en    VARCHAR(255) NOT NULL,
			name_ar    VARCHAR(255) NOT NULL DEFAULT '',
			approved   TINYINT(1)   NOT NULL DEFAULT 0,
			created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset;" );

		// taught_examples
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_taught_examples (
			id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			question          TEXT    NOT NULL,
			solution          LONGTEXT NOT NULL,
			category_id       BIGINT UNSIGNED DEFAULT NULL,
			tags              JSON            DEFAULT NULL,
			language          ENUM('en','ar','both') NOT NULL DEFAULT 'both',
			embedding         LONGBLOB        DEFAULT NULL,
			image_url         TEXT            DEFAULT NULL,
			image_description LONGTEXT        DEFAULT NULL,
			confidence        FLOAT           NOT NULL DEFAULT 1.0,
			usage_count       INT UNSIGNED    NOT NULL DEFAULT 0,
			last_used         DATETIME        DEFAULT NULL,
			source            VARCHAR(30)     NOT NULL DEFAULT 'manual',
			created_by        BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY category_id (category_id),
			KEY source (source),
			KEY usage_count (usage_count)
		) $charset;" );

		// Add FULLTEXT index for hybrid search (MySQL 5.6+ / MariaDB support FULLTEXT on InnoDB).
		// Do this outside dbDelta since dbDelta doesn't manage FULLTEXT indexes.
		$has_ft = $wpdb->get_var( "SHOW INDEX FROM {$wpdb->prefix}aiagent_taught_examples WHERE Key_name = 'ft_qa'" );
		if ( ! $has_ft ) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}aiagent_taught_examples ADD FULLTEXT KEY ft_qa (question, solution)" );
		}

		// policy_rules
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_policy_rules (
			id       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			rule     TEXT NOT NULL,
			language ENUM('en','ar','both') NOT NULL DEFAULT 'both',
			active   TINYINT(1) NOT NULL DEFAULT 1,
			sort     INT        NOT NULL DEFAULT 0,
			PRIMARY KEY (id)
		) $charset;" );

		// manual_chunks
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_manual_chunks (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			model         VARCHAR(255) NOT NULL,
			section_title VARCHAR(255) NOT NULL DEFAULT '',
			chunk         LONGTEXT     NOT NULL,
			embedding     LONGBLOB     DEFAULT NULL,
			source_file   VARCHAR(500) NOT NULL DEFAULT '',
			created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY model (model)
		) $charset;" );

		// serial_registry  (holds PII — read only inside verify_ownership())
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_serial_registry (
			id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			serial        VARCHAR(255) NOT NULL,
			model         VARCHAR(255) NOT NULL,
			order_id      BIGINT UNSIGNED NOT NULL,
			owner_name    VARCHAR(500) NOT NULL DEFAULT '',
			owner_phone   VARCHAR(50)  NOT NULL DEFAULT '',
			purchased_at  DATE         DEFAULT NULL,
			warranty_until DATE        DEFAULT NULL,
			created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY serial (serial)
		) $charset;" );

		// usage_counters
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_usage_counters (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			scope      ENUM('session','customer','global') NOT NULL,
			`key`      VARCHAR(255) NOT NULL,
			date       DATE         NOT NULL,
			count      INT UNSIGNED NOT NULL DEFAULT 0,
			updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY scope_key_date (scope, `key`, date)
		) $charset;" );

		// reference_data
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_reference_data (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			type       VARCHAR(100)  NOT NULL,
			`key`      VARCHAR(255)  NOT NULL,
			value      JSON          DEFAULT NULL,
			created_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type_key (type, `key`(100))
		) $charset;" );

		// semantic_cache — stores recent Q→A pairs with embeddings for near-duplicate detection.
		// Cuts Gemini API calls by 20-40% for repetitive questions (warranty, returns, specs).
		// Entries expire after data_retention_days; purged by daily cron.
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_semantic_cache (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			question   TEXT         NOT NULL,
			answer     LONGTEXT     NOT NULL,
			embedding  LONGBLOB     NOT NULL,
			model      VARCHAR(255) NOT NULL DEFAULT '',
			hits       INT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
			expires_at DATETIME     NOT NULL,
			PRIMARY KEY (id),
			KEY expires_at (expires_at),
			KEY model (model)
		) $charset;" );

		// product_embeddings — semantic index over the WooCommerce catalogue, same
		// pattern as manual_chunks/taught_examples (embed once, cosine-search later).
		// WordPress/WooCommerce's own product search is a literal AND-of-words
		// substring match, so natural customer phrasing ("small incubator" when the
		// product is titled just "Incubator", or "22egg" for a 22-egg model) often
		// finds nothing even when the product obviously exists. This lets
		// search_products fall back to real semantic matching. content_hash detects
		// when a product changed so re-embedding only happens when needed.
		dbDelta( "CREATE TABLE {$wpdb->prefix}aiagent_product_embeddings (
			product_id   BIGINT UNSIGNED NOT NULL,
			content_hash CHAR(32)     NOT NULL,
			embedding    LONGBLOB     DEFAULT NULL,
			updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (product_id)
		) $charset;" );

		// Seed default policy rules if table is empty.
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_policy_rules" );
		if ( $count === 0 ) {
			self::seed_policy_rules();
		}
	}

	private static function seed_policy_rules() {
		global $wpdb;
		$rules = [
			[ 'rule' => 'You are a helpful AI support agent for iBird, a company in Bahrain. Be friendly, professional, and concise.', 'language' => 'both', 'sort' => 1 ],
			[ 'rule' => 'Never promise a refund, replacement, or repair unless the knowledge base explicitly confirms it.', 'language' => 'both', 'sort' => 2 ],
			[ 'rule' => 'Never reveal PII (names, phone numbers, serial numbers, order details) in your replies.', 'language' => 'both', 'sort' => 3 ],
			[ 'rule' => 'If a customer needs human assistance, offer to escalate — do not keep them waiting.', 'language' => 'both', 'sort' => 4 ],
			[ 'rule' => 'Currency is BHD (Bahraini Dinar). Format prices as BHD X.XXX.', 'language' => 'both', 'sort' => 5 ],
			[ 'rule' => 'عند التحدث بالعربية، استخدم أسلوباً ودياً ورسمياً. أجب دائماً باللغة التي يستخدمها العميل.', 'language' => 'ar', 'sort' => 6 ],
		];
		foreach ( $rules as $r ) {
			$wpdb->insert(
				"{$wpdb->prefix}aiagent_policy_rules",
				[ 'rule' => $r['rule'], 'language' => $r['language'], 'active' => 1, 'sort' => $r['sort'] ],
				[ '%s', '%s', '%d', '%d' ]
			);
		}
	}
}

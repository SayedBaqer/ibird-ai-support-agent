<?php
defined( 'ABSPATH' ) || exit;

class AIAgent_Admin {

	public static function init(): void {
		add_action( 'admin_menu',              [ __CLASS__, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts',   [ __CLASS__, 'enqueue_assets' ] );

		// Form submissions.
		add_action( 'admin_post_aiagent_save_settings',     [ __CLASS__, 'save_settings' ] );
		add_action( 'admin_post_aiagent_add_category',      [ __CLASS__, 'add_category' ] );
		add_action( 'admin_post_aiagent_approve_category',  [ __CLASS__, 'approve_category' ] );

		// AJAX: PDF upload (server-side text extraction).
		add_action( 'wp_ajax_aiagent_upload_manual', [ __CLASS__, 'ajax_upload_manual' ] );
	}

	// ── Menus ─────────────────────────────────────────────────────────────────

	public static function register_menus(): void {
		add_menu_page(
			__( 'AI Support Agent', 'ai-support-agent' ),
			__( 'AI Agent', 'ai-support-agent' ),
			'manage_options',
			'aiagent',
			[ __CLASS__, 'page_conversations' ],
			'dashicons-format-chat',
			56
		);

		add_submenu_page( 'aiagent', __( 'Conversations', 'ai-support-agent' ), __( 'Conversations', 'ai-support-agent' ), 'manage_options', 'aiagent',                 [ __CLASS__, 'page_conversations' ] );
		add_submenu_page( 'aiagent', __( 'Agent Workspace', 'ai-support-agent' ), __( '⚡ Workspace', 'ai-support-agent' ), 'manage_options', 'aiagent-workspace', [ __CLASS__, 'page_workspace' ] );
		add_submenu_page( 'aiagent', __( 'Tickets',       'ai-support-agent' ), __( 'Tickets',       'ai-support-agent' ), 'manage_options', 'aiagent-tickets',         [ __CLASS__, 'page_tickets' ] );
		add_submenu_page( 'aiagent', __( 'Teach AI',      'ai-support-agent' ), __( 'Teach AI',      'ai-support-agent' ), 'manage_options', 'aiagent-teach',           [ __CLASS__, 'page_teach' ] );
		add_submenu_page( 'aiagent', __( 'Manuals',       'ai-support-agent' ), __( 'Manuals',       'ai-support-agent' ), 'manage_options', 'aiagent-manuals',         [ __CLASS__, 'page_manuals' ] );
		add_submenu_page( 'aiagent', __( 'Products',      'ai-support-agent' ), __( 'Products',      'ai-support-agent' ), 'manage_options', 'aiagent-products',        [ __CLASS__, 'page_products' ] );
		add_submenu_page( 'aiagent', __( 'Categories',    'ai-support-agent' ), __( 'Categories',    'ai-support-agent' ), 'manage_options', 'aiagent-categories',      [ __CLASS__, 'page_categories' ] );
		add_submenu_page( 'aiagent', __( 'Serial Registry', 'ai-support-agent' ), __( 'Serials',     'ai-support-agent' ), 'manage_options', 'aiagent-serials',         [ __CLASS__, 'page_serials' ] );
		add_submenu_page( 'aiagent', __( 'Analytics',     'ai-support-agent' ), __( 'Analytics',     'ai-support-agent' ), 'manage_options', 'aiagent-analytics',       [ __CLASS__, 'page_analytics' ] );
		add_submenu_page( 'aiagent', __( 'Settings',      'ai-support-agent' ), __( 'Settings',      'ai-support-agent' ), 'manage_options', 'aiagent-settings',        [ __CLASS__, 'page_settings' ] );
	}

	// ── Assets ────────────────────────────────────────────────────────────────

	public static function enqueue_assets( string $hook ): void {
		if ( strpos( $hook, 'aiagent' ) === false ) {
			return;
		}
		wp_enqueue_style(  'aiagent-admin', AIAGENT_URL . 'admin/assets/admin.css', [], AIAGENT_VERSION );
		wp_enqueue_script( 'aiagent-admin', AIAGENT_URL . 'admin/assets/admin.js',  [], AIAGENT_VERSION, true );
	}

	// ── Pages ─────────────────────────────────────────────────────────────────

	public static function page_settings(): void {
		self::require_cap();
		$settings = aiagent_settings();
		include AIAGENT_DIR . 'admin/views/settings.php';
	}

	public static function page_conversations(): void {
		self::require_cap();
		global $wpdb;

		$per_page = 20;
		$page     = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$offset   = ( $page - 1 ) * $per_page;

		$conversations = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}aiagent_conversations ORDER BY updated_at DESC LIMIT %d OFFSET %d",
			$per_page, $offset
		) );
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_conversations" );

		$detail_id   = absint( $_GET['detail'] ?? 0 );
		$detail_msgs = [];
		if ( $detail_id ) {
			$detail_msgs = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}aiagent_messages WHERE conversation_id = %d ORDER BY created_at ASC",
				$detail_id
			) );
		}

		include AIAGENT_DIR . 'admin/views/conversations.php';
	}

	public static function page_tickets(): void {
		self::require_cap();
		global $wpdb;
		$tickets = $wpdb->get_results(
			"SELECT t.*, c.session_token FROM {$wpdb->prefix}aiagent_tickets t
			LEFT JOIN {$wpdb->prefix}aiagent_conversations c ON c.id = t.conversation_id
			ORDER BY t.created_at DESC LIMIT 50"
		);
		include AIAGENT_DIR . 'admin/views/tickets.php';
	}

	public static function page_teach(): void {
		self::require_cap();
		include AIAGENT_DIR . 'admin/views/teach.php';
	}

	public static function page_manuals(): void {
		self::require_cap();
		include AIAGENT_DIR . 'admin/views/manuals.php';
	}

	public static function page_products(): void {
		self::require_cap();
		include AIAGENT_DIR . 'admin/views/products.php';
	}

	public static function page_categories(): void {
		self::require_cap();
		include AIAGENT_DIR . 'admin/views/categories.php';
	}

	public static function page_serials(): void {
		self::require_cap();
		include AIAGENT_DIR . 'admin/views/serials.php';
	}

	public static function page_analytics(): void {
		self::require_cap();
		global $wpdb;

		$today   = gmdate( 'Y-m-d' );
		$week_ago = gmdate( 'Y-m-d', strtotime( '-6 days' ) );

		// ── Summary cards ─────────────────────────────────────────────────────
		$stats = [];
		$stats['total_conversations'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_conversations" );
		$stats['today_conversations'] = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_conversations WHERE DATE(created_at) = %s", $today
		) );
		$stats['total_messages']   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_messages" );
		$stats['total_escalated']  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_conversations WHERE status = 'escalated'" );
		$stats['open_tickets']     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_tickets WHERE status IN ('open','claimed')" );
		$stats['taught_examples']  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_taught_examples" );
		$stats['manual_chunks']    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}aiagent_manual_chunks" );

		// Deflection rate = conversations that never escalated / total.
		$deflected = $stats['total_conversations'] - $stats['total_escalated'];
		$stats['deflection_rate'] = $stats['total_conversations'] > 0
			? round( $deflected / $stats['total_conversations'] * 100 )
			: 0;

		// ── Rating breakdown ─────────────────────────────────────────────────
		$ratings = $wpdb->get_results(
			"SELECT score, COUNT(*) AS cnt FROM {$wpdb->prefix}aiagent_ratings GROUP BY score"
		);
		$rating_map = [ 'good' => 0, 'wrong' => 0, 'incomplete' => 0 ];
		foreach ( $ratings as $r ) {
			$rating_map[ $r->score ] = (int) $r->cnt;
		}
		$total_ratings = array_sum( $rating_map );

		// ── Mode distribution ────────────────────────────────────────────────
		$modes = $wpdb->get_results(
			"SELECT mode, COUNT(*) AS cnt FROM {$wpdb->prefix}aiagent_conversations GROUP BY mode"
		);
		$mode_map = [ 'product' => 0, 'support' => 0 ];
		foreach ( $modes as $m ) {
			$mode_map[ $m->mode ] = (int) $m->cnt;
		}

		// ── Daily usage — last 7 days ─────────────────────────────────────────
		$daily_usage = $wpdb->get_results( $wpdb->prepare(
			"SELECT `key` AS day, SUM(count) AS total
			FROM {$wpdb->prefix}aiagent_usage_counters
			WHERE scope = 'global' AND `date` >= %s
			GROUP BY `key` ORDER BY `key` ASC",
			$week_ago
		) );
		$usage_by_day = [];
		for ( $i = 6; $i >= 0; $i-- ) {
			$d = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
			$usage_by_day[ $d ] = 0;
		}
		foreach ( $daily_usage as $row ) {
			$usage_by_day[ $row->day ] = (int) $row->total;
		}
		$usage_max = max( 1, max( $usage_by_day ) );

		// ── Top categories ────────────────────────────────────────────────────
		$top_cats = $wpdb->get_results(
			"SELECT c.name_en, c.name_ar, COUNT(te.id) AS cnt
			FROM {$wpdb->prefix}aiagent_categories c
			JOIN {$wpdb->prefix}aiagent_taught_examples te ON te.category_id = c.id
			GROUP BY c.id ORDER BY cnt DESC LIMIT 8"
		);

		// ── Rating trend — last 30 days ────────────────────────────────────────
		$rating_trend = $wpdb->get_results(
			"SELECT DATE(created_at) AS day, score, COUNT(*) AS cnt
			FROM {$wpdb->prefix}aiagent_ratings
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			GROUP BY day, score ORDER BY day ASC"
		);

		include AIAGENT_DIR . 'admin/views/analytics.php';
	}

	// ── Settings form post ────────────────────────────────────────────────────

	public static function save_settings(): void {
		self::require_cap();
		check_admin_referer( 'aiagent_save_settings' );

		$current = aiagent_settings();
		$post    = wp_unslash( $_POST );

		$updated = array_merge( $current, [
			// Primary — Gemini.
			'api_key'             => sanitize_text_field( $post['api_key']         ?? '' ),
			'model_reply'         => sanitize_text_field( $post['model_reply']     ?? $current['model_reply'] ),
			'model_classify'      => sanitize_text_field( $post['model_classify']  ?? $current['model_classify'] ),
			'model_embed'         => sanitize_text_field( $post['model_embed']     ?? $current['model_embed'] ),
			// Fallback — Groq.
			'groq_api_key'        => sanitize_text_field( $post['groq_api_key']   ?? '' ),
			'groq_model'          => sanitize_text_field( $post['groq_model']      ?? $current['groq_model'] ),
			// Fallback — Cerebras.
			'cerebras_api_key'    => sanitize_text_field( $post['cerebras_api_key'] ?? '' ),
			'cerebras_model'      => sanitize_text_field( $post['cerebras_model']   ?? $current['cerebras_model'] ),
			// Gemini advanced features (checkboxes: present = true, absent = false).
			'enable_search_grounding' => isset( $post['enable_search_grounding'] ) && $post['enable_search_grounding'] === '1',
			'enable_thinking'         => isset( $post['enable_thinking'] )         && $post['enable_thinking']         === '1',
			'thinking_budget'         => max( 256, min( 8192, absint( $post['thinking_budget'] ?? 1024 ) ) ),
			// GitHub auto-update.
			'github_token'            => sanitize_text_field( wp_unslash( $post['github_token'] ?? $current['github_token'] ?? '' ) ),
			// Throttle.
			'daily_cap'           => absint( $post['daily_cap']        ?? $current['daily_cap'] ),
			'session_cap'         => absint( $post['session_cap']      ?? $current['session_cap'] ),
			'per_customer_cap'    => absint( $post['per_customer_cap'] ?? $current['per_customer_cap'] ),
			// Widget.
			'fallback_message_en' => sanitize_textarea_field( $post['fallback_message_en'] ?? $current['fallback_message_en'] ),
			'fallback_message_ar' => sanitize_textarea_field( $post['fallback_message_ar'] ?? $current['fallback_message_ar'] ),
			'widget_title_en'     => sanitize_text_field( $post['widget_title_en'] ?? $current['widget_title_en'] ),
			'widget_title_ar'     => sanitize_text_field( $post['widget_title_ar'] ?? $current['widget_title_ar'] ),
			'widget_position'     => in_array( $post['widget_position'] ?? '', [ 'bottom-right', 'bottom-left' ], true )
				? $post['widget_position'] : 'bottom-right',
		] );

		update_option( 'aiagent_settings', $updated );
		wp_safe_redirect( admin_url( 'admin.php?page=aiagent-settings&saved=1' ) );
		exit;
	}

	// ── Category form posts ───────────────────────────────────────────────────

	public static function add_category(): void {
		self::require_cap();
		check_admin_referer( 'aiagent_add_category' );

		$name_en = sanitize_text_field( wp_unslash( $_POST['name_en'] ?? '' ) );
		$name_ar = sanitize_text_field( wp_unslash( $_POST['name_ar'] ?? '' ) );

		if ( $name_en !== '' ) {
			global $wpdb;
			$wpdb->insert(
				"{$wpdb->prefix}aiagent_categories",
				[ 'name_en' => $name_en, 'name_ar' => $name_ar, 'approved' => 1 ],
				[ '%s', '%s', '%d' ]
			);
		}

		wp_safe_redirect( admin_url( 'admin.php?page=aiagent-categories' ) );
		exit;
	}

	public static function approve_category(): void {
		self::require_cap();
		$id = absint( $_GET['id'] ?? 0 );
		check_admin_referer( 'aiagent_approve_category_' . $id );

		global $wpdb;
		$wpdb->update(
			"{$wpdb->prefix}aiagent_categories",
			[ 'approved' => 1 ],
			[ 'id'       => $id ],
			[ '%d' ], [ '%d' ]
		);

		wp_safe_redirect( admin_url( 'admin.php?page=aiagent-categories&approved=1' ) );
		exit;
	}

	// ── AJAX: PDF upload + text extraction ────────────────────────────────────

	public static function ajax_upload_manual(): void {
		check_ajax_referer( 'aiagent_manual_upload' );
		self::require_cap();

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( [ 'error' => 'No file uploaded.' ] );
		}

		$file    = $_FILES['file'];
		$ext     = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		$is_pdf  = ( $file['type'] === 'application/pdf' || $ext === 'pdf' );
		$img_mimes = [ 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' ];
		$is_img  = in_array( $file['type'], $img_mimes, true );

		if ( ! $is_pdf && ! $is_img ) {
			wp_send_json_error( [ 'error' => 'Only PDF and image files are accepted.' ] );
		}

		// Save temporarily.
		$upload_dir = wp_upload_dir();
		$dest       = trailingslashit( $upload_dir['basedir'] ) . 'aiagent-manuals/';
		wp_mkdir_p( $dest );
		$filename  = sanitize_file_name( $file['name'] );
		$dest_file = $dest . $filename;

		if ( ! move_uploaded_file( $file['tmp_name'], $dest_file ) ) {
			wp_send_json_error( [ 'error' => 'Failed to save uploaded file.' ] );
		}

		$mime = $is_pdf ? 'application/pdf' : $file['type'];

		// ── Upload to Gemini File API ─────────────────────────────────────────
		// Gemini reads PDFs natively (text + images + tables, including scanned docs).
		// Native embedded text in PDFs is NOT charged as input tokens.
		$uploaded = AIAgent_File_API::upload( $dest_file, $mime, $filename );
		@unlink( $dest_file ); // Clean up local copy — Gemini has it now.

		if ( $uploaded['error'] ) {
			wp_send_json_error( [ 'error' => 'Could not upload to Gemini: ' . $uploaded['message'] ] );
		}

		// ── Ask Gemini to extract all knowledge from the document ─────────────
		$prompt = $is_pdf
			? "Extract ALL content from this document needed for a product support knowledge base.\n\n"
			  . "Output as clean, readable plain text:\n"
			  . "- Preserve section headings and hierarchy\n"
			  . "- Include ALL product specs, features, installation steps, error codes and meanings, "
			  . "troubleshooting procedures, maintenance instructions, safety warnings, and any Q&A content\n"
			  . "- Transcribe tables as readable text (e.g. 'Error E5: Brush jam — clear blockage')\n"
			  . "- Describe diagrams briefly (e.g. 'Diagram: exploded view of motor assembly')\n"
			  . "- Do NOT summarize or omit information — include everything\n"
			  . "- Do NOT add your own commentary"
			: "Describe this product image in full detail for a support knowledge base:\n"
			  . "- Product name and model number (read any labels/text)\n"
			  . "- All visible components, ports, buttons, display\n"
			  . "- Any error codes, status indicators, or warning labels\n"
			  . "- Relevant specifications visible on labels";

		$result = AIAgent_File_API::analyze( $uploaded['file_uri'], $mime, $prompt, [
			'thinking'        => true,
			'thinking_budget' => 2048,
		] );

		// Delete the remote file — we have the text now, no need to keep it.
		AIAgent_File_API::delete( $uploaded['file_name'] );

		if ( $result['error'] || $result['description'] === '' ) {
			wp_send_json_error( [
				'error' => 'Gemini could not extract content from this file. '
				         . 'Check that the Gemini API key is valid and the file is not empty or corrupted.',
			] );
		}

		wp_send_json_success( [ 'text' => $result['description'] ] );
	}

	public static function page_workspace(): void {
		self::require_cap();
		$nonce        = wp_create_nonce( 'wp_rest' );
		$rest_url     = esc_url_raw( rest_url( 'aiagent/v1' ) );
		$ajax_url     = admin_url( 'admin-ajax.php' );
		$upload_nonce = wp_create_nonce( 'aiagent_upload_attachment' );
		include AIAGENT_DIR . 'admin/views/workspace.php';
	}

	// ── Internal ──────────────────────────────────────────────────────────────

	private static function require_cap(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'ai-support-agent' ) );
		}
	}
}

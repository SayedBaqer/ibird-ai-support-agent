<?php
/**
 * Plugin Name: AI Support Agent
 * Plugin URI:  https://ibird.bh
 * Description: Self-improving AI support agent for WooCommerce — bilingual EN/AR, RAG knowledge base, Mode A product Q&A + Mode B verified support.
 * Version:     1.10.2
 * Author:      iBird
 * Text Domain: ai-support-agent
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * WC requires at least: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'AIAGENT_VERSION',    '1.10.2' );
define( 'AIAGENT_FILE',       __FILE__ );
define( 'AIAGENT_DIR',        plugin_dir_path( __FILE__ ) );
define( 'AIAGENT_URL',        plugin_dir_url( __FILE__ ) );
define( 'AIAGENT_DB_VER',     '9' );
define( 'AIAGENT_GITHUB_REPO', 'SayedBaqer/ibird-ai-support-agent' );

// ── Autoload includes ────────────────────────────────────────────────────────
require_once AIAGENT_DIR . 'includes/class-aiagent-db.php';
require_once AIAGENT_DIR . 'includes/class-aiagent-updater.php';
require_once AIAGENT_DIR . 'includes/class-aiagent-i18n.php';
require_once AIAGENT_DIR . 'includes/class-aiagent-file-api.php';
require_once AIAGENT_DIR . 'includes/class-aiagent-llm.php';
require_once AIAGENT_DIR . 'includes/class-aiagent-throttle.php';
require_once AIAGENT_DIR . 'includes/class-aiagent-rag.php';
require_once AIAGENT_DIR . 'includes/class-aiagent-intent.php';
require_once AIAGENT_DIR . 'includes/class-aiagent-verify.php';
require_once AIAGENT_DIR . 'includes/class-aiagent-tools.php';
require_once AIAGENT_DIR . 'includes/class-aiagent-rest.php';
require_once AIAGENT_DIR . 'public/class-aiagent-widget.php';

if ( is_admin() ) {
	require_once AIAGENT_DIR . 'admin/class-aiagent-admin.php';
}

// ── Activation ───────────────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'aiagent_activate' );
function aiagent_activate() {
	if ( ! aiagent_woocommerce_active() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			esc_html__( 'AI Support Agent requires WooCommerce to be installed and active.', 'ai-support-agent' ),
			esc_html__( 'Plugin Activation Error', 'ai-support-agent' ),
			[ 'back_link' => true ]
		);
	}
	AIAgent_DB::install();
	update_option( 'aiagent_db_version', AIAGENT_DB_VER );
	if ( ! get_option( 'aiagent_settings' ) ) {
		update_option( 'aiagent_settings', aiagent_default_settings() );
	}
	aiagent_schedule_retention();
}

register_deactivation_hook( __FILE__, 'aiagent_deactivate' );
function aiagent_deactivate() {
	aiagent_unschedule_retention();
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function aiagent_woocommerce_active() {
	return class_exists( 'WooCommerce' );
}

function aiagent_default_settings() {
	return [
		// Primary provider — Gemini.
		'api_key'             => '',
		'model_reply'         => 'gemini-2.5-flash',
		'model_classify'      => 'gemini-2.5-flash-lite',
		'model_embed'         => 'gemini-embedding-2',
		// Fallback provider 1 — Groq (OpenAI-compatible).
		'groq_api_key'        => '',
		'groq_model'          => 'llama-3.3-70b-versatile',
		// Fallback provider 2 — Cerebras (OpenAI-compatible).
		'cerebras_api_key'    => '',
		'cerebras_model'      => 'llama-3.3-70b',
		// Gemini advanced features (Gemini-only; ignored by Groq/Cerebras fallback).
		'enable_search_grounding' => false,
		'enable_thinking'         => true,
		'thinking_budget'         => 2048,
		// GitHub auto-update (private repo PAT with contents:read scope).
		'github_token'            => '',
		// Escalation notifications.
		'whatsapp_number'         => '',  // WhatsApp number to notify on escalation (intl format: +973XXXXXXXX).
		'notify_email'            => '',  // Email to CC on new tickets (leave blank to use admin email).
		'webhook_secret'          => '',  // Shared secret for N8N / external webhook auth.
		// Throttle.
		'daily_cap'           => 1200,
		'session_cap'         => 20,
		'per_customer_cap'    => 30,
		'languages'           => [ 'en', 'ar' ],
		// Data retention — PDPL compliance (Bahrain): auto-purge chat logs older than N days.
		'data_retention_days' => 90,
		// Widget.
		'fallback_message_en' => 'Thank you for reaching out! Our team will follow up with you shortly.',
		'fallback_message_ar' => 'شكراً للتواصل معنا! سيتواصل معك فريقنا قريباً.',
		'widget_position'     => 'bottom-right',
		'widget_title_en'     => 'iBird Support',
		'widget_title_ar'     => 'دعم iBird',
	];
}

function aiagent_settings() {
	$defaults = aiagent_default_settings();
	$saved    = get_option( 'aiagent_settings', [] );
	return wp_parse_args( $saved, $defaults );
}

// ── Boot ─────────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', 'aiagent_boot' );
function aiagent_boot() {
	if ( ! aiagent_woocommerce_active() ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'AI Support Agent requires WooCommerce. Please install and activate WooCommerce.', 'ai-support-agent' );
			echo '</p></div>';
		} );
		return;
	}

	// Maybe upgrade tables on version bump.
	if ( get_option( 'aiagent_db_version' ) !== AIAGENT_DB_VER ) {
		AIAgent_DB::install();
		update_option( 'aiagent_db_version', AIAGENT_DB_VER );
		// New product_embeddings table — index the existing catalogue right away
		// instead of waiting for products to be individually re-saved.
		wp_schedule_single_event( time() + 10, 'aiagent_embed_products_cron' );
	}

	load_plugin_textdomain( 'ai-support-agent', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	AIAgent_REST::init();
	AIAgent_Widget::init();
	( new AIAgent_Updater() )->init();

	if ( is_admin() ) {
		AIAgent_Admin::init();
	}
}

// ── Data Retention Cron (PDPL / Bahrain compliance) ─────────────────────────
// Auto-purges chat logs and semantic cache entries older than data_retention_days.
// Runs once per day via WP-Cron; retention period is configurable in Settings.

register_activation_hook( __FILE__, 'aiagent_schedule_retention' );
register_deactivation_hook( __FILE__, 'aiagent_unschedule_retention' );

function aiagent_schedule_retention(): void {
	if ( ! wp_next_scheduled( 'aiagent_retention_cron' ) ) {
		wp_schedule_event( time(), 'daily', 'aiagent_retention_cron' );
	}
}

function aiagent_unschedule_retention(): void {
	$ts = wp_next_scheduled( 'aiagent_retention_cron' );
	if ( $ts ) wp_unschedule_event( $ts, 'aiagent_retention_cron' );
}

add_action( 'aiagent_retention_cron', 'aiagent_run_retention' );
function aiagent_run_retention(): void {
	global $wpdb;
	$days = (int) ( aiagent_settings()['data_retention_days'] ?? 90 );
	if ( $days <= 0 ) return;

	// Purge old conversations (and their messages/tickets via the JOIN).
	$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

	$old_conv_ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT id FROM {$wpdb->prefix}aiagent_conversations WHERE created_at < %s",
		$cutoff
	) );

	if ( ! empty( $old_conv_ids ) ) {
		$ids_in = implode( ',', array_map( 'absint', $old_conv_ids ) );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}aiagent_messages WHERE conversation_id IN ({$ids_in})" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}aiagent_tickets  WHERE conversation_id IN ({$ids_in})" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}aiagent_conversations WHERE id IN ({$ids_in})" );
	}

	// Purge expired semantic cache entries.
	$wpdb->query( "DELETE FROM {$wpdb->prefix}aiagent_semantic_cache WHERE expires_at < NOW()" );
}

// ── Background embedding cron jobs ───────────────────────────────────────────
// After a manual is ingested, chunks and Q&A examples are stored immediately
// (no API calls). These cron jobs run seconds later to fill in the embeddings.
// They reschedule themselves until all rows are embedded.

add_action( 'aiagent_embed_chunks_cron', 'aiagent_run_embed_chunks' );
function aiagent_run_embed_chunks(): void {
	@set_time_limit( 120 );
	if ( class_exists( 'AIAgent_RAG' ) ) {
		AIAgent_RAG::embed_pending_chunks( 40 );
	}
}

add_action( 'aiagent_embed_examples_cron', 'aiagent_run_embed_examples' );
function aiagent_run_embed_examples(): void {
	@set_time_limit( 120 );
	if ( class_exists( 'AIAgent_RAG' ) ) {
		AIAgent_RAG::embed_pending_examples( 30 );
	}
}

// ── Product catalogue semantic index ─────────────────────────────────────────
// Keeps a searchable embedding for every published product so search_products
// can fall back to real semantic matching when WooCommerce's literal keyword
// search finds nothing (see AIAgent_RAG::search_products_semantic).

add_action( 'aiagent_embed_products_cron', 'aiagent_run_embed_products' );
function aiagent_run_embed_products(): void {
	@set_time_limit( 120 );
	if ( class_exists( 'AIAgent_RAG' ) ) {
		AIAgent_RAG::embed_pending_products( 25 );
	}
}

// Re-index a product the moment it's created/edited, instead of waiting for a
// customer to search for it against a stale/missing embedding.
add_action( 'woocommerce_update_product', 'aiagent_invalidate_product_embedding' );
add_action( 'woocommerce_new_product',    'aiagent_invalidate_product_embedding' );
function aiagent_invalidate_product_embedding( $product_id ): void {
	if ( ! class_exists( 'AIAgent_RAG' ) ) return;
	AIAgent_RAG::invalidate_product_embedding( (int) $product_id );
	wp_schedule_single_event( time() + 5, 'aiagent_embed_products_cron' );
}

// ── GitHub updater AJAX handlers ─────────────────────────────────────────────
// The updater logic lives in AIAgent_Updater (includes/class-aiagent-updater.php).
// These handlers are the bridge between the Updates admin page and that class.

add_action( 'wp_ajax_aiagent_publish_release', 'aiagent_ajax_publish_release' );
function aiagent_ajax_publish_release(): void {
	@set_time_limit( 0 );
	@ignore_user_abort( true );
	check_ajax_referer( 'aiagent_nonce', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( [ 'message' => 'Permission denied.' ] );
	wp_send_json_success( AIAgent_Updater::publish_github_release() );
}

add_action( 'wp_ajax_aiagent_update_now', 'aiagent_ajax_update_now' );
function aiagent_ajax_update_now(): void {
	check_ajax_referer( 'aiagent_nonce', 'nonce' );
	if ( ! current_user_can( 'update_plugins' ) ) wp_send_json_error( [ 'message' => 'Permission denied.' ] );
	wp_send_json_success( AIAgent_Updater::update_now() );
}

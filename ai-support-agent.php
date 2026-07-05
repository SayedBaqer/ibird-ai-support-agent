<?php
/**
 * Plugin Name: AI Support Agent
 * Plugin URI:  https://ibird.bh
 * Description: Self-improving AI support agent for WooCommerce — bilingual EN/AR, RAG knowledge base, Mode A product Q&A + Mode B verified support.
 * Version:     1.0.8
 * Author:      iBird
 * Text Domain: ai-support-agent
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * WC requires at least: 7.0
 */

defined( 'ABSPATH' ) || exit;

define( 'AIAGENT_VERSION',    '1.2.0' );
define( 'AIAGENT_FILE',       __FILE__ );
define( 'AIAGENT_DIR',        plugin_dir_path( __FILE__ ) );
define( 'AIAGENT_URL',        plugin_dir_url( __FILE__ ) );
define( 'AIAGENT_DB_VER',     '4' );
define( 'AIAGENT_GITHUB_REPO', 'SayedBaqer/ibird-ai-support-agent' );

// ── Autoload includes ────────────────────────────────────────────────────────
require_once AIAGENT_DIR . 'includes/class-aiagent-db.php';
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
	// Seed default settings if not present.
	if ( ! get_option( 'aiagent_settings' ) ) {
		update_option( 'aiagent_settings', aiagent_default_settings() );
	}
}

// ── Deactivation ─────────────────────────────────────────────────────────────
register_deactivation_hook( __FILE__, 'aiagent_deactivate' );
function aiagent_deactivate() {
	// Nothing destructive on deactivate; tables remain.
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
		'enable_thinking'         => false,
		'thinking_budget'         => 1024,
		// GitHub auto-update (private repo PAT with contents:read scope).
		'github_token'            => '',
		// Escalation notifications.
		'whatsapp_number'         => '',  // WhatsApp number to notify on escalation (intl format: +973XXXXXXXX).
		'notify_email'            => '',  // Email to CC on new tickets (leave blank to use admin email).
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
	}

	load_plugin_textdomain( 'ai-support-agent', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	AIAgent_REST::init();
	AIAgent_Widget::init();

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

// ── GitHub Auto-Updater ───────────────────────────────────────────────────────
// Polls the GitHub Releases API (once per hour, cached in WP transient).
// When a new version tag is found, WordPress shows "Update available" in the
// Plugins list — click Update to fetch and install in place, no reinstall needed.
// Requires a GitHub PAT with contents:read scope for private repos (set in Settings).

add_filter( 'pre_set_site_transient_update_plugins', 'aiagent_github_update_check' );
function aiagent_github_update_check( $transient ) {
	if ( empty( $transient->checked ) ) return $transient;

	$settings = aiagent_settings();
	$token    = trim( $settings['github_token'] ?? '' );
	$slug     = plugin_basename( AIAGENT_FILE );

	// Cache result for 1 hour to avoid hammering the GitHub API.
	$cache_key = 'aiagent_github_release';
	$release   = get_transient( $cache_key );

	if ( $release === false ) {
		$api_url = 'https://api.github.com/repos/' . AIAGENT_GITHUB_REPO . '/releases/latest';
		$headers = [
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
			'Accept'     => 'application/vnd.github+json',
		];
		if ( $token !== '' ) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}

		$response = wp_remote_get( $api_url, [ 'headers' => $headers, 'timeout' => 10 ] );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			set_transient( $cache_key, 'error', HOUR_IN_SECONDS );
			return $transient;
		}

		$release = json_decode( wp_remote_retrieve_body( $response ), true );
		set_transient( $cache_key, $release, HOUR_IN_SECONDS );
	}

	if ( ! is_array( $release ) || empty( $release['tag_name'] ) ) return $transient;

	$latest_version = ltrim( $release['tag_name'], 'v' );

	if ( version_compare( $latest_version, AIAGENT_VERSION, '<=' ) ) return $transient;

	// Find the zip asset in the release.
	$download_url = '';
	foreach ( $release['assets'] ?? [] as $asset ) {
		if ( str_ends_with( $asset['name'], '.zip' ) ) {
			$download_url = $asset['browser_download_url'];
			break;
		}
	}

	// For private repos, browser_download_url requires auth — use the API download URL instead.
	if ( $download_url === '' && ! empty( $release['assets'][0]['url'] ) ) {
		$download_url = $release['assets'][0]['url'];
	}

	if ( $download_url === '' ) return $transient;

	// If the repo is private, inject the token into the download URL via a WP filter.
	if ( $token !== '' ) {
		add_filter( 'upgrader_pre_download', function ( $reply, $package, $upgrader ) use ( $token, $download_url ) {
			if ( strpos( $package, 'api.github.com' ) !== false || $package === $download_url ) {
				// Download the asset via the API with auth, save to a temp file,
				// then hand WordPress the local path.
				$tmp  = download_url( $package, 300, true ); // standard WP download (no auth)
				if ( is_wp_error( $tmp ) ) {
					// Auth-required download for private asset.
					$resp = wp_remote_get( $package, [
						'headers' => [
							'Authorization' => 'Bearer ' . $token,
							'Accept'        => 'application/octet-stream',
							'User-Agent'    => 'WordPress',
						],
						'timeout' => 120,
						'stream'  => true,
						'filename'=> wp_tempnam( 'aiagent_update' ),
					] );
					if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) === 200 ) {
						return $resp['filename'];
					}
				}
				return $tmp;
			}
			return $reply;
		}, 10, 3 );
	}

	$transient->response[ $slug ] = (object) [
		'slug'        => dirname( $slug ),
		'plugin'      => $slug,
		'new_version' => $latest_version,
		'url'         => 'https://github.com/' . AIAGENT_GITHUB_REPO,
		'package'     => $download_url,
	];

	return $transient;
}

// Clear the update cache when settings are saved (in case token was just added).
add_action( 'update_option_aiagent_settings', function () {
	delete_transient( 'aiagent_github_release' );
} );

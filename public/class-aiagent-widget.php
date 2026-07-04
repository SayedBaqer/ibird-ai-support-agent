<?php
defined( 'ABSPATH' ) || exit;

class AIAgent_Widget {

	public static function init(): void {
		// Panel served via WordPress AJAX — works on every WP install, no routing conflicts.
		add_action( 'wp_ajax_aiagent_panel',                    [ __CLASS__, 'serve_panel' ] );
		add_action( 'wp_ajax_nopriv_aiagent_panel',             [ __CLASS__, 'serve_panel' ] );
		add_action( 'wp_enqueue_scripts',                       [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'wp_footer',                                [ __CLASS__, 'render_root' ], 100 );
		add_action( 'wp_body_open',                             [ __CLASS__, 'render_root' ] );
		add_shortcode( 'aiagent_chat',                          [ __CLASS__, 'shortcode' ] );
		add_action( 'wp_ajax_aiagent_upload_attachment',        [ __CLASS__, 'ajax_upload_attachment' ] );
		add_action( 'wp_ajax_nopriv_aiagent_upload_attachment', [ __CLASS__, 'ajax_upload_attachment' ] );
	}

	/**
	 * Serve the chat panel HTML via wp_ajax (public, no login required).
	 * Using admin-ajax.php guarantees WordPress is fully booted, nonces work,
	 * and no theme routing interferes.
	 */
	public static function serve_panel(): void {
		$settings     = aiagent_settings();
		$nonce        = wp_create_nonce( 'wp_rest' );
		$rest_url     = rest_url( 'aiagent/v1' );
		$ajax_url     = admin_url( 'admin-ajax.php' );
		$upload_nonce = wp_create_nonce( 'aiagent_upload_attachment' );

		// Output HTML — must send headers before any output.
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'Content-Security-Policy: frame-ancestors \'self\'' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate' );
		header( 'Pragma: no-cache' );

		include AIAGENT_DIR . 'public/views/panel.php';
		wp_die(); // Clean exit; runs shutdown hooks.
	}

	public static function enqueue_assets(): void {
		$settings = aiagent_settings();

		// PWA manifest + service worker registration.
		add_action( 'wp_head', function () {
			echo '<link rel="manifest" href="' . esc_url( AIAGENT_URL . 'public/assets/manifest.json' ) . '">' . "\n";
		} );
		add_action( 'wp_footer', function () {
			echo '<script>if("serviceWorker" in navigator){navigator.serviceWorker.register("'
				. esc_js( AIAGENT_URL . 'public/assets/sw.js' )
				. '",{scope:"/"}).catch(function(){});}</script>' . "\n";
		}, 5 );

		// Loader script only — no external CSS (all panel CSS is inside the iframe).
		wp_enqueue_script(
			'aiagent-widget',
			AIAGENT_URL . 'public/assets/widget.js',
			[],
			AIAGENT_VERSION,
			true
		);

		// If visitor is on a single product page, pass the product ID so the panel
		// can highlight it first. Only the numeric ID is sent — no PII.
		$current_product_id = 0;
		if ( function_exists( 'is_product' ) && is_product() && function_exists( 'wc_get_product' ) ) {
			global $post;
			$p = wc_get_product( $post->ID );
			if ( $p ) $current_product_id = $p->get_id();
		}

		// No API key, no REST nonce, no PII exposed to the browser here.
		wp_localize_script( 'aiagent-widget', 'aiagentConfig', [
			'panelUrl'         => admin_url( 'admin-ajax.php' ) . '?action=aiagent_panel',
			'position'         => $settings['widget_position'],
			'titleEn'          => $settings['widget_title_en'],
			'titleAr'          => $settings['widget_title_ar'],
			'currentProductId' => $current_product_id,
		] );
	}

	public static function render_root(): void {
		static $rendered = false;
		if ( $rendered ) return;
		$rendered = true;
		echo '<div id="aiagent-widget-root" style="display:none" aria-live="polite"></div>' . "\n";
	}

	public static function ajax_upload_attachment(): void {
		check_ajax_referer( 'aiagent_upload_attachment' );

		if ( empty( $_FILES['file'] ) ) {
			wp_send_json_error( [ 'error' => 'No file received.' ] );
		}

		$file      = $_FILES['file'];
		$mime_type = $file['type'] ?? '';
		$allowed   = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];

		if ( ! in_array( $mime_type, $allowed, true ) ) {
			wp_send_json_error( [ 'error' => 'Only image files are accepted.' ] );
		}

		// 5 MB max.
		if ( $file['size'] > 5 * 1024 * 1024 ) {
			wp_send_json_error( [ 'error' => 'Image must be under 5 MB.' ] );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		// Use WP's own upload handling (respects upload_dir, sanitises filename).
		$attachment_id = media_handle_upload( 'file', 0, [], [
			'test_form'   => false,
			'test_upload' => true,
		] );

		if ( is_wp_error( $attachment_id ) ) {
			wp_send_json_error( [ 'error' => $attachment_id->get_error_message() ] );
		}

		$url             = wp_get_attachment_url( $attachment_id );
		$gemini_file_uri  = '';
		$gemini_file_name = '';
		$gemini_mime      = get_post_mime_type( $attachment_id ) ?: 'image/jpeg';

		// Upload to Gemini File API so callers can use fileData (no loopback,
		// no base64 round-trip, re-usable for 48 h).
		$local_path = get_attached_file( $attachment_id );
		if ( $local_path && file_exists( $local_path ) ) {
			$up = AIAgent_File_API::upload( $local_path, $gemini_mime, basename( $local_path ) );
			if ( ! $up['error'] ) {
				$gemini_file_uri  = $up['file_uri'];
				$gemini_file_name = $up['file_name'];
			}
		}

		wp_send_json_success( [
			'url'              => $url,
			'attachment_id'    => $attachment_id,
			'gemini_file_uri'  => $gemini_file_uri,
			'gemini_file_mime' => $gemini_mime,
		] );
	}

	public static function shortcode(): string {
		return '';
	}
}

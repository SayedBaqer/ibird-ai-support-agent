<?php
defined( 'ABSPATH' ) || exit;

/**
 * GitHub-release auto-updater for AI Support Agent.
 * Mirrors the iStock Suite updater pattern exactly.
 *
 * WORKFLOW (same as iStock):
 *  1. Enter GitHub token once in AI Agent → Updates.
 *  2. When ready to ship a new version: bump AIAGENT_VERSION, click "Publish Release".
 *     → Plugin builds its own ZIP, creates the GitHub release, uploads the ZIP.
 *  3. Every WordPress site running the plugin detects the new version and updates
 *     automatically (WP's built-in update checker + auto_update_plugin filter).
 */
class AIAgent_Updater {

	const GITHUB_REPO = 'SayedBaqer/ibird-ai-support-agent';

	public function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
		add_filter( 'plugins_api',                           [ $this, 'plugin_info' ], 20, 3 );
		add_filter( 'upgrader_source_selection',             [ $this, 'fix_folder_name' ], 10, 4 );
		add_filter( 'upgrader_pre_download',                 [ $this, 'auth_download' ], 10, 3 );
		add_filter( 'auto_update_plugin',                    [ $this, 'allow_auto_update' ], 10, 2 );
	}

	// ── 1. Inject into WP update transient ───────────────────────────────────

	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) return $transient;

		$release = $this->get_latest_release();
		if ( ! $release ) return $transient;

		$latest = ltrim( $release['tag_name'] ?? '', 'v' );
		if ( version_compare( $latest, AIAGENT_VERSION, '>' ) ) {
			$zip = $this->get_zip_url( $release );
			if ( $zip ) {
				$transient->response[ $this->basename() ] = (object) [
					'slug'         => $this->slug(),
					'plugin'       => $this->basename(),
					'new_version'  => $latest,
					'url'          => 'https://github.com/' . self::GITHUB_REPO,
					'package'      => $zip,
					'icons'        => [],
					'banners'      => [],
					'tested'       => '6.6',
					'requires'     => '6.0',
					'requires_php' => '7.4',
				];
			}
		}
		return $transient;
	}

	// ── 2. Plugin details modal ───────────────────────────────────────────────

	public function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' || ( $args->slug ?? '' ) !== $this->slug() ) {
			return $result;
		}
		$release = $this->get_latest_release();
		if ( ! $release ) return $result;

		return (object) [
			'name'          => 'AI Support Agent',
			'slug'          => $this->slug(),
			'version'       => ltrim( $release['tag_name'] ?? '', 'v' ),
			'author'        => 'iBird Bahrain',
			'homepage'      => 'https://github.com/' . self::GITHUB_REPO,
			'download_link' => $this->get_zip_url( $release ),
			'sections'      => [
				'description' => 'Self-improving AI support agent for WooCommerce — bilingual EN/AR, RAG knowledge base.',
				'changelog'   => nl2br( esc_html( $release['body'] ?? 'See GitHub releases.' ) ),
			],
			'tested'        => '6.6',
			'requires'      => '6.0',
			'requires_php'  => '7.4',
		];
	}

	// ── 3. Fix folder name after GitHub ZIP extraction ────────────────────────
	// GitHub ZIPs extract as "ibird-ai-support-agent-v1.3.0/" — rename to our slug.

	public function fix_folder_name( $source, $remote_source, $upgrader, $hook_extra ) {
		if ( ( $hook_extra['plugin'] ?? '' ) !== $this->basename() ) return $source;
		global $wp_filesystem;
		$corrected = trailingslashit( $remote_source ) . $this->slug() . '/';
		if ( $source !== $corrected ) {
			$wp_filesystem->move( $source, $corrected );
			return $corrected;
		}
		return $source;
	}

	// ── 4. Download handler ───────────────────────────────────────────────────
	// Repo is public — browser_download_url works directly with no auth.
	// This filter is kept as a passthrough; WordPress downloads the ZIP natively.

	public function auth_download( $reply, $package, $upgrader ) {
		// Public repo: let WordPress handle the download natively — no auth needed.
		return $reply;
	}

	// ── 5. Opt into WordPress native silent auto-updater ─────────────────────

	public function allow_auto_update( $update, $item ): bool {
		if ( isset( $item->plugin ) && $item->plugin === $this->basename() ) {
			return true;
		}
		return (bool) $update;
	}

	// ── In-app update (called from Updates page "Update Now" button) ──────────

	public static function update_now(): array {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return [ 'success' => false, 'message' => 'Insufficient permissions.' ];
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		$u    = new self();
		$tok  = $u->token();
		$repo = get_option( 'aiagent_github_repo', self::GITHUB_REPO ) ?: self::GITHUB_REPO;

		if ( ! $tok ) {
			return [ 'success' => false, 'message' => 'No GitHub token saved. Enter your PAT in AI Agent → Updates and click Save.' ];
		}

		// Step 1 — verify connectivity and repo existence before attempting release fetch.
		$headers = [
			'Authorization' => 'Bearer ' . $tok,
			'Accept'        => 'application/vnd.github.v3+json',
			'User-Agent'    => 'AIAgent/' . AIAGENT_VERSION,
		];
		$repo_resp = wp_remote_get( 'https://api.github.com/repos/' . $repo, [ 'headers' => $headers, 'timeout' => 12 ] );
		if ( is_wp_error( $repo_resp ) ) {
			return [ 'success' => false, 'message' => 'Cannot reach GitHub API: ' . $repo_resp->get_error_message() . '. Check server outbound connectivity.' ];
		}
		$repo_code = (int) wp_remote_retrieve_response_code( $repo_resp );
		if ( $repo_code === 401 ) {
			return [ 'success' => false, 'message' => 'GitHub token is invalid or expired. Generate a new PAT with "repo" scope at github.com/settings/tokens and save it in AI Agent → Updates.' ];
		}
		if ( $repo_code === 403 ) {
			return [ 'success' => false, 'message' => 'GitHub token lacks the "repo" scope. Regenerate your PAT with "repo" checked.' ];
		}
		if ( $repo_code === 404 ) {
			// Repo doesn't exist → auto-publish to create it.
			$pub = self::publish_github_release();
			if ( $pub['success'] ) {
				return [ 'success' => true, 'updated' => false, 'auto_published' => true, 'message' => 'GitHub repo didn\'t exist — published v' . AIAGENT_VERSION . ' automatically. All other sites will auto-update within hours.' . ( ! empty( $pub['release_url'] ) ? ' View: ' . $pub['release_url'] : '' ) ];
			}
			return [ 'success' => false, 'message' => 'Repo "' . $repo . '" not found and auto-publish also failed: ' . $pub['message'] ];
		}
		if ( $repo_code !== 200 ) {
			return [ 'success' => false, 'message' => 'GitHub API returned HTTP ' . $repo_code . '. Check your token and repo name in AI Agent → Updates.' ];
		}

		// Step 2 — repo exists; look for releases.
		$release = self::fetch_latest_release( $tok );
		if ( ! $release ) {
			// Repo has no releases → auto-publish the current version.
			$pub = self::publish_github_release();
			if ( $pub['success'] ) {
				return [ 'success' => true, 'updated' => false, 'auto_published' => true, 'message' => 'No release was on GitHub — published v' . AIAGENT_VERSION . ' automatically. All other sites will auto-update within hours.' . ( ! empty( $pub['release_url'] ) ? ' View: ' . $pub['release_url'] : '' ) ];
			}
			return [ 'success' => false, 'message' => 'No releases on GitHub and auto-publish failed: ' . $pub['message'] ];
		}
		$latest = ltrim( (string) ( $release['tag_name'] ?? '' ), 'v' );
		if ( ! $latest || version_compare( $latest, AIAGENT_VERSION, '<=' ) ) {
			return [ 'success' => true, 'updated' => false, 'message' => 'v' . AIAGENT_VERSION . ' is already the latest on GitHub. To push a new update: bump AIAGENT_VERSION in ai-support-agent.php, then click "📦 Publish to GitHub".' ];
		}
		$zip = $u->get_zip_url( $release );
		if ( ! $zip ) {
			return [ 'success' => false, 'message' => 'Latest release has no downloadable ZIP.' ];
		}

		add_filter( 'upgrader_pre_download',    [ $u, 'auth_download' ], 10, 3 );
		add_filter( 'upgrader_source_selection', [ $u, 'fix_folder_name' ], 10, 4 );

		WP_Filesystem();
		$skin     = new Automatic_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $zip, [ 'overwrite_package' => true ] );

		if ( is_wp_error( $result ) ) {
			return [ 'success' => false, 'message' => $result->get_error_message() ];
		}
		if ( ! $result ) {
			$errs = $skin->get_errors();
			$msg  = ( is_wp_error( $errs ) && $errs->get_error_message() ) ? $errs->get_error_message() : 'Update failed.';
			return [ 'success' => false, 'message' => $msg ];
		}

		delete_transient( 'aiagent_github_release' );
		delete_site_transient( 'update_plugins' );

		$plugin_file = $u->basename();
		if ( function_exists( 'is_plugin_active' ) && ! is_plugin_active( $plugin_file ) ) {
			activate_plugin( $plugin_file );
		}
		return [ 'success' => true, 'updated' => true, 'message' => 'Updated to v' . $latest . '.' ];
	}

	// ── Publish release to GitHub (called from Updates page) ─────────────────
	// Creates the repo if needed, creates the release, builds + uploads the ZIP.
	// Returns a 'steps' array so the UI can show exactly which step succeeded/failed.

	public static function publish_github_release(): array {
		@set_time_limit( 0 );
		@ignore_user_abort( true );

		$u     = new self();
		$token = $u->token();
		$repo  = get_option( 'aiagent_github_repo', self::GITHUB_REPO ) ?: self::GITHUB_REPO;
		$steps = []; // step log for UI

		if ( ! $token ) {
			return [ 'success' => false, 'message' => 'No GitHub token saved. Enter your PAT in AI Agent → Updates.', 'steps' => [] ];
		}

		$tag      = 'v' . AIAGENT_VERSION;
		$api      = 'https://api.github.com/repos/' . $repo;
		$json_hdr = [
			'Authorization' => 'Bearer ' . $token,
			'Accept'        => 'application/vnd.github.v3+json',
			'Content-Type'  => 'application/json',
			'User-Agent'    => 'AIAgent/' . AIAGENT_VERSION,
		];

		// ── Step 1: ensure repo exists ────────────────────────────────────────
		$steps[] = 'Step 1: Checking GitHub repo…';
		$check   = wp_remote_get( $api, [ 'headers' => $json_hdr, 'timeout' => 15 ] );
		if ( is_wp_error( $check ) ) {
			$steps[] = '✗ Cannot reach GitHub: ' . $check->get_error_message();
			return [ 'success' => false, 'message' => $steps[1], 'steps' => $steps ];
		}
		$check_code = (int) wp_remote_retrieve_response_code( $check );

		if ( $check_code === 401 || $check_code === 403 ) {
			$steps[] = '✗ Token rejected (HTTP ' . $check_code . '). Regenerate your PAT with "repo" scope.';
			return [ 'success' => false, 'message' => end( $steps ), 'steps' => $steps ];
		}

		if ( $check_code === 404 ) {
			$steps[] = 'Step 1b: Repo not found — creating "' . $repo . '"…';
			[ $owner, $reponame ] = explode( '/', $repo, 2 );
			$create = wp_remote_post( 'https://api.github.com/user/repos', [
				'headers' => $json_hdr,
				'timeout' => 25,
				'body'    => wp_json_encode( [
					'name'        => $reponame,
					'private'     => true,
					'description' => 'AI Support Agent WordPress plugin — iBird Bahrain',
					'auto_init'   => true,
				] ),
			] );
			$create_code = is_wp_error( $create ) ? 0 : (int) wp_remote_retrieve_response_code( $create );
			if ( $create_code !== 201 ) {
				$msg = is_wp_error( $create ) ? $create->get_error_message()
					: ( json_decode( wp_remote_retrieve_body( $create ), true )['message'] ?? 'HTTP ' . $create_code );
				$steps[] = '✗ Could not create repo: ' . $msg;
				return [ 'success' => false, 'message' => end( $steps ), 'steps' => $steps ];
			}
			$steps[] = '✓ Repo created: https://github.com/' . $repo;
			sleep( 3 ); // wait for GitHub to initialise default branch
		} else {
			$steps[] = '✓ Repo exists: https://github.com/' . $repo;
		}

		// ── Step 2: create the release ────────────────────────────────────────
		$steps[] = 'Step 2: Creating release tag ' . $tag . '…';
		$rel_resp = wp_remote_post( $api . '/releases', [
			'headers' => $json_hdr,
			'timeout' => 25,
			'body'    => wp_json_encode( [
				'tag_name'         => $tag,
				'name'             => 'AI Support Agent ' . $tag,
				'body'             => "## What's new in {$tag}\n\n"
				                   . "- Conversation management: admin reply, claim, close\n"
				                   . "- WhatsApp / N8N webhook integration\n"
				                   . "- PDF manual → auto Q&A extraction (taught examples)\n"
				                   . "- Manual upload split into 3 steps (no more PHP timeout)\n"
				                   . "- GitHub updater: auto-publish if no release exists\n"
				                   . "- Manuals page: extracted text preview\n"
				                   . "\nAuto-published from wp-admin.",
				'draft'            => false,
				'prerelease'       => false,
				'target_commitish' => 'main',
			] ),
		] );

		if ( is_wp_error( $rel_resp ) ) {
			$steps[] = '✗ GitHub API error: ' . $rel_resp->get_error_message();
			return [ 'success' => false, 'message' => end( $steps ), 'steps' => $steps ];
		}

		$rel_code = (int) wp_remote_retrieve_response_code( $rel_resp );
		$rel_body = json_decode( wp_remote_retrieve_body( $rel_resp ), true );

		if ( $rel_code === 422 ) {
			// Release tag already exists — try to get its upload_url so we can still upload the ZIP.
			$steps[] = '⚠ Release ' . $tag . ' already exists — fetching existing release to upload ZIP…';
			$exist_resp = wp_remote_get( $api . '/releases/tags/' . $tag, [ 'headers' => $json_hdr, 'timeout' => 15 ] );
			if ( ! is_wp_error( $exist_resp ) && wp_remote_retrieve_response_code( $exist_resp ) === 200 ) {
				$rel_body = json_decode( wp_remote_retrieve_body( $exist_resp ), true );
				$rel_code = 201; // pretend created so flow continues
			} else {
				$steps[] = '✗ Release already exists and could not fetch it. Bump AIAGENT_VERSION first.';
				return [ 'success' => false, 'message' => end( $steps ), 'steps' => $steps ];
			}
		} elseif ( $rel_code !== 201 ) {
			$steps[] = '✗ Failed to create release: ' . ( $rel_body['message'] ?? 'HTTP ' . $rel_code );
			return [ 'success' => false, 'message' => end( $steps ), 'steps' => $steps ];
		} else {
			$steps[] = '✓ Release ' . $tag . ' created on GitHub.';
		}

		$upload_url = str_replace( '{?name,label}', '', $rel_body['upload_url'] ?? '' );
		$html_url   = $rel_body['html_url'] ?? ( 'https://github.com/' . $repo . '/releases/tag/' . $tag );

		if ( ! $upload_url ) {
			$steps[] = '⚠ No upload_url in GitHub response — release created without ZIP.';
			return [ 'success' => true, 'message' => 'Release created but no upload URL from GitHub.', 'release_url' => $html_url, 'steps' => $steps ];
		}

		// ── Step 3: build the plugin ZIP ──────────────────────────────────────
		$steps[] = 'Step 3: Building plugin ZIP…';
		$zip_content = self::build_zip_content();
		$zip_size    = strlen( $zip_content );

		if ( ! $zip_content || $zip_size < 1000 ) {
			$steps[] = '✗ ZIP build failed or too small (' . $zip_size . ' bytes). ZipArchive extension must be enabled.';
			return [ 'success' => false, 'message' => end( $steps ), 'steps' => $steps ];
		}
		$steps[] = '✓ ZIP built: ' . round( $zip_size / 1024, 1 ) . ' KB.';

		// ── Step 4: upload ZIP as release asset ───────────────────────────────
		$steps[]     = 'Step 4: Uploading ZIP to GitHub…';
		$upload_resp = wp_remote_request( $upload_url . '?name=ai-support-agent.zip&label=Plugin+ZIP', [
			'method'  => 'POST',
			'headers' => [
				'Authorization'  => 'Bearer ' . $token,
				'Accept'         => 'application/vnd.github.v3+json',
				'Content-Type'   => 'application/zip',
				'Content-Length' => (string) $zip_size,
				'User-Agent'     => 'AIAgent/' . AIAGENT_VERSION,
			],
			'body'    => $zip_content,
			'timeout' => 180,
		] );

		unset( $zip_content ); // free memory

		delete_transient( 'aiagent_github_release' );
		delete_site_transient( 'update_plugins' );

		if ( is_wp_error( $upload_resp ) ) {
			$steps[] = '✗ ZIP upload error: ' . $upload_resp->get_error_message();
			return [ 'success' => false, 'message' => end( $steps ), 'steps' => $steps, 'release_url' => $html_url ];
		}

		$up_code = (int) wp_remote_retrieve_response_code( $upload_resp );
		if ( $up_code !== 201 ) {
			$up_body  = json_decode( wp_remote_retrieve_body( $upload_resp ), true );
			$steps[]  = '✗ ZIP upload failed: HTTP ' . $up_code . ' — ' . ( $up_body['message'] ?? wp_remote_retrieve_body( $upload_resp ) );
			return [ 'success' => false, 'message' => end( $steps ), 'steps' => $steps, 'release_url' => $html_url ];
		}

		$steps[] = '✓ ZIP uploaded. Release is live.';
		return [
			'success'     => true,
			'message'     => 'v' . AIAGENT_VERSION . ' published to GitHub. All sites will auto-update within hours.',
			'release_url' => $html_url,
			'steps'       => $steps,
		];
	}

	// ── Diagnostic (for Updates page status card) ─────────────────────────────

	public static function update_status(): array {
		$u       = new self();
		$tok     = $u->token();
		$repo    = get_option( 'aiagent_github_repo', self::GITHUB_REPO ) ?: self::GITHUB_REPO;
		$headers = [
			'Accept'     => 'application/vnd.github.v3+json',
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ),
		];
		if ( $tok ) $headers['Authorization'] = 'Bearer ' . $tok;

		// Probe repo existence first (separate from releases check).
		$repo_probe  = wp_remote_get( 'https://api.github.com/repos/' . $repo, [ 'timeout' => 10, 'headers' => $headers ] );
		$repo_code   = is_wp_error( $repo_probe ) ? 0 : (int) wp_remote_retrieve_response_code( $repo_probe );
		$repo_exists = ( $repo_code === 200 );
		$err         = is_wp_error( $repo_probe ) ? $repo_probe->get_error_message() : '';

		// Auth status from repo probe.
		$auth_ok = ! in_array( $repo_code, [ 401, 403 ], true );

		$release   = $repo_exists ? self::fetch_latest_release( $tok ) : null;
		$latest    = ( $release && ! empty( $release['tag_name'] ) ) ? ltrim( $release['tag_name'], 'vV' ) : '';
		$has_asset = false;
		foreach ( ( $release['assets'] ?? [] ) as $a ) {
			if ( substr( (string) ( $a['name'] ?? '' ), -4 ) === '.zip' ) { $has_asset = true; break; }
		}
		return [
			'installed'    => AIAGENT_VERSION,
			'latest'       => $latest,
			'update_ready' => $latest && version_compare( $latest, AIAGENT_VERSION, '>' ),
			'repo'         => $repo,
			'repo_exists'  => $repo_exists,
			'token_set'    => $tok !== '',
			'auth_ok'      => $auth_ok,
			'http_code'    => $repo_code,
			'error'        => $err,
			'has_zip'      => $has_asset,
			'basename'     => $u->basename(),
		];
	}

	// ── Fetch latest release from GitHub (with version-compare over list) ─────

	public static function fetch_latest_release( string $tok ): ?array {
		$repo    = get_option( 'aiagent_github_repo', self::GITHUB_REPO ) ?: self::GITHUB_REPO;
		$headers = [
			'Accept'     => 'application/vnd.github.v3+json',
			'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
		];
		if ( $tok ) $headers['Authorization'] = 'Bearer ' . $tok;

		$resp = wp_remote_get( 'https://api.github.com/repos/' . $repo . '/releases?per_page=30', [ 'timeout' => 12, 'headers' => $headers ] );
		if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) === 200 ) {
			$list = json_decode( wp_remote_retrieve_body( $resp ), true );
			if ( is_array( $list ) ) {
				$best = null; $best_ver = '0';
				foreach ( $list as $rel ) {
					if ( ! empty( $rel['draft'] ) || ! empty( $rel['prerelease'] ) || empty( $rel['tag_name'] ) ) continue;
					$ver = ltrim( (string) $rel['tag_name'], 'vV' );
					if ( version_compare( $ver, $best_ver, '>' ) ) { $best_ver = $ver; $best = $rel; }
				}
				if ( $best ) return $best;
			}
		}
		// Fallback: single "latest" pointer.
		$resp = wp_remote_get( 'https://api.github.com/repos/' . $repo . '/releases/latest', [ 'timeout' => 10, 'headers' => $headers ] );
		if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) return null;
		$r = json_decode( wp_remote_retrieve_body( $resp ), true );
		return ( is_array( $r ) && ! empty( $r['tag_name'] ) ) ? $r : null;
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	private function token(): string {
		$tok = trim( (string) get_option( 'aiagent_github_token', '' ) );
		if ( $tok === '' ) {
			// Fallback: token may have been saved via the main Settings page before the Updates page existed.
			$settings = function_exists( 'aiagent_settings' ) ? aiagent_settings() : [];
			$tok      = trim( (string) ( $settings['github_token'] ?? '' ) );
			if ( $tok !== '' ) {
				// Migrate to dedicated option so future reads are consistent.
				update_option( 'aiagent_github_token', $tok );
			}
		}
		return $tok;
	}

	private function basename(): string {
		return plugin_basename( AIAGENT_FILE );
	}

	private function slug(): string {
		return dirname( $this->basename() );
	}

	private function get_latest_release(): ?array {
		$cached = get_transient( 'aiagent_github_release' );
		if ( $cached !== false ) return $cached ?: null;
		$r = self::fetch_latest_release( $this->token() );
		if ( ! $r ) { set_transient( 'aiagent_github_release', [], 30 * MINUTE_IN_SECONDS ); return null; }
		set_transient( 'aiagent_github_release', $r, HOUR_IN_SECONDS );
		return $r;
	}

	private function get_zip_url( array $release ): string {
		// Repo is public — always use browser_download_url so WordPress can
		// download the ZIP directly without needing an Authorization header.
		foreach ( $release['assets'] ?? [] as $a ) {
			if ( str_ends_with( (string) ( $a['name'] ?? '' ), '.zip' ) ) {
				return (string) ( $a['browser_download_url'] ?? '' );
			}
		}
		// Fallback: GitHub's auto-generated source ZIP.
		return (string) ( $release['zipball_url'] ?? '' );
	}

	private static function build_zip_content(): string {
		if ( ! class_exists( 'ZipArchive' ) ) return '';
		$tmp  = wp_tempnam( 'aiagent-release' ) . '.zip';
		$zip  = new ZipArchive();
		if ( $zip->open( $tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) return '';

		$base = AIAGENT_DIR;
		$slug = 'ai-support-agent';
		$skip = [ '.git', '.github', 'node_modules', '.gitignore', '.DS_Store' ];

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base, RecursiveDirectoryIterator::SKIP_DOTS )
		);
		foreach ( $files as $file ) {
			if ( ! $file->isFile() ) continue;
			$path = $file->getRealPath();
			$rel  = str_replace( '\\', '/', substr( $path, strlen( rtrim( $base, '/\\' ) ) + 1 ) );
			foreach ( $skip as $s ) { if ( strpos( $rel, $s ) !== false ) continue 2; }
			$zip->addFile( $path, $slug . '/' . $rel );
		}
		$zip->close();
		$content = file_get_contents( $tmp ); // phpcs:ignore
		@unlink( $tmp ); // phpcs:ignore
		return $content ?: '';
	}
}

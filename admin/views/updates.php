<?php defined( 'ABSPATH' ) || exit;

// Handle token/repo save
if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'aiagent_updates' ) && current_user_can( 'manage_options' ) ) {
	if ( isset( $_POST['github_token'] ) ) {
		update_option( 'aiagent_github_token', sanitize_text_field( wp_unslash( $_POST['github_token'] ) ) );
		delete_transient( 'aiagent_github_release' );
	}
	if ( isset( $_POST['github_repo'] ) ) {
		update_option( 'aiagent_github_repo', sanitize_text_field( wp_unslash( $_POST['github_repo'] ) ) );
	}
}

$token    = get_option( 'aiagent_github_token', '' );
$repo     = get_option( 'aiagent_github_repo',  AIAgent_Updater::GITHUB_REPO );
$status   = AIAgent_Updater::update_status();
$last_upd = get_option( 'aiagent_last_auto_update' );
?>
<style>
.aau-grid{display:flex;gap:20px;flex-wrap:wrap;align-items:flex-start;}
.aau-main{flex:1 1 480px;min-width:0;}
.aau-side{flex:0 0 320px;}
@media(max-width:900px){.aau-side{flex:0 0 100%;}}
.aau-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06);margin-bottom:16px;overflow:hidden;}
.aau-head{padding:14px 20px;border-bottom:1px solid #f1f5f9;font-weight:700;font-size:14px;color:#1e293b;display:flex;align-items:center;gap:8px;}
.aau-body{padding:20px;}
.aau-field{margin-bottom:16px;}
.aau-field label{display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;}
.aau-input{display:block;width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:9px 13px;font-size:13px;color:#1e293b;background:#fff;box-sizing:border-box;}
.aau-input:focus{outline:none;border-color:#1a6b3c;box-shadow:0 0 0 3px rgba(26,107,60,.1);}
.aau-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;}
.aau-btn-primary{background:#1a6b3c;color:#fff;}
.aau-btn-primary:hover{background:#145530;}
.aau-btn-primary:disabled{background:#94a3b8;cursor:not-allowed;}
.aau-btn-blue{background:#1d4ed8;color:#fff;}
.aau-btn-blue:hover{background:#1e40af;}
.aau-btn-blue:disabled{background:#94a3b8;cursor:not-allowed;}
.aau-btn-ghost{background:#f8fafc;color:#475569;border:1px solid #e2e8f0;}
.aau-stat-row{display:flex;align-items:center;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f1f5f9;font-size:13px;}
.aau-stat-row:last-child{border:none;}
.aau-stat-label{color:#64748b;}
.aau-stat-val{font-weight:600;color:#1e293b;}
.aau-badge{display:inline-flex;padding:2px 9px;border-radius:50px;font-size:11px;font-weight:600;}
.ok{background:#f0fdf4;color:#166534;}
.warn{background:#fffbeb;color:#92400e;}
.err{background:#fef2f2;color:#991b1b;}
.aau-notice{padding:11px 15px;border-radius:8px;font-size:13px;margin-top:14px;display:none;}
.aau-notice-ok{background:#f0fdf4;color:#166534;border-left:4px solid #1a6b3c;}
.aau-notice-err{background:#fef2f2;color:#991b1b;border-left:4px solid #dc2626;}
.aau-steps{counter-reset:step;list-style:none;margin:0;padding:0;}
.aau-steps li{counter-increment:step;display:flex;gap:10px;margin-bottom:14px;font-size:13px;color:#475569;}
.aau-steps li::before{content:counter(step);flex:0 0 22px;height:22px;background:#1a6b3c;color:#fff;border-radius:50%;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;margin-top:1px;}
.aau-steps li strong{color:#1e293b;}
code{background:#f1f5f9;border-radius:4px;padding:1px 6px;font-size:12px;}
</style>

<div class="wrap aiagent-page">
  <h1 style="margin-bottom:4px;">Plugin Updates</h1>
  <p style="color:#64748b;margin-bottom:24px;">Publish new versions directly from here — no git commands needed. WordPress on all connected sites auto-updates within hours.</p>

  <div class="aau-grid">

    <!-- ── Main ────────────────────────────────────────────────────────── -->
    <div class="aau-main">

      <!-- Token & Repo settings -->
      <div class="aau-card">
        <div class="aau-head">🔑 GitHub Connection</div>
        <div class="aau-body">
          <form method="post">
            <?php wp_nonce_field( 'aiagent_updates' ); ?>

            <div class="aau-field">
              <label>GitHub Personal Access Token (PAT)</label>
              <input type="password" name="github_token" class="aau-input"
                value="<?php echo esc_attr( $token ); ?>"
                placeholder="ghp_xxxxxxxxxxxxxxxxxxxx"
                autocomplete="new-password">
              <p style="font-size:12px;color:#94a3b8;margin:6px 0 0;">
                Needs <code>repo</code> scope.
                <a href="https://github.com/settings/tokens/new?scopes=repo&description=iBird+AI+Agent" target="_blank">Generate one on GitHub ↗</a>
              </p>
            </div>

            <div class="aau-field">
              <label>GitHub Repository</label>
              <input type="text" name="github_repo" class="aau-input"
                value="<?php echo esc_attr( $repo ); ?>"
                placeholder="SayedBaqer/ibird-ai-support-agent">
              <p style="font-size:12px;color:#94a3b8;margin:6px 0 0;">Format: <code>username/repo-name</code>. The repo is created automatically on first Publish.</p>
            </div>

            <button type="submit" class="aau-btn aau-btn-ghost">💾 Save Connection</button>
          </form>
        </div>
      </div>

      <!-- Publish new release -->
      <div class="aau-card">
        <div class="aau-head">🚀 Publish New Release</div>
        <div class="aau-body">
          <p style="font-size:13px;color:#475569;margin-bottom:16px;">
            Current installed version: <strong>v<?php echo esc_html( AIAGENT_VERSION ); ?></strong>.
            Clicking Publish will:
          </p>
          <ol class="aau-steps">
            <li>Create the GitHub repo if it doesn't exist yet</li>
            <li>Build the plugin ZIP automatically (no file needed)</li>
            <li>Create a GitHub release tagged <code>v<?php echo esc_html( AIAGENT_VERSION ); ?></code></li>
            <li>Upload the ZIP as the release asset</li>
            <li>All WordPress sites running this plugin will see the update within hours</li>
          </ol>
          <?php if ( ! $token ) : ?>
            <div class="aau-notice aau-notice-err" style="display:block;">⚠️ Enter a GitHub token above before publishing.</div>
          <?php else : ?>
            <button class="aau-btn aau-btn-primary" id="aau-publish-btn" onclick="aauPublish()">
              📦 Publish v<?php echo esc_html( AIAGENT_VERSION ); ?> to GitHub
            </button>
            <div class="aau-notice" id="aau-publish-result"></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Update Now -->
      <div class="aau-card">
        <div class="aau-head">⬇️ Update This Site Now</div>
        <div class="aau-body">
          <p style="font-size:13px;color:#475569;margin-bottom:14px;">
            Pull and install the latest release from GitHub immediately — no waiting for the next background check.
          </p>
          <?php if ( $status['update_ready'] ) : ?>
            <div class="aau-notice aau-notice-ok" style="display:block;margin-bottom:12px;">
              ✅ Update available: <strong>v<?php echo esc_html( $status['latest'] ); ?></strong> (you have v<?php echo esc_html( AIAGENT_VERSION ); ?>)
            </div>
          <?php endif; ?>
          <button class="aau-btn aau-btn-blue" id="aau-update-btn" onclick="aauUpdateNow()">
            🔄 Update Now
          </button>
          <div class="aau-notice" id="aau-update-result"></div>
        </div>
      </div>

    </div>

    <!-- ── Sidebar: status ──────────────────────────────────────────────── -->
    <div class="aau-side">

      <div class="aau-card">
        <div class="aau-head">📊 Status</div>
        <div class="aau-body" style="padding:12px 16px;">

          <div class="aau-stat-row">
            <span class="aau-stat-label">Installed</span>
            <span class="aau-stat-val">v<?php echo esc_html( $status['installed'] ); ?></span>
          </div>
          <div class="aau-stat-row">
            <span class="aau-stat-label">Latest on GitHub</span>
            <span class="aau-stat-val"><?php echo $status['latest'] ? 'v' . esc_html( $status['latest'] ) : '<span style="color:#94a3b8;">—</span>'; ?></span>
          </div>
          <div class="aau-stat-row">
            <span class="aau-stat-label">Update available</span>
            <span class="aau-badge <?php echo $status['update_ready'] ? 'warn' : 'ok'; ?>">
              <?php echo $status['update_ready'] ? 'Yes' : 'Up to date'; ?>
            </span>
          </div>
          <div class="aau-stat-row">
            <span class="aau-stat-label">Token valid</span>
            <?php
            if ( ! $status['token_set'] ) {
                echo '<span class="aau-badge err">Not set</span>';
            } elseif ( in_array( $status['http_code'], [ 401, 403 ], true ) ) {
                echo '<span class="aau-badge err">Invalid / wrong scope</span>';
            } else {
                echo '<span class="aau-badge ok">OK ✓</span>';
            }
            ?>
          </div>
          <div class="aau-stat-row">
            <span class="aau-stat-label">Repo on GitHub</span>
            <?php if ( $status['repo_exists'] ) : ?>
              <span class="aau-badge ok">Exists ✓</span>
            <?php elseif ( $status['http_code'] === 0 ) : ?>
              <span class="aau-badge err">Can't connect</span>
            <?php else : ?>
              <span class="aau-badge warn">Not created yet</span>
            <?php endif; ?>
          </div>
          <div class="aau-stat-row">
            <span class="aau-stat-label">ZIP attached</span>
            <span class="aau-badge <?php echo $status['has_zip'] ? 'ok' : 'warn'; ?>">
              <?php echo $status['has_zip'] ? 'Yes ✓' : ( $status['repo_exists'] ? 'No — Publish first' : '—' ); ?>
            </span>
          </div>
          <div class="aau-stat-row">
            <span class="aau-stat-label">Repo</span>
            <?php if ( $status['repo_exists'] ) : ?>
              <a href="https://github.com/<?php echo esc_attr( $status['repo'] ); ?>" target="_blank" style="font-size:12px;color:#1d4ed8;word-break:break-all;">
                <?php echo esc_html( $status['repo'] ); ?> ↗
              </a>
            <?php else : ?>
              <span style="font-size:12px;color:#94a3b8;"><?php echo esc_html( $status['repo'] ); ?></span>
            <?php endif; ?>
          </div>

          <?php if ( $last_upd ) : ?>
          <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f1f5f9;">
            <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:8px;">Last Auto-Update</div>
            <div style="font-size:13px;">
              <span class="aau-badge <?php echo $last_upd['status'] === 'success' ? 'ok' : 'err'; ?>">
                <?php echo $last_upd['status'] === 'success' ? '✓ Success' : '✗ Failed'; ?>
              </span>
              v<?php echo esc_html( $last_upd['from'] ?? '?' ); ?> → v<?php echo esc_html( $last_upd['version'] ?? '?' ); ?><br>
              <span style="color:#94a3b8;font-size:12px;"><?php echo esc_html( $last_upd['time'] ?? '' ); ?></span>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>

      <div class="aau-card">
        <div class="aau-head">📋 How it works</div>
        <div class="aau-body">
          <ol class="aau-steps">
            <li>Bump <code>AIAGENT_VERSION</code> in <code>ai-support-agent.php</code></li>
            <li>Come here → <strong>Publish to GitHub</strong></li>
            <li>Done — all sites auto-update</li>
          </ol>
          <p style="font-size:12px;color:#94a3b8;margin:0;">
            WordPress checks for updates every 12 hours. The "Update Now" button triggers an immediate install.
          </p>
        </div>
      </div>

    </div>

  </div>
</div>

<script>
var AAU_NONCE='<?php echo esc_js( wp_create_nonce( 'aiagent_nonce' ) ); ?>';
var AAU_AJAX='<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

function aauPublish(){
  var btn=document.getElementById('aau-publish-btn');
  var res=document.getElementById('aau-publish-result');
  btn.disabled=true; btn.textContent='Publishing… (may take 30–60 s)';
  res.className='aau-notice aau-notice-ok';
  res.style.display='block';
  res.innerHTML='<em>Connecting to GitHub…</em>';
  fetch(AAU_AJAX,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=aiagent_publish_release&nonce='+AAU_NONCE})
  .then(r=>r.json()).then(d=>{
    var ok=d.success && d.data && d.data.success;
    res.className='aau-notice '+(ok?'aau-notice-ok':'aau-notice-err');
    var html='';
    // Steps log
    if(d.data && d.data.steps && d.data.steps.length){
      html+='<div style="font-size:12px;margin-bottom:8px;line-height:1.7;">';
      d.data.steps.forEach(function(s){ html+=s+'<br>'; });
      html+='</div>';
    }
    // Final result line
    if(ok){
      html+='<strong>✅ '+d.data.message+'</strong>';
      if(d.data.release_url) html+=' &nbsp;<a href="'+d.data.release_url+'" target="_blank">View release on GitHub ↗</a>';
    } else {
      html+='<strong>❌ '+(d.data?d.data.message:'Unexpected error. Check server logs.')+'</strong>';
    }
    res.innerHTML=html;
    btn.disabled=false; btn.textContent='📦 Publish v<?php echo esc_js( AIAGENT_VERSION ); ?> to GitHub';
  }).catch(e=>{
    res.className='aau-notice aau-notice-err';
    res.innerHTML='❌ Network error: '+e.message;
    btn.disabled=false; btn.textContent='📦 Publish v<?php echo esc_js( AIAGENT_VERSION ); ?> to GitHub';
  });
}

function aauUpdateNow(){
  var btn=document.getElementById('aau-update-btn');
  var res=document.getElementById('aau-update-result');
  btn.disabled=true; btn.textContent='Updating…';
  res.style.display='none';
  fetch(AAU_AJAX,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=aiagent_update_now&nonce='+AAU_NONCE})
  .then(r=>r.json()).then(d=>{
    res.style.display='block';
    if(d.success && d.data && d.data.success){
      res.className='aau-notice aau-notice-ok';
      res.innerHTML='✅ '+d.data.message+(d.data.updated?' Page will reload…':'');
      if(d.data.updated) setTimeout(()=>location.reload(), 2000);
    } else {
      res.className='aau-notice aau-notice-err';
      res.innerHTML='❌ '+(d.data?d.data.message:'Unexpected error.');
    }
    btn.disabled=false; btn.textContent='🔄 Update Now';
  }).catch(e=>{
    res.style.display='block'; res.className='aau-notice aau-notice-err';
    res.innerHTML='❌ Network error: '+e.message;
    btn.disabled=false; btn.textContent='🔄 Update Now';
  });
}
</script>

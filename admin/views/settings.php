<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap aiagent-settings">
  <h1>Settings</h1>

  <?php if ( isset( $_GET['saved'] ) ) : ?>
    <div class="aa-notice aa-notice--success">✅ Settings saved successfully.</div>
  <?php endif; ?>

  <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
    <?php wp_nonce_field('aiagent_save_settings'); ?>
    <input type="hidden" name="action" value="aiagent_save_settings">

    <!-- ── Gemini ──────────────────────────────────────────────────────────── -->
    <div class="aa-section-title">🤖 Primary Provider — Google Gemini</div>
    <div class="aa-card">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="aa-field" style="grid-column:1/-1;">
          <label>Gemini API Key</label>
          <input type="password" class="aa-input" name="api_key" value="<?php echo esc_attr($settings['api_key']); ?>" autocomplete="off" placeholder="AIza…">
          <div class="aa-field__desc">🔒 Stored server-side only. Never sent to the browser or any frontend code.</div>
        </div>
        <div class="aa-field">
          <label>Reply Model ID</label>
          <input type="text" class="aa-input" name="model_reply" value="<?php echo esc_attr($settings['model_reply']); ?>" placeholder="gemini-2.5-flash">
          <div class="aa-field__desc">Recommended: <code>gemini-2.5-flash</code> (best value, 1M context).</div>
        </div>
        <div class="aa-field">
          <label>Classify Model ID</label>
          <input type="text" class="aa-input" name="model_classify" value="<?php echo esc_attr($settings['model_classify']); ?>" placeholder="gemini-2.5-flash-lite">
          <div class="aa-field__desc">Used for cheap intent detection. Recommended: <code>gemini-2.5-flash-lite</code>.</div>
        </div>
        <div class="aa-field">
          <label>Embedding Model ID</label>
          <input type="text" class="aa-input" name="model_embed" value="<?php echo esc_attr($settings['model_embed']); ?>" placeholder="gemini-embedding-2">
          <div class="aa-field__desc">Recommended: <code>gemini-embedding-2</code> — supports Arabic + 8K context. <strong>Note:</strong> <code>text-embedding-004</code> was discontinued Jan 2026.</div>
        </div>
      </div>
    </div>

    <!-- ── Groq ────────────────────────────────────────────────────────────── -->
    <div class="aa-section-title">⚡ Fallback Provider 1 — Groq</div>
    <div class="aa-card">
      <p style="color:#64748b;font-size:13px;margin-bottom:16px;">Used automatically when Gemini is rate-limited or unavailable. Free at <a href="https://console.groq.com" target="_blank">console.groq.com</a>.</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="aa-field">
          <label>Groq API Key</label>
          <input type="password" class="aa-input" name="groq_api_key" value="<?php echo esc_attr($settings['groq_api_key']??''); ?>" autocomplete="off" placeholder="gsk_…">
          <div class="aa-field__desc">Leave blank to skip this provider.</div>
        </div>
        <div class="aa-field">
          <label>Groq Model ID</label>
          <input type="text" class="aa-input" name="groq_model" value="<?php echo esc_attr($settings['groq_model']??'llama-3.3-70b-versatile'); ?>">
        </div>
      </div>
    </div>

    <!-- ── Cerebras ────────────────────────────────────────────────────────── -->
    <div class="aa-section-title">🧠 Fallback Provider 2 — Cerebras</div>
    <div class="aa-card">
      <p style="color:#64748b;font-size:13px;margin-bottom:16px;">Third-line fallback. Fastest inference available. Free at <a href="https://cloud.cerebras.ai" target="_blank">cloud.cerebras.ai</a>.</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="aa-field">
          <label>Cerebras API Key</label>
          <input type="password" class="aa-input" name="cerebras_api_key" value="<?php echo esc_attr($settings['cerebras_api_key']??''); ?>" autocomplete="off" placeholder="csk_…">
          <div class="aa-field__desc">Leave blank to skip this provider.</div>
        </div>
        <div class="aa-field">
          <label>Cerebras Model ID</label>
          <input type="text" class="aa-input" name="cerebras_model" value="<?php echo esc_attr($settings['cerebras_model']??'llama-3.3-70b'); ?>">
        </div>
      </div>
    </div>

    <!-- ── GitHub Auto-Update ─────────────────────────────────────────────── -->
    <div class="aa-section-title">🔄 GitHub Auto-Update</div>
    <div class="aa-card">
      <p style="color:#64748b;font-size:13px;margin-bottom:16px;">
        The plugin checks GitHub for new releases and shows an <strong>Update available</strong> notice in
        <em>Plugins → Installed Plugins</em> — no manual reinstall needed.<br>
        Repo: <code>SayedBaqer/ibird-ai-support-agent</code> (private) — requires a PAT with <code>contents:read</code> scope.
      </p>
      <div style="display:grid;grid-template-columns:1fr;gap:16px;max-width:520px;">
        <div class="aa-field">
          <label>GitHub Personal Access Token</label>
          <input type="password" class="aa-input" name="github_token"
            value="<?php echo esc_attr( $settings['github_token'] ?? '' ); ?>"
            autocomplete="off" placeholder="github_pat_…">
          <div class="aa-field__desc">
            <a href="https://github.com/settings/tokens/new?scopes=repo&description=iBird+WP+Updater" target="_blank">
              Generate token on GitHub ↗
            </a> — select <em>repo</em> scope → copy here → Save.
            Update cache clears automatically on save.
          </div>
        </div>
      </div>
    </div>

    <!-- ── Gemini Advanced Features ──────────────────────────────────────── -->
    <div class="aa-section-title">🔬 Gemini Advanced Features</div>
    <div class="aa-card">
      <p style="color:#64748b;font-size:13px;margin-bottom:20px;">These features only apply when Gemini is the active provider. They use extra quota — leave disabled if you are on the free tier and quota is tight.</p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

        <div class="aa-field">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
            <input type="checkbox" name="enable_search_grounding" value="1" <?php checked( ! empty( $settings['enable_search_grounding'] ) ); ?> style="width:18px;height:18px;accent-color:#1a6b3c;">
            <span style="font-weight:600;">Google Search Grounding</span>
          </label>
          <div class="aa-field__desc" style="margin-top:6px;">Gemini searches the web in real-time when it lacks enough information in the knowledge base. Great for current stock, live pricing, or topics not yet in your manuals. <strong>Each search uses extra quota.</strong></div>
        </div>

        <div class="aa-field">
          <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
            <input type="checkbox" name="enable_thinking" value="1" <?php checked( ! empty( $settings['enable_thinking'] ) ); ?> style="width:18px;height:18px;accent-color:#1a6b3c;" id="thinking-toggle">
            <span style="font-weight:600;">Thinking Mode</span>
          </label>
          <div class="aa-field__desc" style="margin-top:6px;">Gemini reasons step-by-step before responding. More accurate for complex support questions. Requires <strong>gemini-2.5-flash</strong> or newer as the reply model. Slower (5&ndash;20s extra) and uses more tokens.</div>
          <div style="margin-top:10px;">
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:5px;">Thinking Token Budget</label>
            <input type="range" name="thinking_budget" min="256" max="8192" step="256"
              value="<?php echo esc_attr( $settings['thinking_budget'] ?? 1024 ); ?>"
              oninput="document.getElementById('tb-val').textContent=this.value"
              style="width:100%;accent-color:#1a6b3c;">
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#94a3b8;margin-top:2px;">
              <span>256 (fast)</span>
              <span id="tb-val" style="font-weight:700;color:#1a6b3c;"><?php echo esc_html( $settings['thinking_budget'] ?? 1024 ); ?></span>
              <span>8192 (thorough)</span>
            </div>
            <div class="aa-field__desc" style="margin-top:4px;">Higher = better reasoning, more quota used. 1024 is a good default.</div>
          </div>
        </div>

      </div>
    </div>

    <!-- ── Throttle ────────────────────────────────────────────────────────── -->
    <div class="aa-section-title">🚦 Quota &amp; Throttle</div>
    <div class="aa-card">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;">
        <div class="aa-field">
          <label>Global Daily Cap</label>
          <input type="number" class="aa-input" name="daily_cap" value="<?php echo esc_attr($settings['daily_cap']); ?>" min="100" max="10000">
          <div class="aa-field__desc">Circuit breaker. Gemini free = 1,500/day.</div>
        </div>
        <div class="aa-field">
          <label>Per-Session Cap</label>
          <input type="number" class="aa-input" name="session_cap" value="<?php echo esc_attr($settings['session_cap']); ?>" min="5" max="100">
          <div class="aa-field__desc">Messages before auto-escalating.</div>
        </div>
        <div class="aa-field">
          <label>Per-Customer Daily Cap</label>
          <input type="number" class="aa-input" name="per_customer_cap" value="<?php echo esc_attr($settings['per_customer_cap']); ?>" min="5" max="200">
          <div class="aa-field__desc">Per user/IP per day.</div>
        </div>
      </div>

      <!-- Usage meter -->
      <?php
      $used = AIAgent_Throttle::global_count();
      $cap  = (int) $settings['daily_cap'];
      $pct  = $cap > 0 ? min(100, round($used / $cap * 100)) : 0;
      $bar_class = $pct > 85 ? 'aa-progress__bar--red' : ($pct > 60 ? 'aa-progress__bar--orange' : 'aa-progress__bar--green');
      ?>
      <div style="margin-top:8px;">
        <div class="aa-usage">
          <div class="aa-usage__row">
            <span>Usage today</span>
            <span class="aa-usage__count"><?php echo number_format($used); ?> / <?php echo number_format($cap); ?> requests (<?php echo $pct; ?>%)</span>
          </div>
          <div class="aa-progress"><div class="aa-progress__bar <?php echo esc_attr($bar_class); ?>" style="width:<?php echo $pct; ?>%;"></div></div>
        </div>
      </div>
    </div>

    <!-- ── Escalation Notifications ─────────────────────────────────────────── -->
    <div class="aa-section-title">📲 Escalation Notifications</div>
    <div class="aa-card">
      <p style="color:#64748b;font-size:13px;margin-bottom:16px;">
        When a customer conversation is escalated to a human agent, the plugin sends an email notification automatically.
        Optionally provide a WhatsApp number for a tap-to-open WhatsApp alert (GCC standard).
      </p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="aa-field">
          <label>Notify Email</label>
          <input type="email" class="aa-input" name="notify_email"
            value="<?php echo esc_attr( $settings['notify_email'] ?? '' ); ?>"
            placeholder="<?php echo esc_attr( get_option('admin_email') ); ?>">
          <div class="aa-field__desc">Leave blank to use the WordPress admin email.</div>
        </div>
        <div class="aa-field">
          <label>WhatsApp Number (for alerts)</label>
          <input type="text" class="aa-input" name="whatsapp_number"
            value="<?php echo esc_attr( $settings['whatsapp_number'] ?? '' ); ?>"
            placeholder="+97333XXXXXX">
          <div class="aa-field__desc">International format. A WhatsApp deep-link is logged on escalation so the admin can tap to reply. Standard in Bahrain/GCC.</div>
        </div>
      </div>
      <div style="margin-top:16px;">
        <div class="aa-field">
          <label>Webhook Secret (N8N / External)</label>
          <input type="text" class="aa-input" name="webhook_secret"
            value="<?php echo esc_attr( $settings['webhook_secret'] ?? '' ); ?>"
            placeholder="Leave blank to disable auth">
          <p class="aa-field__desc">Set a random secret and use it as the <code>X-Webhook-Secret</code> header in N8N. Leave blank to allow unauthenticated calls (not recommended for production). You can also regenerate this from the <a href="admin.php?page=aiagent-workspace">Workspace &rarr; WhatsApp / N8N tab</a>.</p>
        </div>
      </div>
    </div>

    <!-- ── Data Retention (PDPL Compliance) ──────────────────────────────── -->
    <div class="aa-section-title">🔒 Data Retention &amp; Privacy (PDPL)</div>
    <div class="aa-card">
      <p style="color:#64748b;font-size:13px;margin-bottom:16px;">
        Bahrain's Personal Data Protection Law (PDPL) requires a defined data retention period.
        Chat logs, messages, and semantic cache entries older than the configured number of days
        are automatically deleted by a daily background job. <strong>Minimum recommended: 30 days. Maximum: 365 days.</strong>
      </p>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="aa-field">
          <label>Chat Log Retention (days)</label>
          <input type="number" class="aa-input" name="data_retention_days"
            value="<?php echo esc_attr( $settings['data_retention_days'] ?? 90 ); ?>"
            min="30" max="365">
          <div class="aa-field__desc">Conversations and messages older than this are purged daily. Tickets are purged too. Taught examples and manual chunks are kept indefinitely.</div>
        </div>
        <div class="aa-field" style="display:flex;flex-direction:column;justify-content:center;">
          <?php
          $next_run = wp_next_scheduled( 'aiagent_retention_cron' );
          if ( $next_run ) {
            echo '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;font-size:13px;color:#166534;">';
            echo '✅ Retention job scheduled — next run: <strong>' . esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_run ), 'D j M H:i' ) ) . '</strong>';
            echo '</div>';
          } else {
            echo '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;font-size:13px;color:#991b1b;">';
            echo '⚠️ Retention cron not scheduled. Deactivate and reactivate the plugin to register it.';
            echo '</div>';
          }
          ?>
        </div>
      </div>
    </div>

    <!-- ── Widget ──────────────────────────────────────────────────────────── -->
    <div class="aa-section-title">💬 Widget</div>
    <div class="aa-card">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="aa-field">
          <label>Widget Title (English)</label>
          <input type="text" class="aa-input" name="widget_title_en" value="<?php echo esc_attr($settings['widget_title_en']); ?>">
        </div>
        <div class="aa-field">
          <label>Widget Title (Arabic)</label>
          <input type="text" class="aa-input" name="widget_title_ar" value="<?php echo esc_attr($settings['widget_title_ar']); ?>" dir="rtl">
        </div>
        <div class="aa-field">
          <label>Widget Position</label>
          <select class="aa-select" name="widget_position">
            <option value="bottom-right" <?php selected($settings['widget_position'],'bottom-right'); ?>>Bottom Right</option>
            <option value="bottom-left"  <?php selected($settings['widget_position'],'bottom-left');  ?>>Bottom Left</option>
          </select>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:4px;">
        <div class="aa-field">
          <label>Fallback Message (English)</label>
          <textarea class="aa-textarea" name="fallback_message_en" rows="3"><?php echo esc_textarea($settings['fallback_message_en']); ?></textarea>
          <div class="aa-field__desc">Shown when all AI providers are unavailable.</div>
        </div>
        <div class="aa-field">
          <label>Fallback Message (Arabic)</label>
          <textarea class="aa-textarea" name="fallback_message_ar" rows="3" dir="rtl"><?php echo esc_textarea($settings['fallback_message_ar']); ?></textarea>
        </div>
      </div>
    </div>

    <div style="margin-top:4px;">
      <button type="submit" class="aa-btn aa-btn--primary" style="padding:12px 32px;font-size:15px;">💾 Save Settings</button>
    </div>

  </form>

  <div class="aa-section-title" style="margin-top:32px;">📋 Shortcode</div>
  <div class="aa-card">
    <p style="color:#64748b;font-size:13px;margin-bottom:10px;">Paste into any page or template to embed the chat widget:</p>
    <code style="background:#f1f5f9;padding:8px 14px;border-radius:6px;font-size:14px;display:inline-block;">[aiagent_chat]</code>
  </div>
</div>

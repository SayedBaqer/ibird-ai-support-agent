<?php defined( 'ABSPATH' ) || exit;
// Variables injected by page_workspace(): $nonce, $rest_url, $ajax_url, $upload_nonce
$pdf_nonce      = wp_create_nonce( 'aiagent_manual_upload' );
$aiagent_nonce  = wp_create_nonce( 'aiagent_nonce' );
$current_ver    = AIAGENT_VERSION;
$github_repo    = AIAGENT_GITHUB_REPO;
?>
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>
/* ── Reset & full-screen wrapper ── */
html,body,#wpwrap,#wpcontent,#wpbody,#wpbody-content{height:100%!important;overflow:hidden!important;}
#wpcontent{padding-left:0!important;}
.wrap.aa-ws{
  padding:0!important;margin:0!important;
  height:100vh!important;height:100dvh!important;
  display:flex!important;flex-direction:row!important;
  overflow:hidden!important;background:#f0f4f8!important;
}
body.admin-bar .wrap.aa-ws{height:calc(100vh - 32px)!important;height:calc(100dvh - 32px)!important;}
/* ── Desktop Sidebar ── */
.aa-ws-sidebar{width:220px!important;flex-shrink:0!important;background:#1e293b!important;display:flex!important;flex-direction:column!important;padding-top:16px!important;overflow-y:auto!important;}
.aa-ws-logo{padding:0 18px 16px!important;color:#fff!important;font-size:16px!important;font-weight:800!important;letter-spacing:-.3px!important;border-bottom:1px solid rgba(255,255,255,.1)!important;}
.aa-ws-logo span{display:block;font-size:10px;font-weight:400;color:rgba(255,255,255,.4);margin-top:2px;letter-spacing:.3px;}
.aa-ws-nav{padding:12px 0!important;flex:1!important;}
.aa-ws-nav-group{padding:8px 18px 4px!important;font-size:9px!important;font-weight:700!important;color:rgba(255,255,255,.3)!important;text-transform:uppercase!important;letter-spacing:.8px!important;}
.aa-ws-tab{display:flex!important;align-items:center!important;justify-content:space-between!important;width:100%!important;text-align:left!important;background:none!important;border:none!important;color:rgba(255,255,255,.7)!important;padding:10px 18px!important;font-size:13px!important;font-weight:600!important;cursor:pointer!important;border-left:3px solid transparent!important;}
.aa-ws-tab:hover{background:rgba(255,255,255,.07)!important;color:#fff!important;}
.aa-ws-tab.active{background:rgba(26,107,60,.5)!important;color:#fff!important;border-left-color:#4ade80!important;}
.aa-ws-badge-count{background:rgba(255,255,255,.15);color:#fff;border-radius:50px;font-size:10px;font-weight:700;padding:1px 7px;min-width:18px;text-align:center;}
.aa-ws-badge-count.hot{background:#ef4444;color:#fff;}
.aa-ws-footer{padding:14px 18px!important;border-top:1px solid rgba(255,255,255,.1)!important;font-size:11px!important;color:rgba(255,255,255,.4)!important;}
/* ── Main ── */
.aa-ws-main{flex:1!important;display:flex!important;flex-direction:column!important;overflow:hidden!important;min-width:0!important;}
.aa-ws-panel{flex:1!important;overflow:hidden!important;display:none!important;}
.aa-ws-panel.active{display:flex!important;flex-direction:column!important;}
/* ── Mobile bottom nav ── */
.aa-ws-mob-nav{display:none!important;position:fixed!important;bottom:0!important;left:0!important;right:0!important;background:#1e293b!important;z-index:9999!important;padding:6px 0 max(calc(env(safe-area-inset-bottom) - 18px),4px)!important;border-top:1px solid rgba(255,255,255,.1)!important;min-height:52px!important;box-sizing:border-box!important;}
.aa-ws-mob-nav-inner{display:flex!important;justify-content:space-around!important;}
.aa-ws-mob-btn{background:none!important;border:none!important;color:rgba(255,255,255,.6)!important;font-size:9px!important;font-weight:700!important;text-align:center!important;padding:6px 4px!important;cursor:pointer!important;min-width:52px!important;position:relative!important;}
.aa-ws-mob-btn.active{color:#4ade80!important;}
.aa-ws-mob-btn-icon{font-size:20px!important;display:block!important;margin-bottom:2px!important;}
.aa-ws-mob-hot{position:absolute!important;top:4px!important;right:8px!important;background:#ef4444!important;color:#fff!important;border-radius:50%!important;width:14px!important;height:14px!important;font-size:9px!important;display:flex!important;align-items:center!important;justify-content:center!important;font-weight:700!important;}
/* ── Mobile header bar ── */
.aa-ws-mob-hdr{display:none!important;background:#1e293b!important;padding:12px 16px 12px!important;padding-top:calc(12px + env(safe-area-inset-top))!important;color:#fff!important;font-weight:800!important;font-size:16px!important;flex-shrink:0!important;align-items:center!important;gap:12px!important;}
.aa-ws-mob-back{background:none!important;border:none!important;color:#fff!important;font-size:22px!important;cursor:pointer!important;padding:0 4px!important;line-height:1!important;}
/* ── Conversation layout ── */
.aa-ws-conv-layout{display:flex!important;flex:1!important;overflow:hidden!important;}
.aa-ws-conv-list{width:290px!important;flex-shrink:0!important;border-right:1px solid #e2e8f0!important;background:#fff!important;display:flex!important;flex-direction:column!important;overflow:hidden!important;}
.aa-ws-conv-top{padding:10px 12px 0!important;border-bottom:1px solid #f1f5f9!important;}
.aa-ws-conv-search{padding:0 0 8px!important;}
.aa-ws-conv-search input{width:100%!important;border:1.5px solid #e2e8f0!important;border-radius:8px!important;padding:8px 12px!important;font-size:16px!important;outline:none!important;box-sizing:border-box!important;}
.aa-ws-conv-search input:focus{border-color:#1a6b3c!important;}
.aa-ws-filter-tabs{display:flex!important;gap:4px!important;overflow-x:auto!important;padding-bottom:8px!important;-webkit-overflow-scrolling:touch!important;}
.aa-ws-filter-tab{flex-shrink:0!important;padding:6px 12px!important;border-radius:50px!important;border:1.5px solid #e2e8f0!important;background:#fff!important;font-size:12px!important;font-weight:700!important;color:#64748b!important;cursor:pointer!important;white-space:nowrap!important;}
.aa-ws-filter-tab.active{background:#1a6b3c!important;color:#fff!important;border-color:#1a6b3c!important;}
.aa-ws-conv-scroll{flex:1!important;overflow-y:auto!important;-webkit-overflow-scrolling:touch!important;}
.aa-ws-ci{padding:14px!important;border-bottom:1px solid #f1f5f9!important;cursor:pointer!important;}
.aa-ws-ci.active{background:#e8f5e9!important;border-left:3px solid #1a6b3c!important;}
.aa-ws-ci-top{display:flex!important;align-items:center!important;gap:6px!important;margin-bottom:4px!important;flex-wrap:wrap!important;}
.aa-ws-ci-id{font-size:13px!important;font-weight:700!important;color:#1e293b!important;}
.aa-ws-ci-preview{font-size:12px!important;color:#64748b!important;white-space:nowrap!important;overflow:hidden!important;text-overflow:ellipsis!important;}
.aa-ws-ci-meta{display:flex!important;align-items:center!important;justify-content:space-between!important;margin-top:4px!important;}
.aa-ws-ci-time{font-size:11px!important;color:#94a3b8!important;}
/* ── Detail ── */
.aa-ws-conv-detail{flex:1!important;display:flex!important;flex-direction:column!important;overflow:hidden!important;background:#f6f7fb!important;min-width:0!important;}
.aa-ws-det-hdr{padding:12px 16px!important;background:#fff!important;border-bottom:1px solid #e2e8f0!important;flex-shrink:0!important;display:flex!important;align-items:center!important;justify-content:space-between!important;gap:8px!important;}
.aa-ws-det-hdr-left{display:flex!important;align-items:center!important;gap:6px!important;flex-wrap:wrap!important;min-width:0!important;}
.aa-ws-det-hdr-actions{display:flex!important;gap:6px!important;flex-shrink:0!important;}
.aa-ws-det-body{flex:1!important;overflow-y:auto!important;padding:16px!important;-webkit-overflow-scrolling:touch!important;}
.aa-ws-msg{margin-bottom:12px!important;}
.aa-ws-msg.cust{display:flex!important;flex-direction:column!important;align-items:flex-end!important;}
.aa-ws-msg.ai,.aa-ws-msg.human{display:flex!important;flex-direction:column!important;align-items:flex-start!important;}
.aa-ws-bubble{padding:11px 15px!important;font-size:14px!important;line-height:1.6!important;max-width:80%!important;word-break:break-word!important;border-radius:14px!important;}
.aa-ws-msg.ai .aa-ws-bubble{background:#fff!important;border-radius:4px 14px 14px 14px!important;box-shadow:0 1px 4px rgba(0,0,0,.08)!important;}
.aa-ws-msg.cust .aa-ws-bubble{background:#1a6b3c!important;color:#fff!important;border-radius:14px 14px 4px 14px!important;}
.aa-ws-msg.human .aa-ws-bubble{background:#1d4ed8!important;color:#fff!important;border-radius:14px 14px 4px 14px!important;}
.aa-ws-msg-meta{font-size:11px!important;color:#94a3b8!important;margin-top:3px!important;}
.aa-ws-msg-actions{display:flex!important;gap:4px!important;margin-top:6px!important;flex-wrap:wrap!important;}
.aa-ws-btn-sm{padding:5px 12px!important;border-radius:50px!important;border:1.5px solid #e2e8f0!important;background:#fff!important;font-size:12px!important;font-weight:600!important;cursor:pointer!important;color:#475569!important;}
/* ── Reply bar ── */
.aa-ws-reply-bar{flex-shrink:0!important;background:#fff!important;border-top:1px solid #e2e8f0!important;padding:10px 14px!important;padding-bottom:calc(10px + env(safe-area-inset-bottom))!important;display:flex!important;gap:10px!important;align-items:flex-end!important;}
.aa-ws-reply-bar textarea{flex:1!important;border:1.5px solid #e2e8f0!important;border-radius:22px!important;padding:10px 16px!important;font-family:inherit!important;font-size:16px!important;resize:none!important;min-height:42px!important;max-height:120px!important;outline:none!important;line-height:1.45!important;color:#1e293b!important;}
.aa-ws-reply-bar textarea:focus{border-color:#1a6b3c!important;}
.aa-ws-reply-bar button{background:#1a6b3c!important;color:#fff!important;border:none!important;border-radius:50%!important;width:42px!important;height:42px!important;font-size:18px!important;cursor:pointer!important;flex-shrink:0!important;display:flex!important;align-items:center!important;justify-content:center!important;}
/* ── Badges ── */
.aa-ws-badge{display:inline-flex!important;padding:2px 8px!important;border-radius:50px!important;font-size:10px!important;font-weight:700!important;text-transform:uppercase!important;}
.aa-ws-badge.product{background:#e3f2fd!important;color:#1565c0!important;}
.aa-ws-badge.support{background:#fce4ec!important;color:#c62828!important;}
.aa-ws-badge.active{background:#e8f5e9!important;color:#2e7d32!important;}
.aa-ws-badge.escalated{background:#fff3e0!important;color:#e65100!important;}
.aa-ws-badge.claimed{background:#ede9fe!important;color:#6d28d9!important;}
.aa-ws-badge.closed{background:#f5f5f5!important;color:#757575!important;}
.aa-ws-badge.wa{background:#dcfce7!important;color:#166534!important;}
.aa-ws-badge.web{background:#e0f2fe!important;color:#0369a1!important;}
.aa-ws-hdr-btn{padding:8px 16px!important;border-radius:8px!important;font-size:13px!important;font-weight:700!important;border:none!important;cursor:pointer!important;}
.aa-ws-hdr-btn.green{background:#1a6b3c!important;color:#fff!important;}
.aa-ws-hdr-btn.red{background:#fee2e2!important;color:#991b1b!important;}
.aa-ws-hdr-btn.blue{background:#dbeafe!important;color:#1d4ed8!important;}
/* ── Teach ── */
.aa-ws-teach{padding:20px!important;max-width:700px!important;}
.aa-ws-teach h2{font-size:18px!important;font-weight:800!important;color:#1e293b!important;margin-bottom:6px!important;}
.aa-ws-teach p{color:#64748b!important;font-size:13px!important;margin-bottom:20px!important;}
.aa-ws-field{margin-bottom:16px!important;}
.aa-ws-label{display:block!important;font-size:11px!important;font-weight:700!important;color:#64748b!important;text-transform:uppercase!important;letter-spacing:.5px!important;margin-bottom:6px!important;}
.aa-ws-input,.aa-ws-ta{display:block!important;width:100%!important;border:1.5px solid #e2e8f0!important;border-radius:8px!important;padding:12px 14px!important;font-size:16px!important;font-family:inherit!important;color:#1e293b!important;background:#fff!important;outline:none!important;box-sizing:border-box!important;}
.aa-ws-ta{resize:vertical!important;min-height:100px!important;}
.aa-ws-input:focus,.aa-ws-ta:focus{border-color:#1a6b3c!important;}
.aa-ws-save-btn{background:#1a6b3c!important;color:#fff!important;border:none!important;border-radius:10px!important;padding:14px 28px!important;font-size:15px!important;font-weight:700!important;cursor:pointer!important;}
/* ── Training Chat ── */
.aa-ws-tchat{display:flex!important;flex-direction:column!important;flex:1!important;overflow:hidden!important;background:#f0f4f8!important;}
.aa-ws-tchat-hdr{padding:14px 16px!important;background:#1e293b!important;flex-shrink:0!important;display:flex!important;align-items:center!important;justify-content:space-between!important;}
.aa-ws-tchat-hdr h2{color:#fff!important;font-size:15px!important;font-weight:700!important;margin:0!important;}
.aa-ws-tchat-hdr p{color:rgba(255,255,255,.6)!important;font-size:11px!important;margin:0!important;}
.aa-ws-tchat-msgs{flex:1!important;overflow-y:auto!important;padding:14px 16px!important;-webkit-overflow-scrolling:touch!important;}
.aa-tc-bbl{display:block!important;width:fit-content!important;max-width:82%!important;margin-bottom:12px!important;}
.aa-tc-bbl.admin{margin-left:auto!important;margin-right:4px!important;}
.aa-tc-bbl.ai{margin-right:auto!important;margin-left:4px!important;}
.aa-tc-body{padding:12px 16px!important;font-size:14px!important;line-height:1.65!important;word-break:break-word!important;border-radius:18px!important;}
.aa-tc-bbl.admin .aa-tc-body{background:#1a6b3c!important;color:#fff!important;border-radius:18px 18px 4px 18px!important;}
.aa-tc-bbl.ai .aa-tc-body{background:#fff!important;color:#1a1a2e!important;border-radius:18px 18px 18px 4px!important;box-shadow:0 2px 8px rgba(0,0,0,.09)!important;}
.aa-tc-meta{font-size:10px!important;color:#94a3b8!important;margin-top:3px!important;padding:0 4px!important;display:block!important;}
.aa-tc-bbl.admin .aa-tc-meta{text-align:right!important;}
.aa-tc-save-card{background:#e8f5e9!important;border:1.5px solid #1a6b3c!important;border-radius:12px!important;padding:12px 16px!important;margin-top:8px!important;}
.aa-tc-save-card p{font-size:12px!important;font-weight:700!important;color:#145530!important;margin:0 0 8px!important;}
.aa-tc-save-card .qa-q,.aa-tc-save-card .qa-a{font-size:12px!important;background:#fff!important;border-radius:6px!important;padding:6px 10px!important;margin-bottom:6px!important;color:#1e293b!important;}
.aa-tc-save-card .qa-q::before{content:'Q: '!important;font-weight:700!important;color:#1a6b3c!important;}
.aa-tc-save-card .qa-a::before{content:'A: '!important;font-weight:700!important;color:#64748b!important;}
.aa-tc-save-btn{background:#1a6b3c!important;color:#fff!important;border:none!important;border-radius:8px!important;padding:9px 18px!important;font-size:13px!important;font-weight:700!important;cursor:pointer!important;}
.aa-tc-edit-btn{background:#fff!important;border:1.5px solid #e2e8f0!important;border-radius:8px!important;padding:9px 14px!important;font-size:13px!important;font-weight:600!important;cursor:pointer!important;color:#64748b!important;margin-left:6px!important;}
.aa-ws-tchat-inp{flex-shrink:0!important;background:#fff!important;border-top:1px solid #e2e8f0!important;padding:10px 12px!important;padding-bottom:calc(10px + env(safe-area-inset-bottom))!important;display:flex!important;gap:8px!important;align-items:flex-end!important;}
.aa-ws-tchat-inp textarea{flex:1!important;border:1.5px solid #e2e8f0!important;border-radius:22px!important;padding:11px 16px!important;font-family:inherit!important;font-size:16px!important;resize:none!important;min-height:44px!important;max-height:120px!important;outline:none!important;line-height:1.45!important;color:#1e293b!important;}
.aa-ws-tchat-inp textarea:focus{border-color:#1a6b3c!important;}
.aa-ws-tchat-inp button{background:#1a6b3c!important;color:#fff!important;border:none!important;border-radius:50%!important;width:44px!important;height:44px!important;font-size:18px!important;cursor:pointer!important;flex-shrink:0!important;display:flex!important;align-items:center!important;justify-content:center!important;}
.aa-ws-tchat-inp button:disabled{background:#cbd5e1!important;}
.aa-tc-typing{display:block!important;margin-left:4px!important;margin-bottom:12px!important;}
.aa-tc-typing-body{background:#fff!important;border-radius:18px 18px 18px 4px!important;padding:12px 16px!important;box-shadow:0 2px 8px rgba(0,0,0,.09)!important;display:inline-flex!important;align-items:center!important;gap:5px!important;}
.aa-tc-dot{width:7px!important;height:7px!important;background:#94a3b8!important;border-radius:50%!important;animation:tcbounce 1.2s ease-in-out infinite!important;}
.aa-tc-dot:nth-child(2){animation-delay:.22s!important;}.aa-tc-dot:nth-child(3){animation-delay:.44s!important;}
@keyframes tcbounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-7px)}}
/* ── Stats ── */
.aa-ws-stats{padding:20px!important;display:grid!important;grid-template-columns:repeat(auto-fit,minmax(140px,1fr))!important;gap:12px!important;align-content:start!important;overflow-y:auto!important;-webkit-overflow-scrolling:touch!important;}
.aa-ws-stat-card{background:#fff!important;border:1px solid #e2e8f0!important;border-radius:12px!important;padding:16px!important;}
.aa-ws-stat-num{font-size:30px!important;font-weight:800!important;color:#1a6b3c!important;line-height:1!important;}
.aa-ws-stat-label{font-size:11px!important;color:#64748b!important;margin-top:6px!important;font-weight:600!important;}
/* ── Manuals ── */
.aa-ws-manuals{padding:20px!important;overflow-y:auto!important;-webkit-overflow-scrolling:touch!important;}
.aa-ws-manuals h2{font-size:18px!important;font-weight:800!important;color:#1e293b!important;margin-bottom:6px!important;}
.aa-ws-manuals p{color:#64748b!important;font-size:13px!important;margin-bottom:16px!important;}
/* ── Serial ── */
.aa-ws-serial{padding:20px!important;}
/* ── Empty ── */
.aa-ws-empty{flex:1!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;color:#94a3b8!important;gap:12px!important;}

/* ══════════════════════════════════════════
   MOBILE — full-screen app, no WP chrome
══════════════════════════════════════════ */
@media (max-width: 768px) {
  /* Strip every WordPress admin UI element */
  #wpadminbar,
  #adminmenumain,#adminmenuback,#adminmenuwrap,
  #wpfooter,#screen-meta,#screen-meta-links { display:none!important; }

  /* True full-screen — remove WP margins */
  html,body,#wpwrap,#wpcontent,#wpbody,#wpbody-content {
    margin:0!important;padding:0!important;
    height:100dvh!important;overflow:hidden!important;
  }
  #wpcontent { margin-left:0!important; }

  /* Workspace: fixed full-screen, leave 58px for bottom nav */
  .wrap.aa-ws {
    position:fixed!important;
    top:0!important;left:0!important;right:0!important;
    bottom:52px!important;
    height:auto!important;
    flex-direction:column!important;
  }

  /* Hide iBird desktop sidebar — bottom nav handles navigation */
  .aa-ws-sidebar { display:none!important; }

  /* Show mobile top header + bottom nav */
  .aa-ws-mob-hdr { display:flex!important; }
  .aa-ws-mob-nav { display:block!important; }

  /* Content fills full width */
  .aa-ws-main { width:100%!important; }

  /* Conversations: list full-width; detail slides over */
  .aa-ws-conv-layout { position:relative!important; }
  .aa-ws-conv-list {
    width:100%!important;border-right:none!important;
    position:absolute!important;inset:0!important;z-index:1!important;
  }
  .aa-ws-conv-detail {
    position:absolute!important;inset:0!important;z-index:2!important;
    transform:translateX(100%)!important;
    transition:transform .25s ease!important;
  }
  .aa-ws-conv-detail.mob-open { transform:translateX(0)!important; }

  /* Tap targets */
  .aa-ws-ci { padding:16px 14px!important; }
  .aa-ws-filter-tab { padding:8px 14px!important;font-size:12px!important; }

  /* Scrollable form panels */
  .aa-ws-teach,.aa-ws-manuals,.aa-ws-serial {
    padding:16px!important;overflow-y:auto!important;
    -webkit-overflow-scrolling:touch!important;flex:1!important;
  }
  .aa-ws-det-body,.aa-ws-tchat-msgs,.aa-ws-stats,.aa-ws-conv-scroll {
    padding-bottom:12px!important;
  }
}
.aa-ws-mob-link-card{display:block;background:rgba(255,255,255,.07);border-radius:8px;margin:10px 12px 0;padding:10px 12px;}
.aa-ws-mob-link-card p{color:rgba(255,255,255,.5);font-size:10px;margin:0 0 6px;text-transform:uppercase;letter-spacing:.5px;font-weight:700;}
.aa-ws-mob-link-card a{color:#4ade80;font-size:11px;word-break:break-all;text-decoration:none;}
.aa-ws-mob-link-card button{margin-top:7px;width:100%;background:#1a6b3c;color:#fff;border:none;border-radius:6px;padding:7px 0;font-size:11px;font-weight:700;cursor:pointer;}
</style>

<div class="wrap aa-ws">

  <!-- ── Sidebar overlay (mobile backdrop) ── -->
  <div class="aa-ws-sidebar-overlay" id="aa-sb-overlay" onclick="wsSidebarClose()"></div>

  <!-- ── Mobile header bar ── -->
  <div class="aa-ws-mob-hdr" id="aa-mob-hdr">
    <button class="aa-ws-mob-back" id="aa-mob-back" onclick="wsBack()" style="display:none;">‹</button>
    <span id="aa-mob-title" style="flex:1;">💬 Conversations</span>
    <button onclick="wsSidebarToggle()" style="background:none;border:none;color:#fff;font-size:22px;cursor:pointer;padding:0 4px;line-height:1;">☰</button>
  </div>

  <!-- ── Mobile bottom nav ── -->
  <div class="aa-ws-mob-nav">
    <div class="aa-ws-mob-nav-inner">
      <button class="aa-ws-mob-btn active" data-tab="conv"    onclick="wsTab('conv',this)">
        <span class="aa-ws-mob-btn-icon">💬</span>Inbox
        <span class="aa-ws-mob-hot" id="mob-hot" style="display:none;"></span>
      </button>
      <button class="aa-ws-mob-btn" data-tab="tchat"   onclick="wsTab('tchat',this)"><span class="aa-ws-mob-btn-icon">🧠</span>Train</button>
      <button class="aa-ws-mob-btn" data-tab="teach"   onclick="wsTab('teach',this)"><span class="aa-ws-mob-btn-icon">🎓</span>Teach</button>
      <button class="aa-ws-mob-btn" data-tab="manuals" onclick="wsTab('manuals',this)"><span class="aa-ws-mob-btn-icon">📄</span>Manuals</button>
      <button class="aa-ws-mob-btn" data-tab="n8n"     onclick="wsTab('n8n',this)"><span class="aa-ws-mob-btn-icon">📱</span>WhatsApp</button>
      <button class="aa-ws-mob-btn" data-tab="update"  onclick="wsTab('update',this)" id="mob-update-btn"><span class="aa-ws-mob-btn-icon">🔄</span>Update<span class="aa-ws-mob-hot" id="mob-update-dot" style="display:none;">!</span></button>
      <?php if ( defined( 'ISUITE_VERSION' ) ) : ?>
      <button class="aa-ws-mob-btn" data-tab="istock" onclick="wsTab('istock',this)"><span class="aa-ws-mob-btn-icon">📦</span>iStock</button>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Sidebar ── -->
  <div class="aa-ws-sidebar">
    <div class="aa-ws-logo">⚡ AI Workspace <span>iBird Support Agent</span></div>
    <?php if ( defined( 'ISUITE_VERSION' ) ) : ?>
    <button onclick="wsTab('istock',this)" data-tab="istock"
       class="aa-ws-tab" style="border-left-color:transparent;justify-content:flex-start;gap:8px;margin:4px 8px;border-radius:8px;padding:9px 10px;background:rgba(255,255,255,.06);border:1.5px solid rgba(255,255,255,.1);border-left:3px solid transparent;">
      <span style="font-size:15px;">📦</span> iStock App
    </button>
    <?php endif; ?>
    <nav class="aa-ws-nav">
      <div class="aa-ws-nav-group">Customer</div>
      <button class="aa-ws-tab active" data-tab="conv" onclick="wsTab('conv',this)">
        💬 Conversations <span class="aa-ws-badge-count" id="sb-conv-badge" style="display:none;"></span>
      </button>

      <div class="aa-ws-nav-group">Training</div>
      <button class="aa-ws-tab" data-tab="tchat"    onclick="wsTab('tchat',this)">🧠 Train by Chat</button>
      <button class="aa-ws-tab" data-tab="teach"    onclick="wsTab('teach',this)">🎓 Teach (Form)</button>
      <button class="aa-ws-tab" data-tab="manuals"  onclick="wsTab('manuals',this)">📄 Manuals</button>

      <div class="aa-ws-nav-group">Tools</div>
      <button class="aa-ws-tab" data-tab="serials"  onclick="wsTab('serials',this)">🔑 Serial Lookup</button>
      <button class="aa-ws-tab" data-tab="stats"    onclick="wsTab('stats',this)">📊 Stats</button>
      <button class="aa-ws-tab" data-tab="n8n"      onclick="wsTab('n8n',this)">📱 WhatsApp / N8N</button>
      <button class="aa-ws-tab" data-tab="update"   onclick="wsTab('update',this)" id="sb-update-tab">🔄 Update</button>
    </nav>
    <!-- Mobile bookmark link -->
    <div class="aa-ws-mob-link-card">
      <p>📲 Mobile Bookmark</p>
      <a href="<?php echo esc_url( admin_url( 'admin.php?page=aiagent-workspace' ) ); ?>" id="aa-mob-link">
        <?php echo esc_html( admin_url( 'admin.php?page=aiagent-workspace' ) ); ?>
      </a>
      <button onclick="wsMobCopyLink()">📋 Copy link</button>
    </div>
    <div class="aa-ws-footer">iBird AI Agent v<?php echo esc_html( AIAGENT_VERSION ); ?></div>
  </div>

  <!-- ── Main ── -->
  <div class="aa-ws-main">

    <!-- ════ CONVERSATIONS ════ -->
    <div class="aa-ws-panel active" id="ws-conv">
      <div class="aa-ws-conv-layout">

        <!-- List -->
        <div class="aa-ws-conv-list">
          <div class="aa-ws-conv-top">
            <div class="aa-ws-conv-search">
              <input type="search" id="ws-search" placeholder="🔍 Search…">
            </div>
            <div class="aa-ws-filter-tabs" id="ws-filter-tabs">
              <button class="aa-ws-filter-tab active" data-status="" onclick="wsSetFilter('',this)">All</button>
              <button class="aa-ws-filter-tab" data-status="active"    onclick="wsSetFilter('active',this)">Active</button>
              <button class="aa-ws-filter-tab" data-status="escalated" onclick="wsSetFilter('escalated',this)">🔥 Escalated</button>
              <button class="aa-ws-filter-tab" data-status="claimed"   onclick="wsSetFilter('claimed',this)">Claimed</button>
              <button class="aa-ws-filter-tab" data-status="closed"    onclick="wsSetFilter('closed',this)">Closed</button>
            </div>
          </div>
          <div class="aa-ws-conv-scroll" id="ws-list">
            <div style="padding:20px;text-align:center;color:#94a3b8;font-size:13px;">Loading…</div>
          </div>
        </div>

        <!-- Detail -->
        <div class="aa-ws-conv-detail" id="ws-det">
          <div class="aa-ws-empty" id="ws-det-empty">
            <div style="font-size:48px;">💬</div>
            <div style="font-weight:700;font-size:15px;color:#475569;">Select a conversation</div>
            <div style="font-size:13px;">Click any row on the left to open the thread.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ════ TRAINING CHAT ════ -->
    <div class="aa-ws-panel" id="ws-tchat">
      <div class="aa-ws-tchat">
        <div class="aa-ws-tchat-hdr">
          <div>
            <h2>🧠 Train by Chat</h2>
            <p>Teach the AI by chatting — it extracts Q&amp;A and saves to the knowledge base.</p>
          </div>
          <button onclick="wsTchatClear()" style="background:rgba(255,255,255,.15);color:#fff;border:none;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;">🗑 Clear</button>
        </div>
        <div class="aa-ws-tchat-msgs" id="tc-msgs"></div>
        <div id="tc-img-bar" style="display:none;padding:8px 16px 0;background:#fff;border-top:1px solid #e2e8f0;flex-shrink:0;">
          <div style="position:relative;display:inline-block;">
            <img id="tc-img-thumb" src="" alt="" style="height:64px;width:auto;border-radius:8px;border:2px solid #1a6b3c;display:block;">
            <button type="button" onclick="tcClearImage()" style="position:absolute;top:-8px;right:-8px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:13px;cursor:pointer;padding:0;">×</button>
          </div>
          <div id="tc-img-status" style="font-size:11px;color:#94a3b8;margin-top:4px;"></div>
        </div>
        <div class="aa-ws-tchat-inp">
          <input type="file" id="tc-file" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="tcHandleFile(this)">
          <button type="button" onclick="document.getElementById('tc-file').click()" title="Upload photo" style="background:#f1f5f9;border:1.5px solid #e2e8f0;border-radius:50%;width:42px;height:42px;font-size:18px;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center;" id="tc-photo-btn">📷</button>
          <textarea id="tc-inp" rows="1" placeholder="Tell the AI what to know, or 📷 upload a product photo…"></textarea>
          <button id="tc-send" onclick="tcSend()">➤</button>
        </div>
      </div>
    </div>

    <!-- ════ TEACH FORM ════ -->
    <div class="aa-ws-panel" id="ws-teach">
      <div style="overflow-y:auto;flex:1;">
        <div class="aa-ws-teach">
          <h2>🎓 Teach the AI</h2>
          <p>Add a question-answer pair directly to the knowledge base.</p>
          <div class="aa-ws-field">
            <label class="aa-ws-label">Customer Question</label>
            <input type="text" class="aa-ws-input" id="ws-teach-q" placeholder="e.g. What is the warranty period for IBR-2000?">
          </div>
          <div class="aa-ws-field">
            <label class="aa-ws-label">Correct Answer</label>
            <textarea class="aa-ws-ta" id="ws-teach-a" placeholder="e.g. The IBR-2000 comes with a 12-month warranty…"></textarea>
          </div>
          <div style="display:flex;align-items:center;gap:14px;">
            <button class="aa-ws-save-btn" onclick="wsTeachSave()">💾 Save to Knowledge Base</button>
            <span id="ws-teach-res" style="font-size:13px;"></span>
          </div>
          <hr style="margin:28px 0;border:none;border-top:1px solid #e2e8f0;">
          <h3 style="font-size:15px;font-weight:700;margin-bottom:14px;">Recent Examples</h3>
          <div id="ws-teach-list" style="font-size:13px;color:#64748b;">Loading…</div>
        </div>
      </div>
    </div>

    <!-- ════ MANUALS ════ -->
    <div class="aa-ws-panel" id="ws-manuals">
      <div style="overflow-y:auto;flex:1;">
        <div class="aa-ws-manuals">
          <h2>📄 Upload Manual</h2>
          <p>Upload a PDF product manual. Gemini extracts all content, which is chunked, embedded and saved — AI can answer from it immediately.</p>

          <div class="aa-ws-field" style="position:relative;">
            <label class="aa-ws-label">Product Model</label>
            <input type="text" class="aa-ws-input" id="mn-model" placeholder="Type model name…" autocomplete="off">
            <div id="mn-model-drop" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #1a6b3c;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);z-index:999;max-height:200px;overflow-y:auto;"></div>
          </div>

          <div class="aa-ws-field">
            <label class="aa-ws-label">PDF File</label>
            <input type="file" id="mn-file" accept=".pdf" style="width:100%;margin-top:4px;">
            <div style="font-size:11px;color:#94a3b8;margin-top:4px;">Max 50 MB. Scanned PDFs are supported via Gemini Vision.</div>
          </div>

          <button class="aa-ws-save-btn" id="mn-btn" onclick="mnIngest()" style="width:100%;">⚡ Ingest Manual</button>
          <div id="mn-result" style="margin-top:14px;display:none;"></div>
        </div>
      </div>
    </div>

    <!-- ════ SERIAL LOOKUP ════ -->
    <div class="aa-ws-panel" id="ws-serials">
      <div style="overflow-y:auto;flex:1;">
        <div class="aa-ws-serial">
          <h2 style="font-size:18px;font-weight:800;color:#1e293b;margin-bottom:6px;">🔑 Serial Lookup</h2>
          <p style="color:#64748b;font-size:13px;margin-bottom:20px;">Search serial registry by serial number or customer phone.</p>
          <div style="display:flex;gap:10px;margin-bottom:16px;">
            <input type="text" class="aa-ws-input" id="ws-ser-search" placeholder="Serial number or phone…" style="flex:1;">
            <button onclick="wsSerialSearch()" style="background:#1a6b3c;color:#fff;border:none;border-radius:10px;padding:10px 20px;font-size:14px;font-weight:700;cursor:pointer;">🔍 Search</button>
          </div>
          <div id="ws-ser-results" style="font-size:13px;color:#94a3b8;">Enter a serial or phone number above.</div>
        </div>
      </div>
    </div>

    <!-- ════ STATS ════ -->
    <div class="aa-ws-panel" id="ws-stats">
      <div class="aa-ws-stats" id="ws-stats-grid">
        <div style="text-align:center;padding:40px;color:#94a3b8;grid-column:1/-1;">Loading…</div>
      </div>
    </div>

    <!-- ════ WHATSAPP / N8N ════ -->
    <div class="aa-ws-panel" id="ws-n8n">
      <div style="overflow-y:auto;flex:1;padding:24px;max-width:760px;">
        <h2 style="font-size:18px;font-weight:800;color:#1e293b;margin-bottom:4px;">📱 WhatsApp via N8N</h2>
        <p style="color:#64748b;font-size:13px;margin-bottom:24px;">Connect WhatsApp Business to the AI agent through N8N.</p>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:16px;">
          <h3 style="font-size:14px;font-weight:700;margin:0 0 12px;">1. Webhook URL</h3>
          <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <code id="ws-n8n-url" style="flex:1;background:#f1f5f9;padding:10px 14px;border-radius:8px;font-size:13px;word-break:break-all;"><?php echo esc_html( rest_url( 'aiagent/v1/webhook/n8n' ) ); ?></code>
            <button onclick="wsN8nCopyUrl()" style="padding:9px 16px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;font-size:12px;font-weight:700;cursor:pointer;" id="ws-n8n-copy-btn">📋 Copy</button>
          </div>
          <div style="margin-top:16px;">
            <label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:6px;text-transform:uppercase;">Webhook Secret</label>
            <div style="display:flex;gap:10px;align-items:center;">
              <input type="text" id="ws-n8n-secret" value="" placeholder="Not set" readonly style="flex:1;border:1.5px solid #e2e8f0;border-radius:8px;padding:9px 14px;font-size:13px;background:#f8fafc;font-family:monospace;">
              <button onclick="wsN8nRegenSecret()" style="padding:9px 14px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;font-size:12px;font-weight:700;cursor:pointer;">🔄 Regenerate</button>
            </div>
          </div>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:16px;">
          <h3 style="font-size:14px;font-weight:700;margin:0 0 14px;">2. N8N Setup</h3>
          <ol style="margin:0;padding-inline-start:20px;font-size:13px;color:#334155;line-height:2.2;">
            <li>Create a new N8N workflow.</li>
            <li>Add <strong>WhatsApp Business Cloud trigger</strong> → connect your Meta App.</li>
            <li>Add <strong>HTTP Request</strong> node:
              <pre style="background:#f1f5f9;border-radius:8px;padding:12px;font-size:12px;margin:8px 0;overflow-x:auto;line-height:1.6;">Method: POST
URL:    <?php echo esc_html( rest_url( 'aiagent/v1/webhook/n8n' ) ); ?>

Header: X-Webhook-Secret = <span id="ws-n8n-secret-inline">YOUR_SECRET</span>
Body (JSON):
{
  "phone":   "={{ $json.from }}",
  "name":    "={{ $json.profile.name }}",
  "message": "={{ $json.text.body }}"
}</pre>
            </li>
            <li>Add <strong>WhatsApp Send Message</strong> node → To: <code>={{ $json.phone }}</code> · Message: <code>={{ $('HTTP Request').item.json.reply }}</code></li>
            <li><strong>Activate the workflow</strong> and test by sending a WhatsApp message.</li>
          </ol>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;">
          <h3 style="font-size:14px;font-weight:700;margin:0 0 14px;">3. Test Webhook</h3>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
            <div><label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;text-transform:uppercase;">Phone</label><input type="text" id="ws-n8n-test-phone" class="aa-ws-input" value="+97333000000"></div>
            <div><label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;text-transform:uppercase;">Name</label><input type="text" id="ws-n8n-test-name" class="aa-ws-input" value="Test User"></div>
          </div>
          <div style="margin-bottom:12px;"><label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;text-transform:uppercase;">Message</label><input type="text" id="ws-n8n-test-msg" class="aa-ws-input" value="What products do you have?"></div>
          <div style="display:flex;gap:12px;align-items:center;">
            <button onclick="wsN8nTest()" class="aa-ws-save-btn" style="padding:10px 22px;font-size:13px;">🧪 Send Test</button>
            <span id="ws-n8n-test-status" style="font-size:13px;color:#64748b;"></span>
          </div>
          <div id="ws-n8n-test-result" style="display:none;margin-top:14px;background:#f0fdf4;border:1.5px solid #1a6b3c;border-radius:10px;padding:14px;">
            <div style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:6px;text-transform:uppercase;">AI Reply</div>
            <div id="ws-n8n-test-reply" style="font-size:14px;color:#1e293b;line-height:1.6;white-space:pre-wrap;"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ════ ISTOCK APP ════ -->
    <?php if ( defined( 'ISUITE_VERSION' ) ) : ?>
    <div class="aa-ws-panel" id="ws-istock">
      <iframe id="ws-istock-frame" src="" style="flex:1;width:100%;height:100%;border:none;" allow="camera;microphone"></iframe>
    </div>
    <?php endif; ?>

    <!-- ════ UPDATE ════ -->
    <div class="aa-ws-panel" id="ws-update">
      <div style="overflow-y:auto;flex:1;padding:20px;max-width:540px;">
        <h2 style="font-size:18px;font-weight:800;color:#1e293b;margin-bottom:4px;">🔄 Plugin Update</h2>
        <p style="color:#64748b;font-size:13px;margin-bottom:20px;">Check for a new version and update with one tap.</p>

        <!-- Version cards -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
          <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">
            <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Installed</div>
            <div style="font-size:22px;font-weight:800;color:#1e293b;" id="upd-current">v<?php echo esc_html( $current_ver ); ?></div>
          </div>
          <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">
            <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Latest</div>
            <div style="font-size:22px;font-weight:800;color:#1a6b3c;" id="upd-latest">—</div>
          </div>
        </div>

        <!-- Status banner -->
        <div id="upd-banner" style="display:none;border-radius:10px;padding:14px 16px;margin-bottom:20px;font-size:14px;font-weight:600;"></div>

        <!-- Actions -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
          <button onclick="wsCheckUpdate()" id="upd-check-btn" style="background:#f1f5f9;color:#1e293b;border:1.5px solid #e2e8f0;border-radius:10px;padding:12px 22px;font-size:14px;font-weight:700;cursor:pointer;">🔍 Check Now</button>
          <button onclick="wsUpdateNow()"  id="upd-now-btn"   style="background:#1a6b3c;color:#fff;border:none;border-radius:10px;padding:12px 22px;font-size:14px;font-weight:700;cursor:pointer;display:none;">⬆️ Update Now</button>
        </div>

        <!-- Progress log -->
        <div id="upd-log" style="display:none;background:#0f172a;border-radius:10px;padding:14px 16px;font-family:monospace;font-size:12px;color:#94a3b8;line-height:1.8;white-space:pre-wrap;max-height:220px;overflow-y:auto;"></div>

        <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">
        <p style="font-size:12px;color:#94a3b8;">Updates are pulled from <strong>GitHub</strong>. The plugin deactivates, replaces itself, then reactivates automatically. The page will reload when done.</p>
      </div>
    </div>

  </div><!-- /.aa-ws-main -->
</div><!-- /.aa-ws -->

<script>
var REST='<?php echo esc_js($rest_url);?>';
var NONCE='<?php echo esc_js($nonce);?>';
var AJAX_URL='<?php echo esc_js($ajax_url);?>';
var UPLOAD_NONCE='<?php echo esc_js($upload_nonce);?>';
var PDF_NONCE='<?php echo esc_js($pdf_nonce);?>';
var AIAGENT_NONCE='<?php echo esc_js($aiagent_nonce);?>';
var CURRENT_VER='<?php echo esc_js($current_ver);?>';
var GITHUB_REPO='<?php echo esc_js($github_repo);?>';
<?php if ( defined( 'ISUITE_VERSION' ) ) : ?>
var ISTOCK_APP_URL='<?php echo esc_js( home_url( '?tic-app' ) ); ?>';
var ISTOCK_TOKEN_URL='<?php echo esc_js( rest_url( 'tic/v1/auth/session-token' ) ); ?>';
<?php endif; ?>
var wsActiveConv=null, wsConvFilter='', wsConvSearch='', wsSearchTimer, wsAutoRefresh;

/* ══════════════════════════════════════════════════════════════
   TAB SWITCHING
══════════════════════════════════════════════════════════════ */
var WS_TAB_LABELS = {conv:'💬 Conversations',tchat:'🧠 Train by Chat',teach:'🎓 Teach',manuals:'📄 Manuals',serials:'🔑 Serials',stats:'📊 Stats',n8n:'📱 WhatsApp / N8N',update:'🔄 Update',istock:'📦 iStock App'};

function wsTab(tab, btn){
  document.querySelectorAll('.aa-ws-tab,.aa-ws-mob-btn').forEach(function(b){
    if(b.dataset.tab===tab) b.classList.add('active');
    else b.classList.remove('active');
  });
  document.querySelectorAll('.aa-ws-panel').forEach(function(p){p.classList.remove('active');});
  document.getElementById('ws-'+tab).classList.add('active');
  // Mobile header
  var mobTitle = document.getElementById('aa-mob-title');
  var mobBack  = document.getElementById('aa-mob-back');
  if(mobTitle) mobTitle.textContent = WS_TAB_LABELS[tab]||tab;
  if(mobBack)  mobBack.style.display='none';
  // Close sidebar drawer & any open detail on mobile
  wsSidebarClose();
  if(tab==='conv'){ document.getElementById('ws-det').classList.remove('mob-open'); }
  if(tab==='conv')    { wsLoadConvList(); wsStartAutoRefresh(); }
  else                  wsStopAutoRefresh();
  if(tab==='teach')   wsLoadTeachList();
  if(tab==='stats')   wsLoadStats();
  if(tab==='tchat')   tcInit();
  if(tab==='n8n')     wsN8nInit();
  if(tab==='update')  wsCheckUpdate();
  if(tab==='istock')  wsIstockInit();
}

function wsBack(){
  document.getElementById('ws-det').classList.remove('mob-open');
  var mobBack  = document.getElementById('aa-mob-back');
  var mobTitle = document.getElementById('aa-mob-title');
  if(mobBack)  mobBack.style.display='none';
  if(mobTitle) mobTitle.textContent = WS_TAB_LABELS['conv'];
}

function wsSidebarToggle(){
  var sb  = document.querySelector('.aa-ws-sidebar');
  var ov  = document.getElementById('aa-sb-overlay');
  if(!sb) return;
  var open = sb.classList.toggle('mob-open');
  if(ov) ov.classList.toggle('open', open);
}

function wsSidebarClose(){
  var sb = document.querySelector('.aa-ws-sidebar');
  var ov = document.getElementById('aa-sb-overlay');
  if(sb) sb.classList.remove('mob-open');
  if(ov) ov.classList.remove('open');
}

function wsMobCopyLink(){
  var link = document.getElementById('aa-mob-link');
  if(!link) return;
  navigator.clipboard.writeText(link.href).then(function(){
    alert('Link copied! Open in iPhone browser and tap Share → Add to Home Screen.');
  }).catch(function(){
    prompt('Copy this link:', link.href);
  });
}

/* ══════════════════════════════════════════════════════════════
   CONVERSATIONS — LIST
══════════════════════════════════════════════════════════════ */
function wsSetFilter(status, btn){
  wsConvFilter = status;
  document.querySelectorAll('.aa-ws-filter-tab').forEach(function(b){b.classList.remove('active');});
  btn.classList.add('active');
  wsActiveConv = null;
  document.getElementById('ws-det').innerHTML = '<div class="aa-ws-empty"><div style="font-size:48px;">💬</div><div style="font-weight:700;font-size:15px;color:#475569;">Select a conversation</div></div>';
  wsLoadConvList();
}

function wsLoadConvList(){
  var url = REST+'/admin/conversations?per_page=40';
  if(wsConvSearch) url += '&search='+encodeURIComponent(wsConvSearch);
  if(wsConvFilter) url += '&status='+encodeURIComponent(wsConvFilter);

  fetch(url, {headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();})
  .then(function(d){
    var rows = d.rows || [];
    var counts = d.counts || {};

    // Update filter tab badges
    ['active','escalated','claimed','closed'].forEach(function(s){
      var tab = document.querySelector('.aa-ws-filter-tab[data-status="'+s+'"]');
      if(tab && counts[s]){
        tab.textContent = ({active:'Active',escalated:'🔥 Escalated',claimed:'Claimed',closed:'Closed'}[s])+' ('+counts[s]+')';
      }
    });

    // Sidebar + mobile badge for escalated
    var badge = document.getElementById('sb-conv-badge');
    var mobHot = document.getElementById('mob-hot');
    var hot = (counts.escalated||0)+(counts.active||0);
    if(badge){
      if(hot > 0){ badge.style.display=''; badge.textContent=hot; badge.className='aa-ws-badge-count'+(counts.escalated?'hot':''); }
      else badge.style.display='none';
    }
    if(mobHot){
      if(counts.escalated > 0){ mobHot.style.display='flex'; mobHot.textContent=counts.escalated; }
      else mobHot.style.display='none';
    }

    if(!rows.length){
      document.getElementById('ws-list').innerHTML='<div style="padding:24px;text-align:center;color:#94a3b8;font-size:13px;">No conversations.</div>';
      return;
    }
    document.getElementById('ws-list').innerHTML = rows.map(function(c){
      var isWA = c.session_token && c.session_token.indexOf('wa_') === 0;
      var channel = isWA ? '<span class="aa-ws-badge wa">WA</span>' : '<span class="aa-ws-badge web">Web</span>';
      var preview = c.last_message ? esc(c.last_message).slice(0,55)+(c.last_message.length>55?'…':'') : '<em style="color:#c8d0d9;">No messages</em>';
      return '<div class="aa-ws-ci'+(c.id==wsActiveConv?' active':'')+'" onclick="wsLoadDet('+c.id+')" data-id="'+c.id+'">'
        +'<div class="aa-ws-ci-top">'
          +'<span class="aa-ws-ci-id">#'+c.id+'</span>'
          +channel
          +'<span class="aa-ws-badge '+(c.mode||'product')+'">'+(c.mode||'product')+'</span>'
          +'<span class="aa-ws-badge '+(c.status||'active')+'">'+(c.status||'active')+'</span>'
        +'</div>'
        +'<div class="aa-ws-ci-preview">'+preview+'</div>'
        +'<div class="aa-ws-ci-meta"><span class="aa-ws-ci-time">'+wsAgo(c.updated_at||c.created_at)+'</span><span style="font-size:10px;color:#94a3b8;">'+(c.msg_count||0)+' msgs</span></div>'
        +'</div>';
    }).join('');
  }).catch(function(){});
}

function wsStartAutoRefresh(){
  clearInterval(wsAutoRefresh);
  wsAutoRefresh = setInterval(wsLoadConvList, 20000);
}
function wsStopAutoRefresh(){ clearInterval(wsAutoRefresh); }

document.getElementById('ws-search').addEventListener('input', function(){
  clearTimeout(wsSearchTimer);
  var q = this.value;
  wsSearchTimer = setTimeout(function(){ wsConvSearch=q; wsLoadConvList(); }, 350);
});

/* ══════════════════════════════════════════════════════════════
   CONVERSATIONS — DETAIL
══════════════════════════════════════════════════════════════ */
function wsLoadDet(id){
  wsActiveConv = id;
  document.querySelectorAll('.aa-ws-ci').forEach(function(el){el.classList.toggle('active', el.dataset.id==id);});
  var det = document.getElementById('ws-det');
  det.innerHTML = '<div class="aa-ws-empty"><div>⏳</div></div>';
  // Mobile: slide in the detail, show back button
  det.classList.add('mob-open');
  var mobBack  = document.getElementById('aa-mob-back');
  var mobTitle = document.getElementById('aa-mob-title');
  if(mobBack)  mobBack.style.display='block';
  if(mobTitle) mobTitle.textContent = '# ' + id;

  fetch(REST+'/admin/conversation/'+id, {headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();})
  .then(function(d){
    var msgs = d.messages || [];
    var conv = d.conversation || {};
    det.innerHTML = '';

    // ── Header ──────────────────────────────────────────────────
    var isWA   = conv.session_token && conv.session_token.indexOf('wa_') === 0;
    var status = conv.status || 'active';
    var hdr = document.createElement('div'); hdr.className = 'aa-ws-det-hdr';
    hdr.innerHTML =
      '<div class="aa-ws-det-hdr-left">'
      +'<strong>#'+id+'</strong>'
      +(isWA?'<span class="aa-ws-badge wa">📱 WhatsApp</span>':'<span class="aa-ws-badge web">💻 Web</span>')
      +'<span class="aa-ws-badge '+(conv.mode||'product')+'">'+(conv.mode||'product')+'</span>'
      +'<span class="aa-ws-badge '+status+'" id="ws-conv-status-badge">'+status+'</span>'
      +'<span style="font-size:11px;color:#94a3b8;">'+(conv.language||'en').toUpperCase()+'</span>'
      +'</div>'
      +'<div class="aa-ws-det-hdr-actions" id="ws-det-actions">'
      +(status!=='claimed' ? '<button class="aa-ws-hdr-btn blue" onclick="wsConvClaim('+id+')">🙋 Claim</button>' : '')
      +(status!=='closed'  ? '<button class="aa-ws-hdr-btn red"  onclick="wsConvClose('+id+')">✖ Close</button>'  : '')
      +'</div>';
    det.appendChild(hdr);

    // ── Messages ────────────────────────────────────────────────
    var body = document.createElement('div'); body.className = 'aa-ws-det-body';
    var prevQ = '';
    msgs.forEach(function(m){
      if(m.role==='customer') prevQ = m.body;
      var wrap = document.createElement('div');
      wrap.className = 'aa-ws-msg '+(m.role==='customer'?'cust':m.role);
      var bbl = document.createElement('div'); bbl.className = 'aa-ws-bubble';
      bbl.textContent = m.body || '';
      wrap.appendChild(bbl);
      var meta = document.createElement('div'); meta.className = 'aa-ws-msg-meta';
      meta.textContent = ({customer:'Customer',ai:'AI',human:'Admin'}[m.role]||m.role)+' · '+(m.created_at||'').slice(11,16);
      wrap.appendChild(meta);
      // Rate + Train buttons on AI messages
      if(m.role==='ai'){
        var acts = document.createElement('div'); acts.className = 'aa-ws-msg-actions';
        acts.innerHTML = '<button class="aa-ws-btn-sm" onclick="wsRate('+m.id+',\'good\')">👍</button>'
          +'<button class="aa-ws-btn-sm" onclick="wsRate('+m.id+',\'wrong\')">❌ Wrong</button>'
          +'<button class="aa-ws-btn-sm" onclick="wsTrain('+m.id+',this)" data-q="'+esc(prevQ)+'" data-a="'+esc(m.body)+'">🎓 Train</button>'
          +'<span id="ws-rate-res-'+m.id+'" style="font-size:11px;color:#1a6b3c;"></span>';
        wrap.appendChild(acts);
        // Train inline form
        var tp = document.createElement('div'); tp.id='ws-tp-'+m.id; tp.style.display='none';
        tp.style.cssText='background:#f0fdf4;border:1.5px solid #1a6b3c;border-radius:8px;padding:10px;margin-top:6px;';
        tp.innerHTML='<div style="font-size:11px;font-weight:700;color:#1a6b3c;margin-bottom:6px;">🎓 Train from this reply</div>'
          +'<label style="font-size:10px;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">Q</label>'
          +'<textarea id="ws-tq-'+m.id+'" style="width:100%;border:1.5px solid #e2e8f0;border-radius:6px;padding:6px 8px;font-size:12px;font-family:inherit;resize:vertical;min-height:40px;box-sizing:border-box;margin-bottom:6px;">'+esc(prevQ)+'</textarea>'
          +'<label style="font-size:10px;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">A</label>'
          +'<textarea id="ws-ta-'+m.id+'" style="width:100%;border:1.5px solid #e2e8f0;border-radius:6px;padding:6px 8px;font-size:12px;font-family:inherit;resize:vertical;min-height:60px;box-sizing:border-box;margin-bottom:6px;">'+esc(m.body)+'</textarea>'
          +'<button onclick="wsDoTrain('+m.id+')" style="background:#1a6b3c;color:#fff;border:none;border-radius:7px;padding:6px 14px;font-size:12px;font-weight:600;cursor:pointer;margin-right:6px;">💾 Save</button>'
          +'<button onclick="document.getElementById(\'ws-tp-'+m.id+'\').style.display=\'none\'" style="border:1.5px solid #e2e8f0;border-radius:7px;padding:6px 12px;font-size:12px;background:#fff;cursor:pointer;">Cancel</button>'
          +'<div id="ws-tr-'+m.id+'" style="margin-top:6px;font-size:11px;"></div>';
        wrap.appendChild(tp);
      }
      body.appendChild(wrap);
    });
    det.appendChild(body);
    body.scrollTop = body.scrollHeight;

    // ── Reply bar ────────────────────────────────────────────────
    if(status !== 'closed'){
      var bar = document.createElement('div'); bar.className='aa-ws-reply-bar';
      bar.innerHTML = '<textarea id="ws-reply-inp" placeholder="Reply to customer…" rows="1" onkeydown="if(event.key===\'Enter\'&&!event.shiftKey){event.preventDefault();wsConvReply('+id+');}"></textarea>'
        +'<button onclick="wsConvReply('+id+')" title="Send reply">➤</button>';
      det.appendChild(bar);
      // Auto-resize
      setTimeout(function(){
        var ta = document.getElementById('ws-reply-inp');
        if(ta){ ta.addEventListener('input',function(){this.style.height='auto';this.style.height=Math.min(100,this.scrollHeight)+'px';}); ta.focus(); }
      }, 100);
    }
  }).catch(function(){ det.innerHTML='<div class="aa-ws-empty"><div style="color:#c62828;">Failed to load.</div></div>'; });
}

function wsConvReply(id){
  var ta = document.getElementById('ws-reply-inp');
  var text = ta ? ta.value.trim() : '';
  if(!text) return;
  ta.disabled = true;
  fetch(REST+'/admin/conversation/'+id+'/reply', {
    method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body: JSON.stringify({body: text})
  }).then(function(r){return r.json();}).then(function(d){
    if(d.error){ ta.disabled=false; alert(d.error); return; }
    // Append human bubble immediately
    var body = document.querySelector('.aa-ws-det-body');
    if(body){
      var wrap = document.createElement('div'); wrap.className='aa-ws-msg human';
      var bbl  = document.createElement('div'); bbl.className='aa-ws-bubble'; bbl.textContent=text;
      var meta = document.createElement('div'); meta.className='aa-ws-msg-meta'; meta.textContent='Admin · just now';
      wrap.appendChild(bbl); wrap.appendChild(meta);
      body.appendChild(wrap);
      body.scrollTop = body.scrollHeight;
    }
    ta.value=''; ta.style.height=''; ta.disabled=false; ta.focus();
    wsLoadConvList();
  }).catch(function(){ ta.disabled=false; });
}

function wsConvClaim(id){
  fetch(REST+'/admin/conversation/'+id+'/claim', {method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},body:'{}'})
  .then(function(r){return r.json();}).then(function(){ wsLoadDet(id); wsLoadConvList(); });
}
function wsConvClose(id){
  if(!confirm('Mark this conversation as closed?')) return;
  fetch(REST+'/admin/conversation/'+id+'/close', {method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},body:'{}'})
  .then(function(r){return r.json();}).then(function(){ wsLoadDet(id); wsLoadConvList(); });
}

function wsTrain(id){ var tp=document.getElementById('ws-tp-'+id); if(tp) tp.style.display=tp.style.display==='none'?'block':'none'; }
function wsDoTrain(id){
  var q=(document.getElementById('ws-tq-'+id)||{}).value||'';
  var a=(document.getElementById('ws-ta-'+id)||{}).value||'';
  var res=document.getElementById('ws-tr-'+id);
  if(!q||!a){if(res)res.innerHTML='<span style="color:#c62828;">Both required.</span>';return;}
  fetch(REST+'/admin/train',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},body:JSON.stringify({message_id:id,question:q,answer:a})})
  .then(function(r){return r.json();}).then(function(d){if(res)res.innerHTML=d.error?'<span style="color:#c62828;">'+esc(d.error)+'</span>':'<span style="color:#1a6b3c;">✅ Saved #'+d.example_id+'</span>';});
}
function wsRate(id,score){
  fetch(REST+'/admin/rate',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},body:JSON.stringify({message_id:id,score:score,correction:'',promote:false})})
  .then(function(r){return r.json();}).then(function(){
    var el=document.getElementById('ws-rate-res-'+id);
    if(el) el.textContent={good:'👍 Good',wrong:'❌ Marked wrong'}[score]||score;
  });
}

/* ══════════════════════════════════════════════════════════════
   TRAINING CHAT
══════════════════════════════════════════════════════════════ */
var tcHistory=[], tcBusy=false, tcLastQA=null, tcPendingImg=null;

function tcInit(){ if(!document.getElementById('tc-msgs').children.length) tcGreet(); }
function tcGreet(){
  tcBubble('ai',"👋 Hi! Teach me anything you want customers to know — product info, policies, FAQs, warranty.\n\nJust say it naturally:\n  • \"IBR-2000 warranty is 12 months.\"\n  • \"Returns accepted within 14 days.\"\n\nI'll extract a Q&A and ask you to confirm.");
}

function tcBubble(role, text, qa, imgUrl, visionPreview){
  var msgs=document.getElementById('tc-msgs');
  var wrap=document.createElement('div'); wrap.className='aa-tc-bbl '+(role==='admin'?'admin':'ai');
  if(imgUrl){var i=document.createElement('img');i.src=imgUrl;i.style.cssText='max-width:180px;max-height:140px;border-radius:10px;display:block;margin-bottom:6px;border:2px solid rgba(255,255,255,.4);';wrap.appendChild(i);}
  var body=document.createElement('div'); body.className='aa-tc-body'; body.style.whiteSpace='pre-wrap';
  var dt=text.replace(/TEACH:\s*Q:.*?\|.*?(\n|$)/gi,'').trim();
  if(dt){body.textContent=dt;wrap.appendChild(body);}
  if(visionPreview&&visionPreview.length>0){
    var vn=document.createElement('div');
    vn.style.cssText='font-size:11px;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:6px 10px;margin-top:6px;';
    vn.innerHTML='<strong>🔍 What I see:</strong> '+esc(visionPreview.slice(0,100))+(visionPreview.length>100?'…':'');
    wrap.appendChild(vn);
  }
  var meta=document.createElement('div');meta.className='aa-tc-meta';meta.textContent=role==='admin'?'You':'AI Training Assistant';wrap.appendChild(meta);
  if(qa&&qa.question&&qa.answer){
    tcLastQA=qa;
    var card=document.createElement('div');card.className='aa-tc-save-card';
    card.innerHTML='<p>💾 Save this to the knowledge base?</p>'
      +'<div class="qa-q">'+esc(qa.question)+'</div><div class="qa-a">'+esc(qa.answer)+'</div>'
      +'<input id="tc-model" type="text" placeholder="Model (optional — leave blank to apply to all products)" style="width:100%;border:1.5px solid #e2e8f0;border-radius:6px;padding:6px 9px;font-size:12px;font-family:inherit;box-sizing:border-box;margin-top:6px;">'
      +'<div style="margin-top:10px;display:flex;gap:8px;align-items:center;">'
      +'<button class="aa-tc-save-btn" onclick="tcSaveQA()">💾 Save</button>'
      +'<button class="aa-tc-edit-btn" onclick="tcEditQA()">✏️ Edit</button>'
      +'<span id="tc-save-res" style="font-size:12px;color:#1a6b3c;"></span></div>'
      +'<div id="tc-edit-form" style="display:none;margin-top:10px;">'
      +'<label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">Q</label>'
      +'<textarea id="tc-eq" style="width:100%;border:1.5px solid #e2e8f0;border-radius:6px;padding:7px;font-size:12px;font-family:inherit;min-height:40px;box-sizing:border-box;margin-bottom:6px;resize:vertical;">'+esc(qa.question)+'</textarea>'
      +'<label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">A</label>'
      +'<textarea id="tc-ea" style="width:100%;border:1.5px solid #e2e8f0;border-radius:6px;padding:7px;font-size:12px;font-family:inherit;min-height:60px;box-sizing:border-box;margin-bottom:8px;resize:vertical;">'+esc(qa.answer)+'</textarea>'
      +'<button class="aa-tc-save-btn" onclick="tcSaveEdited()">💾 Save Edited</button></div>';
    wrap.appendChild(card);
  }
  msgs.appendChild(wrap);msgs.scrollTop=msgs.scrollHeight;
}

function tcSaveQA(){ if(tcLastQA) _tcDoSave(tcLastQA.question,tcLastQA.answer,tcLastQA.image_url||'',tcLastQA.image_desc||''); }
function tcSaveEdited(){
  var q=(document.getElementById('tc-eq')||{}).value||'';
  var a=(document.getElementById('tc-ea')||{}).value||'';
  if(!q||!a){document.getElementById('tc-save-res').textContent='Both required.';return;}
  _tcDoSave(q,a,tcLastQA?tcLastQA.image_url||'':'',tcLastQA?tcLastQA.image_desc||'':'');
}
function tcEditQA(){var f=document.getElementById('tc-edit-form');if(f)f.style.display=f.style.display==='none'?'block':'none';}
function _tcDoSave(q,a,imgUrl,imgDesc){
  var res=document.getElementById('tc-save-res');if(res)res.textContent='Saving…';
  // Optional model tag — same "[MODEL] question" convention admin_ingest_manual
  // already uses for manual-derived Q&A, so retrieval treats both sources alike.
  var modelInp=document.getElementById('tc-model');
  var model=modelInp?modelInp.value.trim():'';
  if(model) q='['+model+'] '+q;
  var body={question:q,answer:a};
  if(imgUrl){body.image_url=imgUrl;body.image_desc=imgDesc||'';}
  fetch(REST+'/admin/train',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},body:JSON.stringify(body)})
  .then(function(r){return r.json();}).then(function(d){
    if(res)res.innerHTML=d.error?'<span style="color:#c62828;">'+esc(d.error)+'</span>':'✅ Saved #'+d.example_id;
    tcLastQA=null;
    setTimeout(function(){tcBubble('ai','✅ Saved! The AI will use this going forward.\n\nAnything else to teach?');},300);
  }).catch(function(){if(res)res.textContent='Failed.';});
}

function tcHandleFile(input){
  var file=input.files[0];if(!file)return;
  if(file.size>5*1024*1024){alert('Max 5 MB.');return;}
  var bar=document.getElementById('tc-img-bar');
  var thumb=document.getElementById('tc-img-thumb');
  var status=document.getElementById('tc-img-status');
  var reader=new FileReader();
  reader.onload=function(e){thumb.src=e.target.result;bar.style.display='block';};
  reader.readAsDataURL(file);
  status.textContent='Uploading…';
  var fd=new FormData();fd.append('action','aiagent_upload_attachment');fd.append('_ajax_nonce',UPLOAD_NONCE);fd.append('file',file);
  fetch(AJAX_URL,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
    if(d.success&&d.data&&d.data.url){
      tcPendingImg={url:d.data.url,attachment_id:d.data.attachment_id||0,gemini_file_uri:d.data.gemini_file_uri||'',gemini_file_mime:d.data.gemini_file_mime||'image/jpeg',localSrc:thumb.src};
      status.innerHTML='✅ Ready — <a href="'+d.data.url+'" target="_blank" style="color:#1a6b3c;">view</a>';
    } else {status.innerHTML='<span style="color:#ef4444;">Failed.</span>';tcPendingImg=null;}
  }).catch(function(){status.innerHTML='<span style="color:#ef4444;">Failed.</span>';tcPendingImg=null;});
  input.value='';
}
function tcClearImage(){tcPendingImg=null;document.getElementById('tc-img-bar').style.display='none';document.getElementById('tc-img-thumb').src='';document.getElementById('tc-img-status').textContent='';}

function tcSend(){
  if(tcBusy)return;
  var inp=document.getElementById('tc-inp');var text=inp.value.trim();
  if(!text&&!tcPendingImg)return;
  inp.value='';tcResizeInp();
  var sentImg=tcPendingImg;if(sentImg)tcClearImage();
  tcBubble('admin',text||'(Photo)',null,sentImg?sentImg.url:null);
  tcHistory.push({role:'user',text:sentImg?'[Photo]'+(text?' — '+text:''):text});
  tcBusy=true;document.getElementById('tc-send').disabled=true;document.getElementById('tc-photo-btn').disabled=true;
  var typing=document.createElement('div');typing.id='tc-typ';typing.className='aa-tc-typing';
  typing.innerHTML=sentImg?'<div class="aa-tc-typing-body"><span style="font-size:13px;color:#64748b;">🔍 Analyzing image…</span></div>':'<div class="aa-tc-typing-body"><span class="aa-tc-dot"></span><span class="aa-tc-dot"></span><span class="aa-tc-dot"></span></div>';
  document.getElementById('tc-msgs').appendChild(typing);document.getElementById('tc-msgs').scrollTop=9999;
  var payload={message:text||'',history:tcHistory.slice(0,-1),lang:'en'};
  if(sentImg){payload.image_url=sentImg.url;payload.image_attachment_id=sentImg.attachment_id||0;payload.gemini_file_uri=sentImg.gemini_file_uri||'';payload.gemini_file_mime=sentImg.gemini_file_mime||'image/jpeg';}
  fetch(REST+'/admin/train-chat',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},body:JSON.stringify(payload)})
  .then(function(r){return r.json();}).then(function(d){
    var t=document.getElementById('tc-typ');if(t)t.remove();
    var reply=d.reply||'';tcHistory.push({role:'model',text:reply});
    var qa=d.extracted_qa||null;
    if(qa&&sentImg){qa.image_url=qa.image_url||sentImg.url;qa.image_desc=qa.image_desc||d.vision_preview||'';}
    tcBubble('ai',reply,qa,null,d.vision_preview||'');
  }).catch(function(){var t=document.getElementById('tc-typ');if(t)t.remove();tcBubble('ai','Something went wrong. Please try again.');})
  .finally(function(){tcBusy=false;document.getElementById('tc-send').disabled=false;document.getElementById('tc-photo-btn').disabled=false;document.getElementById('tc-inp').focus();});
}
function tcResizeInp(){var ta=document.getElementById('tc-inp');ta.style.height='auto';ta.style.height=Math.min(120,Math.max(42,ta.scrollHeight))+'px';ta.style.overflow=ta.scrollHeight>120?'auto':'hidden';}
function wsTchatClear(){tcHistory=[];tcLastQA=null;document.getElementById('tc-msgs').innerHTML='';tcGreet();}
document.getElementById('tc-inp').addEventListener('input',tcResizeInp);
document.getElementById('tc-inp').addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();tcSend();}});

/* ══════════════════════════════════════════════════════════════
   TEACH FORM
══════════════════════════════════════════════════════════════ */
function wsTeachSave(){
  var q=document.getElementById('ws-teach-q').value.trim();
  var a=document.getElementById('ws-teach-a').value.trim();
  var res=document.getElementById('ws-teach-res');
  if(!q||!a){res.innerHTML='<span style="color:#c62828;">Both required.</span>';return;}
  res.textContent='Saving…';
  fetch(REST+'/admin/teach',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},body:JSON.stringify({question:q,solution:a,language:'en'})})
  .then(function(r){return r.json();}).then(function(d){
    if(d.error)res.innerHTML='<span style="color:#c62828;">'+esc(d.error)+'</span>';
    else{res.innerHTML='<span style="color:#1a6b3c;">✅ Saved!</span>';document.getElementById('ws-teach-q').value='';document.getElementById('ws-teach-a').value='';wsLoadTeachList();}
  }).catch(function(){res.innerHTML='<span style="color:#c62828;">Failed.</span>';});
}
function wsLoadTeachList(){
  document.getElementById('ws-teach-list').innerHTML='Loading…';
  fetch(REST+'/admin/examples?per_page=8',{headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();}).then(function(d){
    var rows=d.examples||d.rows||[];
    if(!rows.length){document.getElementById('ws-teach-list').innerHTML='<p style="color:#94a3b8;">No examples yet.</p>';return;}
    document.getElementById('ws-teach-list').innerHTML='<div style="display:flex;flex-direction:column;gap:8px;">'
      +rows.map(function(r){
        return '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 14px;">'
          +'<div style="font-size:12px;font-weight:700;color:#1e293b;margin-bottom:4px;">Q: '+esc(r.question)+'</div>'
          +'<div style="font-size:12px;color:#475569;">A: '+esc((r.solution||r.answer||'').slice(0,120))+'…</div>'
          +'</div>';
      }).join('')+'</div>'
      +'<p style="margin-top:12px;"><a href="admin.php?page=aiagent-teach" style="color:#1a6b3c;font-size:13px;">→ View all examples</a></p>';
  }).catch(function(){document.getElementById('ws-teach-list').innerHTML='<a href="admin.php?page=aiagent-teach" style="color:#1a6b3c;">→ Open full Teach page</a>';});
}

/* ══════════════════════════════════════════════════════════════
   MANUALS — 3-step upload
══════════════════════════════════════════════════════════════ */
function mnResult(msg,type){
  var el=document.getElementById('mn-result');
  el.style.display='block';
  el.innerHTML='<div style="padding:12px 16px;border-radius:8px;font-size:13px;background:'+(type==='error'?'#fce4ec':'#e8f5ee')+';color:'+(type==='error'?'#b71c1c':'#145530')+';border-left:4px solid '+(type==='error'?'#ef5350':'#1a6b3c')+';">'+msg+'</div>';
}

async function mnIngest(){
  var model = document.getElementById('mn-model').value.trim();
  var file  = document.getElementById('mn-file').files[0];
  var btn   = document.getElementById('mn-btn');
  if(!model){mnResult('⚠️ Enter a product model.','error');return;}
  if(!file){mnResult('⚠️ Select a PDF file.','error');return;}

  btn.disabled=true;

  // Step 1: upload to Gemini
  btn.textContent='⏳ Step 1/3 — Uploading PDF to Gemini…';
  mnResult('⏳ Uploading PDF to Gemini File API… (may take 30s)','info');
  var fd=new FormData();fd.append('file',file);fd.append('action','aiagent_manual_upload_to_gemini');fd.append('_ajax_nonce',PDF_NONCE);
  var uploadData;
  try{
    var r=await fetch(AJAX_URL,{method:'POST',body:fd});
    var j=await r.json();
    if(!j.success) throw new Error(j.data&&j.data.error?j.data.error:'Upload failed.');
    uploadData=j.data;
  }catch(e){mnResult('❌ '+e.message,'error');btn.disabled=false;btn.textContent='⚡ Ingest Manual';return;}

  // Step 2: Gemini reads PDF
  btn.textContent='🧠 Step 2/3 — Gemini reading PDF…';
  mnResult('⏳ Gemini is extracting all content from the manual… (may take 60–120s)','info');
  var fd2=new FormData();fd2.append('action','aiagent_manual_analyze_gemini');fd2.append('_ajax_nonce',PDF_NONCE);
  fd2.append('file_uri',uploadData.file_uri);fd2.append('file_name',uploadData.file_name);fd2.append('mime',uploadData.mime);fd2.append('is_pdf','true');
  var text='';
  try{
    var r2=await fetch(AJAX_URL,{method:'POST',body:fd2});
    var j2=await r2.json();
    if(!j2.success) throw new Error(j2.data&&j2.data.error?j2.data.error:'Extraction failed.');
    text=j2.data.text;
  }catch(e){mnResult('❌ '+e.message,'error');btn.disabled=false;btn.textContent='⚡ Ingest Manual';return;}

  // Step 3: chunk + embed + Q&A
  btn.textContent='💾 Step 3/3 — Chunking & extracting Q&A…';
  mnResult('⏳ Storing chunks and extracting Q&A pairs…','info');
  try{
    var r3=await fetch(REST+'/admin/manual',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
      body:JSON.stringify({model:model,text:text,section_title:'',source_file:file.name,image_urls:[]})});
    var data=await r3.json();
    mnResult('✅ <strong>Ingested '+model+'</strong> — '
      +'📄 '+(data.chunks||0)+' chunks stored'
      +(data.qa_stored?'&nbsp;·&nbsp;🧠 '+data.qa_stored+' Q&amp;A pairs saved':'')
      +(data.embed_status?'<br><small style="color:#6b7280;">⏳ '+data.embed_status+'</small>':'')
      +(data.text_preview?'<details style="margin-top:10px;"><summary style="cursor:pointer;font-weight:600;color:#1e40af;">👁 Preview (first 2000 chars)</summary><pre style="white-space:pre-wrap;font-size:11px;background:#f8fafc;padding:10px;border-radius:6px;margin-top:6px;max-height:240px;overflow-y:auto;">'+data.text_preview.replace(/</g,'&lt;')+'</pre></details>':'')
      ,'success');
    document.getElementById('mn-model').value='';document.getElementById('mn-file').value='';
  }catch(e){mnResult('❌ Chunk step failed: '+e.message,'error');}
  btn.disabled=false;btn.textContent='⚡ Ingest Manual';
}

/* Product autocomplete for manuals */
(function(){
  var inp=document.getElementById('mn-model');
  var drop=document.getElementById('mn-model-drop');
  var timer;
  inp.addEventListener('input',function(){
    clearTimeout(timer);var q=this.value.trim();
    if(q.length<2){drop.style.display='none';return;}
    timer=setTimeout(function(){
      fetch(REST+'/admin/products/search?q='+encodeURIComponent(q),{headers:{'X-WP-Nonce':NONCE}})
      .then(function(r){return r.json();}).then(function(d){
        var prods=d.products||[];
        if(!prods.length){drop.style.display='none';return;}
        drop.innerHTML=prods.map(function(p){
          return '<div class="mn-prod-item" data-name="'+esc(p.name)+'" style="padding:9px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;font-size:13px;">'
            +'<strong>'+esc(p.name)+'</strong>'+(p.sku?'<span style="color:#94a3b8;margin-left:6px;font-size:11px;">'+esc(p.sku)+'</span>':'')+'</div>';
        }).join('');
        drop.style.display='block';
        drop.querySelectorAll('.mn-prod-item').forEach(function(item){
          item.addEventListener('mouseenter',function(){this.style.background='#f0fdf4';});
          item.addEventListener('mouseleave',function(){this.style.background='';});
          item.addEventListener('click',function(){inp.value=this.dataset.name;drop.style.display='none';});
        });
      }).catch(function(){drop.style.display='none';});
    },260);
  });
  document.addEventListener('click',function(e){if(!inp.contains(e.target)&&!drop.contains(e.target))drop.style.display='none';});
})();

/* ══════════════════════════════════════════════════════════════
   SERIAL LOOKUP
══════════════════════════════════════════════════════════════ */
function wsSerialSearch(){
  var q=document.getElementById('ws-ser-search').value.trim();if(!q)return;
  var res=document.getElementById('ws-ser-results');res.innerHTML='<span style="color:#94a3b8;">Searching…</span>';
  fetch(REST+'/admin/serials?search='+encodeURIComponent(q)+'&per_page=20',{headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();}).then(function(d){
    var rows=d.rows||[];
    if(!rows.length){res.innerHTML='<p style="color:#94a3b8;">No records found.</p>';return;}
    res.innerHTML='<table style="width:100%;border-collapse:collapse;font-size:13px;">'
      +'<thead><tr style="background:#f6f7fb;"><th style="padding:8px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;">Serial</th><th style="padding:8px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;">Model</th><th style="padding:8px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;">Date</th></tr></thead>'
      +'<tbody>'+rows.map(function(r){
        return '<tr style="border-bottom:1px solid #f1f5f9;">'
          +'<td style="padding:8px 10px;font-family:monospace;font-size:12px;">'+esc(r.serial)+'</td>'
          +'<td style="padding:8px 10px;">'+esc(r.model||'—')+'</td>'
          +'<td style="padding:8px 10px;color:#64748b;font-size:12px;">'+esc(r.purchased_at||'—')+'</td>'
          +'</tr>';
      }).join('')+'</tbody></table>';
  }).catch(function(){res.innerHTML='<p style="color:#c62828;">Search failed.</p>';});
}
document.getElementById('ws-ser-search').addEventListener('keydown',function(e){if(e.key==='Enter')wsSerialSearch();});

/* ══════════════════════════════════════════════════════════════
   STATS
══════════════════════════════════════════════════════════════ */
function wsLoadStats(){
  var grid=document.getElementById('ws-stats-grid');
  Promise.all([
    fetch(REST+'/admin/conversations?per_page=1',{headers:{'X-WP-Nonce':NONCE}}).then(function(r){return r.json();}),
    fetch(REST+'/admin/conversations?per_page=1&status=escalated',{headers:{'X-WP-Nonce':NONCE}}).then(function(r){return r.json();}),
    fetch(REST+'/admin/examples?per_page=1',{headers:{'X-WP-Nonce':NONCE}}).then(function(r){return r.json();}),
    fetch(REST+'/admin/serials?per_page=1',{headers:{'X-WP-Nonce':NONCE}}).then(function(r){return r.json();}),
  ]).then(function(res){
    var total=res[0].total||0, escalated=(res[1].counts||{}).escalated||0, taught=res[2].total||0, serials=res[3].total||0;
    var counts=res[0].counts||{};
    grid.innerHTML=[
      {n:total,          l:'Total Conversations', c:'#1a6b3c'},
      {n:counts.active||0, l:'Active Now',        c:'#2e7d32'},
      {n:escalated,      l:'🔥 Escalated',         c:'#e65100'},
      {n:counts.claimed||0, l:'Claimed',           c:'#6d28d9'},
      {n:taught,         l:'Taught Examples',      c:'#1565c0'},
      {n:serials,        l:'Registered Serials',   c:'#0f766e'},
    ].map(function(s){
      return '<div class="aa-ws-stat-card"><div class="aa-ws-stat-num" style="color:'+s.c+';">'+s.n+'</div><div class="aa-ws-stat-label">'+s.l+'</div></div>';
    }).join('')+'<div class="aa-ws-stat-card" style="grid-column:1/-1;background:#f8fafc;">'
      +'<a href="admin.php?page=aiagent-analytics" style="color:#1a6b3c;font-weight:700;font-size:13px;">→ Full Analytics Dashboard</a>'
      +'</div>';
  }).catch(function(){grid.innerHTML='<div style="color:#c62828;padding:20px;">Could not load stats.</div>';});
}

/* ══════════════════════════════════════════════════════════════
   WHATSAPP / N8N
══════════════════════════════════════════════════════════════ */
var wsN8nSecret='';
function wsN8nInit(){
  fetch(REST+'/admin/settings',{headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();}).then(function(d){
    wsN8nSecret=d.webhook_secret||'';
    var inp=document.getElementById('ws-n8n-secret');var inl=document.getElementById('ws-n8n-secret-inline');
    if(inp)inp.value=wsN8nSecret||'';if(inl)inl.textContent=wsN8nSecret||'YOUR_SECRET';
  }).catch(function(){});
}
function wsN8nCopyUrl(){
  navigator.clipboard.writeText(document.getElementById('ws-n8n-url').textContent.trim())
  .then(function(){var btn=document.getElementById('ws-n8n-copy-btn');btn.textContent='✅ Copied';setTimeout(function(){btn.textContent='📋 Copy';},2000);});
}
function wsN8nRegenSecret(){
  if(!confirm('Generate a new webhook secret? You must update it in N8N.')) return;
  var arr=new Uint8Array(16);crypto.getRandomValues(arr);
  var s=Array.from(arr).map(function(b){return b.toString(16).padStart(2,'0');}).join('');
  fetch(REST+'/admin/settings',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},body:JSON.stringify({webhook_secret:s})})
  .then(function(r){return r.json();}).then(function(d){
    if(d.updated){wsN8nSecret=s;var inp=document.getElementById('ws-n8n-secret');var inl=document.getElementById('ws-n8n-secret-inline');if(inp)inp.value=s;if(inl)inl.textContent=s;alert('New secret saved. Update N8N HTTP Request node.');}
    else alert('Save failed.');
  });
}
function wsN8nTest(){
  var phone=(document.getElementById('ws-n8n-test-phone')||{}).value||'';
  var name=(document.getElementById('ws-n8n-test-name')||{}).value||'Test User';
  var msg=(document.getElementById('ws-n8n-test-msg')||{}).value||'';
  var status=document.getElementById('ws-n8n-test-status');var result=document.getElementById('ws-n8n-test-result');
  if(!phone||!msg){if(status)status.textContent='Phone and message required.';return;}
  if(status)status.textContent='Sending…';if(result)result.style.display='none';
  var hdrs={'Content-Type':'application/json','X-WP-Nonce':NONCE};
  if(wsN8nSecret) hdrs['X-Webhook-Secret']=wsN8nSecret;
  fetch(document.getElementById('ws-n8n-url').textContent.trim(),{method:'POST',headers:hdrs,body:JSON.stringify({phone:phone,name:name,message:msg,channel:'test'})})
  .then(function(r){return r.json();}).then(function(d){
    if(status)status.textContent='';
    if(d.error){if(status)status.innerHTML='<span style="color:#c62828;">'+esc(d.error)+'</span>';return;}
    document.getElementById('ws-n8n-test-reply').textContent=d.reply||'(no reply)';
    if(result)result.style.display='block';
  }).catch(function(){if(status)status.innerHTML='<span style="color:#c62828;">Request failed.</span>';});
}

/* ══════════════════════════════════════════════════════════════
   HELPERS
══════════════════════════════════════════════════════════════ */
function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s||''));return d.innerHTML;}
function wsAgo(dt){if(!dt)return'';var diff=Math.floor((Date.now()-Date.parse(dt))/1000);if(diff<60)return diff+'s ago';if(diff<3600)return Math.floor(diff/60)+'m ago';if(diff<86400)return Math.floor(diff/3600)+'h ago';return Math.floor(diff/86400)+'d ago';}

/* ══════════════════════════════════════════════════════════════
   UPDATE
══════════════════════════════════════════════════════════════ */
function wsCheckUpdate(){
  var btn = document.getElementById('upd-check-btn');
  var latEl = document.getElementById('upd-latest');
  var banner = document.getElementById('upd-banner');
  var nowBtn = document.getElementById('upd-now-btn');
  btn.textContent = '⏳ Checking…'; btn.disabled = true;
  banner.style.display = 'none';
  latEl.textContent = '…';

  fetch('https://api.github.com/repos/'+GITHUB_REPO+'/releases/latest')
  .then(function(r){ return r.json(); })
  .then(function(d){
    btn.textContent = '🔍 Check Now'; btn.disabled = false;
    var tag = (d.tag_name || '').replace(/^v/,'');
    latEl.textContent = 'v' + (tag || '?');
    if(!tag){ wsBanner('⚠️ Could not fetch latest version.','#fff3e0','#e65100'); return; }

    if(tag === CURRENT_VER){
      wsBanner('✅ You are on the latest version (v'+CURRENT_VER+').','#f0fdf4','#166534');
      nowBtn.style.display = 'none';
    } else {
      wsBanner('🆕 Update available: v'+CURRENT_VER+' → v'+tag,'#fffbeb','#92400e');
      nowBtn.style.display = '';
      // Mark badge on mobile nav
      var dot = document.getElementById('mob-update-dot');
      if(dot) dot.style.display = 'flex';
    }
  })
  .catch(function(){
    btn.textContent = '🔍 Check Now'; btn.disabled = false;
    wsBanner('❌ Network error — check your connection.','#fef2f2','#991b1b');
  });
}

function wsBanner(msg, bg, color){
  var b = document.getElementById('upd-banner');
  b.style.display = ''; b.style.background = bg; b.style.color = color; b.textContent = msg;
}

function wsUpdateNow(){
  var nowBtn = document.getElementById('upd-now-btn');
  var log    = document.getElementById('upd-log');
  if(!confirm('Update the plugin now?\n\nThe page will reload automatically when done.')) return;
  nowBtn.textContent = '⏳ Updating…'; nowBtn.disabled = true;
  document.getElementById('upd-check-btn').disabled = true;
  log.style.display = ''; log.textContent = '→ Sending update request…\n';

  var fd = new FormData();
  fd.append('action','aiagent_update_now');
  fd.append('nonce', AIAGENT_NONCE);

  fetch(AJAX_URL, { method:'POST', body:fd })
  .then(function(r){ return r.json(); })
  .then(function(d){
    if(d.success){
      log.textContent += '✅ ' + (d.data && d.data.message ? d.data.message : 'Update complete.') + '\n→ Reloading in 3 seconds…';
      wsBanner('✅ Update complete — reloading…','#f0fdf4','#166534');
      setTimeout(function(){ location.reload(); }, 3000);
    } else {
      var msg = d.data && d.data.message ? d.data.message : JSON.stringify(d);
      log.textContent += '❌ Error: ' + msg + '\n';
      wsBanner('❌ Update failed: '+msg,'#fef2f2','#991b1b');
      nowBtn.textContent = '⬆️ Retry'; nowBtn.disabled = false;
      document.getElementById('upd-check-btn').disabled = false;
    }
  })
  .catch(function(e){
    log.textContent += '❌ Network error: ' + e + '\n';
    wsBanner('❌ Network error.','#fef2f2','#991b1b');
    nowBtn.textContent = '⬆️ Retry'; nowBtn.disabled = false;
    document.getElementById('upd-check-btn').disabled = false;
  });
}

/* ══════════════════════════════════════════════════════════════
   ISTOCK SSO
══════════════════════════════════════════════════════════════ */
<?php if ( defined('ISUITE_VERSION') ) : ?>
var _isIstockLoaded = false;
function wsIstockInit(){
  var frame = document.getElementById('ws-istock-frame');
  if(!frame) return;
  // Load the PWA URL on first open
  if(!_isIstockLoaded){
    _isIstockLoaded = true;
    frame.src = ISTOCK_APP_URL;
  }
  // Fetch a session token for the currently logged-in WP user (no password needed)
  // and inject it into the iframe so the user doesn't need to log in separately.
  fetch(ISTOCK_TOKEN_URL, {headers:{'X-WP-Nonce': NONCE}})
  .then(function(r){ return r.json(); })
  .then(function(d){
    if(!d.token) return;
    var inject = function(){
      if(frame.contentWindow){
        frame.contentWindow.postMessage({type:'tic-inject-token', token:d.token}, location.origin);
      }
    };
    // If the frame is already loaded inject immediately, otherwise wait for load event
    try {
      if(frame.contentDocument && frame.contentDocument.readyState === 'complete'){ inject(); }
      else { frame.addEventListener('load', inject, {once:true}); }
    } catch(ex){
      frame.addEventListener('load', inject, {once:true});
    }
  }).catch(function(){});
}
<?php else : ?>
function wsIstockInit(){}
<?php endif; ?>

/* Also run check silently on load to show badge if update available */
setTimeout(function(){
  fetch('https://api.github.com/repos/'+GITHUB_REPO+'/releases/latest')
  .then(function(r){ return r.json(); })
  .then(function(d){
    var tag = (d.tag_name||'').replace(/^v/,'');
    if(tag && tag !== CURRENT_VER){
      var dot = document.getElementById('mob-update-dot');
      if(dot) dot.style.display='flex';
      var sb = document.getElementById('sb-update-tab');
      if(sb) sb.textContent = '🔄 Update ●';
    }
  }).catch(function(){});
}, 4000);

/* ══════════════════════════════════════════════════════════════
   INIT
══════════════════════════════════════════════════════════════ */
wsLoadConvList();
wsStartAutoRefresh();
</script>

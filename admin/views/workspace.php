<?php defined( 'ABSPATH' ) || exit;
// Variables: $nonce, $rest_url (injected by page_workspace())
?>
<style>
/* Workspace fills the page */
#wpcontent { padding-left:0!important; }
.wrap.aa-ws { padding:0!important; margin:0!important; height:calc(100vh - 46px)!important; display:flex!important; flex-direction:row!important; overflow:hidden!important; background:#f0f4f8!important; }
/* Sidebar */
.aa-ws-sidebar { width:220px!important; flex-shrink:0!important; background:#1e293b!important; display:flex!important; flex-direction:column!important; padding-top:16px!important; }
.aa-ws-logo { padding:0 18px 16px!important; color:#fff!important; font-size:16px!important; font-weight:800!important; letter-spacing:-.3px!important; border-bottom:1px solid rgba(255,255,255,.1)!important; }
.aa-ws-nav { padding:12px 0!important; flex:1!important; }
.aa-ws-tab { display:block!important; width:100%!important; text-align:left!important; background:none!important; border:none!important; color:rgba(255,255,255,.7)!important; padding:11px 18px!important; font-size:13px!important; font-weight:600!important; cursor:pointer!important; border-radius:0!important; transition:all .12s!important; }
.aa-ws-tab:hover { background:rgba(255,255,255,.08)!important; color:#fff!important; }
.aa-ws-tab.active { background:rgba(26,107,60,.6)!important; color:#fff!important; border-left:3px solid #4ade80!important; }
.aa-ws-footer { padding:14px 18px!important; border-top:1px solid rgba(255,255,255,.1)!important; font-size:11px!important; color:rgba(255,255,255,.4)!important; }
/* Main area */
.aa-ws-main { flex:1!important; display:flex!important; flex-direction:column!important; overflow:hidden!important; }
.aa-ws-panel { flex:1!important; overflow:hidden!important; display:none!important; }
.aa-ws-panel.active { display:flex!important; flex-direction:column!important; }
/* Conversations panel */
.aa-ws-conv-layout { display:flex!important; flex:1!important; overflow:hidden!important; }
.aa-ws-conv-list { width:280px!important; flex-shrink:0!important; border-right:1px solid #e2e8f0!important; background:#fff!important; display:flex!important; flex-direction:column!important; overflow:hidden!important; }
.aa-ws-conv-search { padding:12px!important; border-bottom:1px solid #f1f5f9!important; }
.aa-ws-conv-search input { width:100%!important; border:1.5px solid #e2e8f0!important; border-radius:8px!important; padding:8px 12px!important; font-size:13px!important; outline:none!important; box-sizing:border-box!important; }
.aa-ws-conv-search input:focus { border-color:#1a6b3c!important; }
.aa-ws-conv-scroll { flex:1!important; overflow-y:auto!important; }
.aa-ws-ci { padding:12px 14px!important; border-bottom:1px solid #f1f5f9!important; cursor:pointer!important; transition:background .1s!important; }
.aa-ws-ci:hover { background:#f8fafc!important; }
.aa-ws-ci.active { background:#e8f5e9!important; border-left:3px solid #1a6b3c!important; }
.aa-ws-ci-id { font-size:13px!important; font-weight:700!important; color:#1e293b!important; }
.aa-ws-ci-meta { font-size:11px!important; color:#94a3b8!important; margin-top:2px!important; }
/* Detail panel */
.aa-ws-conv-detail { flex:1!important; display:flex!important; flex-direction:column!important; overflow:hidden!important; background:#f6f7fb!important; }
.aa-ws-det-hdr { padding:14px 20px!important; background:#fff!important; border-bottom:1px solid #e2e8f0!important; flex-shrink:0!important; display:flex!important; align-items:center!important; gap:12px!important; }
.aa-ws-det-body { flex:1!important; overflow-y:auto!important; padding:16px 20px!important; }
.aa-ws-msg { margin-bottom:14px!important; }
.aa-ws-msg.cust { display:flex!important; flex-direction:column!important; align-items:flex-end!important; }
.aa-ws-msg.ai { display:flex!important; flex-direction:column!important; align-items:flex-start!important; }
.aa-ws-bubble { padding:10px 14px!important; font-size:13px!important; line-height:1.6!important; max-width:72%!important; word-break:break-word!important; border-radius:14px!important; }
.aa-ws-msg.ai .aa-ws-bubble { background:#fff!important; border-radius:4px 14px 14px 14px!important; box-shadow:0 1px 4px rgba(0,0,0,.08)!important; }
.aa-ws-msg.cust .aa-ws-bubble { background:#1a6b3c!important; color:#fff!important; border-radius:14px 14px 4px 14px!important; }
.aa-ws-badge { display:inline-flex!important; padding:2px 8px!important; border-radius:50px!important; font-size:10px!important; font-weight:700!important; text-transform:uppercase!important; }
.aa-ws-badge.product { background:#e3f2fd!important; color:#1565c0!important; }
.aa-ws-badge.support { background:#fce4ec!important; color:#c62828!important; }
.aa-ws-badge.active  { background:#e8f5e9!important; color:#2e7d32!important; }
.aa-ws-badge.escalated { background:#fff3e0!important; color:#e65100!important; }
.aa-ws-badge.closed  { background:#f5f5f5!important; color:#757575!important; }
.aa-ws-msg-actions { display:flex!important; gap:5px!important; margin-top:5px!important; flex-wrap:wrap!important; }
.aa-ws-btn-sm { padding:3px 10px!important; border-radius:50px!important; border:1.5px solid #e2e8f0!important; background:#fff!important; font-size:11px!important; font-weight:600!important; cursor:pointer!important; }
.aa-ws-btn-sm:hover { border-color:#1a6b3c!important; color:#1a6b3c!important; }
/* Teach panel */
.aa-ws-teach { padding:24px!important; max-width:700px!important; }
.aa-ws-teach h2 { font-size:18px!important; font-weight:800!important; color:#1e293b!important; margin-bottom:6px!important; }
.aa-ws-teach p { color:#64748b!important; font-size:13px!important; margin-bottom:24px!important; }
.aa-ws-field { margin-bottom:16px!important; }
.aa-ws-label { display:block!important; font-size:11px!important; font-weight:700!important; color:#64748b!important; text-transform:uppercase!important; letter-spacing:.5px!important; margin-bottom:6px!important; }
.aa-ws-input,.aa-ws-ta { display:block!important; width:100%!important; border:1.5px solid #e2e8f0!important; border-radius:8px!important; padding:10px 14px!important; font-size:14px!important; font-family:inherit!important; color:#1e293b!important; background:#fff!important; outline:none!important; box-sizing:border-box!important; }
.aa-ws-ta { resize:vertical!important; min-height:100px!important; }
.aa-ws-input:focus,.aa-ws-ta:focus { border-color:#1a6b3c!important; box-shadow:0 0 0 3px rgba(26,107,60,.1)!important; }
.aa-ws-save-btn { background:#1a6b3c!important; color:#fff!important; border:none!important; border-radius:10px!important; padding:12px 28px!important; font-size:14px!important; font-weight:700!important; cursor:pointer!important; }
.aa-ws-save-btn:hover { background:#145530!important; }
/* Serial lookup */
.aa-ws-serial { padding:24px!important; max-width:600px!important; }
/* Training chat */
.aa-ws-tchat { display:flex!important; flex-direction:column!important; flex:1!important; overflow:hidden!important; background:#f0f4f8!important; }
.aa-ws-tchat-hdr { padding:14px 20px!important; background:#1e293b!important; flex-shrink:0!important; display:flex!important; align-items:center!important; justify-content:space-between!important; }
.aa-ws-tchat-hdr h2 { color:#fff!important; font-size:15px!important; font-weight:700!important; margin:0!important; }
.aa-ws-tchat-hdr p { color:rgba(255,255,255,.6)!important; font-size:12px!important; margin:0!important; }
.aa-ws-tchat-msgs { flex:1!important; overflow-y:auto!important; padding:16px 20px!important; display:block!important; }
.aa-ws-tchat-msgs::-webkit-scrollbar { width:4px!important; }
.aa-ws-tchat-msgs::-webkit-scrollbar-thumb { background:#c8d0d9!important; border-radius:4px!important; }
.aa-tc-bbl { display:block!important; width:-webkit-fit-content!important; width:fit-content!important; max-width:78%!important; margin-bottom:12px!important; }
.aa-tc-bbl.admin { margin-left:auto!important; margin-right:4px!important; }
.aa-tc-bbl.ai    { margin-right:auto!important; margin-left:4px!important; }
.aa-tc-body { padding:11px 16px!important; font-size:13px!important; line-height:1.65!important; word-break:break-word!important; border-radius:18px!important; }
.aa-tc-bbl.admin .aa-tc-body { background:#1a6b3c!important; color:#fff!important; border-radius:18px 18px 4px 18px!important; }
.aa-tc-bbl.ai    .aa-tc-body { background:#fff!important; color:#1a1a2e!important; border-radius:18px 18px 18px 4px!important; box-shadow:0 2px 8px rgba(0,0,0,.09)!important; }
.aa-tc-meta { font-size:10px!important; color:#94a3b8!important; margin-top:3px!important; padding:0 4px!important; display:block!important; }
.aa-tc-bbl.admin .aa-tc-meta { text-align:right!important; }
.aa-tc-save-card { background:#e8f5e9!important; border:1.5px solid #1a6b3c!important; border-radius:12px!important; padding:12px 16px!important; margin-top:8px!important; margin-bottom:4px!important; }
.aa-tc-save-card p { font-size:12px!important; font-weight:700!important; color:#145530!important; margin:0 0 8px!important; }
.aa-tc-save-card .qa-q,.aa-tc-save-card .qa-a { font-size:12px!important; background:#fff!important; border-radius:6px!important; padding:6px 10px!important; margin-bottom:6px!important; color:#1e293b!important; }
.aa-tc-save-card .qa-q::before { content:'Q: '!important; font-weight:700!important; color:#1a6b3c!important; }
.aa-tc-save-card .qa-a::before { content:'A: '!important; font-weight:700!important; color:#64748b!important; }
.aa-tc-save-btn { background:#1a6b3c!important; color:#fff!important; border:none!important; border-radius:8px!important; padding:8px 18px!important; font-size:12px!important; font-weight:700!important; cursor:pointer!important; }
.aa-tc-save-btn:hover { background:#145530!important; }
.aa-tc-edit-btn { background:#fff!important; border:1.5px solid #e2e8f0!important; border-radius:8px!important; padding:8px 14px!important; font-size:12px!important; font-weight:600!important; cursor:pointer!important; color:#64748b!important; margin-left:6px!important; }
.aa-ws-tchat-inp { flex-shrink:0!important; background:#fff!important; border-top:1px solid #e2e8f0!important; padding:12px 16px!important; padding-bottom:calc(12px + env(safe-area-inset-bottom,0px))!important; display:flex!important; gap:10px!important; align-items:flex-end!important; }
.aa-ws-tchat-inp textarea { flex:1!important; border:1.5px solid #e2e8f0!important; border-radius:14px!important; padding:10px 14px!important; font-family:inherit!important; font-size:14px!important; resize:none!important; min-height:42px!important; max-height:120px!important; outline:none!important; line-height:1.45!important; overflow:hidden!important; color:#1e293b!important; }
.aa-ws-tchat-inp textarea:focus { border-color:#1a6b3c!important; }
.aa-ws-tchat-inp button { background:#1a6b3c!important; color:#fff!important; border:none!important; border-radius:50%!important; width:42px!important; height:42px!important; font-size:17px!important; cursor:pointer!important; flex-shrink:0!important; display:flex!important; align-items:center!important; justify-content:center!important; }
.aa-ws-tchat-inp button:hover { background:#145530!important; }
.aa-ws-tchat-inp button:disabled { background:#cbd5e1!important; cursor:not-allowed!important; }
.aa-tc-typing { display:block!important; margin-left:4px!important; margin-bottom:12px!important; }
.aa-tc-typing-body { background:#fff!important; border-radius:18px 18px 18px 4px!important; padding:12px 16px!important; box-shadow:0 2px 8px rgba(0,0,0,.09)!important; display:inline-flex!important; align-items:center!important; gap:5px!important; }
.aa-tc-dot { width:7px!important; height:7px!important; background:#94a3b8!important; border-radius:50%!important; animation:tcbounce 1.2s ease-in-out infinite!important; }
.aa-tc-dot:nth-child(2){ animation-delay:.22s!important; }.aa-tc-dot:nth-child(3){ animation-delay:.44s!important; }
@keyframes tcbounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-7px)}}
/* Stats */
.aa-ws-stats { padding:24px!important; display:grid!important; grid-template-columns:repeat(auto-fit,minmax(180px,1fr))!important; gap:16px!important; align-content:start!important; }
.aa-ws-stat-card { background:#fff!important; border:1px solid #e2e8f0!important; border-radius:12px!important; padding:18px!important; }
.aa-ws-stat-num { font-size:32px!important; font-weight:800!important; color:#1a6b3c!important; line-height:1!important; }
.aa-ws-stat-label { font-size:12px!important; color:#64748b!important; margin-top:6px!important; font-weight:600!important; }
/* Empty state */
.aa-ws-empty { flex:1!important; display:flex!important; flex-direction:column!important; align-items:center!important; justify-content:center!important; color:#94a3b8!important; gap:12px!important; }
</style>

<div class="wrap aa-ws">

  <!-- Sidebar -->
  <div class="aa-ws-sidebar">
    <div class="aa-ws-logo">⚡ AI Workspace</div>
    <nav class="aa-ws-nav">
      <button class="aa-ws-tab active" data-tab="conv" onclick="wsTab('conv',this)">💬 Conversations</button>
      <button class="aa-ws-tab" data-tab="tchat" onclick="wsTab('tchat',this)">🧠 Train by Chat</button>
      <button class="aa-ws-tab" data-tab="teach" onclick="wsTab('teach',this)">🎓 Teach (Form)</button>
      <button class="aa-ws-tab" data-tab="serials" onclick="wsTab('serials',this)">🔑 Serial Lookup</button>
      <button class="aa-ws-tab" data-tab="stats" onclick="wsTab('stats',this)">📊 Quick Stats</button>
    </nav>
    <div class="aa-ws-footer">iBird AI Agent</div>
  </div>

  <!-- Main -->
  <div class="aa-ws-main">

    <!-- Conversations -->
    <div class="aa-ws-panel active" id="ws-conv">
      <div class="aa-ws-conv-layout">
        <!-- List -->
        <div class="aa-ws-conv-list">
          <div class="aa-ws-conv-search">
            <input type="search" id="ws-search" placeholder="Search conversations…">
          </div>
          <div class="aa-ws-conv-scroll" id="ws-list">
            <div style="padding:20px;text-align:center;color:#94a3b8;">Loading…</div>
          </div>
        </div>
        <!-- Detail -->
        <div class="aa-ws-conv-detail" id="ws-det">
          <div class="aa-ws-empty">
            <div style="font-size:40px;">💬</div>
            <div style="font-weight:600;font-size:15px;">Select a conversation</div>
            <div style="font-size:13px;">Click any row on the left.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Training Chat -->
    <div class="aa-ws-panel" id="ws-tchat">
      <div class="aa-ws-tchat">
        <div class="aa-ws-tchat-hdr">
          <div>
            <h2>🧠 Train by Chat</h2>
            <p>Teach the AI by chatting — it extracts Q&amp;A pairs and saves them to the knowledge base.</p>
          </div>
          <button onclick="wsTchatClear()" style="background:rgba(255,255,255,.15);color:#fff;border:none;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;">🗑 Clear chat</button>
        </div>
        <div class="aa-ws-tchat-msgs" id="tc-msgs"></div>
        <!-- Pending image preview (shown above the input when admin picks a photo) -->
        <div id="tc-img-bar" style="display:none;padding:8px 16px 0;background:#fff;border-top:1px solid #e2e8f0;flex-shrink:0;">
          <div style="position:relative;display:inline-block;">
            <img id="tc-img-thumb" src="" alt="" style="height:64px;width:auto;border-radius:8px;border:2px solid #1a6b3c;display:block;">
            <button type="button" onclick="tcClearImage()" style="position:absolute;top:-8px;right:-8px;background:#ef4444;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:13px;line-height:1;cursor:pointer;padding:0;">×</button>
          </div>
          <div id="tc-img-status" style="font-size:11px;color:#94a3b8;margin-top:4px;"></div>
        </div>
        <div class="aa-ws-tchat-inp">
          <input type="file" id="tc-file" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="tcHandleFile(this)">
          <button type="button" onclick="document.getElementById('tc-file').click()" title="Upload photo to train from" style="background:#f1f5f9;border:1.5px solid #e2e8f0;border-radius:50%;width:42px;height:42px;font-size:18px;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center;" id="tc-photo-btn">📷</button>
          <textarea id="tc-inp" rows="1" placeholder="Tell the AI what to know — or 📷 upload a photo to teach from it…"></textarea>
          <button id="tc-send" onclick="tcSend()">➤</button>
        </div>
      </div>
    </div>

    <!-- Teach -->
    <div class="aa-ws-panel" id="ws-teach">
      <div style="overflow-y:auto;flex:1;">
      <div class="aa-ws-teach">
        <h2>🎓 Teach the AI</h2>
        <p>Add a question-answer pair to the knowledge base. The AI will use it when answering similar questions.</p>
        <div class="aa-ws-field">
          <label class="aa-ws-label">Question (customer asks)</label>
          <input type="text" class="aa-ws-input" id="ws-teach-q" placeholder="e.g. What is the warranty period for IBR-2000?">
        </div>
        <div class="aa-ws-field">
          <label class="aa-ws-label">Answer (correct response)</label>
          <textarea class="aa-ws-ta" id="ws-teach-a" placeholder="e.g. The IBR-2000 comes with a 12-month warranty covering manufacturing defects…"></textarea>
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

    <!-- Serials -->
    <div class="aa-ws-panel" id="ws-serials">
      <div style="overflow-y:auto;flex:1;">
      <div class="aa-ws-serial">
        <h2 style="font-size:18px;font-weight:800;color:#1e293b;margin-bottom:6px;">🔑 Serial Lookup</h2>
        <p style="color:#64748b;font-size:13px;margin-bottom:20px;">Search iStock billing records by serial number or customer phone.</p>
        <div style="display:flex;gap:10px;margin-bottom:16px;">
          <input type="text" class="aa-ws-input" id="ws-ser-search" placeholder="Serial number or phone…" style="flex:1;">
          <button onclick="wsSerialSearch()" style="background:#1a6b3c;color:#fff;border:none;border-radius:10px;padding:10px 20px;font-size:14px;font-weight:700;cursor:pointer;">🔍 Search</button>
        </div>
        <div id="ws-ser-results" style="font-size:13px;color:#94a3b8;">Enter a serial or phone number above to search.</div>
      </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="aa-ws-panel" id="ws-stats">
      <div style="overflow-y:auto;flex:1;">
      <div class="aa-ws-stats" id="ws-stats-grid">
        <div style="text-align:center;padding:40px;color:#94a3b8;grid-column:1/-1;">Loading stats…</div>
      </div>
      </div>
    </div>

  </div>
</div>

<script>
var REST='<?php echo esc_js($rest_url);?>';
var NONCE='<?php echo esc_js($nonce);?>';
var AJAX_URL='<?php echo esc_js($ajax_url);?>';
var UPLOAD_NONCE='<?php echo esc_js($upload_nonce);?>';
var wsConvPage=1; var wsConvSearch=''; var wsActiveConv=null;
var wsConvTimer; var wsSearchTimer; var wsPrevCust={};

/* ── Tab switching ──────────────────────────────────────────── */
function wsTab(tab, btn){
  document.querySelectorAll('.aa-ws-tab').forEach(function(b){b.classList.remove('active');});
  document.querySelectorAll('.aa-ws-panel').forEach(function(p){p.classList.remove('active');});
  btn.classList.add('active');
  document.getElementById('ws-'+tab).classList.add('active');
  if(tab==='conv')  wsLoadConvList();
  if(tab==='teach') wsLoadTeachList();
  if(tab==='stats') wsLoadStats();
  if(tab==='tchat') tcInit();
}

/* ── Training Chat ──────────────────────────────────────────── */
var tcHistory   = [];     // [{role:'user'|'model', text:'...'}]
var tcBusy      = false;
var tcLastQA    = null;   // last extracted {question, answer, image_url, image_desc}
var tcPendingImg = null;  // {url, localSrc} — uploaded image waiting to be sent

function tcInit(){
  if(document.getElementById('tc-msgs').children.length === 0){
    tcGreet();
  }
}

function tcGreet(){
  var greet = "👋 Hi! I'm your training assistant. Teach me anything you want customers to know — product info, policies, FAQs, warranty terms, anything.\n\nJust tell me naturally, like:\n  • \"When customers ask about the IBR-2000 warranty, tell them it's 12 months.\"\n  • \"The iBird Robot Vacuum supports floors up to 200 sqm per charge.\"\n  • \"Returns are accepted within 14 days of purchase.\"\n\nI'll extract the key Q&A and ask you to confirm before saving.";
  tcBubble('ai', greet);
}

// role: 'admin'|'ai'  text: string  qa: extracted Q&A or null
// imgUrl: optional image URL to show as thumbnail in the bubble
// visionPreview: optional AI's description of image (shown as a subtle note)
function tcBubble(role, text, qa, imgUrl, visionPreview){
  var msgs = document.getElementById('tc-msgs');
  var wrap = document.createElement('div');
  wrap.className = 'aa-tc-bbl ' + (role==='admin' ? 'admin' : 'ai');

  // If there's an image, show thumbnail first.
  if(imgUrl){
    var imgEl = document.createElement('img');
    imgEl.src = imgUrl;
    imgEl.style.cssText = 'max-width:180px;max-height:140px;border-radius:10px;display:block;margin-bottom:6px;border:2px solid rgba(255,255,255,.4);';
    wrap.appendChild(imgEl);
  }

  var body = document.createElement('div');
  body.className = 'aa-tc-body';
  body.style.whiteSpace = 'pre-wrap';
  // Remove the TEACH: line from displayed text — it shows up in the save card instead.
  var displayText = text.replace(/TEACH:\s*Q:.*?\|.*?(\n|$)/gi, '').trim();
  if(displayText) body.textContent = displayText;
  if(displayText) wrap.appendChild(body);

  // AI's vision description — shown as a subtle expandable note.
  if(visionPreview && visionPreview.length > 0){
    var vn = document.createElement('div');
    vn.style.cssText = 'font-size:11px;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:6px 10px;margin-top:6px;cursor:pointer;';
    vn.innerHTML = '<strong>🔍 What I see:</strong> <span id="tc-vp-short">'+esc(visionPreview.slice(0,80))+(visionPreview.length>80?'…':'')+'</span>'
      +(visionPreview.length>80?'<span id="tc-vp-full" style="display:none;">'+esc(visionPreview)+'</span> <a href="#" onclick="this.parentNode.querySelector(\'#tc-vp-short\').style.display=\'none\';this.parentNode.querySelector(\'#tc-vp-full\').style.display=\'inline\';this.style.display=\'none\';return false;" style="color:#1a6b3c;font-size:10px;">more</a>':'');
    wrap.appendChild(vn);
  }

  var meta = document.createElement('div');
  meta.className = 'aa-tc-meta';
  meta.textContent = role==='admin' ? 'You' : 'AI Training Assistant';
  wrap.appendChild(meta);

  // If AI returned an extracted Q&A, show a save card.
  if(qa && qa.question && qa.answer){
    tcLastQA = qa;
    var imgTag = qa.image_url
      ? '<img src="'+esc(qa.image_url)+'" style="height:48px;width:auto;border-radius:6px;vertical-align:middle;margin-right:8px;border:1.5px solid #1a6b3c;">'
      : '';
    var card = document.createElement('div');
    card.className = 'aa-tc-save-card';
    card.innerHTML = '<p>'+imgTag+'💾 Shall I save this to the knowledge base?'+(qa.image_url?' <span style="font-size:10px;background:#e8f5e9;color:#1a6b3c;padding:2px 7px;border-radius:50px;font-weight:700;">+ Photo</span>':'')+'</p>'
      + '<div class="qa-q" id="tc-qtext">'+esc(qa.question)+'</div>'
      + '<div class="qa-a" id="tc-atext">'+esc(qa.answer)+'</div>'
      + '<div style="margin-top:10px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">'
      + '<button class="aa-tc-save-btn" onclick="tcSaveQA()">💾 Save to Knowledge Base</button>'
      + '<button class="aa-tc-edit-btn" onclick="tcEditQA(this)">✏️ Edit</button>'
      + '<span id="tc-save-res" style="font-size:12px;color:#1a6b3c;"></span>'
      + '</div>'
      + '<div id="tc-edit-form" style="display:none;margin-top:10px;">'
      + '<label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">QUESTION</label>'
      + '<textarea id="tc-eq" style="width:100%;border:1.5px solid #e2e8f0;border-radius:6px;padding:7px 10px;font-size:12px;font-family:inherit;min-height:40px;box-sizing:border-box;margin-bottom:6px;resize:vertical;">'+esc(qa.question)+'</textarea>'
      + '<label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">ANSWER</label>'
      + '<textarea id="tc-ea" style="width:100%;border:1.5px solid #e2e8f0;border-radius:6px;padding:7px 10px;font-size:12px;font-family:inherit;min-height:60px;box-sizing:border-box;margin-bottom:8px;resize:vertical;">'+esc(qa.answer)+'</textarea>'
      + '<button class="aa-tc-save-btn" onclick="tcSaveEdited()">💾 Save Edited Version</button>'
      + '</div>';
    wrap.appendChild(card);
  }

  msgs.appendChild(wrap);
  msgs.scrollTop = msgs.scrollHeight;
}

function tcSaveQA(){
  if(!tcLastQA) return;
  _tcDoSave(tcLastQA.question, tcLastQA.answer, tcLastQA.image_url||'', tcLastQA.image_desc||'');
}

function tcSaveEdited(){
  var q = (document.getElementById('tc-eq')||{}).value||'';
  var a = (document.getElementById('tc-ea')||{}).value||'';
  if(!q||!a){ document.getElementById('tc-save-res').textContent='Both fields required.'; return; }
  _tcDoSave(q, a, tcLastQA ? tcLastQA.image_url||'' : '', tcLastQA ? tcLastQA.image_desc||'' : '');
}

function _tcDoSave(q, a, imgUrl, imgDesc){
  var res = document.getElementById('tc-save-res');
  if(res) res.textContent = 'Saving…';
  var body = {question: q, answer: a};
  if(imgUrl) { body.image_url = imgUrl; body.image_desc = imgDesc||''; }
  fetch(REST+'/admin/train', {
    method:'POST', headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body: JSON.stringify(body)
  }).then(function(r){return r.json();}).then(function(d){
    if(res){
      if(d.error) res.innerHTML='<span style="color:#c62828;">'+esc(d.error)+'</span>';
      else res.innerHTML='✅ Saved! Example #'+d.example_id+(imgUrl?' 📷':'');
    }
    tcLastQA = null;
    // Tell the AI it was saved so conversation context stays accurate.
    tcHistory.push({role:'user', text:'Saved.'});
    setTimeout(function(){
      tcBubble('ai','✅ Saved to the knowledge base! The AI will now use this when customers ask similar questions.\n\nWant to teach me anything else?');
      tcHistory.push({role:'model', text:'✅ Saved to the knowledge base! Want to teach me anything else?'});
    }, 300);
  }).catch(function(){if(res) res.textContent='Save failed.';});
}

function tcEditQA(btn){
  var form = document.getElementById('tc-edit-form');
  if(form) form.style.display = form.style.display==='none' ? 'block' : 'none';
}

/* ── Photo upload for training chat ─────────────────────────── */
function tcHandleFile(input){
  var file = input.files[0]; if(!file) return;
  if(file.size > 5*1024*1024){ alert('Image must be under 5 MB.'); return; }
  var bar    = document.getElementById('tc-img-bar');
  var thumb  = document.getElementById('tc-img-thumb');
  var status = document.getElementById('tc-img-status');
  // Show local preview immediately.
  var reader = new FileReader();
  reader.onload = function(e){ thumb.src = e.target.result; bar.style.display='block'; };
  reader.readAsDataURL(file);
  status.textContent = 'Uploading…';
  // Upload to WordPress media.
  var fd = new FormData();
  fd.append('action', 'aiagent_upload_attachment');
  fd.append('_ajax_nonce', UPLOAD_NONCE);
  fd.append('file', file);
  fetch(AJAX_URL, { method:'POST', body:fd })
  .then(function(r){ return r.json(); })
  .then(function(d){
    if(d.success && d.data && d.data.url){
      tcPendingImg = { url: d.data.url, attachment_id: d.data.attachment_id||0, gemini_file_uri: d.data.gemini_file_uri||'', gemini_file_mime: d.data.gemini_file_mime||'image/jpeg', localSrc: thumb.src };
      var badge = d.data.gemini_file_uri ? ' 🤖' : '';
      status.innerHTML = '✅ Ready'+badge+' — <a href="'+d.data.url+'" target="_blank" style="color:#1a6b3c;">view</a>';
    } else {
      status.innerHTML = '<span style="color:#ef4444;">Upload failed.</span>';
      tcPendingImg = null;
    }
  })
  .catch(function(){ status.innerHTML='<span style="color:#ef4444;">Upload failed.</span>'; tcPendingImg=null; });
  input.value = '';
}

function tcClearImage(){
  tcPendingImg = null;
  document.getElementById('tc-img-bar').style.display = 'none';
  document.getElementById('tc-img-thumb').src = '';
  document.getElementById('tc-img-status').textContent = '';
}

function tcSend(){
  if(tcBusy) return;
  var inp = document.getElementById('tc-inp');
  var text = inp.value.trim();
  if(!text && !tcPendingImg) return;
  inp.value = '';
  tcResizeInp();

  // Capture pending image before clearing.
  var sentImg = tcPendingImg;
  if(sentImg) tcClearImage();

  // Show admin bubble (with thumbnail if image).
  tcBubble('admin', text || '(Photo)', null, sentImg ? sentImg.url : null);
  tcHistory.push({role:'user', text: sentImg ? '[Photo sent]' + (text ? ' — ' + text : '') : text});
  tcBusy = true;
  document.getElementById('tc-send').disabled = true;
  document.getElementById('tc-photo-btn').disabled = true;

  // Typing indicator.
  var typing = document.createElement('div');
  typing.id = 'tc-typ';
  typing.className = 'aa-tc-typing';
  typing.innerHTML = sentImg
    ? '<div class="aa-tc-typing-body"><span style="font-size:13px;color:#64748b;">🔍 Analyzing image…</span></div>'
    : '<div class="aa-tc-typing-body"><span class="aa-tc-dot"></span><span class="aa-tc-dot"></span><span class="aa-tc-dot"></span></div>';
  document.getElementById('tc-msgs').appendChild(typing);
  document.getElementById('tc-msgs').scrollTop = 9999;

  var payload = {message: text || '', history: tcHistory.slice(0,-1), lang:'en'};
  if(sentImg) {
    payload.image_url            = sentImg.url;
    payload.image_attachment_id  = sentImg.attachment_id||0;
    payload.gemini_file_uri      = sentImg.gemini_file_uri||'';
    payload.gemini_file_mime     = sentImg.gemini_file_mime||'image/jpeg';
  }

  fetch(REST+'/admin/train-chat', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body: JSON.stringify(payload)
  })
  .then(function(r){return r.json();})
  .then(function(d){
    var t=document.getElementById('tc-typ'); if(t) t.remove();
    var reply = d.reply || '';
    tcHistory.push({role:'model', text: reply});
    // Pass image info through so the save card can store it.
    var qa = d.extracted_qa || null;
    if(qa && sentImg){
      qa.image_url  = qa.image_url  || sentImg.url;
      qa.image_desc = qa.image_desc || d.vision_preview || '';
    }
    tcBubble('ai', reply, qa, null, d.vision_preview || '');
    if(d.auto_saved){
      setTimeout(function(){
        var el=document.getElementById('tc-save-res');
        if(el) el.innerHTML='✅ Auto-saved #'+d.saved_id;
        tcLastQA=null;
      },100);
    }
  })
  .catch(function(){
    var t=document.getElementById('tc-typ'); if(t) t.remove();
    tcBubble('ai','Sorry, something went wrong. Please try again.');
  })
  .finally(function(){
    tcBusy=false;
    document.getElementById('tc-send').disabled=false;
    document.getElementById('tc-photo-btn').disabled=false;
    document.getElementById('tc-inp').focus();
  });
}

function tcResizeInp(){
  var ta=document.getElementById('tc-inp');
  ta.style.height='auto';
  ta.style.height=Math.min(120,Math.max(42,ta.scrollHeight))+'px';
  ta.style.overflow=ta.scrollHeight>120?'auto':'hidden';
}

function wsTchatClear(){
  tcHistory=[];tcLastQA=null;
  document.getElementById('tc-msgs').innerHTML='';
  tcGreet();
}

document.getElementById('tc-inp').addEventListener('input', tcResizeInp);
document.getElementById('tc-inp').addEventListener('keydown',function(e){
  if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();tcSend();}
});

/* ── Conversations: list ────────────────────────────────────── */
function wsLoadConvList(){
  var url=REST+'/admin/conversations?per_page=30&page='+wsConvPage;
  if(wsConvSearch) url+='&search='+encodeURIComponent(wsConvSearch);
  fetch(url,{headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();})
  .then(function(d){
    var rows=d.rows||d.conversations||[];
    if(!rows.length){document.getElementById('ws-list').innerHTML='<div style="padding:24px;text-align:center;color:#94a3b8;">No conversations.</div>';return;}
    document.getElementById('ws-list').innerHTML=rows.map(function(c){
      return '<div class="aa-ws-ci'+(c.id==wsActiveConv?' active':'')+'" onclick="wsLoadDet('+c.id+')" data-id="'+c.id+'">'
        +'<div class="aa-ws-ci-id">#'+c.id+' <span class="aa-ws-badge '+(c.mode||'product')+'">'+(c.mode||'product')+'</span> <span class="aa-ws-badge '+(c.status||'active')+'">'+(c.status||'active')+'</span></div>'
        +'<div class="aa-ws-ci-meta">'+wsAgo(c.updated_at||c.created_at)+'</div>'
        +'</div>';
    }).join('');
  }).catch(function(){});
}

/* ── Conversations: detail ──────────────────────────────────── */
function wsLoadDet(id){
  wsActiveConv=id;
  document.querySelectorAll('.aa-ws-ci').forEach(function(el){el.classList.toggle('active',el.dataset.id==id);});
  var det=document.getElementById('ws-det');
  det.innerHTML='<div class="aa-ws-empty"><div>⏳</div><div>Loading…</div></div>';
  wsPrevCust={};
  fetch(REST+'/admin/conversation/'+id,{headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();})
  .then(function(d){
    var msgs=d.messages||[]; var conv=d.conversation||{};
    det.innerHTML='';
    var hdr=document.createElement('div'); hdr.className='aa-ws-det-hdr';
    hdr.innerHTML='<div><strong>#'+id+'</strong> <span class="aa-ws-badge '+(conv.mode||'product')+'">'+(conv.mode||'product')+'</span> <span class="aa-ws-badge '+(conv.status||'active')+'">'+(conv.status||'active')+'</span></div>'
      +'<div style="font-size:12px;color:#94a3b8;">'+(conv.language||'en').toUpperCase()+' · '+(conv.created_at||'').slice(0,16)+'</div>';
    det.appendChild(hdr);
    var body=document.createElement('div'); body.className='aa-ws-det-body';
    var prevQ='';
    msgs.forEach(function(m){
      if(m.role==='customer') prevQ=m.body;
      var wrap=document.createElement('div'); wrap.className='aa-ws-msg '+(m.role==='customer'?'cust':'ai');
      var bbl=document.createElement('div'); bbl.className='aa-ws-bubble'; bbl.textContent=m.body||'';
      wrap.appendChild(bbl);
      var meta=document.createElement('div'); meta.style.cssText='font-size:10px;color:#94a3b8;margin-top:3px;';
      meta.textContent=(m.role==='customer'?'Customer':'AI')+' · '+(m.created_at||'').slice(11,16);
      wrap.appendChild(meta);
      if(m.role==='ai'){
        var acts=document.createElement('div'); acts.className='aa-ws-msg-actions';
        acts.innerHTML='<button class="aa-ws-btn-sm" onclick="wsRate('+m.id+',\'good\')">👍</button>'
          +'<button class="aa-ws-btn-sm" onclick="wsRate('+m.id+',\'wrong\')">❌</button>'
          +'<button class="aa-ws-btn-sm" onclick="wsRate('+m.id+',\'incomplete\')">⚠️</button>'
          +'<button class="aa-ws-btn-sm" onclick="wsTrain('+m.id+',this)" data-q="'+esc(prevQ)+'" data-a="'+esc(m.body)+'">🎓 Train</button>'
          +'<span id="ws-rate-res-'+m.id+'" style="font-size:11px;color:#1a6b3c;"></span>';
        wrap.appendChild(acts);
        var tp=document.createElement('div'); tp.id='ws-tp-'+m.id; tp.style.display='none';
        tp.style.cssText='background:#f0fdf4;border:1.5px solid #1a6b3c;border-radius:8px;padding:10px;margin-top:6px;';
        tp.innerHTML='<div style="font-size:11px;font-weight:700;color:#1a6b3c;margin-bottom:6px;">🎓 Train from this reply</div>'
          +'<label style="font-size:10px;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">QUESTION</label>'
          +'<textarea id="ws-tq-'+m.id+'" style="width:100%;border:1.5px solid #e2e8f0;border-radius:6px;padding:6px 8px;font-size:12px;font-family:inherit;resize:vertical;min-height:50px;box-sizing:border-box;margin-bottom:6px;">'+esc(prevQ)+'</textarea>'
          +'<label style="font-size:10px;font-weight:700;color:#64748b;display:block;margin-bottom:3px;">ANSWER</label>'
          +'<textarea id="ws-ta-'+m.id+'" style="width:100%;border:1.5px solid #e2e8f0;border-radius:6px;padding:6px 8px;font-size:12px;font-family:inherit;resize:vertical;min-height:60px;box-sizing:border-box;margin-bottom:6px;">'+esc(m.body)+'</textarea>'
          +'<button onclick="wsDoTrain('+m.id+')" style="background:#1a6b3c;color:#fff;border:none;border-radius:7px;padding:6px 14px;font-size:12px;font-weight:600;cursor:pointer;margin-right:6px;">💾 Save</button>'
          +'<button onclick="document.getElementById(\'ws-tp-'+m.id+'\').style.display=\'none\'" style="border:1.5px solid #e2e8f0;border-radius:7px;padding:6px 12px;font-size:12px;background:#fff;cursor:pointer;color:#64748b;">Cancel</button>'
          +'<div id="ws-tr-'+m.id+'" style="margin-top:6px;font-size:11px;"></div>';
        wrap.appendChild(tp);
      }
      body.appendChild(wrap);
    });
    det.appendChild(body);
    body.scrollTop=body.scrollHeight;
  }).catch(function(){det.innerHTML='<div class="aa-ws-empty"><div style="color:#c62828;">Failed to load.</div></div>';});
}

function wsTrain(id, btn){
  var tp=document.getElementById('ws-tp-'+id);
  if(tp) tp.style.display=tp.style.display==='none'?'block':'none';
}
function wsDoTrain(id){
  var q=(document.getElementById('ws-tq-'+id)||{}).value||'';
  var a=(document.getElementById('ws-ta-'+id)||{}).value||'';
  var res=document.getElementById('ws-tr-'+id);
  if(!q||!a){if(res)res.innerHTML='<span style="color:#c62828;">Both fields required.</span>';return;}
  fetch(REST+'/admin/train',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body:JSON.stringify({message_id:id,question:q,answer:a})})
  .then(function(r){return r.json();}).then(function(d){
    if(res) res.innerHTML=d.error?'<span style="color:#c62828;">'+esc(d.error)+'</span>':'<span style="color:#1a6b3c;">✅ Saved! #'+d.example_id+'</span>';
  }).catch(function(){if(res)res.innerHTML='<span style="color:#c62828;">Failed.</span>';});
}
function wsRate(id,score){
  fetch(REST+'/admin/rate',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body:JSON.stringify({message_id:id,score:score,correction:'',promote:false})})
  .then(function(r){return r.json();}).then(function(){
    var el=document.getElementById('ws-rate-res-'+id);
    if(el) el.textContent={good:'👍 Rated good',wrong:'❌ Marked wrong',incomplete:'⚠️ Marked incomplete'}[score]||score;
  }).catch(function(){});
}

/* ── Teach ──────────────────────────────────────────────────── */
function wsTeachSave(){
  var q=document.getElementById('ws-teach-q').value.trim();
  var a=document.getElementById('ws-teach-a').value.trim();
  var res=document.getElementById('ws-teach-res');
  if(!q||!a){res.innerHTML='<span style="color:#c62828;">Both fields required.</span>';return;}
  res.textContent='Saving…';
  fetch(REST+'/admin/teach',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body:JSON.stringify({question:q,solution:a,language:'en'})})
  .then(function(r){return r.json();}).then(function(d){
    if(d.error) res.innerHTML='<span style="color:#c62828;">'+esc(d.error)+'</span>';
    else{res.innerHTML='<span style="color:#1a6b3c;">✅ Saved!</span>';document.getElementById('ws-teach-q').value='';document.getElementById('ws-teach-a').value='';wsLoadTeachList();}
  }).catch(function(){res.innerHTML='<span style="color:#c62828;">Failed.</span>';});
}

function wsLoadTeachList(){
  document.getElementById('ws-teach-list').innerHTML='Loading…';
  fetch(REST+'/admin/categories',{headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();}).then(function(d){
    // Just fetch taught examples count for now.
    document.getElementById('ws-teach-list').innerHTML='<a href="admin.php?page=aiagent-teach" style="color:#1a6b3c;">→ Go to full Teach AI page</a>';
  }).catch(function(){document.getElementById('ws-teach-list').innerHTML='';});
}

/* ── Serial lookup ──────────────────────────────────────────── */
function wsSerialSearch(){
  var q=document.getElementById('ws-ser-search').value.trim();
  if(!q) return;
  var res=document.getElementById('ws-ser-results');
  res.innerHTML='<span style="color:#94a3b8;">Searching…</span>';
  fetch(REST+'/admin/serials?search='+encodeURIComponent(q)+'&per_page=20',{headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();}).then(function(d){
    var rows=d.rows||[];
    if(!rows.length){res.innerHTML='<p style="color:#94a3b8;">No records found.</p>';return;}
    res.innerHTML='<table style="width:100%;border-collapse:collapse;font-size:13px;">'
      +'<thead><tr style="background:#f6f7fb;"><th style="padding:8px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;">Serial</th><th style="padding:8px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;">Product</th><th style="padding:8px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;">Invoice</th><th style="padding:8px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;">Date</th><th style="padding:8px 10px;text-align:left;font-size:11px;font-weight:700;color:#64748b;border-bottom:2px solid #e2e8f0;">Customer</th></tr></thead>'
      +'<tbody>'+rows.map(function(r){
        return '<tr style="border-bottom:1px solid #f1f5f9;">'
          +'<td style="padding:8px 10px;font-family:monospace;font-size:12px;background:#f8fafc;">'+esc(r.serial)+'</td>'
          +'<td style="padding:8px 10px;">'+esc(r.model||'—')+'</td>'
          +'<td style="padding:8px 10px;color:#64748b;">'+esc(r.invoice||'—')+'</td>'
          +'<td style="padding:8px 10px;color:#64748b;font-size:12px;">'+esc(r.purchased_at||'—')+'</td>'
          +'<td style="padding:8px 10px;color:#64748b;font-size:12px;">'+esc(r.customer_hint||'—')+'</td>'
          +'</tr>';
      }).join('')+'</tbody></table>';
  }).catch(function(){res.innerHTML='<p style="color:#c62828;">Search failed.</p>';});
}
document.getElementById('ws-ser-search').addEventListener('keydown',function(e){if(e.key==='Enter')wsSerialSearch();});

/* ── Stats ──────────────────────────────────────────────────── */
function wsLoadStats(){
  var grid=document.getElementById('ws-stats-grid');
  // Fetch a few counts from available endpoints.
  Promise.all([
    fetch(REST+'/admin/conversations?per_page=1',{headers:{'X-WP-Nonce':NONCE}}).then(function(r){return r.json();}),
    fetch(REST+'/admin/serials?per_page=1',{headers:{'X-WP-Nonce':NONCE}}).then(function(r){return r.json();}),
  ]).then(function(results){
    var convTotal=results[0].total||0;
    var serialTotal=results[1].total||0;
    grid.innerHTML=[
      {n:convTotal, l:'Total Conversations'},
      {n:serialTotal, l:'Registered Serials'},
    ].map(function(s){
      return '<div class="aa-ws-stat-card"><div class="aa-ws-stat-num">'+s.n+'</div><div class="aa-ws-stat-label">'+s.l+'</div></div>';
    }).join('')+'<div class="aa-ws-stat-card" style="grid-column:1/-1;"><a href="admin.php?page=aiagent-analytics" style="color:#1a6b3c;font-weight:600;font-size:13px;">→ Full Analytics Dashboard</a></div>';
  }).catch(function(){grid.innerHTML='<div style="color:#c62828;">Failed to load stats.</div>';});
}

/* ── Search ──────────────────────────────────────────────────── */
var wsSearchTimerID;
document.getElementById('ws-search').addEventListener('input',function(){
  clearTimeout(wsSearchTimerID);
  var q=this.value;
  wsSearchTimerID=setTimeout(function(){wsConvSearch=q;wsConvPage=1;wsLoadConvList();},350);
});

/* ── Helpers ─────────────────────────────────────────────────── */
function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s||''));return d.innerHTML;}
function wsAgo(dt){if(!dt)return'';var diff=Math.floor((Date.now()-Date.parse(dt))/1000);if(diff<60)return diff+'s ago';if(diff<3600)return Math.floor(diff/60)+'m ago';if(diff<86400)return Math.floor(diff/3600)+'h ago';return Math.floor(diff/86400)+'d ago';}

/* ── Init ────────────────────────────────────────────────────── */
wsLoadConvList();
setInterval(function(){if(document.querySelector('.aa-ws-tab.active').dataset.tab==='conv')wsLoadConvList();}, 30000);
</script>

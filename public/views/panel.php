<?php
/**
 * Chat panel HTML — served via admin-ajax.php?action=aiagent_panel
 * Variables injected by AIAgent_Widget::serve_panel():
 *   $nonce, $rest_url, $ajax_url, $upload_nonce, $settings
 */
defined( 'ABSPATH' ) || exit;

// Product page context: only a numeric ID is passed — no PII.
$ctx_product_data = null;
$ctx_product_id   = (int) ( $_GET['product_id'] ?? 0 );
if ( $ctx_product_id && function_exists( 'wc_get_product' ) ) {
	$ctx_p = wc_get_product( $ctx_product_id );
	if ( $ctx_p ) {
		$ctx_product_data = [
			'id'    => $ctx_p->get_id(),
			'name'  => $ctx_p->get_name(),
			'price' => html_entity_decode( strip_tags( $ctx_p->get_price_html() ), ENT_QUOTES, 'UTF-8' ),
			'image' => (string) ( wp_get_attachment_image_url( $ctx_p->get_image_id(), 'medium' ) ?: '' ),
		];
	}
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#1a6b3c">
<title>Support</title>
<style>
/* ── Reset ───────────────────────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html,body{width:100%;height:100%;overflow:hidden}
body{
  display:flex;flex-direction:column;
  font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
  font-size:14px;color:#1e293b;background:#fff;
  -webkit-text-size-adjust:100%;text-size-adjust:100%;
  -webkit-font-smoothing:antialiased;
}

/* ── Header ──────────────────────────────────────────────────────── */
.hdr{
  flex-shrink:0;
  display:flex;align-items:center;gap:12px;
  padding:14px 16px;
  background:linear-gradient(135deg,#1a6b3c 0%,#145530 100%);
  user-select:none;
}
.hdr-av{
  width:40px;height:40px;border-radius:50%;flex-shrink:0;
  background:rgba(255,255,255,.2);
  display:flex;align-items:center;justify-content:center;font-size:20px;
}
.hdr-info{flex:1;min-width:0}
.hdr-name{font-size:15px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.hdr-stat{font-size:11px;color:rgba(255,255,255,.85);margin-top:3px;display:flex;align-items:center;gap:5px}
.hdr-dot{width:8px;height:8px;background:#4ade80;border-radius:50%;flex-shrink:0}
.hdr-x{
  background:rgba(255,255,255,.18);border:none;color:#fff;
  width:34px;height:34px;border-radius:50%;font-size:20px;cursor:pointer;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  -webkit-tap-highlight-color:transparent;line-height:1;
}
.hdr-x:hover{background:rgba(255,255,255,.32)}

/* ── Message area — display:block (NOT flex) ─────────────────────
   margin-left:auto on a block element with width:fit-content
   always aligns it to the right — works in every browser since IE8. */
.msgs{
  flex:1;min-height:0;overflow-y:auto;overflow-x:hidden;
  display:block;                /* critical: block, not flex */
  padding:14px 12px;
  background:#f0f4f8;
  scroll-behavior:smooth;
}
.msgs::-webkit-scrollbar{width:4px}
.msgs::-webkit-scrollbar-thumb{background:#c8d0d9;border-radius:4px}

/* ── Bubbles ─────────────────────────────────────────────────────── */
@keyframes bpop{from{opacity:0;transform:scale(.88) translateY(8px)}to{opacity:1;transform:none}}
.bbl{
  display:block;
  width:-webkit-fit-content;
  width:fit-content;
  max-width:80%;
  margin-bottom:10px;
  animation:bpop .18s cubic-bezier(.34,1.4,.64,1) both;
}
/* Customer bubble: right align via margin-left:auto */
.bbl.cust  {margin-left:auto;  margin-right:4px}
/* AI / human bubbles: left align (default block flow) */
.bbl.ai    {margin-right:auto; margin-left:4px}
.bbl.human {margin-right:auto; margin-left:4px}

.bbl-body{
  display:block;padding:11px 15px;line-height:1.65;word-break:break-word;font-size:14px;
}
.bbl.ai    .bbl-body{background:#fff;color:#1a1a2e;border-radius:18px 18px 18px 4px;box-shadow:0 2px 8px rgba(0,0,0,.09)}
.bbl.cust  .bbl-body{background:#1a6b3c;color:#fff;border-radius:18px 18px 4px 18px}
.bbl.human .bbl-body{background:#fffbeb;color:#78350f;border-radius:18px 18px 18px 4px;border-left:3px solid #f59e0b}
.bbl.error .bbl-body{background:#fff1f2;color:#be123c;border-radius:12px;border-left:3px solid #fb7185}

.bbl-meta{font-size:10px;color:#94a3b8;padding:2px 5px;display:block}
.bbl.cust .bbl-meta{text-align:right}

/* ── Typing dots ──────────────────────────────────────────────────── */
.typing-wrap{display:block;margin-right:auto;margin-left:4px;margin-bottom:10px;width:-webkit-fit-content;width:fit-content}
.typing-body{background:#fff;border-radius:18px 18px 18px 4px;padding:14px 18px;box-shadow:0 2px 8px rgba(0,0,0,.09);display:flex;align-items:center;gap:6px}
.dot{width:8px;height:8px;background:#94a3b8;border-radius:50%;animation:bounce 1.2s ease-in-out infinite}
.dot:nth-child(2){animation-delay:.22s}.dot:nth-child(3){animation-delay:.44s}
@keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-8px)}}

/* ── Quick-reply chips ────────────────────────────────────────────── */
.chips{display:flex;flex-wrap:wrap;gap:8px;padding:4px 0 6px;margin-bottom:4px}
.chip{
  background:#e8f5ee;color:#145530;border:1.5px solid #1a6b3c;
  border-radius:100px;padding:9px 16px;
  font-family:inherit;font-size:13px;font-weight:600;
  cursor:pointer;white-space:nowrap;
  -webkit-tap-highlight-color:transparent;
}
.chip:hover{background:#bbf7d0}
.chip.cat{background:#fff;border-color:#e2e8f0;color:#1e293b}
.chip.cat:hover{background:#f0fdf4;border-color:#1a6b3c;color:#145530}
.chip.back{background:#f1f5f9;border-color:#e2e8f0;color:#64748b;font-size:12px;padding:7px 14px}

/* ── Product grid ─────────────────────────────────────────────────── */
.prod-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px}
.prod-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;display:flex;flex-direction:column;cursor:pointer;transition:box-shadow .2s,transform .2s}
.prod-card:hover{box-shadow:0 4px 14px rgba(0,0,0,.13);transform:translateY(-2px)}
.prod-img{width:100%;aspect-ratio:1/1;object-fit:cover;display:block}
.prod-noimg{width:100%;aspect-ratio:1/1;background:linear-gradient(135deg,#e8f5ee,#c8e6d4);display:flex;align-items:center;justify-content:center;font-size:32px}
.prod-info{padding:8px;display:flex;flex-direction:column;flex:1}
.prod-name{font-size:12px;font-weight:600;color:#1e293b;line-height:1.3;margin-bottom:4px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.prod-price{font-size:11px;font-weight:700;color:#1a6b3c;margin-bottom:6px}
.prod-ask{display:block;width:100%;background:#1a6b3c;color:#fff;border:none;border-radius:8px;padding:7px 0;font-family:inherit;font-size:12px;font-weight:600;cursor:pointer;-webkit-tap-highlight-color:transparent;margin-top:auto}
.prod-ask:hover{background:#145530}

.prod-search{display:flex;gap:8px;margin-bottom:10px}
.prod-search-inp{flex:1;border:1.5px solid #e2e8f0;border-radius:10px;padding:10px 14px;font-family:inherit;font-size:16px;color:#1e293b;outline:none;background:#f8fafc;-webkit-appearance:none}
.prod-search-inp:focus{border-color:#1a6b3c;background:#fff}
.prod-search-btn{background:#1a6b3c;color:#fff;border:none;border-radius:10px;padding:10px 16px;font-size:17px;cursor:pointer;-webkit-tap-highlight-color:transparent}

/* ── Input bar ────────────────────────────────────────────────────── */
.inp-wrap{
  flex-shrink:0;background:#fff;border-top:1px solid #e2e8f0;
  padding:10px 12px;
  padding-bottom:calc(10px + env(safe-area-inset-bottom,0px));
  display:flex;flex-direction:column;gap:8px;
}
.inp-row{
  display:flex;align-items:flex-end;gap:8px;
  background:#f1f5f9;border:2px solid #e2e8f0;border-radius:26px;
  padding:7px 8px 7px 14px;
  transition:border-color .15s,background .15s;
}
.inp-row:focus-within{border-color:#1a6b3c;background:#f0fdf4}
.inp-ta{
  flex:1;min-width:0;background:transparent;border:none;outline:none;
  resize:none;overflow:hidden;
  font-family:inherit;font-size:16px;color:#1e293b;line-height:1.45;
  min-height:24px;max-height:110px;padding:4px 0;
  -webkit-appearance:none;
}
.inp-ta::placeholder{color:#94a3b8;font-size:14px}
.inp-send{
  flex-shrink:0;width:40px;height:40px;border-radius:50%;border:none;
  background:#1a6b3c;color:#fff;font-size:17px;cursor:pointer;
  display:flex;align-items:center;justify-content:center;
  transition:background .15s,transform .1s;
  -webkit-tap-highlight-color:transparent;
}
.inp-send:not(:disabled):hover{background:#145530}
.inp-send:not(:disabled):active{transform:scale(.9)}
.inp-send:disabled{background:#cbd5e1;cursor:not-allowed}
.inp-photo{
  flex-shrink:0;background:none;border:none;color:#94a3b8;font-size:20px;cursor:pointer;
  width:34px;height:34px;display:flex;align-items:center;justify-content:center;
  -webkit-tap-highlight-color:transparent;
}
.inp-photo:hover:not(:disabled){color:#1a6b3c}
.inp-photo:disabled{opacity:.4;cursor:not-allowed}
.esc-row{text-align:center}
.esc-btn{
  background:none;border:none;color:#94a3b8;font-family:inherit;
  font-size:12px;cursor:pointer;text-decoration:underline;
  -webkit-tap-highlight-color:transparent;
}
.esc-btn:hover{color:#1a6b3c}

/* ── Ownership verify form ────────────────────────────────────────── */
.vf{background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;padding:16px;margin-bottom:10px;box-shadow:0 2px 10px rgba(0,0,0,.07)}
.vf-title{display:block;font-size:13px;font-weight:700;color:#1a6b3c;margin-bottom:14px}
.vf-field{margin-bottom:10px}
.vf-label{display:block;font-size:11px;font-weight:700;color:#64748b;margin-bottom:5px;text-transform:uppercase;letter-spacing:.5px}
.vf-inp{
  display:block;width:100%;border:1.5px solid #e2e8f0;border-radius:10px;
  padding:10px 14px;font-family:inherit;font-size:16px;color:#1e293b;
  background:#f8fafc;outline:none;-webkit-appearance:none;
}
.vf-inp:focus{border-color:#1a6b3c;background:#fff;box-shadow:0 0 0 3px rgba(26,107,60,.12)}
.vf-status{display:block;font-size:12px;color:#be123c;min-height:18px;margin-bottom:8px}
.vf-actions{display:flex;gap:8px;margin-top:6px}
.vf-submit{flex:1;background:#1a6b3c;color:#fff;border:none;border-radius:10px;padding:12px;font-family:inherit;font-size:14px;font-weight:600;cursor:pointer;-webkit-appearance:none}
.vf-submit:hover:not(:disabled){background:#145530}
.vf-submit:disabled{opacity:.6;cursor:not-allowed}
.vf-cancel{background:none;border:1.5px solid #e2e8f0;border-radius:10px;padding:12px 16px;font-family:inherit;font-size:14px;cursor:pointer;color:#64748b;-webkit-appearance:none}

/* ── PDPL consent bar ─────────────────────────────────────────────── */
.privacy-bar{
  flex-shrink:0;display:flex;align-items:center;justify-content:space-between;
  background:#fffbeb;border-bottom:1px solid #fde68a;
  padding:7px 14px;font-size:11px;color:#92400e;line-height:1.4;
  gap:8px;
}
.privacy-bar a{color:#92400e;font-weight:600;}
.privacy-bar-x{background:none;border:none;color:#92400e;cursor:pointer;font-size:15px;line-height:1;padding:0 2px;flex-shrink:0}

/* ── Selected-product chip ────────────────────────────────────────── */
.sel-bar{
  flex-shrink:0;display:none;align-items:center;justify-content:space-between;gap:8px;
  background:#f0fdf4;border-bottom:1px solid #bbf7d0;
  padding:7px 14px;font-size:12px;color:#145530;
}
.sel-bar strong{font-weight:700}
.sel-bar-change{background:none;border:none;color:#1a6b3c;cursor:pointer;font-size:11px;font-weight:700;text-decoration:underline;flex-shrink:0;-webkit-tap-highlight-color:transparent}
</style>
</head>
<body>

<!-- Header -->
<div class="hdr">
  <div class="hdr-av">🤖</div>
  <div class="hdr-info">
    <div class="hdr-name" id="hdr-title"><?php echo esc_html( $settings['widget_title_en'] ); ?></div>
    <div class="hdr-stat"><span class="hdr-dot"></span><span id="hdr-sub">Online · Replies instantly</span></div>
  </div>
  <button class="hdr-x" id="close-btn" aria-label="Close">×</button>
</div>

<!-- PDPL consent notice — shown once per browser session, dismissed by ✕ -->
<div class="privacy-bar" id="privacy-bar" role="note" aria-label="Privacy notice"
  style="<?php echo ( isset( $_COOKIE['aa_privacy_ok'] ) ? 'display:none' : '' ); ?>">
  <span>🔒 This chat is powered by Google AI (Gemini). By chatting you agree to our use of AI for support. No personal data is shared with AI.</span>
  <button class="privacy-bar-x" id="privacy-ok" aria-label="Dismiss">✕</button>
</div>

<!-- Selected-product context bar -->
<div class="sel-bar" id="sel-bar" role="note">
  <span id="sel-bar-text"></span>
  <button class="sel-bar-change" id="sel-bar-change" type="button"></button>
</div>

<!-- Messages -->
<div class="msgs" id="msgs" role="log" aria-live="polite"></div>

<!-- Hidden file input (outside layout flow) -->
<input type="file" id="photo-file" accept="image/*" style="display:none" aria-hidden="true" tabindex="-1">

<!-- Input bar -->
<div class="inp-wrap">
  <div class="inp-row">
    <button class="inp-photo" id="photo-btn" type="button" aria-label="Attach image">📎</button>
    <textarea class="inp-ta" id="msg-inp" rows="1"
      placeholder="<?php echo esc_attr( $settings['widget_title_en'] ? 'Type your question…' : '' ); ?>"
      aria-label="Your message"></textarea>
    <button class="inp-send" id="send-btn" type="button" aria-label="Send">➤</button>
  </div>
  <div class="esc-row">
    <button class="esc-btn" id="esc-btn" type="button">Talk to a person</button>
  </div>
</div>

<script>
'use strict';
/* ── Config (injected by PHP, no secrets) ───────────────────────────── */
var REST         = '<?php echo esc_js( rtrim( $rest_url, '/' ) ); ?>';
var NONCE        = '<?php echo esc_js( $nonce ); ?>';
var AJAX         = '<?php echo esc_js( $ajax_url ); ?>';
var UPLOAD_NONCE = '<?php echo esc_js( $upload_nonce ); ?>';
var TITLE_EN     = '<?php echo esc_js( $settings['widget_title_en'] ); ?>';
var TITLE_AR     = '<?php echo esc_js( $settings['widget_title_ar'] ); ?>';
var FB_EN        = '<?php echo esc_js( $settings['fallback_message_en'] ); ?>';
var FB_AR        = '<?php echo esc_js( $settings['fallback_message_ar'] ); ?>';
var CTX_PRODUCT  = <?php echo $ctx_product_data ? wp_json_encode( $ctx_product_data ) : 'null'; ?>;

/* ── Privacy bar dismiss ─────────────────────────────────────────── */
var $privacyBar = document.getElementById('privacy-bar');
var $privacyOk  = document.getElementById('privacy-ok');
if ($privacyOk) {
  $privacyOk.addEventListener('click', function() {
    $privacyBar.style.display = 'none';
    // Session cookie — dismissed for this browser session.
    document.cookie = 'aa_privacy_ok=1; path=/; SameSite=Lax';
  });
}

/* ── DOM refs ─────────────────────────────────────────────────────── */
var $msgs      = document.getElementById('msgs');
var $inp       = document.getElementById('msg-inp');
var $send      = document.getElementById('send-btn');
var $photoBtn  = document.getElementById('photo-btn');
var $photoFile = document.getElementById('photo-file');
var $escBtn    = document.getElementById('esc-btn');

/* ── State ────────────────────────────────────────────────────────── */
var lang         = 'en';
var langLocked   = false; // true once customer sends first message
var pendingLang  = null;  // candidate switch language
var pendingCount = 0;     // consecutive messages in pendingLang
var busy  = false;
var vMode = false;
var selectedProductId   = null;
var selectedProductName = null;

/* ── Session token ────────────────────────────────────────────────── */
function sess() {
  var k = 'aa_sess', v = sessionStorage.getItem(k);
  if (!v) { v = 'sess-' + Date.now() + '-' + Math.random().toString(36).slice(2,9); sessionStorage.setItem(k,v); }
  return v;
}

/* ── Language helpers ──────────────────────────────────────────────── */
function isAr(s) { return /[؀-ۿ]/.test(s); }
function t(en, ar) { return lang === 'ar' ? ar : en; }
function fb() { return lang === 'ar' ? FB_AR : FB_EN; }

function setLang(l) {
  lang = l;
  document.documentElement.lang = l;
  var dir = l === 'ar' ? 'rtl' : 'ltr';
  document.documentElement.dir = dir;
  document.getElementById('hdr-title').textContent = l === 'ar' ? TITLE_AR : TITLE_EN;
  document.getElementById('hdr-sub').textContent   = l === 'ar' ? 'متاح · يرد فوراً' : 'Online · Replies instantly';
  $inp.placeholder   = l === 'ar' ? 'اكتب سؤالك…' : 'Type your question…';
  $inp.dir           = dir;
  $escBtn.textContent = l === 'ar' ? 'التحدث مع موظف' : 'Talk to a person';
}

/* ── Language update — locks after first message, switches on 2 consecutive ── */
function updateLang(detected) {
  if (!langLocked) {
    // First customer message: lock immediately.
    langLocked = true;
    setLang(detected);
    pendingLang = null; pendingCount = 0;
    return;
  }
  if (detected === lang) {
    // Same as current: cancel any pending switch.
    pendingLang = null; pendingCount = 0;
    return;
  }
  // Different language: require 2 consecutive messages before switching.
  if (pendingLang === detected) {
    pendingCount++;
    if (pendingCount >= 2) {
      setLang(detected);
      pendingLang = null; pendingCount = 0;
    }
  } else {
    pendingLang = detected;
    pendingCount = 1;
  }
}

/* ── htmlEnc ──────────────────────────────────────────────────────── */
function htmlEnc(s) {
  return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}
function scrollEnd() { $msgs.scrollTop = $msgs.scrollHeight; }

/* ── Bubble ──────────────────────────────────────────────────────── */
function bubble(role, text, isErr) {
  var l   = isAr(text) ? 'ar' : 'en';
  var bbl = document.createElement('div');
  bbl.className = 'bbl ' + role + (isErr ? ' error' : '');
  bbl.dir       = l === 'ar' ? 'rtl' : 'ltr';

  var body  = document.createElement('div');
  body.className = 'bbl-body';
  body.dir       = l === 'ar' ? 'rtl' : 'ltr';
  body.innerHTML = htmlEnc(text);

  var meta = document.createElement('div');
  meta.className   = 'bbl-meta';
  meta.textContent = new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});

  bbl.appendChild(body);
  bbl.appendChild(meta);
  $msgs.appendChild(bbl);
  scrollEnd();
  return bbl;
}

/* ── Typing indicator ────────────────────────────────────────────── */
function showTyping() {
  if (document.getElementById('aa-typ')) return;
  var w = document.createElement('div'); w.id = 'aa-typ'; w.className = 'typing-wrap';
  var b = document.createElement('div'); b.className = 'typing-body';
  for (var i=0;i<3;i++){var d=document.createElement('span');d.className='dot';b.appendChild(d);}
  w.appendChild(b); $msgs.appendChild(w); scrollEnd();
}
function hideTyping() { var el=document.getElementById('aa-typ'); if(el) el.remove(); }

/* ── Quick-reply chips ───────────────────────────────────────────── */
function chips(opts) {
  var old = $msgs.querySelector('.chips'); if (old) old.remove();
  var row = document.createElement('div'); row.className = 'chips';
  opts.forEach(function(opt) {
    var btn = document.createElement('button');
    btn.type      = 'button';
    btn.className = 'chip' + (opt.cls ? ' '+opt.cls : '');
    btn.textContent = opt.label;
    btn.addEventListener('click', function() {
      row.remove();
      if (opt.fn)  opt.fn();
      else if (opt.val) { $inp.value = opt.val; doSend(); }
    });
    row.appendChild(btn);
  });
  $msgs.appendChild(row);
  scrollEnd();
}

/* ── Product browsing ───────────────────────────────────────────── */
function loadCategories() {
  setBusy(true);
  fetch(REST+'/products/categories',{headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();})
  .then(function(d){
    setBusy(false);
    var cats = d.categories||[];
    if(!cats.length){loadProducts(0,'');return;}
    bubble('ai',t('Which category are you interested in?','أي فئة تهمك؟'));
    var opts = cats.slice(0,8).map(function(c){
      return{label:c.name+' ('+c.count+')',cls:'cat',fn:function(){bubble('cust',c.name);loadProducts(c.id,'');}};
    });
    opts.push({label:t('🔍 Search all products','🔍 بحث في جميع المنتجات'),fn:function(){showProdSearch(0);}});
    chips(opts);
  })
  .catch(function(){setBusy(false);loadProducts(0,'');});
}

function loadProducts(catId,search) {
  setBusy(true);
  var url=REST+'/products/list', q=[];
  if(catId)  q.push('category_id='+catId);
  if(search) q.push('search='+encodeURIComponent(search));
  if(q.length) url+='?'+q.join('&');
  fetch(url,{headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();})
  .then(function(d){
    setBusy(false);
    var prods=d.products||[];
    if(!prods.length){bubble('ai',t('No products found. Try a different search.','لم يتم العثور على منتجات. جرب بحثاً آخر.'));showProdSearch(catId);return;}
    bubble('ai',t('Here are some products — tap one to ask about it:','إليك بعض المنتجات — اضغط للسؤال عنه:'));
    prodGrid(prods,d.total||prods.length,catId);
  })
  .catch(function(){setBusy(false);bubble('ai',t('Could not load products.','تعذر تحميل المنتجات.'),true);});
}

function prodGrid(prods,total,catId) {
  var grid=document.createElement('div'); grid.className='prod-grid';
  prods.forEach(function(p){
    var card=document.createElement('div'); card.className='prod-card';
    if(p.image){var img=document.createElement('img');img.className='prod-img';img.src=p.image;img.alt=p.name;img.loading='lazy';card.appendChild(img);}
    else{var ni=document.createElement('div');ni.className='prod-noimg';ni.textContent='📦';card.appendChild(ni);}
    var info=document.createElement('div'); info.className='prod-info';
    var nm=document.createElement('div');   nm.className='prod-name'; nm.textContent=p.name;
    var pr=document.createElement('div');   pr.className='prod-price'; pr.textContent=p.price||'';
    var ab=document.createElement('button');ab.type='button'; ab.className='prod-ask'; ab.textContent=t('Ask about this','اسأل عن هذا');
    ab.addEventListener('click',function(){grid.remove();selectProduct(p);$inp.value=t('Tell me about ','أخبرني عن ')+p.name;doSend();});
    info.appendChild(nm); info.appendChild(pr); info.appendChild(ab);
    card.appendChild(info); grid.appendChild(card);
  });
  $msgs.appendChild(grid);
  var nav=[{label:t('← All categories','← جميع الفئات'),cls:'back',fn:loadCategories}];
  if(total>prods.length) nav.unshift({label:t('🔍 Search more…','🔍 بحث أكثر…'),fn:function(){showProdSearch(catId);}});
  chips(nav); scrollEnd();
}

function showProdSearch(catId) {
  bubble('ai',t('Type a product name to search:','اكتب اسم المنتج للبحث:'));
  var wrap=document.createElement('div'); wrap.className='prod-search';
  var inp=document.createElement('input'); inp.type='text'; inp.className='prod-search-inp';
  inp.placeholder=t('e.g. Robot Vacuum','مثال: مكنسة روبوت');
  var btn=document.createElement('button'); btn.type='button'; btn.className='prod-search-btn'; btn.textContent='🔍';
  function go(){var q=inp.value.trim();if(!q)return;wrap.remove();loadProducts(catId,q);}
  btn.addEventListener('click',go);
  inp.addEventListener('keydown',function(e){if(e.key==='Enter')go();});
  wrap.appendChild(inp); wrap.appendChild(btn);
  $msgs.appendChild(wrap); scrollEnd();
  setTimeout(function(){inp.focus();},80);
}

/* ── Single product card (used for product-page context highlight) ── */
function showProductCard(p) {
  var card = document.createElement('div');
  card.className = 'prod-card';
  card.style.cssText = 'max-width:220px;margin:0 auto 10px;border:2px solid #1a6b3c;cursor:default;';
  if (p.image) {
    var img = document.createElement('img');
    img.className = 'prod-img'; img.src = p.image; img.alt = p.name; img.loading = 'eager';
    card.appendChild(img);
  } else {
    var ni = document.createElement('div'); ni.className = 'prod-noimg'; ni.textContent = '📦';
    card.appendChild(ni);
  }
  var info = document.createElement('div'); info.className = 'prod-info';
  var nm   = document.createElement('div'); nm.className   = 'prod-name'; nm.textContent = p.name;
  var pr   = document.createElement('div'); pr.className   = 'prod-price'; pr.textContent = p.price || '';
  info.appendChild(nm); info.appendChild(pr);
  card.appendChild(info);
  $msgs.appendChild(card);
  scrollEnd();
}

/* ── Selected-product context ────────────────────────────────────── */
/* Persists which product the customer is discussing so manual-grounded
   troubleshooting/how-to can use it before ownership is ever verified —
   mirrors a human rep confirming "so this is about the X, right?" */
function renderSelBar() {
  var bar = document.getElementById('sel-bar');
  var txt = document.getElementById('sel-bar-text');
  var chg = document.getElementById('sel-bar-change');
  if (!selectedProductName) { bar.style.display = 'none'; return; }
  bar.style.display = 'flex';
  txt.innerHTML = t('Discussing: ','بخصوص: ') + '<strong>' + htmlEnc(selectedProductName) + '</strong>';
  chg.textContent = t('Change','تغيير');
}
document.getElementById('sel-bar-change').addEventListener('click', function(){
  selectedProductId = null; selectedProductName = null;
  renderSelBar();
  bubble('cust', t('Browse Products','تصفح المنتجات'));
  loadCategories();
});

function selectProduct(p) {
  selectedProductId   = p.id;
  selectedProductName = p.name;
  renderSelBar();
  // Fire-and-forget — /chat also carries product_id defensively on every call,
  // but setting it now means even a photo sent before any text question is scoped.
  fetch(REST+'/chat/select-product',{
    method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body:JSON.stringify({session_token:sess(),product_id:p.id}),
  }).catch(function(){});
}

/* ── Send message ────────────────────────────────────────────────── */
function doSend() {
  if(vMode) return;
  var text=$inp.value.trim();
  if(!text||busy) return;
  updateLang(isAr(text) ? 'ar' : 'en');
  $inp.value=''; resizeInput();
  var old=$msgs.querySelector('.chips'); if(old) old.remove();
  bubble('cust',text);
  setBusy(true);
  fetch(REST+'/chat',{
    method:'POST',
    headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body:JSON.stringify({session_token:sess(),message:text,product_id:selectedProductId||undefined}),
  })
  .then(function(r){return r.json().then(function(d){return{ok:r.ok,s:r.status,d:d};});})
  .then(function(res){
    hideTyping();
    var d=res.d;
    if(res.s===429||d.throttled){bubble('ai',d.reply||fb(),true);return;}
    if(!res.ok){bubble('ai',fb(),true);return;}
    if(d.selected_model && !selectedProductName){selectedProductName=d.selected_model;renderSelBar();}
    bubble('ai',d.reply);
    if(d.mode==='clarify'){
      chips([
        {label:t('🛍️ Browse Products','🛍️ تصفح المنتجات'),fn:function(){bubble('cust',t('Browse Products','تصفح المنتجات'));loadCategories();}},
        {label:t('🔧 I need support','🔧 دعم فني'),val:t('I need support for a product I already own','أحتاج دعماً لجهاز اشتريته')},
      ]);
    }
    if(d.escalated) bubble('ai',t('🎫 A specialist has been notified and will follow up shortly.','🎫 تم إخطار متخصص وسيتواصل معك قريباً.'));
    if(d.needs_verification) showVerify();
  })
  .catch(function(){hideTyping();bubble('ai',fb(),true);})
  .finally(function(){setBusy(false);});
}

/* ── Serial lookup by name+phone ─────────────────────────────────── */
function showSerialLookup(parentForm, onSelect) {
  var existing = parentForm.querySelector('.aa-lookup-panel');
  if (existing) { existing.remove(); return; }

  var panel = document.createElement('div');
  panel.className = 'aa-lookup-panel';
  panel.style.cssText = 'background:#f0fdf4;border:1.5px solid #1a6b3c;border-radius:10px;padding:12px;margin-bottom:10px;';

  var title = document.createElement('div');
  title.style.cssText = 'font-size:12px;font-weight:700;color:#1a6b3c;margin-bottom:10px;';
  title.textContent = t('Find your products by mobile number:', 'ابحث عن منتجاتك برقم الجوال:');
  panel.appendChild(title);

  function mkInp(id, placeholder) {
    var inp = document.createElement('input');
    inp.type = 'text'; inp.id = id; inp.placeholder = placeholder;
    inp.style.cssText = 'display:block;width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:14px;font-family:inherit;margin-bottom:8px;box-sizing:border-box;outline:none;-webkit-appearance:none;';
    return inp;
  }
  var phoneInp = mkInp('lk-phone', t('Phone: +973 XXXX XXXX','الهاتف: +973 XXXX XXXX'));
  var searchBtn = document.createElement('button');
  searchBtn.type = 'button';
  searchBtn.textContent = t('🔍 Find My Products','🔍 ابحث عن منتجاتي');
  searchBtn.style.cssText = 'background:#1a6b3c;color:#fff;border:none;border-radius:8px;padding:9px 16px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;width:100%;';
  var resultArea = document.createElement('div');
  resultArea.style.cssText = 'margin-top:10px;';

  searchBtn.addEventListener('click', function(){
    var phone = phoneInp.value.trim();
    if(!phone){resultArea.innerHTML='<p style="color:#c62828;font-size:12px;">'+t('Please enter your mobile number.','يرجى إدخال رقم الجوال.')+'</p>';return;}
    searchBtn.disabled=true; searchBtn.textContent=t('Searching…','جاري البحث…');
    resultArea.innerHTML='';
    fetch(REST+'/support/lookup',{
      method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
      body:JSON.stringify({session_token:sess(),phone:phone})
    })
    .then(function(r){return r.json();})
    .then(function(d){
      searchBtn.disabled=false; searchBtn.textContent=t('🔍 Find My Products','🔍 ابحث عن منتجاتي');
      if(!d.found||!d.products||!d.products.length){
        resultArea.innerHTML='<p style="color:#94a3b8;font-size:12px;">'+t('No products found under those details.','لم يتم العثور على منتجات بهذه البيانات.')+'</p>';
        return;
      }
      var html='<p style="font-size:12px;font-weight:700;color:#1a6b3c;margin-bottom:8px;">'+t('Select your device:','اختر جهازك:')+'</p>';
      d.products.forEach(function(p, i){
        html+='<div onclick="aaLookupSelect('+i+','+JSON.stringify(p).replace(/</g,'&lt;')+')" style="padding:10px 12px;background:#fff;border:1.5px solid #e2e8f0;border-radius:8px;margin-bottom:6px;cursor:pointer;font-size:12px;" onmouseover="this.style.borderColor=\'#1a6b3c\'" onmouseout="this.style.borderColor=\'#e2e8f0\'">'
          +'<div style="font-weight:700;color:#1e293b;">'+htmlEnc(p.model||'—')+'</div>'
          +'<div style="color:#94a3b8;margin-top:2px;">SN: '+htmlEnc(p.serial_hint||'—')+' · '+htmlEnc(p.purchased_at||'')+'</div>'
          +'</div>';
      });
      resultArea.innerHTML=html;
      window._aaLookupProducts=d.products;
      window._aaLookupCallback=function(p){
        panel.remove();
        onSelect(p.model, p.serial_hint||'');
      };
    })
    .catch(function(){searchBtn.disabled=false;searchBtn.textContent=t('🔍 Find My Products','🔍 ابحث عن منتجاتي');resultArea.innerHTML='<p style="color:#c62828;font-size:12px;">'+t('Search failed. Please try again.','فشل البحث. يرجى المحاولة مرة أخرى.')+'</p>';});
  });

  panel.appendChild(phoneInp);
  panel.appendChild(searchBtn);
  panel.appendChild(resultArea);

  // Insert before the lookup link (i.e., at the end of the form before submit).
  parentForm.insertBefore(panel, parentForm.querySelector('.aa-lookup-panel') || parentForm.querySelector('.vf-status'));
}

window.aaLookupSelect = function(idx, product) {
  if(window._aaLookupCallback) window._aaLookupCallback(product);
};

/* ── Mode B: verify form ────────────────────────────────────────── */
function showVerify() {
  vMode=true;
  var rtl=lang==='ar';
  var form=document.createElement('div'); form.className='vf'; form.dir=rtl?'rtl':'ltr';
  function field(id,lbl,type,ph){
    var f=document.createElement('div'); f.className='vf-field';
    var lb=document.createElement('label'); lb.className='vf-label'; lb.textContent=lbl; lb.setAttribute('for',id);
    var inp=document.createElement('input'); inp.type=type||'text'; inp.id=id; inp.className='vf-inp'; inp.placeholder=ph||''; inp.autocomplete='off';
    f.appendChild(lb); f.appendChild(inp); return f;
  }
  var status=document.createElement('p'); status.className='vf-status'; status.setAttribute('aria-live','polite');
  var sub=document.createElement('button'); sub.type='button'; sub.className='vf-submit'; sub.textContent=t('Verify Ownership','تحقق من الملكية');
  var can=document.createElement('button'); can.type='button'; can.className='vf-cancel'; can.textContent=t('Cancel','إلغاء');
  sub.addEventListener('click',function(){
    var model=document.getElementById('vf-model').value.trim();
    var serial=document.getElementById('vf-serial').value.trim();
    var phone=document.getElementById('vf-phone').value.trim();
    if(!model||!serial||!phone){status.textContent=t('Please fill in all fields.','يرجى تعبئة جميع الحقول.');return;}
    sub.disabled=true; status.textContent=t('Verifying…','جارٍ التحقق…');
    fetch(REST+'/support/verify',{
      method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
      body:JSON.stringify({session_token:sess(),model:model,serial:serial,phone:phone}),
    })
    .then(function(r){return r.json();})
    .then(function(d){form.remove();vMode=false;if(d.verified){bubble('ai',d.reply);}else{bubble('ai',d.reply||t('Could not verify. Please check your details.','تعذر التحقق. يرجى مراجعة البيانات.'),true);escPrompt();}})
    .catch(function(){form.remove();vMode=false;bubble('ai',fb(),true);});
  });
  can.addEventListener('click',function(){form.remove();vMode=false;bubble('ai',t('No problem. Let me know if you need help.','لا بأس. أخبرني إذا احتجت مساعدة.'));});
  var titleEl=document.createElement('span'); titleEl.className='vf-title'; titleEl.textContent=t('Verify your ownership:','التحقق من ملكيتك:');
  var actions=document.createElement('div'); actions.className='vf-actions'; actions.appendChild(sub); actions.appendChild(can);
  form.appendChild(titleEl);
  form.appendChild(field('vf-model', t('Product Model','موديل المنتج'), 'text', t('e.g. IBR-2000','مثال: IBR-2000')));
  form.appendChild(field('vf-serial',t('Serial Number','الرقم التسلسلي'),'text',t('On label on device','على ملصق الجهاز')));

  // "Find my serial" link.
  var lookupLink = document.createElement('div');
  lookupLink.style.cssText = 'font-size:12px;color:#1a6b3c;text-align:center;margin-bottom:10px;cursor:pointer;text-decoration:underline;';
  lookupLink.textContent = t('I don\'t know my serial — find it by my mobile number','لا أعرف الرقم التسلسلي — ابحث برقم الجوال');
  lookupLink.addEventListener('click', function(){
    lookupLink.style.display = 'none';
    showSerialLookup(form, function(selectedModel, selectedSerialHint){
      var modelEl = document.getElementById('vf-model');
      if(modelEl && selectedModel) modelEl.value = selectedModel;
      var serialEl = document.getElementById('vf-serial');
      if(serialEl) serialEl.value = selectedSerialHint || '';
      lookupLink.style.display = '';
    });
  });
  form.appendChild(lookupLink);

  form.appendChild(field('vf-phone', t('Phone Number','رقم الجوال'),'tel','+973 XXXX XXXX'));
  form.appendChild(status); form.appendChild(actions);
  $msgs.appendChild(form); scrollEnd();
  setTimeout(function(){document.getElementById('vf-model').focus();},60);
}

function escPrompt(){
  var btn=document.createElement('button'); btn.type='button';
  btn.style.cssText='display:block;width:-webkit-fit-content;width:fit-content;margin:8px auto;background:#e8f5ee;color:#145530;border:1.5px solid #1a6b3c;border-radius:100px;padding:10px 22px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;';
  btn.textContent=t('🙋 Talk to a team member','🙋 التحدث مع أحد أعضاء الفريق');
  btn.addEventListener('click',function(){btn.remove();escalate();});
  $msgs.appendChild(btn); scrollEnd();
}

/* ── Escalate ────────────────────────────────────────────────────── */
function escalate(){
  if(busy) return; // guard against double-click/double-tap creating duplicate requests
  setBusy(true);
  fetch(REST+'/escalate',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body:JSON.stringify({session_token:sess(),reason:t('Customer requested human support','طلب العميل دعم بشري')})})
  .then(function(r){return r.json();})
  .then(function(d){hideTyping();bubble('ai',t('🎫 Ticket #'+(d.ticket_id||'?')+' created. Our team will follow up shortly.','🎫 تذكرة #'+(d.ticket_id||'؟')+' تم إنشاؤها. سيتواصل فريقنا معك قريباً.'));})
  .catch(function(){hideTyping();bubble('ai',t('Unable to create a ticket right now.','تعذر إنشاء تذكرة الآن.'),true);})
  .finally(function(){setBusy(false);});
}

/* ── Photo upload ────────────────────────────────────────────────── */
$photoFile.addEventListener('change',function(){
  if(this.files&&this.files[0]) sendPhoto(this.files[0]);
  this.value='';
});
$photoBtn.addEventListener('click',function(){$photoFile.click();});
function sendPhoto(file){
  if(busy) return;
  if(!file.type.match(/^image\//)){bubble('ai',t('Only image files accepted.','الملفات المقبولة هي الصور فقط.'),true);return;}
  if(file.size>5*1024*1024){bubble('ai',t('Image must be under 5 MB.','حجم الصورة يجب أن يكون أقل من 5 ميغابايت.'),true);return;}
  var url=URL.createObjectURL(file);
  var bbl=bubble('cust','');
  if(bbl){var img=document.createElement('img');img.src=url;img.alt='';img.style.cssText='max-width:160px;max-height:120px;border-radius:8px;display:block;margin-top:4px;';bbl.querySelector('.bbl-body').appendChild(img);}
  setBusy(true);
  var fd=new FormData();fd.append('file',file);fd.append('action','aiagent_upload_attachment');fd.append('_ajax_nonce',UPLOAD_NONCE);
  fetch(AJAX,{method:'POST',body:fd})
  .then(function(r){return r.json();})
  .then(function(d){
    if(!d.success||!d.data||!d.data.url) throw new Error('Upload failed.');
    return fetch(REST+'/chat/attachment',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
      body:JSON.stringify({session_token:sess(),attachment_url:d.data.url,attachment_id:d.data.attachment_id||0,gemini_file_uri:d.data.gemini_file_uri||'',gemini_file_mime:d.data.gemini_file_mime||'image/jpeg',product_id:selectedProductId||undefined})}).then(function(r){return r.json();});
  })
  .then(function(d){hideTyping();if(d.selected_model && !selectedProductName){selectedProductName=d.selected_model;renderSelBar();}bubble('ai',d.reply);if(d.needs_verification)showVerify();})
  .catch(function(e){hideTyping();bubble('ai',e.message||fb(),true);})
  .finally(function(){setBusy(false);URL.revokeObjectURL(url);});
}

/* ── Busy state ──────────────────────────────────────────────────── */
function setBusy(b){
  busy=b;$inp.disabled=b;$send.disabled=b;$photoBtn.disabled=b;$escBtn.disabled=b;
  if(b){showTyping();}else{hideTyping();$inp.focus();}
}

/* ── Textarea resize ─────────────────────────────────────────────── */
function resizeInput(){
  $inp.style.height='auto';
  var h=Math.min(110,Math.max(24,$inp.scrollHeight));
  $inp.style.height=h+'px';
  $inp.style.overflow=h>=110?'auto':'hidden';
}
$inp.addEventListener('input',resizeInput);
$inp.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();doSend();}});
$send.addEventListener('click',doSend);

/* ── Close: tell parent to hide iframe ───────────────────────────── */
document.getElementById('close-btn').addEventListener('click',function(){
  window.parent.postMessage({type:'aa:close'},'*');
});

/* ── Keyboard accessibility ──────────────────────────────────────── */
document.addEventListener('keydown',function(e){
  if(e.key==='Escape') window.parent.postMessage({type:'aa:close'},'*');
});

/* ── Escalate button ─────────────────────────────────────────────── */
$escBtn.addEventListener('click',escalate);

/* ── Greeting ────────────────────────────────────────────────────── */
if (CTX_PRODUCT) {
  // Visitor opened widget from a product page — lead with that product, and
  // register it as the selected product so manual-grounded Q&A can use it
  // right away, before any verification.
  selectProduct(CTX_PRODUCT);
  bubble('ai', t(
    'Hello! 👋 I see you\'re looking at ' + CTX_PRODUCT.name + '. I can answer your questions about it, or help with support.',
    'مرحباً! 👋 أرى أنك تتصفح ' + CTX_PRODUCT.name + '. يمكنني الإجابة عن أسئلتك أو تقديم الدعم الفني.'
  ));
  showProductCard(CTX_PRODUCT);
  chips([
    {label: t('❓ Ask about this', '❓ اسأل عن هذا'), val: t('Tell me about ', 'أخبرني عن ') + CTX_PRODUCT.name},
    {label: t('🛍️ Browse All Products', '🛍️ تصفح المنتجات'), fn: function(){ bubble('cust', t('Browse Products','تصفح المنتجات')); loadCategories(); }},
    {label: t('🔧 I need support', '🔧 دعم فني'), val: t('I need support for a product I already own','أحتاج دعماً لجهاز اشتريته')},
  ]);
} else {
  bubble('ai', t('Hello! 👋 Welcome to ' + TITLE_EN + '. How can I help you today?', 'مرحباً! 👋 أهلاً بك في ' + TITLE_AR + '. كيف يمكنني مساعدتك اليوم؟'));
  chips([
    {label: t('🛍️ Browse Products','🛍️ تصفح المنتجات'), fn: function(){ bubble('cust', t('Browse Products','تصفح المنتجات')); loadCategories(); }},
    {label: t('🔧 I need support','🔧 دعم فني'), val: t('I need support for a product I already own','أحتاج دعماً لجهاز اشتريته')},
  ]);
}
setTimeout(function(){$inp.focus();},200);
</script>
</body>
</html>

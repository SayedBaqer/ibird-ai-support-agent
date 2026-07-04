/**
 * AI Support Agent — Widget Loader v1.0.8
 *
 * Architecture: iframe-based (same approach used by Intercom, Drift, HubSpot, Zendesk).
 * The chat panel lives inside an iframe served by PHP at /?aiagent_panel=1.
 * This script only creates the FAB button and the iframe — nothing more.
 *
 * Why iframe?
 *  - 100% CSS isolation: no WordPress theme can override panel styles.
 *  - position:fixed inside an iframe is always relative to the iframe viewport (correct).
 *  - Bubble alignment (margin-left:auto) works without any Shadow DOM tricks.
 *  - Works in Chrome, Firefox, Safari, iOS Safari, and Android Chrome identically.
 *
 * Security: no secrets here. API key, nonce, and upload nonce are injected into
 * the panel HTML by PHP when the iframe is loaded server-side.
 */
(function () {
  'use strict';

  var cfg = window.aiagentConfig || {};
  if (!cfg.panelUrl) return; // safety: don't inject if no panel URL

  // If visitor is on a specific product page, pass that context into the panel.
  var url = cfg.panelUrl + (cfg.currentProductId ? '&product_id=' + encodeURIComponent(cfg.currentProductId) : '');

  var isLeft = (cfg.position || '') === 'bottom-left';
  var SIDE   = isLeft ? 'left' : 'right';
  var isOpen = false;
  var loaded = false;

  /* ── FAB button — all styles inline; immune to theme CSS ────────── */
  var fab = document.createElement('button');
  fab.type = 'button';
  fab.setAttribute('aria-label', cfg.titleEn || 'iBird Support');
  fab.setAttribute('aria-expanded', 'false');
  fab.innerHTML =
    '<span style="font-size:22px;line-height:1;flex-shrink:0">💬</span>' +
    '<span style="font-size:15px;font-weight:600;white-space:nowrap">' +
      (cfg.titleEn || 'iBird Support') +
    '</span>';

  function fabStyle(open) {
    fab.setAttribute('style', [
      'position:fixed', 'bottom:24px', SIDE + ':24px',
      'z-index:2147483647',
      'display:' + (open ? 'none' : 'inline-flex'),
      'align-items:center', 'gap:10px',
      'background:#1a6b3c', 'color:#fff', 'border:none', 'border-radius:100px',
      'padding:0 22px', 'height:52px',
      'font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif',
      'font-size:15px', 'font-weight:600', 'cursor:pointer',
      'box-shadow:0 4px 20px rgba(26,107,60,.55)',
      '-webkit-tap-highlight-color:transparent',
    ].join(';'));
  }

  /* ── iframe — the panel lives here ──────────────────────────────── */
  var frame = document.createElement('iframe');
  frame.title = 'Support Chat';
  frame.setAttribute('allowtransparency', 'true');
  frame.setAttribute('aria-hidden', 'true');

  function frameStyle(open) {
    var mob  = window.innerWidth <= 600;
    var base = [
      'position:fixed', 'border:none',
      'z-index:2147483647',
      'display:' + (open ? 'block' : 'none'),
    ];
    if (mob) {
      base = base.concat(['top:0', 'left:0', 'right:0', 'bottom:0', 'width:100%', 'height:100%']);
    } else {
      base = base.concat([
        'bottom:24px', SIDE + ':24px',
        'width:380px',
        'height:' + Math.min(620, Math.round(window.innerHeight * 0.85)) + 'px',
        'max-height:calc(100vh - 48px)',
        'border-radius:20px',
        'box-shadow:0 12px 40px rgba(0,0,0,.22)',
      ]);
    }
    frame.setAttribute('style', base.join(';'));
  }

  /* ── Open / close ────────────────────────────────────────────────── */
  function openPanel() {
    if (!loaded) {
      frame.src = url; // lazy-load: only fetch panel on first open
      loaded = true;
    }
    isOpen = true;
    fab.setAttribute('aria-expanded', 'true');
    frame.setAttribute('aria-hidden', 'false');
    fabStyle(true);
    frameStyle(true);
    setTimeout(function () { try { frame.contentWindow.focus(); } catch (e) {} }, 320);
  }

  function closePanel() {
    isOpen = false;
    fab.setAttribute('aria-expanded', 'false');
    frame.setAttribute('aria-hidden', 'true');
    fabStyle(false);
    frameStyle(false);
    fab.focus();
  }

  fab.addEventListener('click', openPanel);

  /* ── postMessage from iframe (close button, Escape key) ──────────── */
  window.addEventListener('message', function (e) {
    if (e.data && e.data.type === 'aa:close') closePanel();
  });

  /* ── Resize: recalculate iframe height ───────────────────────────── */
  var resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () { if (isOpen) frameStyle(true); }, 120);
  });

  /* ── Mount ────────────────────────────────────────────────────────── */
  function mount() {
    fabStyle(false);
    frameStyle(false);
    document.body.appendChild(fab);
    document.body.appendChild(frame);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mount);
  } else {
    mount();
  }

})();

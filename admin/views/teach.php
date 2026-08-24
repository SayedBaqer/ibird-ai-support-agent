<?php defined( 'ABSPATH' ) || exit;
$nonce    = wp_create_nonce( 'wp_rest' );
$rest_url = esc_url_raw( rest_url( 'aiagent/v1' ) );
?>
<style>
/* ── Layout ──────────────────────────────────────────────────────────────── */
.aa-teach-wrap{display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;}
.aa-teach-sidebar{flex:0 0 340px;width:340px;}
.aa-teach-main{flex:1 1 500px;min-width:0;}
@media(max-width:1080px){.aa-teach-sidebar{flex:0 0 100%;width:100%;}}

/* ── Cards ───────────────────────────────────────────────────────────────── */
.aa-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.07);margin-bottom:16px;overflow:hidden;}
.aa-card-head{padding:14px 18px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;font-weight:700;font-size:14px;color:#1e293b;}
.aa-card-body{padding:18px;}

/* ── Form fields ─────────────────────────────────────────────────────────── */
.aa-field{margin-bottom:14px;}
.aa-field label{display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;}
.aa-input,.aa-select,.aa-ta{display:block;width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:9px 13px;font-size:13px;color:#1e293b;background:#fff;box-sizing:border-box;}
.aa-ta{resize:vertical;min-height:80px;}
.aa-input:focus,.aa-select:focus,.aa-ta:focus{outline:none;border-color:#1a6b3c;box-shadow:0 0 0 3px rgba(26,107,60,.1);}

/* ── Buttons ─────────────────────────────────────────────────────────────── */
.aa-btn{display:inline-flex;align-items:center;gap:5px;padding:8px 16px;border-radius:7px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:background .15s;}
.aa-btn-primary{background:#1a6b3c;color:#fff;}
.aa-btn-primary:hover{background:#145530;}
.aa-btn-primary:disabled{background:#94a3b8;cursor:not-allowed;}
.aa-btn-sm{padding:5px 11px;font-size:12px;border-radius:6px;}
.aa-btn-danger{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.aa-btn-danger:hover{background:#dc2626;color:#fff;}
.aa-btn-ghost{background:#f8fafc;color:#475569;border:1px solid #e2e8f0;}
.aa-btn-ghost:hover{background:#f1f5f9;}
.aa-btn-teal{background:#0f766e;color:#fff;}
.aa-btn-teal:hover{background:#0d6460;}

/* ── Table ───────────────────────────────────────────────────────────────── */
.aa-tbl{width:100%;border-collapse:collapse;font-size:13px;}
.aa-tbl th{text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;color:#64748b;padding:10px 14px;border-bottom:2px solid #e2e8f0;background:#f6f7fb;white-space:nowrap;}
.aa-tbl td{padding:10px 14px;border-bottom:1px solid #f1f5f9;vertical-align:top;color:#1e293b;}
.aa-tbl tr:hover td{background:#f8fafc;}
.aa-tbl tr.aa-editing td{background:#fffbeb!important;}

/* ── Badges ──────────────────────────────────────────────────────────────── */
.aa-badge{display:inline-flex;padding:2px 9px;border-radius:50px;font-size:11px;font-weight:600;white-space:nowrap;}
.src-manual{background:#f0fdf4;color:#166534;}
.src-auto_rated{background:#fef3c7;color:#92400e;}
.src-corrected{background:#eff6ff;color:#1d4ed8;}
.src-ticket{background:#fdf4ff;color:#7e22ce;}
.src-chat{background:#f0f9ff;color:#0369a1;}
.src-bulk_promote{background:#f0fdf4;color:#065f46;}
.lang-en{background:#e3f2fd;color:#1565c0;}
.lang-ar{background:#fce4ec;color:#ad1457;}
.lang-both{background:#f3e5f5;color:#6a1b9a;}

/* ── Confidence bar ──────────────────────────────────────────────────────── */
.conf-bar{display:flex;align-items:center;gap:6px;}
.conf-track{width:52px;height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;}
.conf-fill{height:100%;border-radius:3px;background:#1a6b3c;}

/* ── Inline edit ─────────────────────────────────────────────────────────── */
.aa-edit-row{display:none;}
.aa-edit-row td{padding:12px 14px;background:#fffbeb;border-bottom:2px solid #fbbf24;}
.aa-edit-row textarea{font-size:13px;padding:8px;}

/* ── Toolbar ─────────────────────────────────────────────────────────────── */
.aa-toolbar{display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:12px 16px;border-bottom:1px solid #f1f5f9;}
.aa-toolbar .aa-input{max-width:220px;}
.aa-toolbar-right{margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;}

/* ── Notices ─────────────────────────────────────────────────────────────── */
.aa-notice{padding:11px 15px;border-radius:8px;font-size:13px;margin-bottom:12px;}
.aa-notice-ok{background:#f0fdf4;color:#166534;border-left:4px solid #1a6b3c;}
.aa-notice-err{background:#fef2f2;color:#991b1b;border-left:4px solid #dc2626;}
.aa-notice-warn{background:#fffbeb;color:#92400e;border-left:4px solid #f59e0b;}

/* ── Pagination ──────────────────────────────────────────────────────────── */
.aa-pager{display:flex;align-items:center;gap:8px;padding:12px 16px;font-size:13px;color:#64748b;}
.aa-pager-btn{padding:5px 11px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;cursor:pointer;font-size:13px;}
.aa-pager-btn:disabled{opacity:.4;cursor:not-allowed;}

/* ── Empty state ─────────────────────────────────────────────────────────── */
.aa-empty-row td{text-align:center;padding:40px 20px;color:#94a3b8;}
</style>

<div class="wrap aiagent-page">
  <h1 style="margin-bottom:4px;">Knowledge Base</h1>
  <p style="color:#64748b;margin-bottom:24px;">Teach the AI Q&amp;A pairs. Each entry is embedded and retrieved automatically when customers ask matching questions.</p>

  <div class="aa-teach-wrap">

    <!-- ── Sidebar: teach form ─────────────────────────────────────────── -->
    <div class="aa-teach-sidebar">

      <div class="aa-card">
        <div class="aa-card-head">➕ New Example</div>
        <div class="aa-card-body">
          <div id="aa-teach-notice" style="display:none;"></div>
          <div id="aa-dedup-warn" class="aa-notice aa-notice-warn" style="display:none;"></div>

          <div class="aa-field">
            <label>Question / Problem</label>
            <textarea class="aa-ta" id="aa-q" rows="4" placeholder="What the customer asks…"></textarea>
          </div>
          <div class="aa-field">
            <label>Answer / Solution</label>
            <textarea class="aa-ta" id="aa-a" rows="5" placeholder="The correct, complete answer…"></textarea>
          </div>
          <div class="aa-field">
            <label>Language</label>
            <select class="aa-select" id="aa-lang">
              <option value="both">Both EN + AR</option>
              <option value="en">English only</option>
              <option value="ar">Arabic only</option>
            </select>
          </div>
          <button class="aa-btn aa-btn-primary" id="aa-teach-btn" style="width:100%;justify-content:center;" onclick="aaTeach()">💾 Save &amp; Teach</button>
        </div>
      </div>

      <div class="aa-card">
        <div class="aa-card-head">⚡ Bulk Actions</div>
        <div class="aa-card-body">
          <p style="font-size:13px;color:#475569;margin-bottom:12px;">Auto-promote all conversations rated <strong>👍 Good</strong> to the knowledge base in one click. Duplicates are skipped automatically.</p>
          <button class="aa-btn aa-btn-teal" style="width:100%;justify-content:center;" onclick="aaBulkPromote()">🚀 Promote All Good Chats</button>
          <div id="aa-bulk-result" style="display:none;margin-top:10px;"></div>
        </div>
      </div>

      <div class="aa-card">
        <div class="aa-card-head" style="font-size:13px;">📊 Stats</div>
        <div class="aa-card-body" id="aa-stats" style="font-size:13px;color:#475569;line-height:1.8;">Loading…</div>
      </div>

    </div>

    <!-- ── Main: knowledge base table ─────────────────────────────────── -->
    <div class="aa-teach-main">
      <div class="aa-card">

        <div class="aa-toolbar">
          <input type="text" class="aa-input" id="aa-search" placeholder="🔍 Search examples…" oninput="aaSearchDelay(this.value)">
          <select class="aa-select" id="aa-filter-source" style="max-width:150px;" onchange="aaLoad(1)">
            <option value="">All sources</option>
            <option value="manual">Manual</option>
            <option value="auto_rated">Auto-rated</option>
            <option value="corrected">Corrected</option>
            <option value="ticket">From ticket</option>
            <option value="chat">From chat</option>
            <option value="bulk_promote">Bulk promoted</option>
          </select>
          <select class="aa-select" id="aa-filter-lang" style="max-width:130px;" onchange="aaLoad(1)">
            <option value="">All languages</option>
            <option value="en">English</option>
            <option value="ar">Arabic</option>
            <option value="both">Both</option>
          </select>
          <div class="aa-toolbar-right">
            <span id="aa-count" style="font-size:13px;color:#64748b;align-self:center;"></span>
          </div>
        </div>

        <div id="aa-table-wrap" style="overflow-x:auto;">
          <table class="aa-tbl">
            <thead><tr>
              <th style="width:38%;">Question / Answer</th>
              <th>Source</th>
              <th>Lang</th>
              <th title="How many times this example was retrieved and used by the AI">Used</th>
              <th title="Confidence score — decreases when this example led to escalation">Quality</th>
              <th>Added</th>
              <th style="text-align:right;">Actions</th>
            </tr></thead>
            <tbody id="aa-tbody">
              <tr><td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;">Loading…</td></tr>
            </tbody>
          </table>
        </div>

        <div class="aa-pager" id="aa-pager" style="display:none;">
          <button class="aa-pager-btn" id="aa-prev" onclick="aaPage(-1)">← Prev</button>
          <span id="aa-page-info"></span>
          <button class="aa-pager-btn" id="aa-next" onclick="aaPage(1)">Next →</button>
        </div>

      </div>
    </div>

  </div>
</div>

<script>
var API='<?php echo esc_js($rest_url);?>';
var NONCE='<?php echo esc_js($nonce);?>';
var curPage=1,totalPages=1,searchTimer=null;

// ── Source badge labels ───────────────────────────────────────────────────
var SRC={manual:'Manual',auto_rated:'Auto-rated',corrected:'Corrected',ticket:'From ticket',chat:'From chat',bulk_promote:'Bulk promoted'};

// ── Load table (server-side, paginated) ───────────────────────────────────
function aaLoad(page){
  curPage=page||curPage;
  var search=document.getElementById('aa-search').value;
  var src=document.getElementById('aa-filter-source').value;
  var lang=document.getElementById('aa-filter-lang').value;
  var params=new URLSearchParams({page:curPage,per_page:25});
  if(search) params.set('search',search);
  if(src)    params.set('source',src);
  if(lang)   params.set('lang',lang);

  var tbody=document.getElementById('aa-tbody');
  tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;">Loading…</td></tr>';

  fetch(API+'/admin/examples?'+params.toString(),{headers:{'X-WP-Nonce':NONCE}})
  .then(r=>r.json()).then(data=>{
    totalPages=data.pages||1;
    document.getElementById('aa-count').textContent=data.total+' examples';
    renderRows(data.rows||[]);
    renderPager(data.total);
  }).catch(()=>{
    tbody.innerHTML='<tr><td colspan="7" style="text-align:center;padding:20px;color:#dc2626;">Failed to load. Check your connection.</td></tr>';
  });
}

function aaSearchDelay(v){
  clearTimeout(searchTimer);
  searchTimer=setTimeout(()=>aaLoad(1),400);
}

// ── Render table rows ─────────────────────────────────────────────────────
function renderRows(rows){
  var tbody=document.getElementById('aa-tbody');
  if(!rows.length){
    tbody.innerHTML='<tr class="aa-empty-row"><td colspan="7">🧠 No examples match your filters. Add one using the form.</td></tr>';
    return;
  }
  tbody.innerHTML='';
  rows.forEach(function(ex){
    var conf=parseFloat(ex.confidence||1).toFixed(2);
    var confPct=Math.round(parseFloat(ex.confidence||1)*100);
    var confColor=confPct>=80?'#1a6b3c':confPct>=50?'#f59e0b':'#dc2626';
    var usedAt=ex.last_used?'Last: '+ex.last_used.substring(0,10):'Never used';
    var addedDate=ex.created_at?ex.created_at.substring(0,10):'';
    var srcLabel=SRC[ex.source]||ex.source||'manual';
    var srcClass='src-'+(ex.source||'manual');

    // Main display row
    var tr=document.createElement('tr');
    tr.setAttribute('data-id',ex.id);
    tr.innerHTML=
      '<td style="max-width:300px;">'
        +'<div style="font-weight:600;margin-bottom:3px;word-break:break-word;">'+esc(trimWords(ex.question,15))+'</div>'
        +'<div style="font-size:12px;color:#64748b;word-break:break-word;">'+esc(trimWords(ex.solution,20))+'</div>'
      +'</td>'
      +'<td><span class="aa-badge '+srcClass+'">'+srcLabel+'</span></td>'
      +'<td><span class="aa-badge lang-'+ex.language+'">'+ex.language+'</span></td>'
      +'<td style="text-align:center;"><strong>'+parseInt(ex.usage_count||0)+'</strong><br><span style="font-size:11px;color:#94a3b8;">'+usedAt+'</span></td>'
      +'<td><div class="conf-bar" title="Confidence: '+conf+'">'
          +'<div class="conf-track"><div class="conf-fill" style="width:'+confPct+'%;background:'+confColor+';"></div></div>'
          +'<span style="font-size:11px;color:#64748b;">'+confPct+'%</span>'
        +'</div></td>'
      +'<td style="font-size:12px;color:#94a3b8;white-space:nowrap;">'+addedDate+'</td>'
      +'<td style="text-align:right;white-space:nowrap;">'
        +'<button class="aa-btn aa-btn-sm aa-btn-ghost" onclick="aaStartEdit('+ex.id+',this)">✏️ Edit</button> '
        +'<button class="aa-btn aa-btn-sm aa-btn-danger" onclick="aaDelete('+ex.id+',this)">🗑</button>'
      +'</td>';
    tbody.appendChild(tr);

    // Hidden inline-edit row
    var editTr=document.createElement('tr');
    editTr.className='aa-edit-row';
    editTr.id='edit-'+ex.id;
    editTr.innerHTML=
      '<td colspan="7">'
        +'<div style="display:flex;gap:12px;flex-wrap:wrap;">'
          +'<div style="flex:1 1 220px;">'
            +'<label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">QUESTION</label>'
            +'<textarea class="aa-input aa-ta" id="eq-'+ex.id+'" rows="3">'+esc(ex.question)+'</textarea>'
          +'</div>'
          +'<div style="flex:2 1 300px;">'
            +'<label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">ANSWER</label>'
            +'<textarea class="aa-input aa-ta" id="ea-'+ex.id+'" rows="4">'+esc(ex.solution)+'</textarea>'
          +'</div>'
        +'</div>'
        +'<div style="margin-top:10px;display:flex;gap:8px;">'
          +'<button class="aa-btn aa-btn-primary aa-btn-sm" onclick="aaSaveEdit('+ex.id+')">💾 Save</button>'
          +'<button class="aa-btn aa-btn-ghost aa-btn-sm" onclick="aaCancelEdit('+ex.id+')">Cancel</button>'
        +'</div>'
      +'</td>';
    tbody.appendChild(editTr);
  });
}

// ── Pager ─────────────────────────────────────────────────────────────────
function renderPager(total){
  var pager=document.getElementById('aa-pager');
  if(totalPages<=1){pager.style.display='none';return;}
  pager.style.display='flex';
  document.getElementById('aa-page-info').textContent='Page '+curPage+' of '+totalPages+' ('+total+' total)';
  document.getElementById('aa-prev').disabled=(curPage<=1);
  document.getElementById('aa-next').disabled=(curPage>=totalPages);
}
function aaPage(dir){aaLoad(curPage+dir);}

// ── Teach (new example) ───────────────────────────────────────────────────
function aaTeach(){
  var q=document.getElementById('aa-q').value.trim();
  var a=document.getElementById('aa-a').value.trim();
  var lang=document.getElementById('aa-lang').value;
  var noticeEl=document.getElementById('aa-teach-notice');
  var dedupEl=document.getElementById('aa-dedup-warn');
  if(!q||!a){showNotice(noticeEl,'err','Please fill in both Question and Answer.');return;}
  var btn=document.getElementById('aa-teach-btn');
  btn.disabled=true;btn.textContent='Saving…';
  noticeEl.style.display='none';dedupEl.style.display='none';

  fetch(API+'/admin/teach',{method:'POST',
    headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body:JSON.stringify({question:q,solution:a,language:lang})})
  .then(r=>r.json()).then(d=>{
    if(d.error){showNotice(noticeEl,'err',d.error);}
    else if(d.duplicate){
      dedupEl.style.display='block';
      dedupEl.innerHTML='⚠️ A very similar question already exists in the knowledge base (#'+d.id+'). The new entry was not saved to avoid duplicates.';
    } else {
      showNotice(noticeEl,'ok','✅ Saved as example #'+d.id+(d.proposed_category?' — new category "<strong>'+esc(d.proposed_category)+'</strong>" pending approval':'')+'. The AI will use this immediately.');
      document.getElementById('aa-q').value='';
      document.getElementById('aa-a').value='';
      aaLoad(1);
      aaLoadStats();
    }
  }).catch(()=>showNotice(noticeEl,'err','Network error. Please try again.'))
  .finally(()=>{btn.disabled=false;btn.textContent='💾 Save & Teach';});
}

// ── Inline edit ───────────────────────────────────────────────────────────
function aaStartEdit(id,btn){
  // Close any open edit row first.
  document.querySelectorAll('.aa-edit-row').forEach(r=>r.style.display='none');
  document.getElementById('edit-'+id).style.display='table-row';
  document.querySelector('[data-id="'+id+'"]').classList.add('aa-editing');
}
function aaCancelEdit(id){
  document.getElementById('edit-'+id).style.display='none';
  document.querySelector('[data-id="'+id+'"]').classList.remove('aa-editing');
}
function aaSaveEdit(id){
  var q=document.getElementById('eq-'+id).value.trim();
  var a=document.getElementById('ea-'+id).value.trim();
  if(!q||!a){alert('Both fields are required.');return;}
  fetch(API+'/admin/example/'+id,{method:'POST',
    headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body:JSON.stringify({question:q,solution:a})})
  .then(r=>r.json()).then(d=>{
    if(d.updated){aaCancelEdit(id);aaLoad(curPage);}
    else alert('Update failed: '+(d.error||'Unknown error'));
  }).catch(()=>alert('Network error.'));
}

// ── Delete ────────────────────────────────────────────────────────────────
function aaDelete(id,btn){
  if(!confirm('Delete this example? The AI will no longer use it.\n\nThis cannot be undone.')) return;
  btn.disabled=true;btn.textContent='…';
  fetch(API+'/admin/example/'+id,{method:'DELETE',headers:{'X-WP-Nonce':NONCE}})
  .then(r=>r.json()).then(d=>{
    if(d.deleted){aaLoad(curPage);aaLoadStats();}
    else{btn.disabled=false;btn.textContent='🗑';alert('Delete failed.');}
  }).catch(()=>{btn.disabled=false;btn.textContent='🗑';});
}

// ── Bulk promote ──────────────────────────────────────────────────────────
function aaBulkPromote(){
  if(!confirm('Promote all conversations rated 👍 Good to the knowledge base?\n\nNear-duplicate questions will be skipped automatically.')) return;
  var res=document.getElementById('aa-bulk-result');
  res.style.display='none';
  fetch(API+'/admin/examples',{method:'POST',
    headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body:JSON.stringify({all_good_rated:true})})
  .then(r=>r.json()).then(d=>{
    res.style.display='block';
    res.innerHTML='<div class="aa-notice aa-notice-ok">✅ Promoted <strong>'+d.promoted+'</strong> new examples. Skipped '+d.duplicates+' duplicates.</div>';
    aaLoad(1);aaLoadStats();
  }).catch(()=>{
    res.style.display='block';
    res.innerHTML='<div class="aa-notice aa-notice-err">Failed. Try again.</div>';
  });
}

// ── Stats ─────────────────────────────────────────────────────────────────
function aaLoadStats(){
  var el=document.getElementById('aa-stats');
  fetch(API+'/admin/examples?per_page=1',{headers:{'X-WP-Nonce':NONCE}})
  .then(r=>r.json()).then(d=>{
    el.innerHTML='<strong>'+d.total+'</strong> total examples';
    // Quick source breakdown via separate queries
    var sources=['manual','auto_rated','corrected','ticket','bulk_promote'];
    var labels={manual:'Manual',auto_rated:'Auto-rated',corrected:'Corrected',ticket:'Tickets',bulk_promote:'Bulk'};
    var fetches=sources.map(s=>fetch(API+'/admin/examples?per_page=1&source='+s,{headers:{'X-WP-Nonce':NONCE}}).then(r=>r.json()).then(d2=>({s,n:d2.total})));
    Promise.all(fetches).then(results=>{
      var rows=results.filter(r=>r.n>0).map(r=>'<span class="aa-badge src-'+r.s+'" style="margin-right:4px;">'+labels[r.s]+' '+r.n+'</span>');
      el.innerHTML='<div style="margin-bottom:8px;"><strong>'+d.total+'</strong> total examples</div>'+rows.join('');
    });
  }).catch(()=>{el.innerHTML='Unable to load stats.';});
}

// ── Utils ─────────────────────────────────────────────────────────────────
function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function trimWords(s,n){if(!s)return'';var w=s.split(/\s+/);return w.length>n?w.slice(0,n).join(' ')+'…':s;}
function showNotice(el,type,msg){
  el.style.display='block';
  el.className='aa-notice '+(type==='ok'?'aa-notice-ok':type==='warn'?'aa-notice-warn':'aa-notice-err');
  el.innerHTML=msg;
}

// ── Init ──────────────────────────────────────────────────────────────────
aaLoad(1);
aaLoadStats();
</script>

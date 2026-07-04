<?php defined( 'ABSPATH' ) || exit;
$nonce    = wp_create_nonce( 'wp_rest' );
$rest_url = esc_url_raw( rest_url( 'aiagent/v1' ) );
?>
<style>
.aiagent-page .aa-2col{display:flex!important;gap:0!important;align-items:stretch!important;height:calc(100vh - 120px)!important;min-height:500px!important;}
.aiagent-page .aa-conv-list{width:320px!important;flex-shrink:0!important;border-right:1px solid #e2e8f0!important;overflow-y:auto!important;background:#fff!important;border-radius:12px 0 0 12px!important;box-shadow:0 1px 4px rgba(0,0,0,.07)!important;}
.aiagent-page .aa-conv-detail{flex:1!important;overflow-y:auto!important;background:#f6f7fb!important;border-radius:0 12px 12px 0!important;box-shadow:0 1px 4px rgba(0,0,0,.07)!important;display:flex!important;flex-direction:column!important;}
.aa-conv-item{padding:14px 16px!important;border-bottom:1px solid #f1f5f9!important;cursor:pointer!important;transition:background .12s!important;}
.aa-conv-item:hover{background:#f0fdf4!important;}
.aa-conv-item.active{background:#e8f5e9!important;border-left:3px solid #1a6b3c!important;}
.aa-conv-id{font-size:13px!important;font-weight:700!important;color:#1e293b!important;}
.aa-conv-meta{font-size:11px!important;color:#94a3b8!important;margin-top:3px!important;display:flex!important;gap:8px!important;align-items:center!important;}
.aa-badge{display:inline-flex!important;padding:2px 8px!important;border-radius:50px!important;font-size:10px!important;font-weight:700!important;text-transform:uppercase!important;}
.aa-badge.product{background:#e3f2fd!important;color:#1565c0!important;}
.aa-badge.support{background:#fce4ec!important;color:#c62828!important;}
.aa-badge.active{background:#e8f5e9!important;color:#2e7d32!important;}
.aa-badge.escalated{background:#fff3e0!important;color:#e65100!important;}
.aa-badge.closed{background:#f5f5f5!important;color:#757575!important;}
.aa-detail-hdr{padding:16px 20px!important;background:#fff!important;border-bottom:1px solid #e2e8f0!important;flex-shrink:0!important;display:flex!important;align-items:center!important;justify-content:space-between!important;}
.aa-detail-body{flex:1!important;overflow-y:auto!important;padding:16px 20px!important;}
.aa-msg-wrap{margin-bottom:14px!important;}
.aa-msg-wrap.cust{display:flex!important;flex-direction:column!important;align-items:flex-end!important;}
.aa-msg-wrap.ai{display:flex!important;flex-direction:column!important;align-items:flex-start!important;}
.aa-bubble{padding:10px 14px!important;font-size:13px!important;line-height:1.6!important;max-width:75%!important;word-break:break-word!important;border-radius:14px!important;}
.aa-msg-wrap.ai .aa-bubble{background:#fff!important;color:#1e293b!important;border-radius:4px 14px 14px 14px!important;box-shadow:0 1px 4px rgba(0,0,0,.08)!important;}
.aa-msg-wrap.cust .aa-bubble{background:#1a6b3c!important;color:#fff!important;border-radius:14px 14px 4px 14px!important;}
.aa-msg-meta{font-size:10px!important;color:#94a3b8!important;margin-top:4px!important;display:flex!important;gap:8px!important;align-items:center!important;}
.aa-rate-row{display:flex!important;gap:6px!important;margin-top:6px!important;align-items:center!important;flex-wrap:wrap!important;}
.aa-rate-btn{padding:3px 10px!important;border-radius:50px!important;border:1.5px solid #e2e8f0!important;background:#fff!important;font-size:11px!important;font-weight:600!important;cursor:pointer!important;}
.aa-rate-btn.good:hover{border-color:#4caf50!important;color:#2e7d32!important;}
.aa-rate-btn.wrong:hover{border-color:#ef5350!important;color:#c62828!important;}
.aa-rate-btn.incomplete:hover{border-color:#ffa726!important;color:#e65100!important;}
.aa-rate-btn.train:hover{border-color:#1a6b3c!important;color:#1a6b3c!important;}
.aa-train-panel{background:#f0fdf4!important;border:1.5px solid #1a6b3c!important;border-radius:10px!important;padding:12px!important;margin-top:8px!important;}
.aa-train-panel textarea{width:100%!important;border:1.5px solid #e2e8f0!important;border-radius:8px!important;padding:8px 10px!important;font-size:12px!important;font-family:inherit!important;resize:vertical!important;min-height:60px!important;margin-bottom:8px!important;color:#1e293b!important;}
.aa-train-panel textarea:focus{border-color:#1a6b3c!important;outline:none!important;}
.aa-train-save{background:#1a6b3c!important;color:#fff!important;border:none!important;border-radius:7px!important;padding:7px 16px!important;font-size:12px!important;font-weight:600!important;cursor:pointer!important;}
.aa-train-cancel{background:none!important;border:1.5px solid #e2e8f0!important;border-radius:7px!important;padding:7px 14px!important;font-size:12px!important;cursor:pointer!important;color:#64748b!important;}
.aa-empty-detail{flex:1!important;display:flex!important;flex-direction:column!important;align-items:center!important;justify-content:center!important;color:#94a3b8!important;gap:12px!important;}
.aa-search-bar{padding:12px 16px!important;border-bottom:1px solid #e2e8f0!important;background:#f6f7fb!important;}
.aa-search-bar input{width:100%!important;border:1.5px solid #e2e8f0!important;border-radius:8px!important;padding:8px 12px!important;font-size:13px!important;background:#fff!important;outline:none!important;}
.aa-search-bar input:focus{border-color:#1a6b3c!important;}
@media(max-width:900px){.aiagent-page .aa-conv-list{width:220px!important;}.aiagent-page .aa-2col{height:auto!important;flex-direction:column!important;}.aiagent-page .aa-conv-detail{min-height:400px!important;}}
</style>

<div class="wrap aiagent-page">
  <h1>Conversations <span id="aa-conv-total" style="font-size:13px;font-weight:400;color:#94a3b8;"></span></h1>

  <div class="aa-2col">

    <!-- List -->
    <div class="aa-conv-list">
      <div class="aa-search-bar">
        <input type="search" id="aa-search" placeholder="Search conversations…">
      </div>
      <div id="aa-list-body">
        <div style="padding:24px;text-align:center;color:#94a3b8;">Loading…</div>
      </div>
      <div id="aa-list-pager" style="padding:10px 16px;display:flex;gap:10px;justify-content:center;border-top:1px solid #f1f5f9;font-size:12px;"></div>
    </div>

    <!-- Detail -->
    <div class="aa-conv-detail" id="aa-detail">
      <div class="aa-empty-detail">
        <div style="font-size:36px;">💬</div>
        <div style="font-weight:600;font-size:15px;color:#64748b;">Select a conversation</div>
        <div style="font-size:13px;">Click any row on the left to read the transcript.</div>
      </div>
    </div>

  </div>
</div>

<script>
var REST='<?php echo esc_js($rest_url);?>';
var NONCE='<?php echo esc_js($nonce);?>';
var aaPending={};
var aaPage=1; var aaTotal=0; var aaSearch=''; var aaActiveId=null;

/* ── Load conversation list ─────────────────────────────────────── */
function aaLoadList(){
  var url=REST+'/admin/conversations?per_page=25&page='+aaPage;
  if(aaSearch) url+='&search='+encodeURIComponent(aaSearch);
  document.getElementById('aa-list-body').innerHTML='<div style="padding:20px;text-align:center;color:#94a3b8;">Loading…</div>';
  fetch(url,{headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();})
  .then(function(d){
    aaTotal=d.total||0;
    document.getElementById('aa-conv-total').textContent='('+aaTotal+' total)';
    var rows=d.rows||d.conversations||[];
    if(!rows.length){
      document.getElementById('aa-list-body').innerHTML='<div style="padding:24px;text-align:center;color:#94a3b8;">No conversations yet.</div>';
      return;
    }
    document.getElementById('aa-list-body').innerHTML=rows.map(function(c){
      var ago=aaAgo(c.updated_at||c.created_at);
      return '<div class="aa-conv-item'+(c.id==aaActiveId?' active':'')+'" onclick="aaLoadDetail('+c.id+')" data-id="'+c.id+'">'
        +'<div class="aa-conv-id">#'+c.id+' <span class="aa-badge '+esc(c.mode||'product')+'">'+esc((c.mode||'product'))+'</span>'
        +' <span class="aa-badge '+esc(c.status||'active')+'">'+esc(c.status||'active')+'</span></div>'
        +'<div class="aa-conv-meta">'+ago+'</div>'
        +'</div>';
    }).join('');
    var pages=Math.ceil(aaTotal/25)||1;
    var pg='<span style="color:#64748b;">Page '+aaPage+' of '+pages+'</span>';
    pg+=' <button onclick="if(aaPage>1){aaPage--;aaLoadList();}" '+(aaPage<=1?'disabled':'')+' style="padding:3px 10px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;font-size:11px;">←</button>';
    pg+=' <button onclick="if(aaPage<'+pages+'){aaPage++;aaLoadList();}" '+(aaPage>=pages?'disabled':'')+' style="padding:3px 10px;border-radius:6px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;font-size:11px;">→</button>';
    document.getElementById('aa-list-pager').innerHTML=pg;
  })
  .catch(function(){document.getElementById('aa-list-body').innerHTML='<div style="padding:20px;text-align:center;color:#c62828;">Failed to load.</div>';});
}

/* ── Load conversation detail ────────────────────────────────────── */
function aaLoadDetail(id){
  aaActiveId=id;
  // Highlight active row.
  document.querySelectorAll('.aa-conv-item').forEach(function(el){
    el.classList.toggle('active', el.dataset.id==id);
  });
  var det=document.getElementById('aa-detail');
  det.innerHTML='<div class="aa-empty-detail"><div style="font-size:28px;">⏳</div><div>Loading conversation…</div></div>';
  fetch(REST+'/admin/conversation/'+id,{headers:{'X-WP-Nonce':NONCE}})
  .then(function(r){return r.json();})
  .then(function(d){
    var msgs=d.messages||[];
    var conv=d.conversation||{};
    det.innerHTML='';
    // Header.
    var hdr=document.createElement('div'); hdr.className='aa-detail-hdr';
    hdr.innerHTML='<div><strong style="font-size:15px;">Conversation #'+id+'</strong>'
      +' <span class="aa-badge '+(conv.mode||'product')+'">'+(conv.mode||'product')+'</span>'
      +' <span class="aa-badge '+(conv.status||'active')+'">'+(conv.status||'active')+'</span></div>'
      +'<div style="font-size:12px;color:#94a3b8;">'+(conv.language||'en').toUpperCase()+' · '+(conv.created_at||'').slice(0,16)+'</div>';
    det.appendChild(hdr);
    // Body.
    var body=document.createElement('div'); body.className='aa-detail-body';
    var prevCustMsg='';
    msgs.forEach(function(m,idx){
      if(m.role==='customer') prevCustMsg=m.body;
      var wrap=document.createElement('div');
      wrap.className='aa-msg-wrap '+(m.role==='customer'?'cust':'ai');
      wrap.dataset.msgId=m.id;
      var bubble=document.createElement('div'); bubble.className='aa-bubble';
      bubble.textContent=m.body||'';
      wrap.appendChild(bubble);
      if(m.attachment_url){var img=document.createElement('img');img.src=m.attachment_url;img.style.cssText='max-width:160px;border-radius:8px;display:block;margin-top:4px;';wrap.appendChild(img);}
      var meta=document.createElement('div'); meta.className='aa-msg-meta';
      meta.innerHTML='<span style="font-weight:700;text-transform:capitalize;">'+esc(m.role)+'</span><span>'+esc((m.created_at||'').slice(11,16))+'</span>';
      wrap.appendChild(meta);
      if(m.role==='ai'){
        var rateRow=document.createElement('div'); rateRow.className='aa-rate-row';
        rateRow.innerHTML='<span style="font-size:10px;color:#94a3b8;">Rate:</span>'
          +'<button class="aa-rate-btn good" onclick="aaRate('+m.id+',\'good\',this)">👍 Good</button>'
          +'<button class="aa-rate-btn wrong" onclick="aaRate('+m.id+',\'wrong\',this)">❌ Wrong</button>'
          +'<button class="aa-rate-btn incomplete" onclick="aaRate('+m.id+',\'incomplete\',this)">⚠️ Incomplete</button>'
          +'<button class="aa-rate-btn train" onclick="aaToggleTrain('+m.id+',this)" data-q="'+esc(prevCustMsg)+'" data-a="'+esc(m.body)+'">🎓 Train</button>';
        wrap.appendChild(rateRow);
        // Rate result area.
        var rRes=document.createElement('div'); rRes.id='aa-result-'+m.id; wrap.appendChild(rRes);
        // Train panel (hidden).
        var tp=document.createElement('div'); tp.id='aa-train-'+m.id; tp.className='aa-train-panel'; tp.style.display='none';
        tp.innerHTML='<div style="font-size:12px;font-weight:700;color:#1a6b3c;margin-bottom:8px;">🎓 Save as training example</div>'
          +'<label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">QUESTION</label>'
          +'<textarea id="aa-tq-'+m.id+'">'+esc(prevCustMsg)+'</textarea>'
          +'<label style="font-size:11px;font-weight:700;color:#64748b;display:block;margin-bottom:4px;">ANSWER</label>'
          +'<textarea id="aa-ta-'+m.id+'">'+esc(m.body)+'</textarea>'
          +'<div style="display:flex;gap:8px;">'
          +'<button class="aa-train-save" onclick="aaDoTrain('+m.id+')">💾 Save to Knowledge Base</button>'
          +'<button class="aa-train-cancel" onclick="aaToggleTrain('+m.id+')">Cancel</button>'
          +'</div>'
          +'<div id="aa-train-res-'+m.id+'" style="margin-top:6px;font-size:12px;"></div>';
        wrap.appendChild(tp);
      }
      body.appendChild(wrap);
    });
    det.appendChild(body);
    body.scrollTop=body.scrollHeight;
  })
  .catch(function(){det.innerHTML='<div class="aa-empty-detail"><div style="color:#c62828;">Failed to load conversation.</div></div>';});
}

/* ── Train panel toggle ──────────────────────────────────────────── */
function aaToggleTrain(id, btn){
  var tp=document.getElementById('aa-train-'+id);
  if(!tp) return;
  var show=tp.style.display==='none';
  tp.style.display=show?'block':'none';
}

/* ── Save training example ───────────────────────────────────────── */
function aaDoTrain(id){
  var q=(document.getElementById('aa-tq-'+id)||{}).value||'';
  var a=(document.getElementById('aa-ta-'+id)||{}).value||'';
  var res=document.getElementById('aa-train-res-'+id);
  if(!q||!a){if(res)res.innerHTML='<span style="color:#c62828;">Both question and answer are required.</span>';return;}
  fetch(REST+'/admin/train',{
    method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body:JSON.stringify({message_id:id,question:q,answer:a})
  }).then(function(r){return r.json();}).then(function(d){
    if(res){
      if(d.error) res.innerHTML='<span style="color:#c62828;">'+esc(d.error)+'</span>';
      else res.innerHTML='<span style="color:#1a6b3c;">✅ Saved! Example #'+d.example_id+'</span>';
    }
  }).catch(function(){if(res)res.innerHTML='<span style="color:#c62828;">Save failed. Try again.</span>';});
}

/* ── Rate message ────────────────────────────────────────────────── */
function aaRate(id,score,btn){
  aaPending[id]=score;
  var cf=document.getElementById('aa-cf-'+id);
  if(score==='good'){ aaSubmitRate(id); return; }
  // For wrong/incomplete, show a quick correction prompt.
  var res=document.getElementById('aa-result-'+id);
  if(res){
    res.innerHTML='<div style="margin-top:6px;">'
      +'<textarea id="aa-corr-'+id+'" placeholder="Correct answer (optional)…" style="width:100%;border:1.5px solid #e2e8f0;border-radius:8px;padding:6px 10px;font-size:12px;resize:vertical;min-height:50px;margin-bottom:6px;"></textarea>'
      +'<div style="display:flex;gap:6px;">'
      +'<button onclick="aaSubmitRate('+id+')" style="background:#1a6b3c;color:#fff;border:none;border-radius:7px;padding:6px 14px;font-size:12px;font-weight:600;cursor:pointer;">Save</button>'
      +'<button onclick="document.getElementById(\'aa-result-'+id+'\').innerHTML=\'\'" style="border:1.5px solid #e2e8f0;border-radius:7px;padding:6px 12px;font-size:12px;background:#fff;cursor:pointer;">Cancel</button>'
      +'</div></div>';
  }
}

function aaSubmitRate(id){
  var score=aaPending[id]; if(!score)return;
  var corr=(document.getElementById('aa-corr-'+id)||{}).value||'';
  fetch(REST+'/admin/rate',{
    method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},
    body:JSON.stringify({message_id:id,score:score,correction:corr,promote:corr!==''})
  }).then(function(r){return r.json();}).then(function(d){
    var ic={good:'👍 Good',wrong:'❌ Wrong',incomplete:'⚠️ Incomplete'};
    var co={good:'#2e7d32',wrong:'#c62828',incomplete:'#e65100'};
    var res=document.getElementById('aa-result-'+id);
    if(res) res.innerHTML='<div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:'+(co[score]||'#333')+';">'+(ic[score]||score)+(d.promoted?' <span style="color:#1a6b3c;">· ✅ Added to KB</span>':'')+'</div>'+(corr?'<div style="font-size:11px;margin-top:4px;background:#fff9e6;border-left:3px solid #ffc107;padding:4px 8px;border-radius:0 5px 5px 0;">'+esc(corr)+'</div>':'');
    delete aaPending[id];
  }).catch(function(){});
}

/* ── Helpers ─────────────────────────────────────────────────────── */
function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s||''));return d.innerHTML;}
function aaAgo(dt){
  if(!dt)return'';
  var diff=Math.floor((Date.now()-Date.parse(dt))/1000);
  if(diff<60)return diff+'s ago';
  if(diff<3600)return Math.floor(diff/60)+'m ago';
  if(diff<86400)return Math.floor(diff/3600)+'h ago';
  return Math.floor(diff/86400)+'d ago';
}

/* ── Search ──────────────────────────────────────────────────────── */
var aaSearchTimer;
document.getElementById('aa-search').addEventListener('input',function(){
  clearTimeout(aaSearchTimer);
  var q=this.value;
  aaSearchTimer=setTimeout(function(){aaSearch=q;aaPage=1;aaLoadList();},350);
});

/* ── Init ────────────────────────────────────────────────────────── */
aaLoadList();
setInterval(aaLoadList, 30000); // auto-refresh every 30s
</script>

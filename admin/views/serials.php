<?php defined( 'ABSPATH' ) || exit;
global $wpdb;
$nonce    = wp_create_nonce( 'wp_rest' );
$rest_url = esc_url_raw( rest_url( 'aiagent/v1' ) );
$istock   = (bool) $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}isb_document_items'" );
?>
<style>
.aiagent-page .aa-2col{display:flex!important;flex-direction:row!important;gap:20px!important;align-items:flex-start!important;flex-wrap:wrap!important;}
.aiagent-page .aa-main{flex:1 1 500px!important;min-width:0!important;}
.aiagent-page .aa-side{flex:0 0 320px!important;width:320px!important;}
.aiagent-page .aa-card{background:#fff!important;border:1px solid #e2e8f0!important;border-radius:12px!important;box-shadow:0 1px 4px rgba(0,0,0,.07)!important;padding:20px!important;margin-bottom:16px!important;}
.aiagent-page .aa-card-title{font-size:14px!important;font-weight:700!important;color:#1e293b!important;margin-bottom:14px!important;padding-bottom:10px!important;border-bottom:1px solid #e2e8f0!important;display:block!important;}
.aiagent-page .aa-table{width:100%!important;border-collapse:collapse!important;font-size:13px!important;}
.aiagent-page .aa-table th{text-align:left!important;font-size:11px!important;font-weight:700!important;text-transform:uppercase!important;letter-spacing:.5px!important;color:#64748b!important;padding:10px 12px!important;border-bottom:2px solid #e2e8f0!important;background:#f6f7fb!important;}
.aiagent-page .aa-table td{padding:10px 12px!important;border-bottom:1px solid #f1f5f9!important;vertical-align:middle!important;color:#1e293b!important;}
.aiagent-page .aa-table tr:hover td{background:#f8fafc!important;}
.aiagent-page .aa-badge{display:inline-flex!important;padding:3px 10px!important;border-radius:50px!important;font-size:11px!important;font-weight:600!important;}
.aiagent-page .aa-badge.green{background:#e8f5e9!important;color:#2e7d32!important;}
.aiagent-page .aa-badge.red{background:#fce4ec!important;color:#c62828!important;}
.aiagent-page .aa-badge.grey{background:#f1f5f9!important;color:#64748b!important;}
.aiagent-page .aa-badge.blue{background:#e3f2fd!important;color:#1565c0!important;}
.aiagent-page .aa-input{display:block!important;width:100%!important;border:1.5px solid #e2e8f0!important;border-radius:8px!important;padding:9px 12px!important;font-size:13px!important;color:#1e293b!important;background:#fff!important;outline:none!important;}
.aiagent-page .aa-input:focus{border-color:#1a6b3c!important;}
.aiagent-page .aa-field{margin-bottom:14px!important;}
.aiagent-page .aa-label{display:block!important;font-size:11px!important;font-weight:700!important;color:#64748b!important;text-transform:uppercase!important;letter-spacing:.5px!important;margin-bottom:5px!important;}
.aiagent-page .aa-btn{display:inline-flex!important;align-items:center!important;gap:6px!important;padding:9px 18px!important;border-radius:8px!important;font-size:13px!important;font-weight:600!important;border:none!important;cursor:pointer!important;}
.aiagent-page .aa-btn-primary{background:#1a6b3c!important;color:#fff!important;}
.aiagent-page .aa-btn-primary:hover{background:#145530!important;}
.aiagent-page .aa-notice{padding:12px 16px!important;border-radius:8px!important;font-size:13px!important;margin-bottom:14px!important;}
.aiagent-page .aa-notice.info{background:#e3f2fd!important;color:#1565c0!important;border-left:4px solid #1565c0!important;}
.aiagent-page .aa-notice.success{background:#e8f5e9!important;color:#2e7d32!important;border-left:4px solid #4caf50!important;}
.aiagent-page .aa-notice.warn{background:#fff3e0!important;color:#e65100!important;border-left:4px solid #ff9800!important;}
.aa-search-row{display:flex!important;gap:10px!important;margin-bottom:16px!important;align-items:center!important;}
.aa-search-row input{flex:1!important;}
.aa-pagination{display:flex!important;align-items:center!important;gap:12px!important;margin-top:14px!important;font-size:13px!important;}
.aa-pagination button{padding:6px 14px!important;border-radius:6px!important;border:1px solid #e2e8f0!important;background:#fff!important;cursor:pointer!important;font-size:13px!important;}
.aa-pagination button:disabled{opacity:.4!important;cursor:not-allowed!important;}
@media(max-width:1100px){.aiagent-page .aa-side{flex:0 0 100%!important;width:100%!important;}}
</style>

<div class="wrap aiagent-page">
  <h1>Serial Registry
    <span id="aa-src-badge" style="font-size:13px;font-weight:400;vertical-align:middle;margin-left:10px;"></span>
  </h1>

  <?php if ( $istock ) : ?>
  <div class="aa-notice info">
    <strong>✅ iStock-Suite connected.</strong>
    Serials are pulled live from iStock billing invoices &amp; sales orders. To register a new serial, create an invoice in iStock-Suite billing. CSV import is still available as a fallback below.
  </div>
  <?php else : ?>
  <div class="aa-notice warn">
    <strong>⚠️ iStock-Suite not detected.</strong>
    Showing serials from the local CSV import table. Install iStock-Suite for automatic serial management.
  </div>
  <?php endif; ?>

  <div class="aa-2col">

    <!-- ── Main: serials table ──────────────────────────────────────────── -->
    <div class="aa-main">
      <div class="aa-card" style="padding:0;overflow:hidden;">
        <div style="padding:16px 18px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
          <span style="font-weight:700;font-size:15px;">🔑 Registered Serials <span id="aa-total" style="font-size:13px;font-weight:400;color:#64748b;"></span></span>
          <div class="aa-search-row" style="margin:0!important;flex-wrap:nowrap;">
            <input type="search" class="aa-input" id="aa-search" placeholder="Search serial, product, invoice…" style="width:260px!important;">
            <button class="aa-btn aa-btn-primary" id="aa-search-btn">🔍 Search</button>
          </div>
        </div>
        <table class="aa-table">
          <thead>
            <tr>
              <th>Serial Number</th>
              <th>Product / Model</th>
              <th>Invoice</th>
              <th>Sold Date</th>
              <th>Warranty</th>
              <th>Customer</th>
            </tr>
          </thead>
          <tbody id="aa-tbody">
            <tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;">Loading…</td></tr>
          </tbody>
        </table>
        <div class="aa-pagination" style="padding:12px 18px;" id="aa-pager">
          <button id="aa-prev" onclick="aaSerials.prev()" disabled>← Prev</button>
          <span id="aa-page-info">Page 1</span>
          <button id="aa-next" onclick="aaSerials.next()">Next →</button>
        </div>
      </div>
    </div>

    <!-- ── Side: CSV import (fallback) ─────────────────────────────────── -->
    <div class="aa-side">
      <div class="aa-card">
        <span class="aa-card-title">📥 CSV Import <span style="font-size:11px;font-weight:400;color:#94a3b8;">(fallback only)</span></span>

        <?php if ( $istock ) : ?>
        <div class="aa-notice info" style="font-size:12px;margin-bottom:14px!important;">
          iStock is active — use this only for serials not in iStock (e.g. older devices).
        </div>
        <?php endif; ?>

        <p style="font-size:12px;color:#64748b;margin-bottom:12px;">
          Required CSV columns:<br>
          <code style="font-size:11px;">serial, model, order_id, owner_name, owner_phone, purchased_at, warranty_until</code>
        </p>
        <div class="aa-field">
          <label class="aa-label">CSV File</label>
          <input type="file" id="aa-csv-file" accept=".csv" class="aa-input">
        </div>
        <button class="aa-btn aa-btn-primary" id="aa-import-btn" onclick="aaImport()">⬆ Import CSV</button>
        <div id="aa-import-result" style="margin-top:12px;font-size:13px;display:none;"></div>
      </div>

      <div class="aa-card">
        <span class="aa-card-title">ℹ️ How verification works</span>
        <ol style="font-size:12px;color:#475569;line-height:1.8;padding-left:18px;">
          <li>Customer provides: <strong>model, serial, name, phone</strong></li>
          <li>System searches iStock billing for the serial</li>
          <li>Phone is matched to the customer on file</li>
          <li>If matched: AI gets <em>"Verified owner of [Model], purchased [date], in/out of warranty"</em></li>
          <li><strong>No PII ever reaches the AI</strong></li>
        </ol>
      </div>
    </div>

  </div>
</div>

<script>
var REST   = '<?php echo esc_js($rest_url); ?>';
var NONCE  = '<?php echo esc_js($nonce); ?>';
var now    = Date.now();

var aaSerials = (function(){
  var page = 1, total = 0, perPage = 50, search = '';

  function load(){
    var url = REST+'/admin/serials?per_page='+perPage+'&page='+page;
    if(search) url += '&search='+encodeURIComponent(search);
    document.getElementById('aa-tbody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;">Loading…</td></tr>';
    fetch(url,{headers:{'X-WP-Nonce':NONCE,'Content-Type':'application/json'}})
    .then(function(r){return r.json();})
    .then(function(d){
      total = d.total || 0;
      var src = d.source || 'registry';
      document.getElementById('aa-total').textContent = '('+total+' total)';
      document.getElementById('aa-src-badge').innerHTML = src==='istock'
        ? '<span style="background:#e8f5e9;color:#2e7d32;padding:3px 10px;border-radius:50px;font-size:12px;font-weight:600;">iStock-Suite Live</span>'
        : '<span style="background:#fff3e0;color:#e65100;padding:3px 10px;border-radius:50px;font-size:12px;font-weight:600;">Local Registry</span>';
      renderRows(d.rows||[]);
      updatePager();
    })
    .catch(function(){
      document.getElementById('aa-tbody').innerHTML = '<tr><td colspan="6" style="text-align:center;color:#c62828;">Failed to load serials.</td></tr>';
    });
  }

  function renderRows(rows){
    if(!rows.length){
      document.getElementById('aa-tbody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;">No serials found.</td></tr>';
      return;
    }
    document.getElementById('aa-tbody').innerHTML = rows.map(function(r){
      var wBadge = warrantyBadge(r.warranty_raw, r.purchased_at);
      return '<tr>'+
        '<td><code style="font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:4px;">'+esc(r.serial)+'</code></td>'+
        '<td style="max-width:200px;"><div style="font-size:13px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'+esc(r.model||'—')+'</div></td>'+
        '<td style="font-size:12px;color:#64748b;">'+esc(r.invoice||'—')+'</td>'+
        '<td style="font-size:12px;color:#64748b;">'+esc(r.purchased_at||'—')+'</td>'+
        '<td>'+wBadge+'</td>'+
        '<td style="font-size:12px;color:#64748b;">'+esc(r.customer_hint||'—')+'</td>'+
        '</tr>';
    }).join('');
  }

  function warrantyBadge(raw, purchasedAt){
    if(!raw) return '<span class="aa-badge grey">—</span>';
    // Try parse as date first
    var ts = Date.parse(raw);
    if(!isNaN(ts)){
      return ts > now
        ? '<span class="aa-badge green">✓ In warranty</span><br><small style="color:#94a3b8;font-size:11px;">until '+raw+'</small>'
        : '<span class="aa-badge red">Expired</span>';
    }
    // Duration: "12 months", "1 year", etc.
    var m = raw.match(/(\d+)\s*(month|year)/i);
    if(m && purchasedAt){
      var base = Date.parse(purchasedAt);
      var months = parseInt(m[1]) * (/year/i.test(m[2]) ? 12 : 1);
      var expiry = new Date(base);
      expiry.setMonth(expiry.getMonth() + months);
      return expiry.getTime() > now
        ? '<span class="aa-badge green">✓ In warranty</span><br><small style="color:#94a3b8;font-size:11px;">'+raw+' from sale</small>'
        : '<span class="aa-badge red">Expired</span>';
    }
    return '<span class="aa-badge grey">'+esc(raw)+'</span>';
  }

  function updatePager(){
    var pages = Math.ceil(total/perPage)||1;
    document.getElementById('aa-page-info').textContent = 'Page '+page+' of '+pages;
    document.getElementById('aa-prev').disabled = page <= 1;
    document.getElementById('aa-next').disabled = page >= pages;
  }

  document.getElementById('aa-search').addEventListener('keydown',function(e){if(e.key==='Enter'){search=this.value.trim();page=1;load();}});
  document.getElementById('aa-search-btn').addEventListener('click',function(){search=document.getElementById('aa-search').value.trim();page=1;load();});

  function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s||''));return d.innerHTML;}

  load();
  return {
    prev: function(){if(page>1){page--;load();}},
    next: function(){var pages=Math.ceil(total/perPage)||1;if(page<pages){page++;load();}}
  };
})();

function aaImport(){
  var file = document.getElementById('aa-csv-file').files[0];
  var res  = document.getElementById('aa-import-result');
  var btn  = document.getElementById('aa-import-btn');
  res.style.display='none';
  if(!file){res.style.display='';res.className='aa-notice warn';res.textContent='Please select a CSV file.';return;}
  btn.disabled=true; btn.textContent='Importing…';
  var reader=new FileReader();
  reader.onload=function(e){
    var rows=parseCSV(e.target.result);
    if(!rows.length){res.style.display='';res.className='aa-notice warn';res.textContent='CSV appears empty.';btn.disabled=false;btn.textContent='⬆ Import CSV';return;}
    fetch(REST+'/admin/serials/import',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':NONCE},body:JSON.stringify({rows:rows})})
    .then(function(r){return r.json();})
    .then(function(d){
      res.style.display='';
      if(d.error){res.className='aa-notice warn';res.textContent='Error: '+d.error;}
      else{res.className='aa-notice success';res.textContent='✅ Imported: '+d.imported+', Skipped duplicates: '+d.skipped;aaSerials.prev();aaSerials.next();}
    })
    .catch(function(){res.style.display='';res.className='aa-notice warn';res.textContent='Network error.'})
    .finally(function(){btn.disabled=false;btn.textContent='⬆ Import CSV';});
  };
  reader.readAsText(file);
}

function parseCSV(text){
  var lines=text.trim().split(/\r?\n/);
  if(lines.length<2)return[];
  var headers=lines[0].split(',').map(function(h){return h.trim().toLowerCase().replace(/^"|"$/g,'');});
  var rows=[];
  for(var i=1;i<lines.length;i++){
    var vals=splitCSV(lines[i]);
    var row={};
    headers.forEach(function(h,j){row[h]=(vals[j]||'').trim().replace(/^"|"$/g,'');});
    if(row.serial&&row.model)rows.push(row);
  }
  return rows;
}
function splitCSV(line){
  var result=[],curr='',inQ=false;
  for(var i=0;i<line.length;i++){
    var c=line[i];
    if(c==='"'){inQ=!inQ;}else if(c===','&&!inQ){result.push(curr);curr='';}else{curr+=c;}
  }
  result.push(curr);
  return result;
}
</script>

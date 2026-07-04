<?php defined( 'ABSPATH' ) || exit;
global $wpdb;
$nonce    = wp_create_nonce( 'wp_rest' );
$rest_url = esc_url_raw( rest_url( 'aiagent/v1' ) );

$examples = $wpdb->get_results(
  "SELECT te.*, c.name_en AS cat_name FROM {$wpdb->prefix}aiagent_taught_examples te
   LEFT JOIN {$wpdb->prefix}aiagent_categories c ON c.id = te.category_id
   ORDER BY te.created_at DESC LIMIT 100"
);
?>
<style>
.aiagent-page .aa-2col{display:flex!important;flex-direction:row!important;gap:20px!important;align-items:flex-start!important;flex-wrap:wrap!important;float:none!important;clear:both!important;}
.aiagent-page .aa-main{flex:1 1 400px!important;min-width:0!important;float:none!important;}
.aiagent-page .aa-side{flex:0 0 380px!important;width:380px!important;float:none!important;}
.aiagent-page .aa-card{background:#fff!important;border:1px solid #e2e8f0!important;border-radius:12px!important;box-shadow:0 1px 4px rgba(0,0,0,.07)!important;padding:22px!important;margin-bottom:16px!important;}
.aiagent-page .aa-card-title{font-size:14px!important;font-weight:700!important;color:#1e293b!important;margin-bottom:16px!important;padding-bottom:12px!important;border-bottom:1px solid #e2e8f0!important;display:block!important;}
.aiagent-page .aa-field{display:block!important;float:none!important;margin-bottom:16px!important;}
.aiagent-page .aa-field label{display:block!important;float:none!important;width:auto!important;font-size:11px!important;font-weight:700!important;color:#64748b!important;text-transform:uppercase!important;letter-spacing:.5px!important;margin-bottom:6px!important;padding:0!important;}
.aiagent-page .aa-input,.aiagent-page .aa-select,.aiagent-page .aa-textarea{display:block!important;width:100%!important;border:1.5px solid #e2e8f0!important;border-radius:8px!important;padding:10px 14px!important;font-size:14px!important;color:#1e293b!important;background:#fff!important;outline:none!important;box-shadow:none!important;margin:0!important;height:auto!important;max-width:100%!important;}
.aiagent-page .aa-textarea{resize:vertical!important;min-height:90px!important;}
.aiagent-page .aa-input:focus,.aiagent-page .aa-select:focus,.aiagent-page .aa-textarea:focus{border-color:#1a6b3c!important;box-shadow:0 0 0 3px rgba(26,107,60,.1)!important;}
.aiagent-page .aa-btn-primary{display:inline-flex!important;align-items:center!important;justify-content:center!important;width:100%!important;padding:11px 18px!important;border-radius:8px!important;font-size:14px!important;font-weight:600!important;border:none!important;cursor:pointer!important;background:#1a6b3c!important;color:#fff!important;}
.aiagent-page .aa-btn-primary:hover{background:#145530!important;}
.aiagent-page .aa-table{width:100%!important;border-collapse:collapse!important;font-size:13px!important;}
.aiagent-page .aa-table th{text-align:left!important;font-size:11px!important;font-weight:700!important;text-transform:uppercase!important;color:#64748b!important;padding:10px 14px!important;border-bottom:2px solid #e2e8f0!important;background:#f6f7fb!important;}
.aiagent-page .aa-table td{padding:11px 14px!important;border-bottom:1px solid #e2e8f0!important;vertical-align:middle!important;color:#1e293b!important;}
.aiagent-page .aa-table tr:hover td{background:#f8fafc!important;}
.aiagent-page .aa-badge{display:inline-flex!important;padding:3px 10px!important;border-radius:50px!important;font-size:11px!important;font-weight:600!important;}
.aiagent-page .aa-badge.cat{background:#f3e5f5!important;color:#6a1b9a!important;}
.aiagent-page .aa-badge.lang{background:#e3f2fd!important;color:#1565c0!important;}
.aiagent-page .aa-notice-success{background:#e8f5ee!important;color:#145530!important;border-left:4px solid #1a6b3c!important;padding:12px 16px!important;border-radius:8px!important;font-size:13px!important;margin-bottom:14px!important;}
.aiagent-page .aa-notice-error{background:#fce4ec!important;color:#b71c1c!important;border-left:4px solid #ef5350!important;padding:12px 16px!important;border-radius:8px!important;font-size:13px!important;margin-bottom:14px!important;}
@media(max-width:1100px){.aiagent-page .aa-side{flex:0 0 100%!important;width:100%!important;}}
</style>
<div class="wrap aiagent-page">
  <h1>Teach the AI</h1>
  <p style="color:#64748b;margin-bottom:24px;">Add Q&amp;A pairs the AI will use to answer customers. Each entry is embedded and retrieved automatically.</p>

  <div class="aa-2col">

    <!-- ── Teach form ─────────────────────────────────────────────────────── -->
    <div class="aa-side">
      <div class="aa-card" id="aa-teach-card">
        <span class="aa-card-title">➕ New Example</span>

        <div class="aa-field">
          <label>Question / Problem</label>
          <textarea class="aa-textarea" id="aa-q" rows="4" placeholder="What the customer asks…"></textarea>
        </div>
        <div class="aa-field">
          <label>Answer / Solution</label>
          <textarea class="aa-textarea" id="aa-a" rows="5" placeholder="The correct, complete answer…"></textarea>
        </div>
        <div class="aa-field">
          <label>Language</label>
          <select class="aa-select" id="aa-lang">
            <option value="both">Both EN + AR (default)</option>
            <option value="en">English only</option>
            <option value="ar">Arabic only</option>
          </select>
        </div>

        <button class="aa-btn-primary" id="aa-teach-btn" onclick="aaTeach()">💾 Save &amp; Teach</button>
        <div id="aa-teach-result" style="margin-top:14px;display:none;"></div>
      </div>
    </div>

    <!-- ── Knowledge base table ───────────────────────────────────────────── -->
    <div class="aa-main">
      <div class="aa-card" style="padding:0;overflow:hidden;">
        <div style="padding:16px 20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
          <span style="font-weight:700;font-size:15px;">📚 Knowledge Base <span style="color:#64748b;font-weight:400;font-size:13px;">(<?php echo count($examples); ?> examples)</span></span>
          <input type="text" class="aa-input" style="max-width:220px;" placeholder="🔍 Search…" oninput="aaSearchExamples(this.value)">
        </div>
        <table class="aa-table" id="aa-examples-table">
          <thead><tr>
            <th>Question</th><th>Category</th><th>Lang</th><th>Added</th>
          </tr></thead>
          <tbody>
          <?php if ( empty($examples) ) : ?>
            <tr><td colspan="4">
              <div class="aa-empty">
                <div class="aa-empty__icon">🧠</div>
                <div class="aa-empty__title">No examples yet</div>
                <div class="aa-empty__desc">Add your first Q&amp;A pair using the form.</div>
              </div>
            </td></tr>
          <?php else : foreach ($examples as $ex) : ?>
            <tr class="aa-ex-row">
              <td style="max-width:300px;">
                <div style="font-size:13px;font-weight:600;margin-bottom:3px;"><?php echo esc_html(wp_trim_words($ex->question,12)); ?></div>
                <div style="font-size:12px;color:#64748b;"><?php echo esc_html(wp_trim_words($ex->solution,14)); ?></div>
              </td>
              <td>
                <?php if ($ex->cat_name): ?>
                  <span class="aa-badge cat"><?php echo esc_html($ex->cat_name); ?></span>
                <?php else: echo '<span style="color:#94a3b8;font-size:12px;">—</span>'; endif; ?>
              </td>
              <td><span class="aa-badge lang"><?php echo esc_html($ex->language); ?></span></td>
              <td style="font-size:12px;color:#94a3b8;"><?php echo esc_html(gmdate('M d, Y',strtotime($ex->created_at))); ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<script>
var aaRest='<?php echo esc_js($rest_url);?>';var aaNonce='<?php echo esc_js($nonce);?>';

function aaTeach(){
  var q=document.getElementById('aa-q').value.trim();
  var a=document.getElementById('aa-a').value.trim();
  var lang=document.getElementById('aa-lang').value;
  var res=document.getElementById('aa-teach-result');
  if(!q||!a){res.style.display='block';res.innerHTML='<div class="aa-notice aa-notice--error">Please fill in both fields.</div>';return;}
  var btn=document.getElementById('aa-teach-btn');
  btn.disabled=true;btn.textContent='Teaching…';res.style.display='none';
  fetch(aaRest+'/admin/teach',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':aaNonce},
    body:JSON.stringify({question:q,solution:a,language:lang})})
  .then(function(r){return r.json();}).then(function(d){
    res.style.display='block';
    if(d.id){
      res.innerHTML='<div class="aa-notice-success">✅ Saved as example #'+d.id+(d.category?' · Category: <strong>'+d.category+'</strong>':'')+'. The AI will use this immediately.</div>';
      document.getElementById('aa-q').value='';
      document.getElementById('aa-a').value='';
      // Add row to table
      var tbody=document.querySelector('#aa-examples-table tbody');
      if(tbody){
        var row=document.createElement('tr');row.className='aa-ex-row';
        row.innerHTML='<td><div style="font-size:13px;font-weight:600;margin-bottom:3px;">'+q.substring(0,60)+'…</div><div style="font-size:12px;color:#64748b;">'+a.substring(0,80)+'…</div></td><td>'+(d.category?'<span class="aa-badge aa-badge--pending">'+d.category+'</span>':'<span style="color:#94a3b8;font-size:12px;">—</span>')+'</td><td><span class="aa-badge" style="background:#e3f2fd;color:#1565c0;">'+lang+'</span></td><td style="font-size:12px;color:#94a3b8;">Just now</td>';
        tbody.insertBefore(row,tbody.firstChild);
      }
    } else {
      res.innerHTML='<div class="aa-notice-error">Error: '+(d.message||'Failed to save.')+'</div>';
    }
  }).catch(function(){
    res.style.display='block';res.innerHTML='<div class="aa-notice-error">Network error. Please try again.</div>';
  }).finally(function(){btn.disabled=false;btn.textContent='💾 Save & Teach';});
}

function aaSearchExamples(q){
  q=q.toLowerCase();
  document.querySelectorAll('.aa-ex-row').forEach(function(row){
    row.style.display=row.textContent.toLowerCase().includes(q)?'':'none';
  });
}
</script>

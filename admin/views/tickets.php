<?php defined( 'ABSPATH' ) || exit;
// $tickets injected by AIAgent_Admin::page_tickets()
$nonce    = wp_create_nonce( 'wp_rest' );
$rest_url = esc_url_raw( rest_url( 'aiagent/v1' ) );
?>
<div class="wrap aiagent-page">
  <h1>Support Tickets</h1>

  <div class="aa-layout-2col">

    <!-- ── Tickets list ───────────────────────────────────────────────────── -->
    <div class="aa-col-main">

      <!-- Status filter tabs -->
      <div class="aa-tabs" id="aa-filter-tabs">
        <button class="aa-tab aa-tab--active" data-filter="all"      onclick="aaFilter('all',this)">All</button>
        <button class="aa-tab"                data-filter="open"     onclick="aaFilter('open',this)">Open</button>
        <button class="aa-tab"                data-filter="claimed"  onclick="aaFilter('claimed',this)">Claimed</button>
        <button class="aa-tab"                data-filter="resolved" onclick="aaFilter('resolved',this)">Resolved</button>
      </div>

      <div class="aa-card" style="padding:0;overflow:hidden;">
        <table class="aa-table" id="aa-tickets-table">
          <thead><tr>
            <th>#</th><th>Reason</th><th>Status</th><th>Contact</th><th>Created</th><th>Actions</th>
          </tr></thead>
          <tbody>
          <?php if ( empty( $tickets ) ) : ?>
            <tr><td colspan="6">
              <div class="aa-empty">
                <div class="aa-empty__icon">🎫</div>
                <div class="aa-empty__title">No tickets yet</div>
                <div class="aa-empty__desc">Escalations from the chat will appear here.</div>
              </div>
            </td></tr>
          <?php else : foreach ( $tickets as $t ) : ?>
            <tr data-status="<?php echo esc_attr($t->status); ?>" id="aa-ticket-<?php echo (int)$t->id; ?>">
              <td><strong>#<?php echo (int)$t->id; ?></strong></td>
              <td style="max-width:200px;"><span style="font-size:13px;"><?php echo esc_html( wp_trim_words($t->reason,10) ); ?></span></td>
              <td><span class="aa-badge aa-badge--<?php echo esc_attr($t->status); ?>"><?php echo esc_html(ucfirst($t->status)); ?></span></td>
              <td style="font-size:12px;color:#64748b;"><?php echo esc_html( $t->contact ?: '—' ); ?></td>
              <td style="font-size:12px;color:#94a3b8;"><?php echo esc_html( human_time_diff(strtotime($t->created_at),time()) . ' ago' ); ?></td>
              <td style="white-space:nowrap;">
                <?php if ($t->status === 'open') : ?>
                  <button class="aa-btn aa-btn--outline aa-btn--sm" onclick="aaClaim(<?php echo (int)$t->id; ?>)">Claim</button>
                <?php endif; ?>
                <?php if ($t->status !== 'resolved') : ?>
                  <button class="aa-btn aa-btn--primary aa-btn--sm" onclick="aaOpenResolve(<?php echo (int)$t->id; ?>,<?php echo (int)$t->conversation_id; ?>)">Resolve</button>
                <?php else: ?>
                  <span style="font-size:12px;color:#2e7d32;">✅ Resolved</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

    </div><!-- /col-main -->

    <!-- ── Resolve panel ─────────────────────────────────────────────────── -->
    <div class="aa-col-side">
      <div class="aa-panel" id="aa-resolve-panel" style="display:none;">
        <div class="aa-panel__title">✅ Resolve Ticket <span id="aa-resolve-id" style="color:#64748b;"></span></div>

        <div class="aa-field">
          <label>Resolution / Notes</label>
          <textarea class="aa-textarea" id="aa-resolution" rows="5" placeholder="Describe how the issue was resolved…"></textarea>
        </div>

        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:16px;">
          <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:13px;">
            <input type="checkbox" id="aa-promo-ticket" style="margin-top:2px;">
            <span>
              <strong>Add to Knowledge Base</strong><br>
              <span style="color:#64748b;font-size:12px;">AI will use this resolution to answer similar questions automatically.</span>
            </span>
          </label>
          <div id="aa-question-wrap" style="display:none;margin-top:12px;">
            <label style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Customer Question (pre-filled)</label>
            <input type="text" class="aa-input" id="aa-question-text" style="margin-top:6px;" placeholder="Original question…">
          </div>
        </div>

        <div style="display:flex;gap:8px;">
          <button class="aa-btn aa-btn--primary" onclick="aaSubmitResolve()" id="aa-resolve-submit">Mark Resolved</button>
          <button class="aa-btn aa-btn--outline"  onclick="aaCloseResolve()">Cancel</button>
        </div>
      </div>

      <!-- Placeholder when no ticket selected -->
      <div class="aa-panel" id="aa-resolve-placeholder">
        <div class="aa-empty" style="padding:32px 10px;">
          <div class="aa-empty__icon">🎫</div>
          <div class="aa-empty__title">Select a ticket to resolve</div>
          <div class="aa-empty__desc">Click Resolve on any open or claimed ticket.</div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>
var aaRest='<?php echo esc_js($rest_url);?>';var aaNonce='<?php echo esc_js($nonce);?>';
var aaActiveTicket=null,aaActiveConv=null;

function aaFilter(status,btn){
  document.querySelectorAll('.aa-tab').forEach(function(b){b.classList.remove('aa-tab--active');});
  btn.classList.add('aa-tab--active');
  document.querySelectorAll('#aa-tickets-table tbody tr[data-status]').forEach(function(row){
    row.style.display=(status==='all'||row.dataset.status===status)?'':'none';
  });
}

function aaClaim(id){
  fetch(aaRest+'/admin/ticket/'+id+'/claim',{method:'POST',headers:{'X-WP-Nonce':aaNonce}})
  .then(function(r){return r.json();}).then(function(){
    var row=document.getElementById('aa-ticket-'+id);
    if(row){row.querySelector('.aa-badge').className='aa-badge aa-badge--claimed';row.querySelector('.aa-badge').textContent='Claimed';}
  });
}

function aaOpenResolve(ticketId,convId){
  aaActiveTicket=ticketId;aaActiveConv=convId;
  document.getElementById('aa-resolve-panel').style.display='block';
  document.getElementById('aa-resolve-placeholder').style.display='none';
  document.getElementById('aa-resolve-id').textContent='#'+ticketId;
  document.getElementById('aa-resolution').value='';
  document.getElementById('aa-promo-ticket').checked=false;
  document.getElementById('aa-question-wrap').style.display='none';
  document.getElementById('aa-question-text').value='';
  // Pre-fill question from first customer message in conversation.
  if(convId){
    fetch(aaRest+'/admin/conversation/'+convId,{headers:{'X-WP-Nonce':aaNonce}})
    .then(function(r){return r.json();}).then(function(d){
      var first=(d.messages||[]).find(function(m){return m.role==='customer';});
      if(first)document.getElementById('aa-question-text').value=first.body;
    });
  }
}

document.getElementById('aa-promo-ticket').addEventListener('change',function(){
  document.getElementById('aa-question-wrap').style.display=this.checked?'block':'none';
});

function aaCloseResolve(){
  document.getElementById('aa-resolve-panel').style.display='none';
  document.getElementById('aa-resolve-placeholder').style.display='block';
}

function aaSubmitResolve(){
  if(!aaActiveTicket)return;
  var resolution=document.getElementById('aa-resolution').value.trim();
  var promote=document.getElementById('aa-promo-ticket').checked;
  var question=document.getElementById('aa-question-text').value.trim();
  if(!resolution){alert('Please enter a resolution note.');return;}
  var btn=document.getElementById('aa-resolve-submit');
  btn.disabled=true;btn.textContent='Saving…';
  fetch(aaRest+'/admin/ticket/'+aaActiveTicket+'/resolve',{
    method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':aaNonce},
    body:JSON.stringify({resolution:resolution,promote:promote,question:question})
  }).then(function(r){return r.json();}).then(function(d){
    var row=document.getElementById('aa-ticket-'+aaActiveTicket);
    if(row){
      var badge=row.querySelector('.aa-badge');
      if(badge){badge.className='aa-badge aa-badge--resolved';badge.textContent='Resolved';}
      var actions=row.querySelector('td:last-child');
      if(actions)actions.innerHTML='<span style="font-size:12px;color:#2e7d32;">✅ Resolved</span>';
    }
    aaCloseResolve();
    btn.disabled=false;btn.textContent='Mark Resolved';
  }).catch(function(){btn.disabled=false;btn.textContent='Mark Resolved';alert('Failed to resolve.');});
}
</script>

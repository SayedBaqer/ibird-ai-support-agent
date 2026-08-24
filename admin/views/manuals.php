<?php defined( 'ABSPATH' ) || exit;
global $wpdb;
$nonce        = wp_create_nonce( 'wp_rest' );
$rest_url     = esc_url_raw( rest_url( 'aiagent/v1' ) );
$manual_nonce = wp_create_nonce( 'aiagent_upload_attachment' );
$pdf_nonce    = wp_create_nonce( 'aiagent_manual_upload' );

$chunks = $wpdb->get_results(
  "SELECT model, COUNT(*) AS cnt, MAX(created_at) AS last_ingested
   FROM {$wpdb->prefix}aiagent_manual_chunks GROUP BY model ORDER BY last_ingested DESC"
);

// Fetch stored images per model.
$model_images = [];
$img_rows = $wpdb->get_results(
  "SELECT `key`, `value` FROM {$wpdb->prefix}aiagent_reference_data WHERE `type` = 'manual_images'"
);
foreach ( $img_rows as $row ) {
  $model_images[ $row->key ] = json_decode( $row->value, true ) ?? [];
}
?>
<style>
.aiagent-page .aa-2col{display:flex!important;flex-direction:row!important;gap:20px!important;align-items:flex-start!important;flex-wrap:wrap!important;float:none!important;clear:both!important;}
.aiagent-page .aa-main{flex:1 1 400px!important;min-width:0!important;float:none!important;}
.aiagent-page .aa-side{flex:0 0 400px!important;width:400px!important;float:none!important;}
.aiagent-page .aa-card{background:#fff!important;border:1px solid #e2e8f0!important;border-radius:12px!important;box-shadow:0 1px 4px rgba(0,0,0,.07)!important;padding:22px!important;margin-bottom:16px!important;}
.aiagent-page .aa-card-title{font-size:14px!important;font-weight:700!important;color:#1e293b!important;margin-bottom:16px!important;padding-bottom:12px!important;border-bottom:1px solid #e2e8f0!important;display:block!important;}
.aiagent-page .aa-field{display:block!important;float:none!important;margin-bottom:16px!important;}
.aiagent-page .aa-field label{display:block!important;float:none!important;width:auto!important;font-size:11px!important;font-weight:700!important;color:#64748b!important;text-transform:uppercase!important;letter-spacing:.5px!important;margin-bottom:6px!important;padding:0!important;}
.aiagent-page .aa-field__desc{font-size:11px!important;color:#94a3b8!important;margin-top:5px!important;}
.aiagent-page .aa-input,.aiagent-page .aa-select,.aiagent-page .aa-textarea{display:block!important;width:100%!important;border:1.5px solid #e2e8f0!important;border-radius:8px!important;padding:10px 14px!important;font-size:14px!important;color:#1e293b!important;background:#fff!important;outline:none!important;box-shadow:none!important;margin:0!important;height:auto!important;max-width:100%!important;}
.aiagent-page .aa-textarea{resize:vertical!important;min-height:90px!important;}
.aiagent-page .aa-input:focus,.aiagent-page .aa-select:focus,.aiagent-page .aa-textarea:focus{border-color:#1a6b3c!important;box-shadow:0 0 0 3px rgba(26,107,60,.1)!important;}
.aiagent-page .aa-tabs{display:flex!important;gap:6px!important;margin-bottom:16px!important;}
.aiagent-page .aa-tab{padding:7px 16px!important;border-radius:8px!important;border:1.5px solid #e2e8f0!important;background:#fff!important;font-size:13px!important;font-weight:600!important;color:#64748b!important;cursor:pointer!important;}
.aiagent-page .aa-tab.aa-tab--active{background:#1a6b3c!important;color:#fff!important;border-color:#1a6b3c!important;}
.aiagent-page .aa-btn-primary{display:inline-flex!important;align-items:center!important;justify-content:center!important;width:100%!important;padding:11px 18px!important;border-radius:8px!important;font-size:14px!important;font-weight:600!important;border:none!important;cursor:pointer!important;background:#1a6b3c!important;color:#fff!important;}
.aiagent-page .aa-btn-primary:hover{background:#145530!important;}
.aiagent-page .aa-table{width:100%!important;border-collapse:collapse!important;font-size:13px!important;}
.aiagent-page .aa-table th{text-align:left!important;font-size:11px!important;font-weight:700!important;text-transform:uppercase!important;color:#64748b!important;padding:10px 14px!important;border-bottom:2px solid #e2e8f0!important;background:#f6f7fb!important;}
.aiagent-page .aa-table td{padding:11px 14px!important;border-bottom:1px solid #e2e8f0!important;vertical-align:middle!important;color:#1e293b!important;}
.aiagent-page .aa-table tr:hover td{background:#f8fafc!important;}
.aiagent-page .aa-badge,.aiagent-page .aa-badge--active{display:inline-flex!important;padding:3px 10px!important;border-radius:50px!important;font-size:11px!important;font-weight:600!important;background:#e8f5e9!important;color:#2e7d32!important;}
.aiagent-page .aa-notice{padding:12px 16px!important;border-radius:8px!important;font-size:13px!important;margin-bottom:8px!important;}
.aiagent-page .aa-notice--success{background:#e8f5ee!important;color:#145530!important;border-left:4px solid #1a6b3c!important;}
.aiagent-page .aa-notice--error{background:#fce4ec!important;color:#b71c1c!important;border-left:4px solid #ef5350!important;}
.aiagent-page .aa-notice--info{background:#eff6ff!important;color:#1e40af!important;border-left:4px solid #3b82f6!important;}
.aiagent-page .aa-empty{text-align:center!important;padding:32px 20px!important;color:#64748b!important;}
@media(max-width:1100px){.aiagent-page .aa-side{flex:0 0 100%!important;width:100%!important;}}
</style>
<div class="wrap aiagent-page">
  <h1>Product Manuals</h1>
  <p style="color:#64748b;margin-bottom:24px;">Upload PDF manuals or paste text. Optionally attach product images that will be shown when the AI retrieves this manual section.</p>

  <div class="aa-2col">

    <!-- ── Upload form ────────────────────────────────────────────────────── -->
    <div class="aa-side">
      <div class="aa-card">
        <span class="aa-card-title">📄 Ingest Manual</span>

        <div class="aa-field" style="position:relative;">
          <label>Product Model</label>
          <input type="text" class="aa-input" id="aa-model" placeholder="Type to search products…" autocomplete="off">
          <input type="hidden" id="aa-model-id">
          <div id="aa-model-drop" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1.5px solid #1a6b3c;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.12);z-index:999;max-height:220px;overflow-y:auto;"></div>
          <div class="aa-field__desc">Search by name or SKU — linked to WooCommerce/iStock products.</div>
        </div>

        <div class="aa-field">
          <label style="display:flex;align-items:center;gap:8px;text-transform:none;font-size:13px;font-weight:600;color:#1e293b;">
            <input type="checkbox" id="aa-common" style="width:auto;" onchange="aaToggleCommon(this.checked)">
            🌐 General / applies to all products
          </label>
          <div class="aa-field__desc">For cross-product content (battery care, resets, connectivity, packaging…) instead of one specific model. The agent uses this for any product when nothing model-specific matches.</div>
        </div>

        <!-- Content tabs -->
        <div class="aa-tabs">
          <button class="aa-tab aa-tab--active" onclick="aaManualTab('pdf',this)">📎 PDF</button>
          <button class="aa-tab"                onclick="aaManualTab('text',this)">📝 Paste Text</button>
        </div>

        <div id="aa-tab-pdf">
          <div class="aa-field">
            <label>PDF File</label>
            <input type="file" id="aa-pdf-file" accept=".pdf" style="width:100%;margin-top:4px;">
            <div class="aa-field__desc">Text-layer PDFs only. Scanned PDFs → use Paste Text.</div>
          </div>
        </div>

        <div id="aa-tab-text" style="display:none;">
          <div class="aa-field">
            <label>Manual Text</label>
            <textarea class="aa-textarea" id="aa-manual-text" rows="8" placeholder="Paste the full manual text here…"></textarea>
          </div>
          <div class="aa-field">
            <label>Section Title (optional)</label>
            <input type="text" class="aa-input" id="aa-section-title" placeholder="e.g. Installation Guide">
          </div>
        </div>

        <!-- Image uploads -->
        <div class="aa-field" style="margin-top:16px;padding-top:16px;border-top:1px solid #e2e8f0;">
          <label>Product Images (optional)</label>
          <input type="file" id="aa-img-files" accept="image/*" multiple style="width:100%;margin-top:4px;">
          <div class="aa-field__desc">Upload product photos shown alongside manual answers. Multiple files allowed.</div>
          <div id="aa-img-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;"></div>
        </div>

        <?php
        $s = aiagent_settings();
        if ( empty( $s['api_key'] ) ) : ?>
        <div class="aa-notice aa-notice--error" style="display:block;margin-bottom:12px;">
          ⚠️ <strong>Gemini API key not set.</strong> Go to <a href="<?php echo esc_url(admin_url('admin.php?page=aiagent-settings')); ?>">AI Agent → Settings</a> and enter your key before ingesting.
        </div>
        <?php endif; ?>
        <button class="aa-btn-primary" style="margin-top:4px;" id="aa-ingest-btn" onclick="aaIngest()">⚡ Ingest Manual</button>
        <div id="aa-manual-result" style="margin-top:14px;display:none;"></div>
      </div>
    </div>

    <!-- ── Ingested manuals ───────────────────────────────────────────────── -->
    <div class="aa-main">

      <!-- Gallery cards -->
      <?php if ( ! empty( $chunks ) ) : ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-bottom:24px;" id="aa-model-cards">
        <?php foreach ( $chunks as $ch ) :
          $imgs  = $model_images[ $ch->model ] ?? [];
          $thumb = ! empty( $imgs ) ? $imgs[0] : '';
          $is_common   = ( $ch->model === AIAgent_RAG::COMMON_MODEL );
          $model_label = $is_common ? '🌐 General (All Products)' : $ch->model;
        ?>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.07);cursor:pointer;" onclick="aaFilterModel('<?php echo esc_js($ch->model); ?>')">
          <?php if ( $thumb ) : ?>
            <div style="height:140px;overflow:hidden;background:#f0f2f5;">
              <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($model_label); ?>" style="width:100%;height:100%;object-fit:cover;">
            </div>
          <?php else : ?>
            <div style="height:140px;background:linear-gradient(135deg,#e8f5ee,#c8e6d4);display:flex;align-items:center;justify-content:center;font-size:40px;"><?php echo $is_common ? '🌐' : '📄'; ?></div>
          <?php endif; ?>
          <div style="padding:14px;">
            <div style="font-weight:700;font-size:14px;margin-bottom:4px;"><?php echo esc_html($model_label); ?></div>
            <div style="display:flex;align-items:center;justify-content:space-between;">
              <span class="aa-badge aa-badge--active"><?php echo (int)$ch->cnt; ?> chunks</span>
              <span style="font-size:11px;color:#94a3b8;"><?php echo esc_html(human_time_diff(strtotime($ch->last_ingested),time()).' ago'); ?></span>
            </div>
            <?php if ( count($imgs) > 1 ) : ?>
              <div style="display:flex;gap:4px;margin-top:8px;">
                <?php foreach ( array_slice($imgs,1,3) as $img ) : ?>
                  <img src="<?php echo esc_url($img); ?>" style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;">
                <?php endforeach; ?>
                <?php if (count($imgs) > 4) : ?><span style="font-size:11px;color:#64748b;align-self:center;">+<?php echo count($imgs)-4; ?></span><?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Chunks table -->
      <div class="aa-card" style="padding:0;overflow:hidden;">
        <div style="padding:14px 20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
          <span style="font-weight:700;">📚 All Chunks</span>
          <input type="text" class="aa-input" style="max-width:200px;" placeholder="🔍 Filter model…" oninput="aaFilterModel(this.value)">
        </div>
        <table class="aa-table" id="aa-chunks-table">
          <thead><tr><th>Model</th><th>Chunks</th><th>Last Ingested</th></tr></thead>
          <tbody id="aa-chunks-tbody">
          <?php if ( empty($chunks) ) : ?>
            <tr><td colspan="3">
              <div class="aa-empty">
                <div class="aa-empty__icon">📄</div>
                <div class="aa-empty__title">No manuals yet</div>
                <div class="aa-empty__desc">Upload a product manual to get started.</div>
              </div>
            </td></tr>
          <?php else : foreach ($chunks as $ch) :
            $row_label = ( $ch->model === AIAgent_RAG::COMMON_MODEL ) ? '🌐 General (All Products)' : $ch->model;
          ?>
            <tr class="aa-chunk-row" data-model="<?php echo esc_attr(strtolower($ch->model)); ?>">
              <td><strong><?php echo esc_html($row_label); ?></strong></td>
              <td><span class="aa-badge aa-badge--active"><?php echo (int)$ch->cnt; ?> chunks</span></td>
              <td style="font-size:12px;color:#94a3b8;"><?php echo esc_html(human_time_diff(strtotime($ch->last_ingested),time()).' ago'); ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<script>
var aaRest='<?php echo esc_js($rest_url);?>';
var aaNonce='<?php echo esc_js($nonce);?>';
var aaManualNonce='<?php echo esc_js($manual_nonce);?>';
var aaPdfNonce='<?php echo esc_js($pdf_nonce);?>';
var aaAjax='<?php echo esc_js(admin_url("admin-ajax.php")); ?>';
var AA_COMMON_MODEL='<?php echo esc_js( AIAgent_RAG::COMMON_MODEL ); ?>';

function aaToggleCommon(checked){
  var modelInp=document.getElementById('aa-model');
  modelInp.disabled=checked;
  modelInp.placeholder=checked?'General — applies to all products':'Type to search products…';
  if(checked) modelInp.value='';
}

// Image preview
document.getElementById('aa-img-files').addEventListener('change', function(){
  var preview=document.getElementById('aa-img-preview');
  preview.innerHTML='';
  Array.from(this.files).forEach(function(f){
    var url=URL.createObjectURL(f);
    var img=document.createElement('img');
    img.src=url;img.style.cssText='width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;';
    preview.appendChild(img);
  });
});

function aaManualTab(tab,btn){
  document.querySelectorAll('.aa-tab').forEach(function(b){b.classList.remove('aa-tab--active');});
  btn.classList.add('aa-tab--active');
  document.getElementById('aa-tab-pdf').style.display=tab==='pdf'?'block':'none';
  document.getElementById('aa-tab-text').style.display=tab==='text'?'block':'none';
}

function aaResult(msg,type){
  var el=document.getElementById('aa-manual-result');
  el.style.display='block';
  el.innerHTML='<div class="aa-notice aa-notice--'+(type||'success')+'">'+msg+'</div>';
}

function aaFilterModel(q){
  q=(q||'').toLowerCase();
  document.querySelectorAll('.aa-chunk-row').forEach(function(r){
    r.style.display=r.dataset.model.includes(q)?'':'none';
  });
}

async function aaIngest(){
  var isCommon=document.getElementById('aa-common').checked;
  var model=isCommon?AA_COMMON_MODEL:document.getElementById('aa-model').value.trim();
  if(!model){aaResult('⚠️ Please enter a product model.','error');return;}

  var btn=document.getElementById('aa-ingest-btn');
  btn.disabled=true;
  document.getElementById('aa-manual-result').style.display='none';

  // Step 1 — upload images (if any).
  var imgFiles=document.getElementById('aa-img-files').files;
  var imgUrls=[];
  for(var i=0;i<imgFiles.length;i++){
    btn.textContent='⏫ Uploading image '+(i+1)+'/'+imgFiles.length+'…';
    var fd=new FormData();
    fd.append('file',imgFiles[i]);
    fd.append('action','aiagent_upload_attachment');
    fd.append('_ajax_nonce',aaManualNonce);
    try{
      var r=await fetch(aaAjax,{method:'POST',body:fd});
      var d=await r.json();
      if(d.success&&d.data&&d.data.url)imgUrls.push(d.data.url);
    }catch(e){}
  }

  // Step 2 — get text content.
  var activeTab=document.getElementById('aa-tab-pdf').style.display!=='none'?'pdf':'text';
  var text='',source='pasted-text',section='';

  if(activeTab==='pdf'){
    var pdfFile=document.getElementById('aa-pdf-file').files[0];
    if(!pdfFile){
      if(imgUrls.length===0){
        aaResult('⚠️ Please select a PDF file or switch to the Text tab.','error');
        btn.disabled=false;btn.textContent='⚡ Ingest Manual';return;
      }
      text='[Product images uploaded for model '+model+']';
    } else {

      // ── Phase A: upload file to Gemini ─────────────────────────────────────
      btn.textContent='⏳ Uploading PDF to Gemini… (may take 30s)';
      aaResult('⏳ Step 1/3 — Uploading PDF to Gemini File API…','info');

      var fd2=new FormData();
      fd2.append('file',pdfFile);
      fd2.append('action','aiagent_manual_upload_to_gemini');
      fd2.append('_ajax_nonce',aaPdfNonce);

      var uploadData;
      try{
        var upResp=await fetch(aaAjax,{method:'POST',body:fd2});
        var upJson=await upResp.json();
        if(!upJson.success){
          throw new Error(upJson.data&&upJson.data.error?upJson.data.error:'Upload to Gemini failed.');
        }
        uploadData=upJson.data;
      }catch(e){
        aaResult('❌ Upload failed: '+(e.message||'Could not upload file.'),'error');
        btn.disabled=false;btn.textContent='⚡ Ingest Manual';return;
      }

      // ── Phase B: ask Gemini to extract text ────────────────────────────────
      btn.textContent='🧠 Gemini reading PDF… (may take 60–90s)';
      aaResult('⏳ Step 2/3 — Gemini is reading and extracting the manual text…','info');

      var fd3=new FormData();
      fd3.append('action','aiagent_manual_analyze_gemini');
      fd3.append('_ajax_nonce',aaPdfNonce);
      fd3.append('file_uri',uploadData.file_uri);
      fd3.append('file_name',uploadData.file_name);
      fd3.append('mime',uploadData.mime);
      fd3.append('is_pdf',uploadData.is_pdf?'true':'false');

      try{
        var anResp=await fetch(aaAjax,{method:'POST',body:fd3});
        var anJson=await anResp.json();
        if(!anJson.success){
          throw new Error(anJson.data&&anJson.data.error?anJson.data.error:'Gemini text extraction failed.');
        }
        text=anJson.data.text;
        source=pdfFile.name;
      }catch(e){
        aaResult('❌ Gemini extraction failed: '+(e.message||'Unknown error.'),'error');
        btn.disabled=false;btn.textContent='⚡ Ingest Manual';return;
      }
    }
  } else {
    text=document.getElementById('aa-manual-text').value.trim();
    section=document.getElementById('aa-section-title').value.trim();
    if(!text){aaResult('⚠️ Please paste some text.','error');btn.disabled=false;btn.textContent='⚡ Ingest Manual';return;}
  }

  // Step 3 — chunk, embed, extract Q&A and store.
  btn.textContent='💾 Chunking, embedding & extracting Q&A…';
  aaResult('⏳ Step 3/3 — Chunking text, generating embeddings and extracting Q&A pairs…','info');
  try{
    var res=await fetch(aaRest+'/admin/manual',{
      method:'POST',
      headers:{'Content-Type':'application/json','X-WP-Nonce':aaNonce},
      body:JSON.stringify({model:model,text:text,section_title:section,source_file:source,image_urls:imgUrls})
    });
    var data=await res.json();
    if(data.code&&!data.chunks&&data.chunks!==0){throw new Error(data.message||'REST error: '+JSON.stringify(data));}

    var summary='✅ <strong>Ingestion complete for '+model+'</strong><br>'
      +'📄 <strong>'+(data.chunks||0)+'</strong> manual chunks stored'
      +(imgUrls.length?' · 🖼 <strong>'+imgUrls.length+'</strong> image(s)':'')
      +(data.qa_stored?'<br>🧠 <strong>'+(data.qa_stored)+'</strong> Q&A pairs extracted and saved to Teach AI':'')
      +(data.qa_skipped?' (<em>'+data.qa_skipped+' duplicates skipped</em>)':'')
      +(data.embed_status?'<br><small style="color:#6b7280;">⏳ '+data.embed_status+'</small>':'');

    // Show extracted text preview if available.
    if(data.text_preview){
      summary+='<details style="margin-top:12px;"><summary style="cursor:pointer;font-weight:600;color:#1e40af;">👁 Preview extracted text (first 2 000 chars)</summary>'
        +'<pre style="white-space:pre-wrap;word-break:break-word;font-size:11px;background:#f8fafc;padding:10px;border-radius:6px;margin-top:8px;max-height:300px;overflow-y:auto;">'+data.text_preview.replace(/</g,'&lt;').replace(/>/g,'&gt;')+'</pre></details>';
    }

    aaResult(summary,'success');
    document.getElementById('aa-model').value='';
    document.getElementById('aa-pdf-file').value='';
    document.getElementById('aa-manual-text').value='';
    document.getElementById('aa-img-files').value='';
    document.getElementById('aa-img-preview').innerHTML='';
  } catch(e){
    aaResult('❌ Chunk/embed step failed: '+(e.message||'Unknown error.'),'error');
  }
  btn.disabled=false;btn.textContent='⚡ Ingest Manual';
}

/* ── Product model live search ──────────────────────────────────── */
(function(){
  var inp  = document.getElementById('aa-model');
  var drop = document.getElementById('aa-model-drop');
  var hid  = document.getElementById('aa-model-id');
  var timer;

  inp.addEventListener('input', function(){
    clearTimeout(timer);
    var q = this.value.trim();
    if(q.length < 2){ drop.style.display='none'; return; }
    timer = setTimeout(function(){
      fetch('<?php echo esc_js(rest_url("aiagent/v1")); ?>/admin/products/search?q='+encodeURIComponent(q),
        {headers:{'X-WP-Nonce':'<?php echo esc_js(wp_create_nonce("wp_rest")); ?>'}})
      .then(function(r){return r.json();})
      .then(function(d){
        var products = d.products||[];
        if(!products.length){drop.style.display='none';return;}
        drop.innerHTML = products.map(function(p){
          var cats = (p.categories||[]).join(', ');
          return '<div class="aa-prod-item" data-name="'+esc(p.name)+'" data-id="'+p.id+'" style="padding:9px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;">'
            +'<div style="font-size:13px;font-weight:600;">'+esc(p.name)+'</div>'
            +'<div style="font-size:11px;color:#94a3b8;">'+(p.sku?'SKU: '+esc(p.sku)+' · ':'')+esc(cats)+'</div>'
            +'</div>';
        }).join('');
        drop.style.display='block';
        drop.querySelectorAll('.aa-prod-item').forEach(function(item){
          item.addEventListener('mouseenter',function(){this.style.background='#f0fdf4';});
          item.addEventListener('mouseleave',function(){this.style.background='';});
          item.addEventListener('click',function(){
            inp.value  = this.dataset.name;
            hid.value  = this.dataset.id;
            drop.style.display='none';
          });
        });
      })
      .catch(function(){drop.style.display='none';});
    }, 260);
  });

  document.addEventListener('click',function(e){
    if(!inp.contains(e.target)&&!drop.contains(e.target)) drop.style.display='none';
  });

  function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(s||''));return d.innerHTML;}
})();
</script>

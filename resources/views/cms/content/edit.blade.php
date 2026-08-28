@extends('cms.layouts.app')
@section('title','Edit '.$groupName)
@push('styles')<style>
.editor-wrap{max-width:100%}.editor-shell{display:grid;grid-template-columns:minmax(0,540px) minmax(0,1fr);gap:22px;align-items:start}.editor-shell.is-solo{grid-template-columns:minmax(0,1fr)}.editor-shell.is-solo .preview-pane{display:none}.editor-pane{min-width:0}.preview-pane{position:sticky;top:14px;min-width:0;border:1px solid var(--line);border-radius:14px;background:#fff;box-shadow:var(--shadow);overflow:hidden}.preview-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 14px;border-bottom:1px solid var(--line);background:#fafbfc}.preview-bar strong{font-size:13px}.preview-bar small{display:block;color:var(--muted);font-size:11.5px;font-weight:500}.preview-tools{display:inline-flex;gap:3px;padding:3px;background:#eceef1;border-radius:9px}.preview-tools button{border:0;background:transparent;color:#59616d;border-radius:7px;padding:6px 10px;cursor:pointer;font-size:11.5px;font-weight:800}.preview-tools button.is-active{background:#fff;color:#171717;box-shadow:0 1px 3px rgba(0,0,0,.12)}.preview-stage{position:relative;overflow:hidden;background:#e9eaec}.preview-frame{border:0;transform-origin:top left;display:block;background:#fff}.preview-note{display:flex;align-items:center;gap:8px;margin:0;padding:10px 14px;border-top:1px solid var(--line);background:#fafbfc;color:#59616d;font-size:11.5px}.preview-jump{margin-left:auto;color:var(--red);font-weight:800;white-space:nowrap}.content-field.is-picked{border-color:var(--red);box-shadow:0 0 0 3px rgba(227,25,25,.13)}@media(max-width:1180px){.editor-shell{grid-template-columns:minmax(0,1fr)}.preview-pane{position:static}}.section-location{display:flex;gap:9px;align-items:center;background:#f0f5ff;color:#27518e;border:1px solid #d9e6fb;border-radius:10px;padding:11px 14px;margin-bottom:18px}.media-guide{display:grid;grid-template-columns:auto 1fr;gap:4px 13px;background:#fff8ec;border:1px solid #f2d7a6;border-radius:12px;padding:15px 17px;margin:-4px 0 18px;color:#714b0e}.media-guide-icon{grid-row:1/3;width:30px;height:30px;border-radius:50%;display:grid;place-items:center;background:#fff1cf;font-weight:900}.media-guide strong{font-size:14px}.media-guide span:last-child{font-size:12px}.content-list{display:grid;gap:14px}.content-field{padding:19px;border:1px solid var(--line);border-radius:12px;background:#fff}.content-field-media{padding:22px}.field-head{margin-bottom:13px}.field-title{font-weight:800;font-size:15px}.field-help{color:var(--muted);font-size:12px;margin-top:3px}.media-kind{display:inline-flex;align-items:center;border-radius:99px;background:#f2f3f5;color:#4e5661;padding:3px 8px;margin-left:7px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;vertical-align:2px}.primary-label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:#59616d;font-weight:800;margin-bottom:7px}.translations{margin-top:12px;border:1px solid var(--line);border-radius:10px;background:#fafbfc}.translations summary{cursor:pointer;padding:10px 12px;font-weight:700;color:#4f5865;list-style:none}.translations summary::-webkit-details-marker{display:none}.translations summary:before{content:'+';display:inline-grid;place-items:center;width:20px;height:20px;margin-right:7px;border-radius:50%;background:#eef1f4}.translations[open] summary:before{content:'−'}.translation-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:2px 12px 12px}.translation-grid label.field{margin:0}.lang-label{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);font-weight:800}.media-picker{border:1px solid #dde0e4;border-radius:13px;overflow:hidden}.media-method-head{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:13px 15px;background:#fafbfc;border-bottom:1px solid #e7e8ea}.media-method-head>span{color:#4e5661;font-size:12px}.media-methods{display:inline-flex;padding:3px;background:#eceef1;border-radius:9px;flex:0 0 auto}.media-method{border:0;background:transparent;color:#59616d;border-radius:7px;padding:7px 11px;cursor:pointer;font-size:12px;font-weight:800}.media-method.is-active{background:#fff;color:#171717;box-shadow:0 1px 3px rgba(0,0,0,.12)}.media-panel{padding:15px}.media-panel-url label.field{margin:0}.media-url-example{display:block;margin-top:2px;color:#7a818a;font-size:11px;font-weight:400}.media-current{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:11px 15px;border-top:1px solid #e7e8ea;background:#fafbfc;color:#526070;font-size:12px}.media-status{display:flex;align-items:center;gap:8px}.media-status:before{content:'';width:8px;height:8px;border-radius:50%;background:#26a269;box-shadow:0 0 0 3px #dbf4e7;flex:0 0 auto}.media-picker.is-reset .media-status:before{background:#d28a18;box-shadow:0 0 0 3px #fff0cc}.media-reset{border:0;background:none;color:#a52b23;font-weight:800;cursor:pointer;padding:2px 0;font-size:11px;white-space:nowrap}.collection-editor{margin-bottom:20px}.collection-editor-head{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:20px 22px}.collection-editor-head h2{margin:0 0 4px;font-size:19px}.collection-editor-head p{margin:0;color:var(--muted);font-size:12px}.collection-count{display:inline-flex;margin-left:6px;padding:2px 8px;border-radius:99px;background:#f1f2f4;color:#59616d;font-size:11px}.collection-list{display:grid;gap:14px}.collection-item{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px}.collection-item-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.collection-item-head>div:first-child{display:flex;align-items:center;gap:9px}.collection-index{display:grid;place-items:center;width:28px;height:28px;border-radius:8px;background:#fff0f0;color:var(--red);font-size:11px;font-weight:900}.collection-actions{display:flex;gap:5px}.collection-actions button{border:1px solid var(--line);background:#fff;border-radius:7px;padding:5px 9px;cursor:pointer;font-size:11px;font-weight:800}.collection-actions .collection-remove{color:#b42318;border-color:#efc8c5}.collection-copy-grid{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-top:14px}.collection-copy-grid .field{margin:0}.collection-description{grid-column:1/-1}.collection-description textarea.input{min-height:80px}.collection-empty{padding:28px;border:1px dashed #ccd0d5;border-radius:13px;color:var(--muted);text-align:center;background:#fafbfc}.single-field label.field{margin-bottom:0}.form-footer{position:sticky;bottom:16px;margin-top:20px;padding:13px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow);z-index:10}@media(max-width:850px){.translation-grid,.collection-copy-grid{grid-template-columns:1fr}.collection-description{grid-column:auto}}@media(max-width:620px){.content-field-media,.collection-item{padding:16px}.collection-editor-head{align-items:flex-start;flex-direction:column}.collection-editor-head .button{width:100%}.media-method-head{align-items:flex-start;flex-direction:column}.media-methods{width:100%}.media-method{flex:1}.media-current{align-items:flex-start;flex-direction:column}.upload-zone{grid-template-columns:1fr;text-align:center}.upload-preview{margin:auto}}
</style>@endpush
@section('content')
<div class="editor-wrap">
<div class="editor-shell" data-editor-shell>
 <div class="editor-pane">
 <div class="page-head"><div><a href="{{ route('cms.content.index') }}" style="color:var(--muted)">← Kembali ke daftar bagian</a><h1 style="margin-top:8px">{{ $groupName }}</h1><p>Edit teks dan media yang terlihat pada bagian ini.</p></div><div style="display:flex;gap:8px;flex-wrap:wrap"><button class="button" type="button" data-preview-toggle>Sembunyikan pratinjau</button><a class="button" href="{{ route('home').($fields->first()['anchor'] ?? '#top') }}" target="_blank">Lihat hasil di website ↗</a></div></div>
 <div class="section-location"><strong>Bagian website:</strong><span>{{ $groupName }}</span></div>
 @if($fields->contains(fn($field) => in_array($field['type'], ['image','video'], true)))
 <div class="media-guide"><span class="media-guide-icon">i</span><strong>Cukup pilih salah satu cara</strong><span>Gunakan <b>Upload file</b> untuk mengambil media dari perangkat. Pilih <b>Gunakan URL</b> hanya jika file sudah tersimpan online. Media lama tidak berubah sebelum tombol simpan ditekan.</span></div>
 @endif
 <form method="post" action="{{ route('cms.content.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
  @if($collectionName)
  @php($initialCollectionItems=collect($collectionItems)->mapWithKeys(fn($item,$index)=>[($item['key']??'item-'.$index)=>$item])->all())
  @php($editorItems=old('collection_items',$initialCollectionItems))
  <input type="hidden" name="collection_name" value="{{ $collectionName }}">
  <section class="collection-editor" data-collection-editor data-collection-type="{{ $collectionName }}">
   <div class="card collection-editor-head"><div><h2>{{ $collectionName==='videos' ? 'Daftar Video Portofolio' : 'Daftar Foto Galeri' }} <span class="collection-count"><span data-collection-count>{{ count($editorItems) }}</span>&nbsp;item</span></h2><p>Jumlah item bebas. Tambahkan, hapus, atau ubah urutannya sesuai kebutuhan.</p></div><button class="button button-primary" type="button" data-add-item>+ Tambah {{ $collectionName==='videos' ? 'Video' : 'Foto' }}</button></div>
   <div class="collection-list" data-collection-list>
    @foreach($editorItems as $itemKey=>$collectionItem)
     @include('cms.partials.collection-item',['item'=>$collectionItem,'itemKey'=>$itemKey,'position'=>$loop->iteration,'collectionName'=>$collectionName])
    @endforeach
   </div>
   <div class="collection-empty" data-collection-empty @if(count($editorItems)) hidden @endif>Belum ada item. Klik <strong>Tambah {{ $collectionName==='videos' ? 'Video' : 'Foto' }}</strong> untuk mulai.</div>
   <template data-collection-template>@include('cms.partials.collection-item',['item'=>['source'=>'upload','title'=>'','category'=>'Video','description'=>'','meta'=>''],'itemKey'=>'__KEY__','position'=>'__NUMBER__','collectionName'=>$collectionName])</template>
  </section>
  @endif
  <section class="content-list">
  @foreach($fields as $field) @php($item=$saved->get($field['key'])) @php($formKey=str_replace('.','__',$field['key']))
   <div class="content-field {{ in_array($field['type'],['image','video']) ? 'content-field-media' : '' }}" data-field-key="{{ $field['key'] }}"><div class="field-head"><div class="field-title">{{ $field['label'] ?: 'Konten website' }} @if(in_array($field['type'],['image','video']))<span class="media-kind">{{ $field['type']==='video' ? 'Video' : 'Foto' }}</span>@endif</div><div class="field-help">{{ $field['help'] ?? 'Ubah isi yang tampil pada website.' }}</div></div>
   @if(in_array($field['type'],['image','video']))
    @php($currentMedia=$item?->value_id)
    @php($selectedSource=old("contents.$formKey.source",$currentMedia && str_starts_with($currentMedia,'http') ? 'url' : 'upload'))
    <div class="media-picker" data-media-picker>
     <input type="hidden" name="contents[{{ $formKey }}][source]" value="{{ $selectedSource }}" data-media-source>
     <div class="media-method-head"><span>Pilih sumber {{ $field['type']==='video' ? 'video' : 'foto' }}:</span><div class="media-methods" role="tablist" aria-label="Pilih sumber media"><button class="media-method" type="button" role="tab" data-media-method="upload">↑ Upload file</button><button class="media-method" type="button" role="tab" data-media-method="url">⌁ Gunakan URL</button></div></div>
     <div class="media-panel" data-media-panel="upload">@include('cms.partials.upload',['name'=>"media[$formKey]",'accept'=>$field['type']==='video'?'video/mp4,video/webm,video/quicktime':'image/jpeg,image/png,image/webp,image/gif,image/svg+xml','title'=>$field['type']==='video'?'Pilih video dari perangkat':'Pilih foto dari perangkat','hint'=>$field['type']==='video'?'MP4, WebM, MOV · maks. 200 MB':'JPG, PNG, WebP, GIF, SVG · maks. 15 MB','current'=>$currentMedia])</div>
     <div class="media-panel media-panel-url" data-media-panel="url" hidden><label class="field">URL langsung {{ $field['type']==='video' ? 'file video' : 'file gambar' }}<input class="input" name="contents[{{ $formKey }}][id]" value="{{ old("contents.$formKey.id",$currentMedia) }}" placeholder="{{ $field['type']==='video' ? 'https://contoh.com/video/company-profile.mp4' : 'https://contoh.com/images/foto-galeri.jpg' }}" data-media-url><span class="hint">Alamat harus diawali <b>https://</b> dan langsung membuka file {{ $field['type']==='video' ? 'MP4, WebM, atau MOV' : 'gambar' }}, bukan halaman YouTube/Google Drive.</span><span class="media-url-example">Contoh benar: {{ $field['type']==='video' ? 'https://cdn.contoh.com/video-profil.mp4' : 'https://cdn.contoh.com/foto-gudang.jpg' }}</span></label></div>
     <div class="media-current"><span class="media-status" data-media-status>@if($currentMedia){{ str_starts_with($currentMedia,'http') ? 'URL saat ini sedang digunakan.' : 'File upload saat ini sedang digunakan.' }}@else Media bawaan website sedang digunakan.@endif</span>@if($currentMedia)<button class="media-reset" type="button" data-media-reset>Gunakan kembali media bawaan</button>@endif</div>
    </div>
   @elseif(!($field['translatable'] ?? true))
    <div class="single-field"><label class="field"><span class="primary-label">Isi yang digunakan</span><input class="input" name="contents[{{ $formKey }}][id]" value="{{ old("contents.$formKey.id",$item?->value_id ?? $field['default']) }}"></label></div>
   @else
    @php($currentId=$item?->value_id)
    <label class="field" style="margin-bottom:0"><span class="primary-label">Bahasa Indonesia</span>@if($field['type']==='textarea')<textarea class="input" name="contents[{{ $formKey }}][id]">{{ old("contents.$formKey.id",$currentId ?? $field['default']) }}</textarea>@else<input class="input" name="contents[{{ $formKey }}][id]" value="{{ old("contents.$formKey.id",$currentId ?? $field['default']) }}">@endif</label>
    <details class="translations"><summary>Terjemahan opsional</summary><div class="translation-grid">@foreach(['en'=>'Bahasa Inggris','zh'=>'Bahasa Mandarin'] as $lang=>$langLabel) @php($current=$item?->{'value_'.$lang})<label class="field"><span class="lang-label">{{ $langLabel }}</span>@if($field['type']==='textarea')<textarea class="input" name="contents[{{ $formKey }}][{{ $lang }}]" placeholder="Kosongkan untuk memakai terjemahan bawaan">{{ old("contents.$formKey.$lang",$current ?? '') }}</textarea>@else<input class="input" name="contents[{{ $formKey }}][{{ $lang }}]" value="{{ old("contents.$formKey.$lang",$current ?? '') }}" placeholder="Kosongkan untuk memakai terjemahan bawaan">@endif</label>@endforeach</div></details>
   @endif</div>
  @endforeach
  </section>
  <div class="card form-footer"><span><strong>{{ $groupName }}</strong><br><small style="color:var(--muted)">{{ $fields->count() }} kolom konten @if($collectionName)· <span data-footer-media-count>{{ count($editorItems) }}</span> media @endif</small></span><button class="button button-primary" type="submit">Simpan perubahan</button></div>
 </form>
 </div>
 <aside class="preview-pane" data-preview-pane>
  <div class="preview-bar"><span><strong>Pratinjau langsung</strong><small>Klik teks atau foto di pratinjau untuk membuka kolomnya.</small></span>
   <span class="preview-tools"><button type="button" data-preview-width="desktop" class="is-active">Desktop</button><button type="button" data-preview-width="mobile">Ponsel</button><button type="button" data-preview-reload>Muat ulang</button></span>
  </div>
  <div class="preview-stage" data-preview-stage><iframe class="preview-frame" data-preview-frame title="Pratinjau website" src="{{ route('home') }}?cms_preview=1{{ $fields->first()['anchor'] ?? '' }}"></iframe></div>
  <p class="preview-note"><span data-preview-message>Perubahan teks langsung terlihat di sini sebelum disimpan.</span><a class="preview-jump" data-preview-jump hidden href="#">Buka bagian itu &rarr;</a></p>
 </aside>
</div>
</div>
@endsection
@if($collectionName)
@push('scripts')<script>
(function(){var editor=document.querySelector('[data-collection-editor]');if(!editor)return;var list=editor.querySelector('[data-collection-list]'),template=editor.querySelector('[data-collection-template]'),empty=editor.querySelector('[data-collection-empty]');function refresh(){var items=list.querySelectorAll('[data-collection-item]');items.forEach(function(item,index){item.querySelector('[data-collection-index]').textContent=index+1;item.querySelector('[data-collection-number]').textContent=index+1;var up=item.querySelector('[data-move-item="up"]'),down=item.querySelector('[data-move-item="down"]');up.disabled=index===0;down.disabled=index===items.length-1});editor.querySelector('[data-collection-count]').textContent=items.length;var footer=document.querySelector('[data-footer-media-count]');if(footer)footer.textContent=items.length;empty.hidden=items.length>0}function bind(item){item.querySelector('[data-remove-item]').addEventListener('click',function(){item.remove();refresh()});item.querySelector('[data-move-item="up"]').addEventListener('click',function(){var previous=item.previousElementSibling;if(previous)list.insertBefore(item,previous);refresh()});item.querySelector('[data-move-item="down"]').addEventListener('click',function(){var next=item.nextElementSibling;if(next)list.insertBefore(next,item);refresh()})}list.querySelectorAll('[data-collection-item]').forEach(bind);editor.querySelector('[data-add-item]').addEventListener('click',function(){var key='new-'+Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,7),number=list.querySelectorAll('[data-collection-item]').length+1,holder=document.createElement('div');holder.innerHTML=template.innerHTML.replaceAll('__KEY__',key).replaceAll('__NUMBER__',String(number));var item=holder.firstElementChild,input=item.querySelector('[data-upload-input]'),label=item.querySelector('[data-upload-zone]');if(input&&label){input.id='collection-upload-'+key;label.setAttribute('for',input.id)}list.appendChild(item);bind(item);if(window.initCmsMediaInputs)window.initCmsMediaInputs(item);refresh();item.scrollIntoView({behavior:'smooth',block:'center'})});refresh()})();
</script>@endpush
@endif
@push('scripts')<script>
(function(){
 var shell=document.querySelector('[data-editor-shell]');if(!shell)return;
 var frame=shell.querySelector('[data-preview-frame]'),stage=shell.querySelector('[data-preview-stage]');
 var message=shell.querySelector('[data-preview-message]'),jump=shell.querySelector('[data-preview-jump]');
 var toggle=document.querySelector('[data-preview-toggle]');
 var directory=@json($fieldDirectory);
 var sectionUrl=@json(route('cms.content.edit', '__SLUG__'));
 var ORIGIN=window.location.origin,ready=false;

 function send(payload){if(!ready||!frame.contentWindow)return;payload.source='dmc-cms-editor';frame.contentWindow.postMessage(payload,ORIGIN);}

 /* The site needs desktop width to look like itself, so the frame is rendered at a
    fixed logical width and scaled down to whatever room the pane has. */
 var sizes={desktop:[1280,860],mobile:[390,844]},mode='desktop';
 function layout(){
  var size=sizes[mode],available=stage.clientWidth||size[0],scale=Math.min(1,available/size[0]);
  frame.style.width=size[0]+'px';frame.style.height=size[1]+'px';
  frame.style.transform='scale('+scale+')';
  stage.style.height=Math.round(size[1]*scale)+'px';
 }
 window.addEventListener('resize',layout);
 frame.addEventListener('load',layout);
 layout();

 Array.prototype.forEach.call(shell.querySelectorAll('[data-preview-width]'),function(button){
  button.addEventListener('click',function(){
   mode=button.getAttribute('data-preview-width');
   Array.prototype.forEach.call(shell.querySelectorAll('[data-preview-width]'),function(other){other.classList.toggle('is-active',other===button);});
   layout();
  });
 });
 shell.querySelector('[data-preview-reload]').addEventListener('click',function(){ready=false;frame.contentWindow.location.reload();});

 if(toggle){
  function applyToggle(hidden){
   shell.classList.toggle('is-solo',hidden);
   toggle.textContent=hidden?'Tampilkan pratinjau':'Sembunyikan pratinjau';
   if(!hidden)window.setTimeout(layout,0);
  }
  var stored=null;try{stored=window.localStorage.getItem('dmc-cms-preview-hidden');}catch(e){}
  applyToggle(stored==='1');
  toggle.addEventListener('click',function(){
   var hidden=!shell.classList.contains('is-solo');
   applyToggle(hidden);
   try{window.localStorage.setItem('dmc-cms-preview-hidden',hidden?'1':'0');}catch(e){}
  });
 }

 function fieldNode(key){return shell.querySelector('[data-field-key="'+key+'"]');}
 function pick(keys){
  for(var i=0;i<keys.length;i++){
   var node=fieldNode(keys[i]);
   if(!node)continue;
   Array.prototype.forEach.call(shell.querySelectorAll('.content-field.is-picked'),function(n){n.classList.remove('is-picked');});
   node.classList.add('is-picked');
   node.scrollIntoView({behavior:'smooth',block:'center'});
   var input=node.querySelector('textarea,input:not([type=hidden]):not([type=file])');
   if(input)window.setTimeout(function(){input.focus({preventScroll:true});},320);
   message.textContent='Kolomnya sudah dibuka di sebelah kiri.';
   jump.hidden=true;
   return;
  }
  for(var j=0;j<keys.length;j++){
   var info=directory[keys[j]];
   if(!info)continue;
   message.textContent='Yang Anda klik diatur di bagian \u201c'+info.group+'\u201d.';
   jump.hidden=false;
   jump.setAttribute('href',sectionUrl.replace('__SLUG__',info.slug));
   return;
  }
  message.textContent='Bagian yang diklik belum punya kolom di CMS.';
  jump.hidden=true;
 }

 window.addEventListener('message',function(event){
  if(event.origin!==ORIGIN)return;
  var data=event.data;if(!data||data.source!=='dmc-cms-preview')return;
  if(data.type==='ready'){ready=true;return;}
  if(data.type==='pick')pick(data.keys||[]);
 });

 var timer=null;
 Array.prototype.forEach.call(shell.querySelectorAll('[data-field-key]'),function(node){
  var key=node.getAttribute('data-field-key'),input=node.querySelector('[name$="[id]"]');
  if(!input)return;
  input.addEventListener('focus',function(){send({type:'focus',key:key});});
  input.addEventListener('input',function(){
   var value=input.value;
   window.clearTimeout(timer);
   timer=window.setTimeout(function(){
    if(key.indexOf('text.')===0){send({type:'text',key:key,value:value});return;}
    if(key.indexOf('dynamic.business.')===0){var parts=key.split('.');send({type:'dynamic',line:parts[2],field:parts[3],value:value});}
   },160);
  });
 });
})();
</script>@endpush

@extends('cms.layouts.app')
@section('title','Edit '.$groupName)
@push('styles')<style>
.editor-wrap{max-width:1060px}.section-location{display:flex;gap:9px;align-items:center;background:#f0f5ff;color:#27518e;border:1px solid #d9e6fb;border-radius:10px;padding:11px 14px;margin-bottom:18px}.content-field{padding:21px 0;border-bottom:1px solid var(--line)}.content-field:last-child{border-bottom:0}.field-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:11px}.field-title{font-weight:800;font-size:15px}.field-code{font:11px/1.4 ui-monospace,monospace;color:#8a9098}.field-location{display:inline-flex;align-items:center;gap:6px;background:#f5f6f7;color:#59616d;border-radius:999px;padding:5px 9px;font-size:11px;white-space:nowrap}.languages{display:grid;grid-template-columns:repeat(3,1fr);gap:11px}.lang-label{font-size:10px;text-transform:uppercase;letter-spacing:.09em;color:var(--muted);font-weight:800}.media-row{display:grid;grid-template-columns:minmax(240px,.85fr) minmax(300px,1.15fr);gap:14px}.form-footer{position:sticky;bottom:16px;margin-top:20px;padding:13px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow);z-index:10}@media(max-width:850px){.languages,.media-row{grid-template-columns:1fr}.field-head{display:block}.field-location{margin-top:8px}}
</style>@endpush
@section('content')
<div class="editor-wrap">
 <div class="page-head"><div><a href="{{ route('cms.content.index') }}" style="color:var(--muted)">← Semua halaman/bagian</a><h1 style="margin-top:8px">{{ $groupName }}</h1><p>Hanya field untuk bagian ini yang ditampilkan.</p></div><a class="button" href="{{ route('home').($fields->first()['anchor'] ?? '#top') }}" target="_blank">Lihat lokasi di frontend ↗</a></div>
 <div class="section-location"><strong>◉ Lokasi edit:</strong><span>Frontend → {{ $groupName }}</span></div>
 <form method="post" action="{{ route('cms.content.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
  <section class="card card-pad">
  @foreach($fields as $field) @php($item=$saved->get($field['key'])) @php($formKey=str_replace('.','__',$field['key']))
   <div class="content-field"><div class="field-head"><div><div class="field-title">{{ $field['label'] ?: 'Konten tanpa judul' }}</div><div class="field-code">{{ $field['key'] }}</div></div><a class="field-location" href="{{ route('home').$field['anchor'] }}" target="_blank">◉ Frontend → {{ $field['group'] }} ↗</a></div>
   @if(in_array($field['type'],['image','video']))
    <div class="media-row"><label class="field">URL pengganti<input class="input" name="contents[{{ $formKey }}][id]" value="{{ old("contents.$formKey.id",$item?->value_id) }}" placeholder="Kosong = media bawaan"><span class="hint">Tempel URL atau upload file di sebelahnya.</span></label>@include('cms.partials.upload',['name'=>"media[$formKey]",'accept'=>$field['type']==='video'?'video/mp4,video/webm,video/quicktime':'image/*','title'=>$field['type']==='video'?'Upload video':'Upload gambar','hint'=>$field['type']==='video'?'MP4, WebM, MOV · maks. 200 MB':'JPG, PNG, WebP, SVG · maks. 15 MB','current'=>$item?->value_id])</div>
   @else
    <div class="languages">@foreach(['id'=>'Indonesia','en'=>'English','zh'=>'中文'] as $lang=>$langLabel) @php($current=$item?->{'value_'.$lang})<label class="field"><span class="lang-label">{{ $langLabel }}</span>@if($field['type']==='textarea')<textarea class="input" name="contents[{{ $formKey }}][{{ $lang }}]" placeholder="{{ $lang==='id'?$field['default']:'Kosong = terjemahan bawaan' }}">{{ old("contents.$formKey.$lang",$current ?? ($lang==='id'?$field['default']:'')) }}</textarea>@else<input class="input" name="contents[{{ $formKey }}][{{ $lang }}]" value="{{ old("contents.$formKey.$lang",$current ?? ($lang==='id'?$field['default']:'')) }}" placeholder="{{ $lang==='id'?$field['default']:'Kosong = terjemahan bawaan' }}">@endif</label>@endforeach</div>
   @endif</div>
  @endforeach
  </section>
  <div class="card form-footer"><span><strong>{{ $groupName }}</strong><br><small style="color:var(--muted)">{{ $fields->count() }} field akan disimpan</small></span><button class="button button-primary" type="submit">Simpan perubahan</button></div>
 </form>
</div>
@endsection

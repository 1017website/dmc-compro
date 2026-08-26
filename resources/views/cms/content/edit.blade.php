@extends('cms.layouts.app')
@section('title','Edit '.$groupName)
@push('styles')<style>
.editor-wrap{max-width:1060px}.section-location{display:flex;gap:9px;align-items:center;background:#f0f5ff;color:#27518e;border:1px solid #d9e6fb;border-radius:10px;padding:11px 14px;margin-bottom:18px}.content-list{display:grid;gap:14px}.content-field{padding:19px;border:1px solid var(--line);border-radius:12px;background:#fff}.field-head{margin-bottom:13px}.field-title{font-weight:800;font-size:15px}.field-help{color:var(--muted);font-size:12px;margin-top:3px}.primary-label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:#59616d;font-weight:800;margin-bottom:7px}.translations{margin-top:12px;border:1px solid var(--line);border-radius:10px;background:#fafbfc}.translations summary{cursor:pointer;padding:10px 12px;font-weight:700;color:#4f5865;list-style:none}.translations summary::-webkit-details-marker{display:none}.translations summary:before{content:'+';display:inline-grid;place-items:center;width:20px;height:20px;margin-right:7px;border-radius:50%;background:#eef1f4}.translations[open] summary:before{content:'−'}.translation-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:2px 12px 12px}.translation-grid label.field{margin:0}.lang-label{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);font-weight:800}.media-row{display:grid;grid-template-columns:minmax(240px,.85fr) minmax(300px,1.15fr);gap:14px}.single-field label.field,.media-row label.field{margin-bottom:0}.form-footer{position:sticky;bottom:16px;margin-top:20px;padding:13px 16px;display:flex;align-items:center;justify-content:space-between;box-shadow:var(--shadow);z-index:10}@media(max-width:850px){.translation-grid,.media-row{grid-template-columns:1fr}}
</style>@endpush
@section('content')
<div class="editor-wrap">
 <div class="page-head"><div><a href="{{ route('cms.content.index') }}" style="color:var(--muted)">← Kembali ke daftar bagian</a><h1 style="margin-top:8px">{{ $groupName }}</h1><p>Edit teks dan media yang terlihat pada bagian ini.</p></div><a class="button" href="{{ route('home').($fields->first()['anchor'] ?? '#top') }}" target="_blank">Lihat hasil di website ↗</a></div>
 <div class="section-location"><strong>Bagian website:</strong><span>{{ $groupName }}</span></div>
 <form method="post" action="{{ route('cms.content.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
  <section class="content-list">
  @foreach($fields as $field) @php($item=$saved->get($field['key'])) @php($formKey=str_replace('.','__',$field['key']))
   <div class="content-field"><div class="field-head"><div class="field-title">{{ $field['label'] ?: 'Konten website' }}</div><div class="field-help">{{ $field['help'] ?? 'Ubah isi yang tampil pada website.' }}</div></div>
   @if(in_array($field['type'],['image','video']))
    <div class="media-row"><label class="field">Gunakan URL media<input class="input" name="contents[{{ $formKey }}][id]" value="{{ old("contents.$formKey.id",$item?->value_id) }}" placeholder="Boleh dikosongkan jika menggunakan media bawaan"><span class="hint">Tempel URL hanya jika file sudah tersedia di internet.</span></label>@include('cms.partials.upload',['name'=>"media[$formKey]",'accept'=>$field['type']==='video'?'video/mp4,video/webm,video/quicktime':'image/*','title'=>$field['type']==='video'?'Upload video baru':'Upload gambar baru','hint'=>$field['type']==='video'?'MP4, WebM, MOV · maks. 200 MB':'JPG, PNG, WebP, SVG · maks. 15 MB','current'=>$item?->value_id])</div>
   @elseif(!($field['translatable'] ?? true))
    <div class="single-field"><label class="field"><span class="primary-label">Isi yang digunakan</span><input class="input" name="contents[{{ $formKey }}][id]" value="{{ old("contents.$formKey.id",$item?->value_id ?? $field['default']) }}"></label></div>
   @else
    @php($currentId=$item?->value_id)
    <label class="field" style="margin-bottom:0"><span class="primary-label">Bahasa Indonesia</span>@if($field['type']==='textarea')<textarea class="input" name="contents[{{ $formKey }}][id]">{{ old("contents.$formKey.id",$currentId ?? $field['default']) }}</textarea>@else<input class="input" name="contents[{{ $formKey }}][id]" value="{{ old("contents.$formKey.id",$currentId ?? $field['default']) }}">@endif</label>
    <details class="translations"><summary>Terjemahan opsional</summary><div class="translation-grid">@foreach(['en'=>'Bahasa Inggris','zh'=>'Bahasa Mandarin'] as $lang=>$langLabel) @php($current=$item?->{'value_'.$lang})<label class="field"><span class="lang-label">{{ $langLabel }}</span>@if($field['type']==='textarea')<textarea class="input" name="contents[{{ $formKey }}][{{ $lang }}]" placeholder="Kosongkan untuk memakai terjemahan bawaan">{{ old("contents.$formKey.$lang",$current ?? '') }}</textarea>@else<input class="input" name="contents[{{ $formKey }}][{{ $lang }}]" value="{{ old("contents.$formKey.$lang",$current ?? '') }}" placeholder="Kosongkan untuk memakai terjemahan bawaan">@endif</label>@endforeach</div></details>
   @endif</div>
  @endforeach
  </section>
  <div class="card form-footer"><span><strong>{{ $groupName }}</strong><br><small style="color:var(--muted)">{{ $fields->count() }} kolom konten</small></span><button class="button button-primary" type="submit">Simpan perubahan</button></div>
 </form>
</div>
@endsection

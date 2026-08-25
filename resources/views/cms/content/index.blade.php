@extends('cms.layouts.app')
@section('title','Konten Website')
@push('styles')<style>
.page-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.page-card{padding:22px;display:flex;flex-direction:column;min-height:210px;transition:.2s ease}.page-card:hover{transform:translateY(-3px);border-color:#c9cbd0;box-shadow:var(--shadow)}.page-icon{width:44px;height:44px;border-radius:12px;background:#fff0f0;color:var(--red);display:grid;place-items:center;font-size:19px;margin-bottom:18px}.page-card h2{font-size:18px;margin:0 0 6px}.page-card p{color:var(--muted);margin:0 0 18px}.page-meta{margin-top:auto;display:flex;align-items:center;justify-content:space-between;padding-top:15px;border-top:1px solid var(--line);font-size:12px}.location-chip{display:inline-flex;align-items:center;gap:6px;color:#59616d;background:#f1f3f5;border-radius:999px;padding:5px 9px}@media(max-width:1050px){.page-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.page-grid{grid-template-columns:1fr}}
</style>@endpush
@section('content')
<div class="page-head"><div><h1>Pilih halaman atau bagian</h1><p>Konten dipisahkan berdasarkan lokasi tampilnya agar lebih mudah ditemukan.</p></div><a class="button" href="{{ route('home') }}" target="_blank">Buka frontend ↗</a></div>
<div class="page-grid">
@foreach($groups as $groupName=>$fields)
 @php($mediaCount=$fields->whereIn('type',['image','video'])->count())
 <a class="card page-card" href="{{ route('cms.content.edit',Str::slug($groupName)) }}">
  <span class="page-icon">{{ ['Header & Navigasi'=>'⌘','Hero'=>'◆','Produk'=>'▦','Produk Dinamis'=>'◫','Galeri'=>'▧','Kontak'=>'✉','Footer'=>'⌄','SEO'=>'⌕'][$groupName] ?? '✦' }}</span>
  <h2>{{ $groupName }}</h2><p>Edit teks, link, dan media khusus bagian {{ Str::lower($groupName) }}.</p>
  <div class="page-meta"><span class="location-chip">◉ Frontend → {{ $groupName }}</span><strong>{{ $fields->count() }} field{{ $mediaCount ? ' · '.$mediaCount.' media' : '' }}</strong></div>
 </a>
@endforeach
</div>
@endsection

@extends('cms.layouts.app')
@section('title','Konten Website')
@push('styles')<style>
.page-guide{padding:15px 17px;margin-bottom:18px;background:#f0f5ff;border-color:#d9e6fb;color:#27518e}.page-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.page-card{padding:22px;display:flex;flex-direction:column;min-height:205px;transition:.2s ease}.page-card:hover{transform:translateY(-3px);border-color:#c9cbd0;box-shadow:var(--shadow)}.page-number{width:42px;height:42px;border-radius:12px;background:#fff0f0;color:var(--red);display:grid;place-items:center;font-size:13px;font-weight:800;margin-bottom:18px}.page-card h2{font-size:18px;margin:0 0 6px}.page-card p{color:var(--muted);margin:0 0 18px}.page-meta{margin-top:auto;display:flex;align-items:center;justify-content:space-between;gap:10px;padding-top:15px;border-top:1px solid var(--line);font-size:12px}.edit-label{color:#59616d}.page-arrow{font-weight:800;color:var(--red)}@media(max-width:1050px){.page-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:620px){.page-grid{grid-template-columns:1fr}}
</style>@endpush
@section('content')
<div class="page-head"><div><h1>Pilih bagian website</h1><p>Pilih berdasarkan lokasi konten yang ingin diubah.</p></div><a class="button" href="{{ route('home') }}" target="_blank">Lihat website ↗</a></div>
<div class="card page-guide"><strong>Cara mengedit:</strong> pilih bagian, ubah isi Bahasa Indonesia, lalu tekan <strong>Simpan perubahan</strong>. Terjemahan bahasa lain bersifat opsional.</div>
<div class="page-grid">
@foreach($groups as $groupName=>$fields)
 @php($mediaCount=$collectionCounts[$groupName] ?? $fields->whereIn('type',['image','video'])->count())
 <a class="card page-card" href="{{ route('cms.content.edit',Str::slug($groupName)) }}">
  <span class="page-number">{{ str_pad((string) $loop->iteration,2,'0',STR_PAD_LEFT) }}</span>
  <h2>{{ $groupName }}</h2><p>Ubah teks{{ $mediaCount ? ', gambar, atau video' : '' }} pada bagian {{ Str::lower($groupName) }}.</p>
  <div class="page-meta"><span class="edit-label">{{ $fields->count() }} kolom konten{{ $mediaCount ? ' · '.$mediaCount.' media' : '' }}</span><span class="page-arrow">Edit →</span></div>
 </a>
@endforeach
</div>
@endsection

@extends('cms.layouts.app')
@section('title','SEO & Tracking')
@section('content')
<div class="page-head"><div><h1>SEO, iklan & analytics</h1><p>Isi ID dari akun resmi Anda. Script tracking akan dipasang otomatis tanpa menyentuh template.</p></div></div>
<form method="post" action="{{ route('cms.settings.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
<div class="grid two-col">
 <div class="grid">
  <section class="card card-pad"><h2 style="margin-top:0">SEO halaman utama</h2>
   <label class="field">Judul SEO <input class="input" name="seo_title" maxlength="70" value="{{ old('seo_title',$settings['seo_title']??'') }}"><span class="hint">Ideal 50–60 karakter.</span></label>
   <label class="field">Deskripsi SEO <textarea class="input" name="seo_description" maxlength="180">{{ old('seo_description',$settings['seo_description']??'') }}</textarea></label>
   <label class="field">Kata kunci <input class="input" name="seo_keywords" value="{{ old('seo_keywords',$settings['seo_keywords']??'') }}" placeholder="garam industri, chemical supply, water treatment"></label>
   <div class="grid" style="grid-template-columns:1fr 1fr"><label class="field">Canonical URL <input class="input" type="url" name="canonical_url" value="{{ old('canonical_url',$settings['canonical_url']??'') }}" placeholder="https://dmcpro.co.id/"></label><label class="field">Indexing <select class="input" name="seo_robots">@foreach(['index, follow','noindex, nofollow','index, nofollow','noindex, follow'] as $option)<option @selected(($settings['seo_robots']??'index, follow')===$option)>{{ $option }}</option>@endforeach</select></label></div>
  </section>
  <section class="card card-pad"><h2 style="margin-top:0">Preview saat dibagikan</h2><label class="field">Judul Open Graph <input class="input" name="og_title" value="{{ old('og_title',$settings['og_title']??'') }}"></label><label class="field">Deskripsi Open Graph <textarea class="input" name="og_description">{{ old('og_description',$settings['og_description']??'') }}</textarea></label><label class="field">URL gambar <input class="input" type="url" name="og_image_url" value="{{ old('og_image_url',$settings['og_image']??'') }}"></label>@include('cms.partials.upload',['name'=>'og_image_file','accept'=>'image/*','title'=>'Upload gambar Open Graph','hint'=>'JPG, PNG, WebP · rekomendasi 1200 × 630 px','current'=>$settings['og_image']??null])</section>
 </div>
 <div class="grid" style="align-content:start">
  <section class="card card-pad"><h2 style="margin-top:0">Google Analytics</h2><label class="field">Measurement ID <input class="input" name="google_analytics_id" value="{{ old('google_analytics_id',$settings['google_analytics_id']??'') }}" placeholder="G-XXXXXXXXXX"><span class="hint">Dari Google Analytics 4 → Admin → Data Streams.</span></label></section>
  <section class="card card-pad"><h2 style="margin-top:0">Google Ads</h2><label class="field">Google Ads ID <input class="input" name="google_ads_id" value="{{ old('google_ads_id',$settings['google_ads_id']??'') }}" placeholder="AW-123456789"></label><label class="field">Conversion label <input class="input" name="google_ads_conversion_label" value="{{ old('google_ads_conversion_label',$settings['google_ads_conversion_label']??'') }}" placeholder="Opsional"></label></section>
  <section class="card card-pad"><h2 style="margin-top:0">Meta Ads / Pixel</h2><label class="field">Meta Pixel ID <input class="input" name="meta_pixel_id" value="{{ old('meta_pixel_id',$settings['meta_pixel_id']??'') }}" placeholder="123456789012345"><span class="hint">Hanya angka, tersedia dari Meta Events Manager.</span></label></section>
  <button class="button button-primary" type="submit">Simpan pengaturan</button>
 </div>
</div></form>
@endsection

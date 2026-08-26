@php($isVideo=$collectionName==='videos')
@php($source=$item['source'] ?? 'upload')
@php($currentUrl=$source==='default' ? null : ($item['url'] ?? null))
<article class="collection-item" data-collection-item>
    <input type="hidden" name="collection_items[{{ $itemKey }}][default_index]" value="{{ $item['default_index'] ?? '' }}">
    <div class="collection-item-head">
        <div><span class="collection-index" data-collection-index>{{ $position }}</span><strong>{{ $isVideo ? 'Video' : 'Foto' }} <span data-collection-number>{{ $position }}</span></strong></div>
        <div class="collection-actions"><button type="button" data-move-item="up" title="Pindahkan ke atas">↑</button><button type="button" data-move-item="down" title="Pindahkan ke bawah">↓</button><button class="collection-remove" type="button" data-remove-item>Hapus</button></div>
    </div>
    <div class="media-picker" data-media-picker>
        <input type="hidden" name="collection_items[{{ $itemKey }}][source]" value="{{ $source }}" data-media-source>
        <div class="media-method-head"><span>Pilih sumber {{ $isVideo ? 'video' : 'foto' }}:</span><div class="media-methods" role="tablist" aria-label="Pilih sumber media"><button class="media-method" type="button" role="tab" data-media-method="upload">↑ Upload file</button><button class="media-method" type="button" role="tab" data-media-method="url">⌁ Gunakan URL</button></div></div>
        <div class="media-panel" data-media-panel="upload">@include('cms.partials.upload',['name'=>"collection_media[$itemKey]",'accept'=>$isVideo?'video/mp4,video/webm,video/quicktime':'image/jpeg,image/png,image/webp,image/gif,image/svg+xml','title'=>$isVideo?'Pilih video dari perangkat':'Pilih foto dari perangkat','hint'=>$isVideo?'MP4, WebM, MOV · maks. 200 MB':'JPG, PNG, WebP, GIF, SVG · maks. 15 MB','current'=>$currentUrl])</div>
        <div class="media-panel media-panel-url" data-media-panel="url" hidden><label class="field">URL langsung file {{ $isVideo ? 'video' : 'gambar' }}<input class="input" name="collection_items[{{ $itemKey }}][url]" value="{{ $item['url'] ?? '' }}" placeholder="{{ $isVideo ? 'https://contoh.com/video.mp4' : 'https://contoh.com/foto.jpg' }}" data-media-url><span class="hint">Gunakan alamat http(s) yang langsung membuka file, bukan halaman YouTube atau Google Drive.</span></label></div>
        <div class="media-current"><span class="media-status" data-media-status>@if($source==='default') Media bawaan website sedang digunakan.@elseif($source==='url') URL saat ini sedang digunakan.@else File upload saat ini sedang digunakan.@endif</span></div>
    </div>
    <div class="collection-copy-grid">
        @if($isVideo)<label class="field">Kategori<input class="input" name="collection_items[{{ $itemKey }}][category]" value="{{ $item['category'] ?? 'Video' }}" placeholder="Contoh: Profil Perusahaan"></label>@endif
        <label class="field">Judul<input class="input" name="collection_items[{{ $itemKey }}][title]" value="{{ $item['title'] ?? '' }}" placeholder="Judul yang tampil di website" required></label>
        @if($isVideo)<label class="field collection-description">Deskripsi<textarea class="input" name="collection_items[{{ $itemKey }}][description]" placeholder="Ringkasan isi video">{{ $item['description'] ?? '' }}</textarea></label>@else<label class="field">Keterangan singkat<input class="input" name="collection_items[{{ $itemKey }}][meta]" value="{{ $item['meta'] ?? '' }}" placeholder="Contoh: Gudang · Distribusi"></label>@endif
    </div>
</article>

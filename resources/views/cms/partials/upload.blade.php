@php($uploadId='upload-'.md5($name))
@php($isVideo=str_contains($accept,'video'))
@php(preg_match('/maks\.\s*(\d+)\s*MB/i', $hint, $sizeMatch))
@php($maxBytes=((int)($sizeMatch[1] ?? ($isVideo ? 200 : 15)))*1024*1024)
<div class="modern-upload" data-upload data-kind="{{ $isVideo ? 'video' : 'image' }}" data-max-bytes="{{ $maxBytes }}">
    <input id="{{ $uploadId }}" type="file" name="{{ $name }}" accept="{{ $accept }}" hidden data-upload-input>
    <label class="upload-zone" for="{{ $uploadId }}" data-upload-zone>
        <span class="upload-preview" data-upload-preview>
            @if($current && $isVideo)<video src="{{ $current }}" muted preload="metadata" aria-label="Preview video saat ini"></video>@elseif($current)<img src="{{ $current }}" alt="Preview file saat ini">@else<span class="upload-icon">{{ $isVideo ? '▶' : '↑' }}</span>@endif
        </span>
        <span class="upload-copy"><strong>{{ $title }}</strong><span>Tarik file ke area ini atau <b class="upload-action">Pilih {{ $isVideo ? 'video' : 'foto' }}</b></span><small>{{ $hint }}</small></span>
    </label>
    <div class="upload-feedback" aria-live="polite">
        <span data-upload-name data-empty-label="Belum ada file baru dipilih">Belum ada file baru dipilih</span>
        <button type="button" data-upload-clear hidden>Batalkan pilihan</button>
    </div>
    <p class="upload-error" data-upload-error role="alert" hidden></p>
</div>

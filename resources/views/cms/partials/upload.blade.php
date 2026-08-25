@php($uploadId='upload-'.md5($name))
@php($isVideo=str_contains($accept,'video'))
<div class="modern-upload" data-upload>
    <input id="{{ $uploadId }}" type="file" name="{{ $name }}" accept="{{ $accept }}" hidden data-upload-input>
    <label class="upload-zone" for="{{ $uploadId }}" data-upload-zone>
        <span class="upload-preview" data-upload-preview>
            @if($current && !$isVideo)<img src="{{ $current }}" alt="Preview file saat ini">@else<span class="upload-icon">{{ $isVideo ? '▶' : '↑' }}</span>@endif
        </span>
        <span class="upload-copy"><strong>{{ $title }}</strong><span>Tarik file ke sini atau <u>pilih file</u></span><small>{{ $hint }}</small><em data-upload-name>{{ $current ? 'File saat ini sudah terpasang' : 'Belum ada file dipilih' }}</em></span>
    </label>
</div>

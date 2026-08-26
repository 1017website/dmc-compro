<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
use App\Models\SiteSetting;
use App\Services\TemplateContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(TemplateContentService $template): View
    {
        return view('cms.content.index', [
            'groups' => collect($template->fields())->groupBy('group'),
            'collectionCounts' => [
                'Galeri' => count($this->collectionItems('gallery', $template)),
                'Portofolio Video' => count($this->collectionItems('videos', $template)),
            ],
        ]);
    }

    public function edit(string $section, TemplateContentService $template): View
    {
        $groups = collect($template->fields())->groupBy('group');
        $groupName = $groups->keys()->first(fn (string $name) => Str::slug($name) === $section);
        abort_unless($groupName, 404);

        $fields = $groups->get($groupName);
        $collectionName = match ($groupName) {
            'Galeri' => 'gallery',
            'Portofolio Video' => 'videos',
            default => null,
        };
        if ($groupName === 'Galeri') {
            $fields = $fields->takeWhile(fn (array $field) => $field['type'] !== 'image')->values();
        } elseif ($groupName === 'Portofolio Video') {
            $fields = $fields->take(3)->values();
        }

        return view('cms.content.edit', [
            'groupName' => $groupName,
            'fields' => $fields,
            'saved' => SiteContent::query()->get()->keyBy('content_key'),
            'collectionName' => $collectionName,
            'collectionItems' => $collectionName ? $this->collectionItems($collectionName, $template) : [],
        ]);
    }

    public function update(Request $request, TemplateContentService $template): RedirectResponse
    {
        $submitted = $request->input('contents', []);
        foreach ($template->fields() as $field) {
            $formKey = str_replace('.', '__', $field['key']);
            if (! array_key_exists($formKey, $submitted) && ! $request->hasFile("media.{$formKey}")) {
                continue;
            }
            $row = $submitted[$formKey] ?? [];
            $existing = SiteContent::query()->where('content_key', $field['key'])->first();

            if (in_array($field['type'], ['image', 'video'], true) && ($row['source'] ?? null) === 'default') {
                $this->deleteManagedMedia($existing?->value_id);
                $existing?->delete();
                continue;
            }

            $values = [];
            foreach (['id', 'en', 'zh'] as $language) {
                $value = isset($row[$language]) ? trim((string) $row[$language]) : null;
                if ($value && in_array($field['type'], ['url', 'image', 'video'], true) && ! $this->isSafeUrl($value)) {
                    throw ValidationException::withMessages(["contents.{$formKey}.{$language}" => 'URL harus memakai http(s), tel, mailto, atau path website yang valid.']);
                }
                $values['value_'.$language] = $value === '' ? null : mb_substr($value, 0, 10000);
            }

            if ($request->hasFile("media.{$formKey}")) {
                $rules = $field['type'] === 'video'
                    ? ['file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:204800']
                    : ['file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:15360'];
                $request->validate(
                    ["media.{$formKey}" => $rules],
                    $field['type'] === 'video'
                        ? [
                            "media.{$formKey}.mimetypes" => 'Format video harus MP4, WebM, atau MOV.',
                            "media.{$formKey}.max" => 'Ukuran video maksimal 200 MB.',
                        ]
                        : [
                            "media.{$formKey}.mimes" => 'Format foto harus JPG, PNG, WebP, GIF, atau SVG.',
                            "media.{$formKey}.max" => 'Ukuran foto maksimal 15 MB.',
                        ],
                    ["media.{$formKey}" => strtolower($field['label'])],
                );
                $path = $request->file("media.{$formKey}")->store('site-media', 'public');
                $this->deleteManagedMedia($existing?->value_id);
                $values['value_id'] = Storage::url($path);
            }

            if (! array_filter($values, fn ($value) => $value !== null)) {
                $this->deleteManagedMedia($existing?->value_id);
                $existing?->delete();
                continue;
            }

            if (in_array($field['type'], ['image', 'video'], true)
                && ! $request->hasFile("media.{$formKey}")
                && $existing?->value_id !== $values['value_id']) {
                $this->deleteManagedMedia($existing?->value_id);
            }
            SiteContent::query()->updateOrCreate(['content_key' => $field['key']], [
                'group_name' => $field['group'], 'label' => $field['label'], 'type' => $field['type'], ...$values,
            ]);
        }

        if (in_array($request->input('collection_name'), ['gallery', 'videos'], true)) {
            $this->updateCollection($request, $request->input('collection_name'), $template);
        }

        return back()->with('success', 'Konten website berhasil disimpan.');
    }

    private function isSafeUrl(string $value): bool
    {
        if (str_starts_with($value, '/')) {
            return ! str_starts_with($value, '//');
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https', 'tel', 'mailto'], true);
    }

    private function deleteManagedMedia(?string $url): void
    {
        if ($url && str_starts_with($url, '/storage/site-media/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $url));
        }
    }

    private function collectionItems(string $collection, TemplateContentService $template): array
    {
        $stored = SiteSetting::query()->where('setting_key', 'media_collection_'.$collection)->value('value');
        if ($stored !== null) {
            $decoded = json_decode($stored, true);
            if (is_array($decoded)) {
                return array_values($decoded);
            }
        }

        return $template->defaultMediaCollection($collection);
    }

    private function updateCollection(Request $request, string $collection, TemplateContentService $template): void
    {
        $submitted = $request->input('collection_items', []);
        if (! is_array($submitted)) {
            throw ValidationException::withMessages(['collection_items' => 'Daftar media tidak valid.']);
        }

        $rules = [];
        foreach ($submitted as $itemKey => $item) {
            if (! preg_match('/^[a-zA-Z0-9_-]+$/', (string) $itemKey) || ! is_array($item)) {
                throw ValidationException::withMessages(['collection_items' => 'Data item media tidak valid.']);
            }
            $rules["collection_media.{$itemKey}"] = $collection === 'videos'
                ? ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:204800']
                : ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:15360'];
        }
        if ($rules !== []) {
            $request->validate($rules, [
                'collection_media.*.mimetypes' => 'Format video harus MP4, WebM, atau MOV.',
                'collection_media.*.mimes' => 'Format foto harus JPG, PNG, WebP, GIF, atau SVG.',
                'collection_media.*.max' => $collection === 'videos' ? 'Ukuran setiap video maksimal 200 MB.' : 'Ukuran setiap foto maksimal 15 MB.',
            ]);
        }

        $defaults = $template->defaultMediaCollection($collection);
        $prepared = [];
        foreach ($submitted as $itemKey => $item) {
            $title = trim((string) ($item['title'] ?? ''));
            $source = (string) ($item['source'] ?? 'upload');
            $url = trim((string) ($item['url'] ?? ''));
            $file = $request->file("collection_media.{$itemKey}");
            if ($title === '') {
                throw ValidationException::withMessages(["collection_items.{$itemKey}.title" => 'Judul setiap item wajib diisi.']);
            }
            if (! in_array($source, ['default', 'upload', 'url'], true)) {
                throw ValidationException::withMessages(["collection_items.{$itemKey}.source" => 'Sumber media tidak valid.']);
            }

            $defaultIndex = isset($item['default_index']) && $item['default_index'] !== '' ? (int) $item['default_index'] : null;
            if ($source === 'default' && ($defaultIndex === null || ! isset($defaults[$defaultIndex]))) {
                throw ValidationException::withMessages(["collection_items.{$itemKey}.source" => 'Media bawaan untuk item ini tidak tersedia.']);
            }
            if ($source === 'url' && ($url === '' || ! $this->isSafeMediaUrl($url))) {
                throw ValidationException::withMessages(["collection_items.{$itemKey}.url" => 'Masukkan URL http(s) langsung menuju file media.']);
            }
            if ($source === 'upload' && ! $file && ($url === '' || ! $this->isSafeMediaUrl($url))) {
                throw ValidationException::withMessages(["collection_media.{$itemKey}" => 'Pilih file untuk item “'.$title.'”.']);
            }

            $prepared[] = compact('item', 'itemKey', 'title', 'source', 'url', 'file', 'defaultIndex');
        }

        $items = [];
        foreach ($prepared as $preparedItem) {
            ['item' => $item, 'title' => $title, 'source' => $source, 'url' => $url, 'file' => $file, 'defaultIndex' => $defaultIndex] = $preparedItem;

            if ($file) {
                $path = $file->store('site-media/collections/'.$collection, 'public');
                $url = Storage::url($path);
                $source = 'upload';
                $defaultIndex = null;
            } elseif ($source === 'default') {
                $url = null;
            }

            $row = [
                'key' => 'item-'.Str::uuid(),
                'source' => $source,
                'default_index' => $defaultIndex,
                'url' => $url ?: null,
                'title' => mb_substr($title, 0, 160),
            ];
            if ($collection === 'gallery') {
                $row['meta'] = mb_substr(trim((string) ($item['meta'] ?? '')), 0, 100);
            } else {
                $row['category'] = mb_substr(trim((string) ($item['category'] ?? 'Video')), 0, 100);
                $row['description'] = mb_substr(trim((string) ($item['description'] ?? '')), 0, 1000);
            }
            $items[] = $row;
        }

        $settingKey = 'media_collection_'.$collection;
        $oldItems = json_decode((string) SiteSetting::query()->where('setting_key', $settingKey)->value('value'), true) ?: [];
        SiteSetting::query()->updateOrCreate(['setting_key' => $settingKey], [
            'value' => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $activeUrls = array_filter(array_column($items, 'url'));
        foreach (array_filter(array_column($oldItems, 'url')) as $oldUrl) {
            if (! in_array($oldUrl, $activeUrls, true)) {
                $this->deleteManagedMedia($oldUrl);
            }
        }
    }

    private function isSafeMediaUrl(string $value): bool
    {
        if (str_starts_with($value, '/')) {
            return ! str_starts_with($value, '//');
        }

        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}

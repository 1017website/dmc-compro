<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\SiteContent;
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
        ]);
    }

    public function edit(string $section, TemplateContentService $template): View
    {
        $groups = collect($template->fields())->groupBy('group');
        $groupName = $groups->keys()->first(fn (string $name) => Str::slug($name) === $section);
        abort_unless($groupName, 404);

        return view('cms.content.edit', [
            'groupName' => $groupName,
            'fields' => $groups->get($groupName),
            'saved' => SiteContent::query()->get()->keyBy('content_key'),
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
                    : ['image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:15360'];
                $request->validate(["media.{$formKey}" => $rules]);
                $old = SiteContent::query()->where('content_key', $field['key'])->value('value_id');
                if ($old && str_starts_with($old, '/storage/site-media/')) Storage::disk('public')->delete(str_replace('/storage/', '', $old));
                $path = $request->file("media.{$formKey}")->store('site-media', 'public');
                $values['value_id'] = Storage::url($path);
            }

            if (! array_filter($values, fn ($value) => $value !== null)) {
                SiteContent::query()->where('content_key', $field['key'])->delete();
                continue;
            }
            SiteContent::query()->updateOrCreate(['content_key' => $field['key']], [
                'group_name' => $field['group'], 'label' => $field['label'], 'type' => $field['type'], ...$values,
            ]);
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
}

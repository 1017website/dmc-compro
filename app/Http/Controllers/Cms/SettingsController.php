<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('cms.settings.edit', ['settings' => SiteSetting::values()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'seo_title' => ['nullable', 'string', 'max:70'],
            'seo_description' => ['nullable', 'string', 'max:180'],
            'seo_keywords' => ['nullable', 'string', 'max:500'],
            'seo_robots' => ['nullable', Rule::in([
                'index, follow',
                'noindex, nofollow',
                'index, nofollow',
                'noindex, follow',
            ])],
            'canonical_url' => ['nullable', 'url:http,https', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:100'],
            'og_description' => ['nullable', 'string', 'max:220'],
            'og_image_url' => ['nullable', 'url:http,https', 'max:500'],
            'og_image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'google_analytics_id' => ['nullable', 'regex:/^G-[A-Z0-9]+$/i'],
            'google_ads_id' => ['nullable', 'regex:/^AW-[0-9]+$/i'],
            'google_ads_conversion_label' => ['nullable', 'string', 'max:100'],
            'meta_pixel_id' => ['nullable', 'regex:/^[0-9]{5,30}$/'],
        ]);

        unset($data['og_image_file']);
        $data['og_image'] = $data['og_image_url'] ?? null;
        unset($data['og_image_url']);
        if ($request->hasFile('og_image_file')) {
            $old = SiteSetting::query()->where('setting_key', 'og_image')->value('value');
            if ($old && str_starts_with($old, '/storage/seo/')) Storage::disk('public')->delete(str_replace('/storage/', '', $old));
            $data['og_image'] = Storage::url($request->file('og_image_file')->store('seo', 'public'));
        }

        foreach ($data as $key => $value) {
            SiteSetting::query()->updateOrCreate(['setting_key' => $key], ['value' => $value]);
        }

        return back()->with('success', 'Pengaturan SEO dan tracking berhasil disimpan.');
    }
}

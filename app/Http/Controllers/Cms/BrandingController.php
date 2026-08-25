<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandingController extends Controller
{
    public function edit(): View
    {
        return view('cms.branding.edit', ['branding' => SiteSetting::values()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'frontend_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'cms_logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:5120'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,webp,svg', 'max:2048'],
            'remove_frontend_logo' => ['nullable', 'boolean'],
            'remove_cms_logo' => ['nullable', 'boolean'],
            'remove_favicon' => ['nullable', 'boolean'],
        ]);

        foreach (['frontend_logo', 'cms_logo', 'favicon'] as $key) {
            if ($request->boolean('remove_'.$key)) {
                $this->remove($key);
            }
            if ($request->hasFile($key)) {
                $this->remove($key);
                $path = $request->file($key)->store('branding', 'public');
                SiteSetting::query()->updateOrCreate(['setting_key' => $key], ['value' => Storage::url($path)]);
            }
        }

        return back()->with('success', 'Logo dan favicon berhasil diperbarui.');
    }

    private function remove(string $key): void
    {
        $setting = SiteSetting::query()->where('setting_key', $key)->first();
        if ($setting?->value && str_starts_with($setting->value, '/storage/branding/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $setting->value));
        }
        $setting?->delete();
    }
}

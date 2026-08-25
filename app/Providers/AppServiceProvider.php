<?php

namespace App\Providers;

use App\Models\Inquiry;
use App\Models\SiteSetting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['cms.layouts.app', 'cms.auth.login'], function ($view) {
            $count = 0;
            $branding = [];
            try {
                $count = Inquiry::query()->where('status', 'new')->count();
                $branding = SiteSetting::query()->whereIn('setting_key', ['cms_logo', 'favicon'])->pluck('value', 'setting_key')->all();
            } catch (\Throwable) {
            }
            $view->with('newInquiryCount', $count)->with('cmsBranding', $branding);
        });
    }
}

<?php

use App\Http\Controllers\Cms\AuthController;
use App\Http\Controllers\Cms\BrandingController;
use App\Http\Controllers\Cms\ContentController;
use App\Http\Controllers\Cms\DashboardController;
use App\Http\Controllers\Cms\InquiryController as CmsInquiryController;
use App\Http\Controllers\Cms\ProfileController;
use App\Http\Controllers\Cms\SettingsController;
use App\Http\Controllers\Cms\UserController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\InquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/', FrontendController::class)->name('home');
Route::post('/inquiry', [InquiryController::class, 'store'])->middleware('throttle:10,1')->name('inquiries.store');

Route::middleware('guest')->group(function () {
    Route::get('/cms/login', [AuthController::class, 'create'])->name('login');
    Route::post('/cms/login', [AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
});

Route::prefix('cms')->name('cms.')->middleware(['auth', 'cms.active'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/content', [ContentController::class, 'index'])->name('content.index');
    Route::get('/content/{section}', [ContentController::class, 'edit'])->name('content.edit');
    Route::put('/content', [ContentController::class, 'update'])->name('content.update');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/branding', [BrandingController::class, 'edit'])->name('branding.edit');
    Route::put('/branding', [BrandingController::class, 'update'])->name('branding.update');

    Route::get('/inquiries', [CmsInquiryController::class, 'index'])->name('inquiries.index');
    Route::get('/inquiries/{inquiry}', [CmsInquiryController::class, 'show'])->name('inquiries.show');
    Route::patch('/inquiries/{inquiry}', [CmsInquiryController::class, 'update'])->name('inquiries.update');
    Route::delete('/inquiries/{inquiry}', [CmsInquiryController::class, 'destroy'])->name('inquiries.destroy');

    Route::resource('users', UserController::class)->except('show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

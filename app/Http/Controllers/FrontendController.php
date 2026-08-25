<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use App\Models\SiteSetting;
use App\Services\TemplateContentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FrontendController extends Controller
{
    public function __invoke(Request $request, TemplateContentService $template): Response
    {
        try {
            PageView::query()->create([
                'path' => '/'.ltrim($request->path(), '/'),
                'visitor_hash' => hash_hmac('sha256', ($request->ip() ?? '').'|'.($request->userAgent() ?? ''), config('app.key')),
                'referrer' => $request->headers->get('referer'),
                'user_agent' => $request->userAgent(),
                'viewed_at' => now(),
            ]);
        } catch (\Throwable) {
            // The public page remains available if analytics storage is temporarily unavailable.
        }

        return response($template->render(SiteSetting::values()))->header('Content-Type', 'text/html; charset=UTF-8');
    }
}

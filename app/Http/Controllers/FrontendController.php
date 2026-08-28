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
        // The CMS editor loads this same page inside an iframe. In that mode the
        // markup is annotated so the editor can map elements back to fields, and the
        // visit is not counted — otherwise every content edit would inflate the
        // analytics on the dashboard.
        $preview = $request->boolean('cms_preview') && $request->user() !== null;

        if (! $preview) {
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
        }

        return response($template->render(SiteSetting::values(), $preview))
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }
}

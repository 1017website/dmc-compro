<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\PageView;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $days = collect(range(6, 0))->map(function (int $offset) {
            $date = today()->subDays($offset);
            return [
                'label' => $date->translatedFormat('D'),
                'date' => $date->format('Y-m-d'),
                'views' => PageView::query()->whereDate('viewed_at', $date)->count(),
            ];
        });

        return view('cms.dashboard', [
            'viewsToday' => PageView::query()->whereDate('viewed_at', today())->count(),
            'uniqueToday' => PageView::query()->whereDate('viewed_at', today())->distinct('visitor_hash')->count('visitor_hash'),
            'viewsMonth' => PageView::query()->where('viewed_at', '>=', now()->startOfMonth())->count(),
            'newInquiries' => Inquiry::query()->where('status', 'new')->count(),
            'users' => User::query()->where('is_active', true)->count(),
            'days' => $days,
            'maxViews' => max(1, (int) $days->max('views')),
            'recentInquiries' => Inquiry::query()->latest()->limit(5)->get(),
        ]);
    }
}

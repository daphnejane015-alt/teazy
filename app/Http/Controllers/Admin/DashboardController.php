<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tea;
use App\Models\User;
use App\Models\Rating;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $lastScrapeTea = Tea::where('source', 'scraped')->latest('updated_at')->value('updated_at');
        $cachedResults = Cache::get('tea_scraping_results');
        $cachedTimestamp = isset($cachedResults['timestamp'])
            ? Carbon::parse($cachedResults['timestamp'])
            : null;

        $lastScrapeAt = $cachedTimestamp && (!$lastScrapeTea || $cachedTimestamp->greaterThan($lastScrapeTea))
            ? $cachedTimestamp
            : $lastScrapeTea;

        return view('admin.dashboard', [
            'teaCount' => Tea::count(),
            'userCount' => User::where('role', 'user')->count(),
            'ratingCount' => Rating::count(),
            'lastScrapeAt' => $lastScrapeAt,
            'scrapeRunning' => (bool) Cache::get('scrape_running', false),
        ]);
    }
}

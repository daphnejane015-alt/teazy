<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\Request;

class ApiUsageController extends Controller
{
    public function index()
    {
        $weatherService = app(WeatherService::class);
        $stats = $weatherService->getApiUsageStats();
        
        return view('admin.api-usage', compact('stats'));
    }

    public function clearCache(Request $request)
    {
        if ($request->user()->isAdmin()) {
            $weatherService = app(WeatherService::class);
            $weatherService->clearApiUsageCache();
            
            return redirect()->back()->with('success', 'API usage cache cleared successfully.');
        }
        
        return redirect()->back()->with('error', 'Unauthorized action.');
    }
}

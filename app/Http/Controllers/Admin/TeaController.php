<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tea;
use Artisan;
use Illuminate\Support\Facades\Log;

class TeaController extends Controller
{
    // 1. Show all teas (scraped + manual)
    public function index()
    {
        $teas = Tea::latest()->get();
        return view('admin.teas.index', compact('teas'));
    }

    // Show only scraped teas
    public function scraped(Request $request)
    {
        $flavorFilter = $request->get('flavor', 'all');
        $sortOrder = $request->get('sort', 'name_asc'); // Default: alphabetical A-Z
        
        $query = Tea::where('source', 'scraped');
        
        if ($flavorFilter !== 'all') {
            $query->where('flavor', $flavorFilter);
        }
        
        // Apply sorting
        switch ($sortOrder) {
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'newest':
                $query->latest();
                break;
            case 'oldest':
                $query->oldest();
                break;
            default:
                $query->orderBy('name', 'asc');
        }
        
        $teas = $query->get();
        
        // Get available flavors for filter dropdown
        $availableFlavors = Tea::where('source', 'scraped')
            ->whereNotNull('flavor')
            ->where('flavor', '!=', '')
            ->distinct()
            ->pluck('flavor')
            ->sort()
            ->values();
        
        return view('admin.teas.scraped', compact('teas', 'availableFlavors', 'flavorFilter', 'sortOrder'));
    }

    // Show only manual teas
    public function manual()
    {
        $teas = Tea::where('source', 'manual')->latest()->get();
        return view('admin.teas.manual', compact('teas'));
    }

    public function create()
    {
        return view('admin.teas.create');
    }

    public function edit($id)
    {
        $tea = Tea::findOrFail($id);
        return view('admin.teas.edit', compact('tea'));
    }

    // 2. Manual insert (admin add tea)
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'flavor' => ['nullable', 'string', 'max:255'],
            'caffeine_level' => ['nullable', 'string', 'max:255'],
            'health_benefit' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'shop_link' => ['nullable', 'url', 'max:500'],
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $path = $request->file('image')->store('teas', 'public');

        $tea = Tea::create([
            'name' => $request->name,
            'flavor' => $request->flavor,
            'caffeine_level' => $request->caffeine_level,
            'health_benefit' => $request->health_benefit,
            'source_url' => $request->source_url,
            'shop_link' => $request->shop_link,
            'image' => $path,
            'source' => 'manual'
        ]);

        // Redirect based on where user came from (passed via query param)
        $redirectRoute = match($request->get('source')) {
            'manual' => 'admin.teas.manual',
            'scraped' => 'admin.teas.scraped',
            default => 'admin.teas.index'
        };

        return redirect()->route($redirectRoute)->with('success', 'Tea created successfully!');
    }

    public function update(Request $request, $id)
    {
        $tea = Tea::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'flavor' => ['nullable', 'string', 'max:255'],
            'caffeine_level' => ['nullable', 'string', 'max:255'],
            'health_benefit' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'shop_link' => ['nullable', 'url', 'max:500'],
            'image' => ['nullable', 'image'],
        ]);

        $tea->name = $request->name;
        $tea->flavor = $request->flavor;
        $tea->caffeine_level = $request->caffeine_level;
        $tea->health_benefit = $request->health_benefit;
        $tea->source_url = $request->source_url;
        $tea->shop_link = $request->shop_link;

        if ($request->hasFile('image')) {
            $tea->image = $request->file('image')->store('teas', 'public');
        }

        $tea->save();

        // Redirect back to appropriate page based on tea source
        $redirectRoute = match($tea->source) {
            'scraped' => 'admin.teas.scraped',
            'manual' => 'admin.teas.manual',
            default => 'admin.teas.index'
        };

        return redirect()->route($redirectRoute)->with('success', 'Tea updated successfully!');
    }

    // 3. Trigger scraper from dashboard
    public function scrape(Request $request)
    {
        $forceRefresh = $request->has('force');
        $delay = $request->get('delay', 2); // Default 2s instead of 3s
        
        // Set cache TTL based on scrape type:
        // - Regular scrape (daily): 24 hours
        // - Force refresh (weekly): 168 hours (7 days)
        $cacheTtlHours = $forceRefresh ? 168 : 24;
        $cacheTtlSeconds = $cacheTtlHours * 3600;
        
        // Extend PHP execution time for scraping (5 minutes)
        set_time_limit(300);
        
        // Force refresh should use shorter delay
        if ($forceRefresh && $delay > 2) {
            $delay = 2;
        }
        
        try {
            // Run the robust scraper
            $exitCode = Artisan::call('scrape:robust-tea', [
                '--force' => $forceRefresh,
                '--delay' => $delay
            ]);
            
            // Get the output
            $output = Artisan::output();
            
            // Parse the output for results
            $created = $this->parseScrapeOutput($output, 'Created');
            $updated = $this->parseScrapeOutput($output, 'Updated');
            $requests = $this->parseScrapeOutput($output, 'Total requests');
            
            // Update cache with proper TTL and metadata
            $cacheKey = 'tea_scraping_results';
            $cacheData = [
                'timestamp' => now()->toDateTimeString(),
                'cache_ttl_hours' => $cacheTtlHours,
                'scrape_type' => $forceRefresh ? 'weekly' : 'daily',
                'data' => [
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $this->parseScrapeOutput($output, 'Skipped'),
                    'request_count' => $requests,
                    'total_teas' => \App\Models\Tea::where('source', 'scraped')->count()
                ]
            ];
            \Illuminate\Support\Facades\Cache::put($cacheKey, $cacheData, $cacheTtlSeconds);
            
            // Build success message
            $message = "Tea data scraped successfully!";
            
            if ($requests === 0) {
                $message .= " (Using cached data)";
            }
            
            $message .= " | Cache valid for {$cacheTtlHours} hours (" . ($forceRefresh ? 'weekly' : 'daily') . " mode)";
            
            // Log the scraping activity
            Log::info('Admin tea scraping completed', [
                'created' => $created,
                'updated' => $updated,
                'requests' => $requests,
                'force' => $forceRefresh,
                'delay' => $delay,
                'cache_ttl_hours' => $cacheTtlHours
            ]);
            
            return redirect()->route('admin.teas.scraped')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            Log::error('Admin tea scraping failed', ['error' => $e->getMessage()]);
            return redirect()->route('admin.teas.scraped')
                ->with('error', 'Scraping failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Parse scraper output to extract metrics
     */
    private function parseScrapeOutput($output, $key)
    {
        if (preg_match("/{$key}:\s*(\d+)/", $output, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    // 4. Delete tea
    public function destroy($id, Request $request)
    {
        $tea = Tea::findOrFail($id);
        $source = $tea->source; // Get source before deleting
        
        // Record deletion so scraper won't re-add it
        if ($source === 'scraped') {
            \App\Models\DeletedTea::recordDeletion($tea, auth()->id());
        }
        
        $tea->delete();
        
        // Redirect based on tea source
        $redirectRoute = match($source) {
            'scraped' => 'admin.teas.scraped',
            'manual' => 'admin.teas.manual',
            default => 'admin.teas.index'
        };
        
        return redirect()->route($redirectRoute)->with('success', 'Tea deleted!');
    }
}

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

    // 3. Trigger scraper from dashboard (fires background process — returns immediately)
    public function scrape(Request $request)
    {
        $forceRefresh = $request->has('force');

        // Mark scrape as running so the UI can show a spinner
        \Illuminate\Support\Facades\Cache::put('scrape_running', true, now()->addMinutes(15));
        \Illuminate\Support\Facades\Cache::forget('scrape_done');
        // Store the scrape type so the status panel knows what mode was used
        \Illuminate\Support\Facades\Cache::put('scrape_type', $forceRefresh ? 'force' : 'normal', now()->addMinutes(30));

        // Build artisan command string
        $artisan  = PHP_BINARY . ' ' . base_path('artisan');
        $cmd      = $artisan . ' scrape:tea-data --source=all';
        if ($forceRefresh) {
            $cmd .= ' --fresh';
        }

        // Append a callback that writes a 'done' cache entry when finished
        // We piggyback on a small wrapper: write result to a temp file the
        // status endpoint can read, then set cache flags.
        $doneFlag = storage_path('logs/scrape_done.flag');

        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: use START /B to detach the process
            $cmd = 'START /B cmd /C "' . $cmd . ' > NUL 2>&1 && echo done > ' . addslashes($doneFlag) . '"';
            pclose(popen($cmd, 'r'));
        } else {
            $cmd = $cmd . ' > /dev/null 2>&1 && touch ' . escapeshellarg($doneFlag) . ' &';
            exec($cmd);
        }

        Log::info('Admin triggered background tea scrape', ['force' => $forceRefresh]);

        return redirect()->route('admin.teas.scraped')
            ->with('info', '🕷️ Scraping started in the background. The page will refresh automatically when done.');
    }

    // 3b. Poll endpoint — returns JSON status for the scrape progress indicator
    public function scrapeStatus()
    {
        $running  = \Illuminate\Support\Facades\Cache::get('scrape_running', false);
        $doneFlag = storage_path('logs/scrape_done.flag');
        $done     = file_exists($doneFlag);

        if ($done) {
            // Clear flags
            \Illuminate\Support\Facades\Cache::forget('scrape_running');
            \Illuminate\Support\Facades\Cache::put('scrape_done', true, now()->addMinutes(5));
            @unlink($doneFlag);

            $total        = Tea::where('source', 'scraped')->count();
            $scrapeType   = \Illuminate\Support\Facades\Cache::get('scrape_type', 'normal');
            $ttlHours     = ($scrapeType === 'force') ? 168 : 24;

            // Write the results cache so the status panel on the page updates
            \Illuminate\Support\Facades\Cache::put('tea_scraping_results', [
                'timestamp'      => now()->toDateTimeString(),
                'scrape_type'    => $scrapeType,
                'cache_ttl_hours'=> $ttlHours,
                'data'           => [
                    'total_teas' => $total,
                    'created'    => 0,
                    'updated'    => 0,
                    'skipped'    => 0,
                ],
            ], now()->addHours($ttlHours));

            return response()->json(['status' => 'done', 'total' => $total]);
        }

        if ($running) {
            return response()->json(['status' => 'running']);
        }

        return response()->json(['status' => 'idle']);
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

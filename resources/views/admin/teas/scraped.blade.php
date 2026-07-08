@extends('layouts.admin-sidebar')

@section('content')

{{-- Flash messages --}}
@if(session('info'))
<div class="mb-4 flex items-center gap-2 bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-lg text-sm font-medium">
    <svg class="animate-spin h-4 w-4 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
    </svg>
    {{ session('info') }}
</div>
@endif
@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded-lg text-sm font-medium">
    ✅ {{ session('success') }}
</div>
@endif
@if(session('error'))
<div class="mb-4 bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">
    ❌ {{ session('error') }}
</div>
@endif

<div class="mb-6">
    <div class="flex justify-between items-start mb-4">
        <div>
            <h1 class="text-3xl font-bold mb-2">Scraped Teas</h1>
            <p class="text-gray-600">Manage and edit scraped tea data</p>
        </div>
        <div class="flex gap-3 items-center flex-wrap">

            {{-- Scrape status banner (shown while running) --}}
            <div id="scrape-status-banner" class="hidden items-center gap-2 bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-2 rounded-lg text-sm font-medium">
                <svg class="animate-spin h-4 w-4 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                Scraping in progress… page will refresh when done.
            </div>

            {{-- Scrape buttons (hidden while running) --}}
            <div id="scrape-buttons" class="flex gap-3">
                <!-- Scrape (merge) -->
                <form action="{{ route('admin.scrape.teas') }}" method="POST" class="inline" onsubmit="startScrapeUI(); return true;">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm font-medium" title="Scrape and merge with existing data">
                        🕷️ Scrape Tea Data
                    </button>
                </form>

                <!-- Force Scrape (fresh) -->
                <form action="{{ route('admin.scrape.teas') }}" method="POST" class="inline" onsubmit="startScrapeUI(); return true;">
                    @csrf
                    <input type="hidden" name="force" value="1">
                    <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 text-sm font-medium" title="Force fresh scrape (bypasses merge, fetches new data)">
                        🔄 Force Scrape
                    </button>
                </form>
            </div>

            <a href="{{ route('admin.teas.create', ['source' => 'manual']) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-medium inline-block">
                ➕ Add New Tea
            </a>
        </div>
    </div>
    
    <!-- Filters & Sorting -->
    <div class="bg-white rounded-lg shadow p-4">
        <form method="GET" action="{{ route('admin.teas.scraped') }}" class="flex flex-wrap items-center gap-4">
            <!-- Flavor Filter -->
            <div class="flex items-center gap-2">
                <label for="flavor" class="text-sm font-medium text-gray-700">
                    🍃 Flavor:
                </label>
                <select name="flavor" id="flavor" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" {{ $flavorFilter === 'all' ? 'selected' : '' }}>
                        All Flavors ({{ \App\Models\Tea::where('source', 'scraped')->count() }})
                    </option>
                    @foreach($availableFlavors as $flavor)
                        @php
                            $count = \App\Models\Tea::where('source', 'scraped')->where('flavor', $flavor)->count();
                        @endphp
                        <option value="{{ $flavor }}" {{ $flavorFilter === $flavor ? 'selected' : '' }}>
                            {{ ucfirst($flavor) }} ({{ $count }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <!-- Alphabetical Sort -->
            <div class="flex items-center gap-2">
                <label for="sort" class="text-sm font-medium text-gray-700">
                    🔤 Sort:
                </label>
                <select name="sort" id="sort" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="name_asc" {{ $sortOrder === 'name_asc' ? 'selected' : '' }}>
                        Name A → Z 🔼
                    </option>
                    <option value="name_desc" {{ $sortOrder === 'name_desc' ? 'selected' : '' }}>
                        Name Z → A 🔽
                    </option>
                    <option value="newest" {{ $sortOrder === 'newest' ? 'selected' : '' }}>
                        Newest First 🆕
                    </option>
                    <option value="oldest" {{ $sortOrder === 'oldest' ? 'selected' : '' }}>
                        Oldest First 📅
                    </option>
                </select>
            </div>
            
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">
                🔍 Apply
            </button>
            
            @if($flavorFilter !== 'all' || $sortOrder !== 'name_asc')
                <a href="{{ route('admin.teas.scraped') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-300 inline-block">
                    ✖️ Clear
                </a>
            @endif
        </form>
        
        @if($flavorFilter !== 'all' || $sortOrder !== 'name_asc')
            <div class="mt-3 flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    @if($flavorFilter !== 'all')
                        Showing <span class="font-semibold text-blue-600">{{ $teas->count() }}</span> teas with flavor: 
                        <span class="font-semibold text-blue-600">{{ ucfirst($flavorFilter) }}</span>
                    @endif
                    @if($sortOrder !== 'name_asc')
                        @if($flavorFilter !== 'all') | @endif
                        Sorted by: 
                        <span class="font-semibold text-blue-600">
                            @switch($sortOrder)
                                @case('name_asc') Name A → Z @break
                                @case('name_desc') Name Z → A @break
                                @case('newest') Newest First @break
                                @case('oldest') Oldest First @break
                            @endswitch
                        </span>
                    @endif
                </div>
                <div class="text-xs text-gray-500">
                    Total scraped teas: {{ \App\Models\Tea::where('source', 'scraped')->count() }}
                </div>
            </div>
        @else
            <div class="mt-3 text-xs text-gray-500 text-right">
                Total scraped teas: {{ \App\Models\Tea::where('source', 'scraped')->count() }}
            </div>
        @endif
    </div>
</div>

<!-- Scraping Status Panel -->
@php
    $cacheKey     = 'tea_scraping_results';
    $hasCache     = \Illuminate\Support\Facades\Cache::has($cacheKey);
    $cachedData   = $hasCache ? \Illuminate\Support\Facades\Cache::get($cacheKey) : null;
    $totalTeas    = \App\Models\Tea::where('source', 'scraped')->count();
    $deletedCount = \App\Models\DeletedTea::count();

    // Determine mode: 'force' = weekly (168h), 'normal' = daily (24h)
    $scrapeType      = $cachedData['scrape_type'] ?? null;
    $isForce         = ($scrapeType === 'force');
    $cacheTtlHours   = $isForce ? 168 : 24;
    $modeLabel       = $isForce ? 'weekly' : 'daily';
    $modeColor       = $isForce ? 'text-purple-600' : 'text-green-600';
    $lastUpdated     = $cachedData['timestamp'] ?? null;
    $validUntil      = $lastUpdated
                         ? \Carbon\Carbon::parse($lastUpdated)->addHours($cacheTtlHours)
                         : null;
    $created         = $cachedData['data']['created'] ?? 0;
    $updated         = $cachedData['data']['updated'] ?? 0;
@endphp

<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <span class="text-2xl">📊</span>
            <div>
                <h3 class="font-semibold text-blue-900">Scraping Status</h3>
                @if($lastUpdated && $validUntil)
                    <p class="text-sm text-blue-700">
                        Last updated: <span class="font-medium">{{ $lastUpdated }}</span>
                        <span class="text-gray-400 mx-1">|</span>
                        Valid until: <span class="font-medium">{{ $validUntil->format('Y-m-d H:i:s') }}</span>
                        <span class="text-xs {{ $modeColor }} ml-1 font-medium">[{{ $modeLabel }} mode - {{ $cacheTtlHours }}h]</span>
                    </p>
                @else
                    <p class="text-sm text-blue-700">No scraping data available. Run scraping to fetch tea data.</p>
                @endif
            </div>
        </div>
        <div class="text-right">
            <div class="text-sm font-medium text-blue-800">{{ $totalTeas }} teas in database</div>
            @if($hasCache && $cachedData)
                <div class="text-xs text-blue-600">
                    {{ $created }} created, {{ $updated }} updated
                    @if($deletedCount > 0)
                        <span class="text-orange-600 ml-1">({{ $deletedCount }} excluded)</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Button legend --}}
    <div class="mt-3 pt-3 border-t border-blue-200 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-blue-700">
        <div class="flex items-start gap-2">
            <span class="mt-0.5 inline-block bg-green-600 text-white px-1.5 py-0.5 rounded font-bold">🕷️</span>
            <div><span class="font-semibold">Scrape Tea Data</span> — merges new data with existing teas (daily mode, cache valid 24h). Safe to run anytime.</div>
        </div>
        <div class="flex items-start gap-2">
            <span class="mt-0.5 inline-block bg-orange-600 text-white px-1.5 py-0.5 rounded font-bold">🔄</span>
            <div><span class="font-semibold">Force Scrape</span> — re-fetches everything fresh from source sites (weekly mode, cache valid 168h). Use when data feels stale.</div>
        </div>
    </div>
</div>

<!-- List View: Scraped Teas Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">
                        Image
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Tea Name & Details
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                        Flavor
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">
                        Caffeine
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-48">
                        Health Benefit
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-36">
                        Timestamps
                    </th>
                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($teas as $tea)
                    @php
                        $fallbackImage = 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';
                        $imgSrc = $tea->image
                            ? (str_starts_with($tea->image, 'http') ? $tea->image
                                : (str_starts_with($tea->image, '//') ? 'https:'.$tea->image
                                : (str_starts_with($tea->image, '/storage/') ? $tea->image : '/storage/'.$tea->image)))
                            : $fallbackImage;
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <!-- Image -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <img src="{{ $imgSrc }}" alt="{{ $tea->name }}" class="h-12 w-12 rounded-md object-cover"
                                 onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                        </td>
                        
                        <!-- Name & Source Link -->
                        <td class="px-4 py-3">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium text-gray-900">{{ $tea->name }}</span>
                                @if($tea->source_url)
                                    <a href="{{ $tea->source_url }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 truncate max-w-xs">
                                        📄 Source
                                    </a>
                                @endif
                                @if($tea->shop_link)
                                    <a href="{{ $tea->shop_link }}" target="_blank" class="text-xs text-green-600 hover:text-green-800 truncate max-w-xs">
                                        � Shop Link
                                    </a>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Flavor -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($flavorFilter !== 'all' && $tea->flavor === $flavorFilter)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    🍃 {{ $tea->flavor }}
                                </span>
                            @else
                                <span class="text-sm text-gray-600">{{ $tea->flavor ?: 'N/A' }}</span>
                            @endif
                        </td>
                        
                        <!-- Caffeine -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="text-sm text-gray-600">{{ $tea->caffeine_level ?: 'N/A' }}</span>
                        </td>
                        
                        <!-- Health Benefit -->
                        <td class="px-4 py-3">
                            <p class="text-xs text-gray-600 leading-relaxed" title="{{ $tea->health_benefit }}">
                                {{ Str::limit($tea->health_benefit, 60) }}
                            </p>
                        </td>
                        
                        <!-- Timestamps -->
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-col text-xs">
                                <span class="text-gray-500">
                                    <span class="font-medium text-gray-700">Created:</span><br>
                                    {{ $tea->created_at ? $tea->created_at->format('M d, Y H:i') : 'N/A' }}
                                </span>
                                @if($tea->updated_at && $tea->updated_at->ne($tea->created_at))
                                    <span class="text-gray-500 mt-1">
                                        <span class="font-medium text-orange-600">Updated:</span><br>
                                        {{ $tea->updated_at->format('M d, Y H:i') }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('admin.teas.edit', $tea->id) }}" class="text-blue-600 hover:text-blue-900 p-1" title="Edit">
                                    ✏️
                                </a>
                                <form action="{{ route('admin.teas.destroy', $tea->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 p-1" onclick="return confirm('Delete {{ $tea->name }}?')" title="Delete">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <span class="text-3xl">🍵</span>
                                <p>No scraped teas found.</p>
                                <p class="text-sm text-gray-400">Use the "Scrape Tea Data" button to get started.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination Summary -->
    @if($teas->count() > 0)
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing <span class="font-medium">{{ $teas->count() }}</span> tea{{ $teas->count() !== 1 ? 's' : '' }}
                </div>
                <div class="text-xs text-gray-400">
                    Last database update: {{ now()->format('M d, Y H:i') }}
                </div>
            </div>
        </div>
    @endif
</div>

<script>
const STATUS_URL = '{{ route('admin.scrape.status') }}';
let pollTimer = null;

function startScrapeUI() {
    const buttons = document.getElementById('scrape-buttons');
    const banner  = document.getElementById('scrape-status-banner');
    if (buttons) buttons.classList.add('hidden');
    if (banner)  { banner.classList.remove('hidden'); banner.classList.add('flex'); }
    startPolling();
}

function startPolling() {
    if (pollTimer) return;
    pollTimer = setInterval(async () => {
        try {
            const res  = await fetch(STATUS_URL);
            const data = await res.json();
            if (data.status === 'done') {
                clearInterval(pollTimer);
                window.location.reload();
            }
        } catch (e) { /* keep polling on network errors */ }
    }, 5000); // poll every 5 s — kinder to ngrok
}

document.addEventListener('DOMContentLoaded', async () => {
    @if(session('info'))
    startScrapeUI(); // scrape just triggered
    @else
    try {
        const res  = await fetch(STATUS_URL);
        const data = await res.json();
        if (data.status === 'running') startScrapeUI();
    } catch (e) {}
    @endif
});
</script>

@endsection

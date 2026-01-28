@extends('layouts.admin-sidebar')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-start mb-4">
        <div>
            <h1 class="text-3xl font-bold mb-2">Scraped Teas</h1>
            <p class="text-gray-600">Manage and edit scraped tea data</p>
        </div>
        <div class="flex gap-4">
            <!-- Scrape with Cache -->
            <form action="{{ route('admin.scrape.teas') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700" title="Use cached data if available">
                    🕷️ Scrap Tea Data
                </button>
            </form>
            
            <!-- Force Scrape (Refresh) -->
            <form action="{{ route('admin.scrape.teas') }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="force" value="1">
                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700" title="Force fresh scraping (slower, bypasses cache)">
                    🔄 Force Scrape
                </button>
            </form>
            
            <a href="{{ route('admin.teas.create', ['source' => 'manual']) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 inline-block">
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

<!-- Cache Status Panel -->
@php
$cacheKey = 'tea_scraping_results';
$hasCache = \Illuminate\Support\Facades\Cache::has($cacheKey);
$cachedData = $hasCache ? \Illuminate\Support\Facades\Cache::get($cacheKey) : null;
$actualTotalTeas = \App\Models\Tea::where('source', 'scraped')->count();
$deletedTeasCount = \App\Models\DeletedTea::count();

// Calculate cache expiration time based on cache_ttl_hours stored in cache
$cacheExpiresAt = null;
$cacheDurationHours = 24; // default
if ($hasCache && $cachedData && isset($cachedData['timestamp'])) {
    $cacheDurationHours = $cachedData['cache_ttl_hours'] ?? 24;
    $cacheExpiresAt = \Carbon\Carbon::parse($cachedData['timestamp'])->addHours($cacheDurationHours);
}
@endphp

<div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-2xl">📊</span>
            <div>
                <h3 class="font-semibold text-blue-900">Scraping Status</h3>
                @if($hasCache && $cachedData && $cacheExpiresAt)
                    @php
                        $scrapeType = $cachedData['scrape_type'] ?? 'daily';
                        $typeColor = $scrapeType === 'weekly' ? 'text-purple-600' : 'text-green-600';
                    @endphp
                    <p class="text-sm text-blue-700">
                        Last updated: <span class="font-medium">{{ $cachedData['timestamp'] }}</span>
                        <span class="text-gray-500 mx-1">|</span>
                        Valid until: <span class="font-medium">{{ $cacheExpiresAt->format('Y-m-d H:i:s') }}</span>
                        <span class="text-xs {{ $typeColor }} ml-1 font-medium">[{{ $scrapeType }} mode - {{ $cacheDurationHours }}h]</span>
                    </p>
                @else
                    <p class="text-sm text-blue-700">No scraping data available. Run scraping to fetch tea data.</p>
                @endif
            </div>
        </div>
        <div class="text-right">
            <div class="text-sm font-medium text-blue-800">
                {{ $actualTotalTeas }} teas in database
            </div>
            @if($hasCache && $cachedData)
                <div class="text-xs text-blue-600">
                    {{ $cachedData['data']['created'] ?? 0 }} created, {{ $cachedData['data']['updated'] ?? 0 }} updated
                    @if($deletedTeasCount > 0)
                        <span class="text-orange-600 ml-1">({{ $deletedTeasCount }} excluded)</span>
                    @endif
                </div>
            @endif
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
                            <img src="{{ $imgSrc }}" alt="{{ $tea->name }}" class="h-12 w-12 rounded-md object-cover">
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

@endsection

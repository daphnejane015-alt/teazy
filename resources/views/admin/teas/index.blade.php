@extends('layouts.admin-sidebar')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Tea Management</h1>
        <a href="{{ route('admin.teas.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 inline-block">
            ➕ Add New Tea
        </a>
    </div>
    <p class="text-gray-600 mt-2">
        <a href="{{ route('admin.teas.manual') }}" class="text-blue-600 hover:underline">→ Manage Manual Teas</a> | 
        <a href="{{ route('admin.teas.scraped') }}" class="text-green-600 hover:underline">→ View Scraped Teas</a>
    </p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    @forelse($teas as $tea)
        <div class="bg-white rounded shadow overflow-hidden">
            @php
                $fallbackImage = 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';
                $imgSrc = $tea->image
                    ? (str_starts_with($tea->image, 'http') ? $tea->image
                        : (str_starts_with($tea->image, '//') ? 'https:'.$tea->image
                        : (str_starts_with($tea->image, '/storage/') ? $tea->image : '/storage/'.$tea->image)))
                    : $fallbackImage;
            @endphp
            <img src="{{ $imgSrc }}" alt="{{ $tea->name }}" class="w-full h-48 object-cover"
                 onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <h2 class="text-lg font-semibold text-gray-900 leading-tight">{{ $tea->name }}</h2>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('admin.teas.edit', $tea->id) }}" class="text-blue-600 hover:text-blue-900">
                            ✏️
                        </a>
                        <form action="{{ route('admin.teas.destroy', $tea->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">
                                🗑️
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mt-3 space-y-1 text-sm text-gray-600">
                    <div>
                        <span class="font-medium text-gray-700">Flavor:</span>
                        <span>{{ $tea->flavor }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Caffeine:</span>
                        <span>{{ $tea->caffeine_level }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Benefit:</span>
                        <span>{{ $tea->health_benefit }}</span>
                    </div>
                    <div class="flex gap-2 mt-2">
                        @if($tea->source_url)
                            <span class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded" title="{{ $tea->source_url }}">🔗 Source</span>
                        @endif
                        @if($tea->shop_link)
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded" title="{{ $tea->shop_link }}">🛒 Shop</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full bg-white rounded shadow p-6 text-center text-gray-500">
            No teas found. Use the "Scrape Tea Data" button to get started.
        </div>
    @endforelse
</div>

@endsection

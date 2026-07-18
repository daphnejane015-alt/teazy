@extends('layouts.admin-sidebar')

@php
    // Determine back route based on tea source
    $backRoute = match($tea->source) {
        'scraped' => route('admin.teas.scraped'),
        'manual' => route('admin.teas.manual'),
        default => route('admin.teas.index')
    };
    $backLabel = match($tea->source) {
        'scraped' => '← Back to Scraped Teas',
        'manual' => '← Back to Manual Teas',
        default => '← Back to All Teas'
    };
@endphp

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Edit Tea</h1>
        <a href="{{ $backRoute }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
            {{ $backLabel }}
        </a>
    </div>
</div>

<div class="bg-white rounded shadow p-6 max-w-2xl">
    <form action="{{ route('admin.teas.update', $tea->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" name="name" value="{{ old('name', $tea->name) }}" class="mt-1 block w-full border-gray-300 rounded" required>
            @error('name')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Flavor</label>
            <input type="text" name="flavor" value="{{ old('flavor', $tea->flavor) }}" class="mt-1 block w-full border-gray-300 rounded">
            @error('flavor')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Caffeine Level</label>
            <input type="text" name="caffeine_level" value="{{ old('caffeine_level', $tea->caffeine_level) }}" class="mt-1 block w-full border-gray-300 rounded">
            @error('caffeine_level')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Health Benefit</label>
            <textarea name="health_benefit" rows="4" class="mt-1 block w-full border-gray-300 rounded" maxlength="1000">{{ old('health_benefit', $tea->health_benefit) }}</textarea>
            <p class="text-xs text-gray-400 mt-1">{{ strlen($tea->health_benefit) }}/1000 characters</p>
            @error('health_benefit')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Source URL</label>
            <input type="url" name="source_url" value="{{ old('source_url', $tea->source_url) }}" class="mt-1 block w-full border-gray-300 rounded" placeholder="https://example.com/tea-article">
            @error('source_url')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="bg-gray-50 rounded-lg p-4 space-y-4">
            <h4 class="font-semibold text-gray-800 flex items-center gap-2">
                <span>🛒</span> Shop Links
            </h4>

            <div>
                <label class="block text-sm font-medium text-gray-700">Shopee Link</label>
                <input type="url" name="shopee_link" value="{{ old('shopee_link', $tea->shopee_link) }}" class="mt-1 block w-full border-gray-300 rounded" placeholder="https://shopee.com.my/product-link or leave blank for search">
                <p class="text-xs text-gray-500 mt-1">Leave blank to use Shopee search by tea name.</p>
                @error('shopee_link')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Lazada Link</label>
                <input type="url" name="lazada_link" value="{{ old('lazada_link', $tea->lazada_link) }}" class="mt-1 block w-full border-gray-300 rounded" placeholder="https://lazada.com.my/product-link or leave blank for search">
                <p class="text-xs text-gray-500 mt-1">Leave blank to use Lazada search by tea name.</p>
                @error('lazada_link')
                    <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Current Image</label>
            <div class="mt-2">
                <img src="{{ ($tea->image && str_starts_with($tea->image, 'http')) ? $tea->image : (($tea->image && str_starts_with($tea->image, '//')) ? ('https:'.$tea->image) : (($tea->image && str_starts_with($tea->image, '/storage/')) ? $tea->image : ($tea->image ? ('/storage/'.$tea->image) : ''))) }}" alt="{{ $tea->name }}" class="w-48 h-32 object-cover rounded">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Replace Image (optional)</label>
            <input type="file" name="image" class="mt-1 block w-full" accept="image/*">
            @error('image')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Update Tea
            </button>
        </div>
    </form>
</div>
@endsection

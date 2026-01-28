@extends('layouts.admin-sidebar')

@php
    // Get source from query parameter or default to manual
    $source = request('source', 'manual');
    
    // Determine back route and label
    $backRoute = match($source) {
        'scraped' => route('admin.teas.scraped'),
        'manual' => route('admin.teas.manual'),
        default => route('admin.teas.index')
    };
    $backLabel = match($source) {
        'scraped' => '← Back to Scraped Teas',
        'manual' => '← Back to Manual Teas',
        default => '← Back to All Teas'
    };
@endphp

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold">Add New Tea</h1>
        <a href="{{ $backRoute }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
            {{ $backLabel }}
        </a>
    </div>
</div>

<div class="bg-white rounded shadow p-6 max-w-2xl">
    <form action="{{ route('admin.teas.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="hidden" name="source" value="{{ $source }}">

        <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded" required>
            @error('name')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Flavor</label>
            <input type="text" name="flavor" value="{{ old('flavor') }}" class="mt-1 block w-full border-gray-300 rounded">
            @error('flavor')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Caffeine Level</label>
            <input type="text" name="caffeine_level" value="{{ old('caffeine_level') }}" class="mt-1 block w-full border-gray-300 rounded">
            @error('caffeine_level')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Health Benefit</label>
            <input type="text" name="health_benefit" value="{{ old('health_benefit') }}" class="mt-1 block w-full border-gray-300 rounded">
            @error('health_benefit')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Source URL</label>
            <input type="url" name="source_url" value="{{ old('source_url') }}" class="mt-1 block w-full border-gray-300 rounded" placeholder="https://example.com/tea-article">
            @error('source_url')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Shop Link</label>
            <input type="url" name="shop_link" value="{{ old('shop_link') }}" class="mt-1 block w-full border-gray-300 rounded" placeholder="https://shop.example.com/buy-tea">
            @error('shop_link')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Image</label>
            <input type="file" name="image" class="mt-1 block w-full" accept="image/*" required>
            @error('image')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="pt-2">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Save Tea
            </button>
        </div>
    </form>
</div>
@endsection

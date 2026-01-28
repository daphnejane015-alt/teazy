@extends('layouts.admin-sidebar')

@section('content')
<div class="mb-6">
    <div class="flex items-center">
        <a href="{{ route('admin.ratings.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">
            ← Back to Ratings
        </a>
        <h1 class="text-3xl font-bold">Edit Rating</h1>
    </div>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-6">
        <!-- Rating Info -->
        <div class="bg-gray-50 p-4 rounded-md mb-6">
            <h3 class="text-sm font-medium text-gray-700 mb-2">Rating Information</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">User:</span>
                    <span class="ml-2 font-medium">{{ $rating->user->name }} ({{ $rating->user->email }})</span>
                </div>
                <div>
                    <span class="text-gray-500">Tea:</span>
                    <span class="ml-2 font-medium">{{ $rating->tea->name }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Current Rating:</span>
                    <span class="ml-2 font-medium text-blue-600">{{ $rating->rating }}/5</span>
                </div>
                <div>
                    <span class="text-gray-500">Description:</span>
                    <span class="ml-2 font-medium">{{ $rating->description ?: 'No description' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Rated On:</span>
                    <span class="ml-2 font-medium">{{ $rating->created_at->format('M j, Y g:i A') }}</span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.ratings.update', $rating->id) }}">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <!-- Rating -->
                <div>
                    <label for="rating" class="block text-sm font-medium text-gray-700">
                        Rating <span class="text-red-500">*</span>
                    </label>
                    <select id="rating" 
                            name="rating" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="">Select rating</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" {{ old('rating', $rating->rating) == $i ? 'selected' : '' }}>
                                {{ $i }} ⭐ @if($i == 1) Poor @elseif($i == 2) Fair @elseif($i == 3) Good @elseif($i == 4) Very Good @else Excellent @endif
                            </option>
                        @endfor
                    </select>
                    @error('rating')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">
                        User Description
                    </label>
                    <textarea id="description" 
                              name="description" 
                              rows="4"
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Edit user's rating description or comment...">{{ old('description', $rating->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Maximum 500 characters. This is the user's personal note about this tea.</p>
                </div>

                <!-- Tea Info -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <h4 class="text-sm font-medium text-blue-800 mb-2">Tea Information:</h4>
                    <div class="text-xs text-blue-700 space-y-1">
                        <p><strong>Name:</strong> {{ $rating->tea->name }}</p>
                        <p><strong>Flavor:</strong> {{ $rating->tea->flavor }}</p>
                        <p><strong>Caffeine:</strong> {{ $rating->tea->caffeine_level }}</p>
                        <p><strong>Health Benefit:</strong> {{ Str::limit($rating->tea->health_benefit, 100) }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-8 flex items-center justify-between">
                <a href="{{ route('admin.ratings.index') }}" class="text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                    Update Rating
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

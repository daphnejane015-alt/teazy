@extends('layouts.admin-sidebar')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold">Rating Management</h1>
            <p class="text-gray-600">Manage user ratings and reviews</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-500 rounded-md p-2">
                <span class="text-white text-lg">⭐</span>
            </div>
            <div class="ml-3 w-0 flex-1">
                <dl>
                    <dt class="text-xs font-medium text-gray-500 truncate">Total Ratings</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ $stats['total_ratings'] }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-500 rounded-md p-2">
                <span class="text-white text-lg">📊</span>
            </div>
            <div class="ml-3 w-0 flex-1">
                <dl>
                    <dt class="text-xs font-medium text-gray-500 truncate">Average Rating</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ number_format($stats['average_rating'], 1) }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-purple-500 rounded-md p-2">
                <span class="text-white text-lg">👥</span>
            </div>
            <div class="ml-3 w-0 flex-1">
                <dl>
                    <dt class="text-xs font-medium text-gray-500 truncate">Users Rated</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ $stats['users_with_ratings'] }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-orange-500 rounded-md p-2">
                <span class="text-white text-lg">🍵</span>
            </div>
            <div class="ml-3 w-0 flex-1">
                <dl>
                    <dt class="text-xs font-medium text-gray-500 truncate">Teas Rated</dt>
                    <dd class="text-lg font-medium text-gray-900">{{ $stats['teas_with_ratings'] }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- Ratings Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">All Ratings</h2>
            <div class="text-sm text-gray-500">
                Showing {{ $ratings->count() }} most recent ratings
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        User
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Tea
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Rating
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Description
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($ratings as $rating)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                        <span class="text-white text-xs font-medium">
                                            {{ strtoupper(substr($rating->user->name, 0, 1)) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $rating->user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $rating->user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $rating->tea->name }}</div>
                            <div class="text-xs text-gray-500">{{ $rating->tea->flavor }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-lg font-bold text-blue-600">
                                {{ $rating->rating }}/5
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs truncate">
                                {{ $rating->description ?: 'No description' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $rating->created_at->format('M j, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('admin.ratings.edit', $rating->id) }}" 
                                   class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                    ✏️
                                </a>
                                <form action="{{ route('admin.ratings.destroy', $rating->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" 
                                            onclick="return confirm('Are you sure you want to delete this rating?')" 
                                            title="Delete">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No ratings found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

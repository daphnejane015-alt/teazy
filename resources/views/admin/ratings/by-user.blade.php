@extends('layouts.admin-sidebar')

@section('content')
<div class="mb-6">
    <div class="flex items-center">
        <a href="{{ route('admin.users.show', $user->id) }}" class="text-gray-600 hover:text-gray-900 mr-4">
            ← Back to User
        </a>
        <h1 class="text-3xl font-bold">Ratings by {{ $user->name }}</h1>
    </div>
</div>

<!-- User Info Summary -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center">
        <div class="flex-shrink-0 h-12 w-12">
            <div class="h-12 w-12 rounded-full bg-blue-500 flex items-center justify-center">
                <span class="text-white text-lg font-bold">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </span>
            </div>
        </div>
        <div class="ml-4">
            <h2 class="text-lg font-semibold text-gray-900">{{ $user->name }}</h2>
            <p class="text-gray-600">{{ $user->email }}</p>
            <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                <span>Total Ratings: {{ $ratings->count() }}</span>
                <span>Average: {{ $ratings->avg('rating') ? number_format($ratings->avg('rating'), 1) : '0.0' }}/5</span>
                <span>Joined: {{ $user->created_at->format('M j, Y') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Ratings List -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold">All Ratings ({{ $ratings->count() }})</h2>
            <div class="text-sm text-gray-500">
                User's complete rating history
            </div>
        </div>
    </div>
    
    @if($ratings->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
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
                    @foreach($ratings as $rating)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $rating->tea->name }}</div>
                                <div class="text-xs text-gray-500">{{ $rating->tea->flavor }} • {{ $rating->tea->caffeine_level }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-lg font-bold text-blue-600">
                                    {{ $rating->rating }}/5
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs">
                                    {{ $rating->description ?: 'No description' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $rating->created_at->format('M j, Y g:i A') }}
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
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-8">
            <div class="text-gray-400 text-4xl mb-2">⭐</div>
            <p class="text-gray-500">{{ $user->name }} hasn't rated any teas yet.</p>
        </div>
    @endif
</div>
@endsection

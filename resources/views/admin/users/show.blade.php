@extends('layouts.admin-sidebar')

@section('content')
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">
                ← Back to Users
            </a>
            <h1 class="text-3xl font-bold">User Details</h1>
        </div>
        <div class="space-x-4">
            <a href="{{ route('admin.users.edit', $user->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                ✏️ Edit User
            </a>
            @if($user->id !== auth()->guard('admin')->id())
                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700"
                            onclick="return confirm('Are you sure you want to delete this user? All their ratings and preferences will also be deleted.')">
                        🗑️ Delete User
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- User Information -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center">
                <div class="h-20 w-20 rounded-full bg-blue-500 flex items-center justify-center mx-auto mb-4">
                    <span class="text-white text-2xl font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </span>
                </div>
                <h2 class="text-xl font-bold text-gray-900">{{ $user->name }}</h2>
                <p class="text-gray-600">{{ $user->email }}</p>
                
                <div class="mt-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        {{ $user->role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>
            </div>

            <div class="mt-6 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">User ID:</span>
                    <span class="font-medium">{{ $user->id }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Joined:</span>
                    <span class="font-medium">{{ $user->created_at->format('M j, Y') }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Last Login:</span>
                    <span class="font-medium">{{ $user->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Email Verified:</span>
                    <span class="font-medium">{{ $user->email_verified_at ? 'Yes' : 'No' }}</span>
                </div>
            </div>
        </div>

        <!-- User Preferences -->
        @if($user->preference)
            <div class="bg-white rounded-lg shadow p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">Tea Preferences</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Flavor:</span>
                        <span class="font-medium">{{ $user->preference->preferred_flavor ?? 'Not set' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Caffeine:</span>
                        <span class="font-medium">{{ $user->preference->preferred_caffeine ?? 'Not set' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Health Goal:</span>
                        <span class="font-medium">{{ $user->preference->health_goal ?? 'Not set' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">City:</span>
                        <span class="font-medium">{{ $user->preference->city ?? 'Not set' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Weather-based:</span>
                        <span class="font-medium">{{ $user->preference->weather_based_recommendations ? 'Enabled' : 'Disabled' }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- User Activity -->
    <div class="lg:col-span-2">
        <!-- Statistics -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4">Activity Statistics</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $user->ratings()->count() }}</div>
                    <div class="text-sm text-gray-500">Total Ratings</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">
                        {{ $user->ratings()->avg('rating') ? number_format($user->ratings()->avg('rating'), 1) : '0.0' }}
                    </div>
                    <div class="text-sm text-gray-500">Avg Rating</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $user->recommendations()->count() }}</div>
                    <div class="text-sm text-gray-500">Recommendations</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600">
                        {{ $user->ratings()->where('rating', '>=', 4)->count() }}
                    </div>
                    <div class="text-sm text-gray-500">High Ratings</div>
                </div>
            </div>
        </div>

        <!-- Recent Ratings -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Recent Ratings</h3>
                <a href="{{ route('admin.ratings.byUser', $user->id) }}" class="text-blue-600 hover:text-blue-900 text-sm">
                    View All →
                </a>
            </div>
            
            @if($user->ratings()->count() > 0)
                <div class="space-y-4">
                    @foreach($user->ratings()->with('tea')->latest()->take(5)->get() as $rating)
                        <div class="border-b pb-4 last:border-b-0">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="font-medium">{{ $rating->tea->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $rating->tea->flavor }} • {{ $rating->tea->caffeine_level }}</div>
                                    @if($rating->side_note)
                                        <div class="mt-2 text-sm text-gray-600">
                                            <span class="font-medium">Note:</span> {{ $rating->side_note }}
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-4 text-right">
                                    <div class="text-lg font-bold text-yellow-500">
                                        {{ str_repeat('⭐', $rating->rating) }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $rating->created_at->format('M j, Y') }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center text-gray-500 py-8">
                    <div class="text-4xl mb-2">⭐</div>
                    <p>No ratings yet</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

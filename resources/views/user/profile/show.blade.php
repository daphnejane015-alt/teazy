@extends('layouts.sidebar')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-2xl p-8 mb-8 text-white">
        <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
            <!-- Profile Picture -->
            <div class="relative">
                <div class="w-32 h-32 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center border-4 border-white/30">
                    <span class="text-5xl">👤</span>
                </div>
                <div class="absolute bottom-0 right-0 bg-green-400 w-8 h-8 rounded-full flex items-center justify-center border-4 border-white">
                    <span class="text-white text-sm">✓</span>
                </div>
            </div>
            
            <!-- Profile Info -->
            <div class="flex-1 text-center md:text-left">
                <h1 class="text-3xl font-bold mb-2">{{ $user->name }}</h1>
                <p class="text-white/90 mb-4">{{ $user->email }}</p>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex flex-col gap-2">
                <a href="{{ route('user.profile.edit') }}" class="bg-white text-green-600 px-6 py-2 rounded-lg font-semibold hover:bg-white/90 transition duration-200">
                    ✏️ Edit Profile
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Profile Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Information -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <span class="text-2xl mr-2">📋</span>
                    Personal Information
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-500">Full Name</label>
                        <p class="text-gray-900 font-medium">{{ $user->name }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Email Address</label>
                        <p class="text-gray-900 font-medium">{{ $user->email }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Phone Number</label>
                        <p class="text-gray-900 font-medium">{{ $user->phone ?? 'Not provided' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Member Since</label>
                        <p class="text-gray-900 font-medium">{{ $user->created_at->format('M d, Y') }}</p>
                    </div>
                </div>
                
                @if ($user->bio)
                <div class="mt-4">
                    <label class="text-sm text-gray-500">Bio</label>
                    <p class="text-gray-900 mt-1">{{ $user->bio }}</p>
                </div>
                @endif
            </div>

            <!-- Tea Preferences -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <span class="text-2xl mr-2">🍵</span>
                    Tea Preferences
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-500">Favorite Tea Type</label>
                        <p class="text-gray-900 font-medium">{{ $user->favorite_tea_type ?? 'Not specified' }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-500">Caffeine Preference</label>
                        <p class="text-gray-900 font-medium">
                            @if ($user->caffeine_preference)
                                <span class="capitalize">{{ $user->caffeine_preference }}</span>
                            @else
                                Not specified
                            @endif
                        </p>
                    </div>
                </div>
            </div>

                    </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('user.dashboard') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                        <span class="text-xl">🏠</span>
                        <span class="text-gray-700">Dashboard</span>
                    </a>
                    <a href="{{ route('top.tea') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                        <span class="text-xl">🍵</span>
                        <span class="text-gray-700">Top Tea</span>
                    </a>
                    <a href="{{ route('recommendations') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                        <span class="text-xl">�</span>
                        <span class="text-gray-700">Recommendations</span>
                    </a>
                </div>
            </div>

                    </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-gray-900 mb-4">Delete Account</h3>
        <p class="text-gray-600 mb-6">Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently removed.</p>
        
        <form method="POST" action="{{ route('user.profile.destroy') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Enter your password to confirm:</label>
                <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-600 text-white py-2 rounded-lg font-semibold hover:bg-red-700 transition duration-200">
                    Delete Account
                </button>
                <button type="button" onclick="closeDeleteModal()" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg font-semibold hover:bg-gray-300 transition duration-200">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete() {
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
</script>
@endsection

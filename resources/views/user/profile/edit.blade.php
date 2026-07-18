@extends('layouts.sidebar')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Edit Profile</h1>
        <p class="text-gray-600">Update your personal information and preferences</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('user.profile.update') }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <!-- Personal Information -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <span class="text-2xl mr-2">👤</span>
                        Personal Information
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Full Name
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="{{ old('name', $user->name) }}" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                                placeholder="Enter your full name"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Username -->
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                                Username
                            </label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                value="{{ old('username', $user->username) }}" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                                placeholder="Choose a username"
                            >
                            @error('username')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email', $user->email) }}" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                                placeholder="Enter your email address"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number
                            </label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                value="{{ old('phone', $user->phone) }}" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                                placeholder="Enter your phone number"
                            >
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Member Since (Read-only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Member Since
                            </label>
                            <input 
                                type="text" 
                                value="{{ $user->created_at->format('M d, Y') }}" 
                                readonly
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-600"
                            >
                        </div>

                        <!-- Bio -->
                        <div class="md:col-span-2">
                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
                                Bio
                            </label>
                            <textarea 
                                id="bio" 
                                name="bio" 
                                rows="4" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                                placeholder="Tell us about yourself and your tea preferences..."
                            >{{ old('bio', $user->bio) }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">{{ 500 - strlen(old('bio', $user->bio ?? '')) }} characters remaining</p>
                            @error('bio')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Tea Preferences -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <span class="text-2xl mr-2">🍵</span>
                        Tea Preferences
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Favorite Tea -->
                        <div>
                            <label for="favorite_tea_type" class="block text-sm font-medium text-gray-700 mb-2">
                                Favorite Tea
                            </label>
                            <input 
                                type="text" 
                                id="favorite_tea_type" 
                                name="favorite_tea_type" 
                                value="{{ old('favorite_tea_type', $user->favorite_tea_type) }}" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                                placeholder="e.g., Jasmine Green Tea, Earl Grey"
                            >
                            @error('favorite_tea_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Caffeine Preference -->
                        <div>
                            <label for="caffeine_preference" class="block text-sm font-medium text-gray-700 mb-2">
                                Caffeine Preference
                            </label>
                            <select 
                                id="caffeine_preference" 
                                name="caffeine_preference" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                            >
                                <option value="">Select preference</option>
                                <option value="low" {{ old('caffeine_preference', $user->caffeine_preference) == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('caffeine_preference', $user->caffeine_preference) == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('caffeine_preference', $user->caffeine_preference) == 'high' ? 'selected' : '' }}>High</option>
                                <option value="caffeine_free" {{ old('caffeine_preference', $user->caffeine_preference) == 'caffeine_free' ? 'selected' : '' }}>Caffeine Free</option>
                            </select>
                            @error('caffeine_preference')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition duration-200">
                        💾 Save Changes
                    </button>
                    <a href="{{ route('user.profile.show') }}" class="flex-1 bg-gray-200 text-gray-800 py-3 px-6 rounded-lg font-semibold hover:bg-gray-300 transition duration-200 text-center">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Profile Tips -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <span class="text-xl mr-2">💡</span>
                    Profile Tips
                </h3>
                <ul class="space-y-3 text-sm text-gray-700">
                    <li class="flex items-start gap-2">
                        <span class="text-green-600 mt-1">✓</span>
                        <span>Complete your profile to get better tea recommendations</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-600 mt-1">✓</span>
                        <span>Add your favorite tea type for personalized suggestions</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-600 mt-1">✓</span>
                        <span>Keep your email updated for important notifications</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-green-600 mt-1">✓</span>
                        <span>Your bio helps us understand your preferences better</span>
                    </li>
                </ul>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('user.profile.show') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                        <span class="text-xl">👁️</span>
                        <span class="text-gray-700">View Profile</span>
                    </a>
                                        <a href="{{ route('user.dashboard') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                        <span class="text-xl">🏠</span>
                        <span class="text-gray-700">Dashboard</span>
                    </a>
                </div>
            </div>

            <!-- Account Security -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <span class="text-xl mr-2">🔒</span>
                    Account Security
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Email Verified</span>
                        @if ($user->email_verified_at)
                            <span class="text-green-600 font-medium">✓ Verified</span>
                        @else
                            <span class="text-yellow-600 font-medium">⚠ Not Verified</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Last Login</span>
                        <span class="text-gray-600 font-medium">Recently</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Character counter for bio
document.getElementById('bio').addEventListener('input', function() {
    const remaining = 500 - this.value.length;
    const counter = this.parentElement.querySelector('p.text-sm.text-gray-500');
    counter.textContent = remaining + ' characters remaining';
    
    if (remaining < 50) {
        counter.classList.add('text-red-600');
        counter.classList.remove('text-gray-500');
    } else {
        counter.classList.remove('text-red-600');
        counter.classList.add('text-gray-500');
    }
});
</script>
@endsection

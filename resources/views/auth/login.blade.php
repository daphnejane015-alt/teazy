<x-guest-layout>
    <!-- Session Status -->
    @if (session('status'))
        <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('status') }}
        </div>
    @endif

    <!-- Success Messages -->
    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Login Form -->
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Header -->
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome Back</h2>
            <p class="text-gray-600">Sign in to your account to continue</p>
        </div>

        <!-- Email Address -->
        <div class="input-group">
            <input 
                id="email" 
                type="email" 
                name="email" 
                :value="old('email')" 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                placeholder=" "
                required 
                autofocus 
                autocomplete="username"
            >
            <label for="email" class="bg-white px-1">Email Address</label>
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="input-group">
            <input 
                id="password" 
                type="password" 
                name="password" 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                placeholder=" "
                required 
                autocomplete="current-password"
            >
            <label for="password" class="bg-white px-1">Password</label>
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me -->
        <div class="flex items-center">
            <label class="flex items-center">
                <input 
                    type="checkbox" 
                    name="remember" 
                    class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                >
                <span class="ml-2 text-sm text-gray-600">Remember me</span>
            </label>
        </div>

        <!-- Login Button -->
        <button type="submit" class="w-full btn-primary text-white font-semibold py-3 px-4 rounded-lg transition duration-200">
            Sign In
        </button>

        <!-- Register Link -->
        <div class="text-center pt-6 border-t border-gray-200">
            <p class="text-gray-600">
                Don't have an account? 
                <a href="{{ route('register') }}" class="text-green-600 hover:text-green-500 font-semibold transition duration-200">
                    Sign up now
                </a>
            </p>
            <p class="text-gray-600 mt-2">
                Admin access? 
                <a href="{{ route('admin.login') }}" class="text-green-600 hover:text-green-500 font-semibold transition duration-200">
                    Admin Login
                </a>
            </p>
        </div>
    </form>
</x-guest-layout>

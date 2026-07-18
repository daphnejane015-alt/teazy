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
        <div class="input-group relative">
            <input 
                id="password" 
                type="password" 
                name="password" 
                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                placeholder=" "
                required 
                autocomplete="current-password"
            >
            <label for="password" class="bg-white px-1">Password</label>
            <button type="button" class="toggle-password text-gray-500 hover:text-green-600 focus:outline-none" data-target="password" aria-label="Show password" tabindex="-1">
                <svg class="eye-open w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg class="eye-closed w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 01-1.18-2.19m15.084 9.393A10.05 10.05 0 0021.542 12C20.268 7.943 16.478 5 12 5a10.05 10.05 0 00-2.68.366M9 9l-1.63 1.63m5.53 5.53l-1.63-1.63M3 3l18 18"/>
                </svg>
            </button>
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between">
            <label class="flex items-center">
                <input 
                    type="checkbox" 
                    name="remember" 
                    class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
                >
                <span class="ml-2 text-sm text-gray-600">Remember me</span>
            </label>

            <a href="{{ route('password.request') }}" class="text-sm text-green-600 hover:text-green-500 font-semibold transition duration-200">
                Forgot password?
            </a>
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

@extends('layouts.sidebar')

@section('content')
<div class="space-y-6">
    <!-- Welcome Card -->
    <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-8 text-white">
        <h2 class="text-3xl font-bold mb-4">Welcome to Teazy! 🍵</h2>
        <p class="text-lg mb-6">Discover your perfect tea match based on weather, preferences, and mood.</p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                <div class="text-2xl mb-2">🌤️</div>
                <h3 class="font-semibold mb-1">Weather-Based</h3>
                <p class="text-sm text-white/80">Get recommendations based on current weather</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                <div class="text-2xl mb-2">⭐</div>
                <h3 class="font-semibold mb-1">Top Rated</h3>
                <p class="text-sm text-white/80">Browse the highest-rated teas</p>
            </div>
            <div class="bg-white/20 backdrop-blur-sm rounded-lg p-4">
                <div class="text-2xl mb-2">🎯</div>
                <h3 class="font-semibold mb-1">Personalized</h3>
                <p class="text-sm text-white/80">Get tea suggestions tailored to you</p>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="{{ route('top.tea') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
            <div class="text-3xl mb-4">🍵</div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Top Tea</h3>
            <p class="text-gray-600 text-sm">Get weather-based tea recommendations</p>
        </a>

        <a href="{{ route('recommendations') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
            <div class="text-3xl mb-4">⭐</div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">My Recommendations</h3>
            <p class="text-gray-600 text-sm">View your personalized tea suggestions</p>
        </a>

        <a href="{{ route('tea.search') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
            <div class="text-3xl mb-4">🔍</div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Search Tea</h3>
            <p class="text-gray-600 text-sm">Explore our tea collection</p>
        </a>

        <a href="{{ route('find.tea') }}" class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition duration-200">
            <div class="text-3xl mb-4">🎯</div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Find Tea</h3>
            <p class="text-gray-600 text-sm">Get personalized recommendations</p>
        </a>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6">Recent Activity</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <span class="text-lg">🍵</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Welcome to Teazy!</p>
                        <p class="text-sm text-gray-500">Start exploring our tea recommendations</p>
                    </div>
                </div>
                <span class="text-sm text-gray-500">Just now</span>
            </div>
        </div>
        
        <div class="mt-6 text-center">
            <a href="{{ route('top.tea') }}" class="inline-flex items-center text-green-600 hover:text-green-500 font-medium">
                Get your first tea recommendation →
            </a>
        </div>
    </div>
</div>
@endsection

@extends('layouts.sidebar')
@section('content')

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-2" style="color: var(--text-dark);">
        🇲🇾 Top Tea This Week - Malaysia Focus
    </h1>
    <p class="text-lg" style="color: var(--text-light);">
        Weather-based tea recommendations optimized for Malaysian climate patterns
    </p>
    @if($weeklyWeatherTeas && $weeklyWeatherTeas->isNotEmpty() && isset($weeklyWeatherTeas[0]['weather_details']['is_hot']) && $weeklyWeatherTeas[0]['weather_details']['is_hot'])
        <div class="mt-2 p-2 rounded" style="background: var(--light-green);">
            <p class="text-sm text-white">
                🌡️ <strong>Hot Weather Alert:</strong> Staying cool with refreshing Malaysian tea recommendations
            </p>
        </div>
    @endif
</div>

<!-- Weather-Based Weekly Recommendations -->
@if($weeklyWeatherTeas && $weeklyWeatherTeas->isNotEmpty())
    <div class="tea-card p-6 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold" style="color: var(--text-dark);">
                📅 Your Weekly Tea Forecast
            </h2>
            <div class="text-right">
                <p class="text-sm font-medium" style="color: var(--text-medium);">
                    🇲🇾 {{ $weatherInfo['city'] }}
                </p>
                @if($weatherInfo['current'])
                    <p class="text-xs" style="color: var(--text-light);">
                        Currently {{ round($weatherInfo['current']->temperature) }}°C, {{ $weatherInfo['current']->description }}
                    </p>
                    <p class="text-xs" style="color: var(--text-light);">
                        🕐 {{ \App\Services\WeatherService::formatMalaysianTime(now()) }} (MYT)
                    </p>
                @endif
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($weeklyWeatherTeas as $dayRecommendation)
                <div class="tea-card overflow-hidden group flex flex-col h-full">
                    <!-- Day Header Section -->
                    <div class="relative" style="background: linear-gradient(135deg, var(--accent-green), var(--primary-green));">
                        <!-- Top Section with Day Info -->
                        <div class="p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold bg-white bg-opacity-20 backdrop-blur-sm">
                                        {{ $dayRecommendation['day_number'] }}
                                    </div>
                                    <div class="text-white">
                                        <p class="text-sm font-semibold">{{ $dayRecommendation['day_name'] }}</p>
                                        <p class="text-xs opacity-90">{{ $dayRecommendation['short_date'] }}</p>
                                    </div>
                                </div>
                                
                                <!-- Weather Icon and Temp -->
                                <div class="text-right text-white">
                                    @if($dayRecommendation['weather_details']['icon_code'])
                                        <img src="{{ \App\Services\WeatherService::getWeatherIconUrl($dayRecommendation['weather_details']['icon_code']) }}" 
                                             alt="{{ $dayRecommendation['weather_details']['description'] }}" 
                                             class="w-10 h-10 mb-1">
                                    @else
                                        <span class="text-3xl block mb-1">{{ $dayRecommendation['weather_details']['tea_emoji'] }}</span>
                                    @endif
                                    <p class="text-lg font-bold">{{ $dayRecommendation['weather_details']['temperature'] }}°C</p>
                                    <p class="text-xs opacity-90 capitalize">{{ $dayRecommendation['weather_details']['description'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tea Recommendation Section -->
                    <div class="p-4 flex flex-col flex-1">
                        @foreach($dayRecommendation['teas'] as $tea)
                            <div class="flex items-center space-x-3 mb-3">
                                <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 border-2" style="border-color: var(--accent-green);">
                                    @php
                                        $fallbackImage = 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';
                                        $imgSrc = $tea->image
                                            ? (str_starts_with($tea->image, 'http') ? $tea->image
                                                : (str_starts_with($tea->image, '//') ? 'https:'.$tea->image
                                                : (str_starts_with($tea->image, '/storage/') ? $tea->image : '/storage/'.$tea->image)))
                                            : $fallbackImage;
                                    @endphp
                                    
                                    <img src="{{ $imgSrc }}" 
                                         class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" 
                                         alt="{{ $tea->name }}"
                                         onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                                </div>
                                 
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between mb-1">
                                        <h4 class="text-base font-bold truncate group-hover:text-green-700 transition-colors" style="color: var(--text-dark);">
                                            {{ $tea->name }}
                                        </h4>
                                        <button type="button" class="favourite-btn ml-1 p-1 rounded-full hover:bg-red-50 transition-colors"
                                                data-tea-id="{{ $tea->id }}"
                                                onclick="toggleFavourite({{ $tea->id }}, this)">
                                            <svg class="w-5 h-5 text-gray-400 favourite-icon" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="text-sm leading-relaxed mb-2" style="color: var(--text-medium);">
                                        {{ $tea->health_benefit }}
                                    </p>
                                    <div class="flex items-center flex-wrap gap-1 mb-2">
                                        <span class="text-xs font-medium px-2 py-1 rounded" style="background: var(--cream-green); color: var(--accent-green);">
                                            ⭐ {{ $tea->averageRating() ? number_format($tea->averageRating(), 1) : 'N/A' }}
                                        </span>
                                        @if(isset($tea->weather_score) && $tea->weather_score !== null)
                                            <span class="text-xs px-2 py-1 rounded text-white" style="background: var(--accent-green);">
                                                {{ round($tea->weather_score * 100) }}%
                                            </span>
                                        @else
                                            <span class="text-xs px-2 py-1 rounded" style="background: var(--light-green); color: var(--text-medium);">
                                                Weather Match: N/A
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex gap-1">
                                        <button type="button" class="text-xs px-2 py-1 rounded bg-green-100 text-green-700 hover:bg-green-200 transition-colors"
                                                onclick="openTeaDetails({{ $tea->id }}, '{{ addslashes($tea->name) }}', '{{ addslashes($tea->flavor) }}', '{{ addslashes($tea->caffeine_level) }}', '{{ addslashes($tea->health_benefit) }}', '{{ $imgSrc }}', '{{ $tea->shop_link ?? '' }}', '{{ $tea->source_url ?? '' }}')">
                                            Details
                                        </button>
                                        @if($tea->shop_link)
                                            <a href="{{ $tea->shop_link }}" target="_blank" class="text-xs px-2 py-1 rounded bg-amber-100 text-amber-700 hover:bg-amber-200 transition-colors flex items-center gap-1">
                                                Shop
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        
                        <!-- Recommendation Reason -->
                        <div class="p-3 rounded-lg text-sm mt-auto" style="background: var(--cream-green); border-left: 3px solid var(--accent-green);">
                            <div class="flex items-start space-x-2">
                                <span class="text-lg flex-shrink-0">💡</span>
                                <p class="leading-relaxed" style="color: var(--text-medium);">
                                    {{ $dayRecommendation['recommendation_reason'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif


<!-- Regular Top Teas (Fallback or Additional) -->
<div class="mb-8">
    <h2 class="text-2xl font-bold mb-6" style="color: var(--text-dark);">
        🏆 Top 5 Rated Teas
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($topTeas as $tea)
            <div class="tea-card overflow-hidden group">
                <div class="relative h-48 overflow-hidden">
                    @php
                        $fallbackImage = 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';
                        $imgSrc = $tea->image
                            ? (str_starts_with($tea->image, 'http') ? $tea->image
                                : (str_starts_with($tea->image, '//') ? 'https:'.$tea->image
                                : (str_starts_with($tea->image, '/storage/') ? $tea->image : '/storage/'.$tea->image)))
                            : $fallbackImage;
                    @endphp
                    
                    <img src="{{ $imgSrc }}" 
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                         alt="{{ $tea->name }}"
                         onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                    
                    <!-- Rating Badge -->
                    <div class="absolute top-4 right-4">
                        <span class="score-badge">
                            {{ $tea->averageRating() ? number_format($tea->averageRating(), 1) : 'N/A' }}
                        </span>
                    </div>
                    
                    <!-- Flavor Tag -->
                    <div class="absolute top-4 left-4">
                        <span class="flavor-tag">
                            {{ $tea->flavor }}
                        </span>
                    </div>
                </div>

                <div class="p-4">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="text-lg font-bold group-hover:text-green-700 transition-colors" style="color: var(--text-dark);">
                            {{ $tea->name }}
                        </h3>
                        <button type="button" class="favourite-btn ml-1 p-1 rounded-full hover:bg-red-50 transition-colors"
                                data-tea-id="{{ $tea->id }}"
                                onclick="toggleFavourite({{ $tea->id }}, this)">
                            <svg class="w-5 h-5 text-gray-400 favourite-icon" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-1 mb-3">
                        <div class="flex items-center justify-between py-1 border-b" style="border-color: var(--border-color);">
                            <span class="text-xs font-medium flex items-center" style="color: var(--text-medium);">
                                <span class="mr-2">🍃</span> Flavor
                            </span>
                            <span class="text-xs font-semibold" style="color: var(--accent-green);">
                                {{ $tea->flavor }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-1 border-b" style="border-color: var(--border-color);">
                            <span class="text-xs font-medium flex items-center" style="color: var(--text-medium);">
                                <span class="mr-2">⚡</span> Caffeine
                            </span>
                            <span class="text-xs font-semibold" style="color: var(--accent-green);">
                                {{ $tea->caffeine_level }}
                            </span>
                        </div>
                    </div>

                    <p class="text-xs mb-3 line-clamp-2" style="color: var(--text-light);">
                        {{ $tea->health_benefit }}
                    </p>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <button type="button" class="btn-secondary text-xs py-1.5 px-2 text-center"
                                onclick="openTeaDetails({{ $tea->id }}, '{{ addslashes($tea->name) }}', '{{ addslashes($tea->flavor) }}', '{{ addslashes($tea->caffeine_level) }}', '{{ addslashes($tea->health_benefit) }}', '{{ $imgSrc }}', '{{ $tea->shop_link ?? '' }}', '{{ $tea->source_url ?? '' }}')">
                            👁️ Details
                        </button>
                        @if($tea->shop_link)
                            <a href="{{ $tea->shop_link }}" target="_blank" rel="noopener noreferrer"
                               class="btn-primary text-xs py-1.5 px-2 text-center flex items-center justify-center gap-1">
                                Shop
                            </a>
                        @elseif($tea->source_url)
                            <a href="{{ $tea->source_url }}" target="_blank" rel="noopener noreferrer"
                               class="btn-secondary text-xs py-1.5 px-2 text-center flex items-center justify-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                Source
                            </a>
                        @else
                            <span class="text-xs text-gray-400 py-1.5 px-2 text-center border border-dashed rounded-lg">
                                No link
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between pt-2 border-t" style="border-color: var(--border-color);">
                        <div class="flex items-center space-x-1">
                            <span class="text-sm font-medium" style="color: var(--accent-green);">
                                {{ $tea->averageRating() ? number_format($tea->averageRating(), 1) : 'N/A' }}
                            </span>
                        </div>
                        <span class="text-xs" style="color: var(--text-light);">
                            {{ $tea->ratings_count }} ratings
                        </span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Debug Information (Remove in production) -->
@if(auth()->user() && auth()->user()->preference)
    @php
        $weatherService = app(\App\Services\WeatherService::class);
        $apiStats = $weatherService->getApiUsageStats();
    @endphp
    
    <div class="mb-4 p-4 rounded border" style="background: var(--cream-green); border-color: var(--accent-green);">
        <h4 class="font-bold mb-2" style="color: var(--text-dark);">🔍 System Information:</h4>
        
        <!-- Weather Status -->
        <div class="grid grid-cols-2 gap-4 text-sm mb-4">
            <div>
                <p style="color: var(--text-medium);">
                    <strong>Weather Enabled:</strong> 
                    <span class="{{ auth()->user()->preference->weather_based_recommendations ? 'text-green-600' : 'text-red-600' }}">
                        {{ auth()->user()->preference->weather_based_recommendations ? '✅ Yes' : '❌ No' }}
                    </span>
                </p>
                <p style="color: var(--text-medium);">
                    <strong>City:</strong> 
                    <span class="{{ auth()->user()->preference->city ? 'text-green-600' : 'text-red-600' }}">
                        {{ auth()->user()->preference->city ?: '❌ Not set' }}
                    </span>
                </p>
                <p style="color: var(--text-medium);">
                    <strong>Weather Preference:</strong> 
                    <span class="{{ auth()->user()->preference->weather_preference ? 'text-green-600' : 'text-orange-600' }}">
                        {{ auth()->user()->preference->weather_preference ?: '⚠️ Not set (defaults to auto)' }}
                    </span>
                </p>
            </div>
            <div>
                <p style="color: var(--text-medium);">
                    <strong>Weekly Weather Data:</strong> 
                    <span class="{{ $weeklyWeatherTeas && $weeklyWeatherTeas->isNotEmpty() ? 'text-green-600' : 'text-red-600' }}">
                        {{ $weeklyWeatherTeas ? '✅ ' . $weeklyWeatherTeas->count() . ' days' : '❌ None' }}
                    </span>
                </p>
                <p style="color: var(--text-medium);">
                    <strong>API Key Status:</strong> 
                    <span class="{{ config('services.openweather.api_key') ? 'text-green-600' : 'text-red-600' }}">
                        {{ config('services.openweather.api_key') ? '✅ Set' : '❌ Missing' }}
                    </span>
                </p>
                <p style="color: var(--text-medium);">
                    <strong>Weather Info:</strong> 
                    <span class="{{ $weatherInfo ? 'text-green-600' : 'text-red-600' }}">
                        {{ $weatherInfo ? '✅ Available' : '❌ Not available' }}
                    </span>
                </p>
            </div>
        </div>
        
        <!-- API Usage Status -->
        <div class="border-t pt-3" style="border-color: var(--border-color);">
            <h5 class="font-semibold mb-2" style="color: var(--text-dark);">📊 API Usage Until Jan 31:</h5>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div class="text-center p-2 rounded" style="background: var(--pale-green);">
                    <div class="font-bold" style="color: {{ $apiStats['daily_percentage'] >= 90 ? 'var(--danger-red)' : 'var(--accent-green)' }};">
                        {{ $apiStats['daily_calls'] }}/{{ $apiStats['daily_limit'] }}
                    </div>
                    <p class="text-xs" style="color: var(--text-light);">Today's Calls</p>
                </div>
                <div class="text-center p-2 rounded" style="background: var(--pale-green);">
                    <div class="font-bold" style="color: {{ $apiStats['days_until_jan31'] <= 3 ? 'var(--danger-red)' : 'var(--accent-green)' }};">
                        {{ $apiStats['days_until_jan31'] }} days
                    </div>
                    <p class="text-xs" style="color: var(--text-light);">Until Deadline</p>
                </div>
                <div class="text-center p-2 rounded" style="background: var(--pale-green);">
                    <div class="font-bold" style="color: var(--accent-green);">
                        {{ $apiStats['cache_duration_minutes'] }} min
                    </div>
                    <p class="text-xs" style="color: var(--text-light);">Cache Duration</p>
                </div>
            </div>
            <div class="mt-2 p-2 rounded text-xs" 
                 style="background: {{ $apiStats['days_until_jan31'] <= 1 ? 'var(--danger-red)' : 
                               ($apiStats['days_until_jan31'] <= 3 ? 'var(--warning-orange)' : 'var(--light-green)') }};">
                <p style="color: black;">
                    📋 <strong>Status:</strong> {{ $apiStats['recommendation'] }}
                </p>
            </div>
        </div>
        
        @if(!auth()->user()->preference->weather_based_recommendations)
            <div class="mt-3 p-2 rounded" style="background: var(--light-green);">
                <p class="text-xs text-white">
                    💡 <strong>Solution:</strong> Go to <a href="{{ route('find.tea') }}" class="underline">Find Tea</a> and enable weather-based recommendations
                </p>
            </div>
        @elseif(!auth()->user()->preference->city)
            <div class="mt-3 p-2 rounded" style="background: var(--light-green);">
                <p class="text-xs text-white">
                    💡 <strong>Solution:</strong> Go to <a href="{{ route('find.tea') }}" class="underline">Find Tea</a> and enter your city
                </p>
            </div>
        @elseif(!config('services.openweather.api_key'))
            <div class="mt-3 p-2 rounded" style="background: var(--warning-orange);">
                <p class="text-xs text-white">
                    ⚠️ <strong>Action Required:</strong> Add OpenWeatherMap API key to your .env file
                </p>
            </div>
        @elseif(!$weeklyWeatherTeas || $weeklyWeatherTeas->isEmpty())
            <div class="mt-3 p-2 rounded" style="background: var(--warning-orange);">
                <p class="text-xs text-white">
                    ⚠️ <strong>Issue:</strong> Weather data not found. The system will try to fetch it automatically.
                </p>
            </div>
        @endif
    </div>
@endif

<!-- Enable Weather Recommendations CTA -->
@if(!$weeklyWeatherTeas || $weeklyWeatherTeas->isEmpty())
    <div class="text-center py-8">
        <div class="max-w-md mx-auto">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: var(--cream-green);">
                <span class="text-3xl">🌤️</span>
            </div>
            
            <h3 class="text-xl font-bold mb-3" style="color: var(--text-dark);">
                Get Weather-Based Tea Recommendations!
            </h3>
            
            <p class="text-sm mb-4" style="color: var(--text-light);">
                Enable weather-based recommendations to get personalized tea suggestions for each day of the week based on your local weather forecast.
            </p>
            
            <a href="{{ route('find.tea') }}" class="btn-primary">
                🌤️ Set Up Weather Preferences
            </a>
        </div>
    </div>
@endif

<!-- Tea Details Modal -->
<div id="teaDetailsModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" onclick="closeTeaDetails()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all max-w-2xl w-full">
                <div class="relative h-64">
                    <img id="modalTeaImage" src="" alt="" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                    <button onclick="closeTeaDetails()" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/90 hover:bg-white flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="absolute bottom-4 left-6 right-6">
                        <h2 id="modalTeaName" class="text-3xl font-bold text-white mb-2"></h2>
                        <span id="modalTeaFlavor" class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white backdrop-blur-sm"></span>
                    </div>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-xl bg-green-50 border border-green-100">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-2xl">⚡</span>
                                <span class="text-sm font-medium text-green-700">Caffeine Level</span>
                            </div>
                            <p id="modalTeaCaffeine" class="text-lg font-bold text-green-800"></p>
                        </div>
                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-100">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-2xl">🌿</span>
                                <span class="text-sm font-medium text-amber-700">Flavor</span>
                            </div>
                            <p id="modalTeaFlavor2" class="text-lg font-bold text-amber-800"></p>
                        </div>
                    </div>
                    <div class="p-5 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 border border-green-100">
                        <h3 class="text-lg font-bold text-green-800 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            Health Benefits
                        </h3>
                        <p id="modalTeaHealth" class="text-green-700 leading-relaxed"></p>
                    </div>
                    <!-- Rating Section -->
                    <div class="p-5 rounded-xl bg-gradient-to-br from-amber-50 to-yellow-50 border border-amber-100">
                        <h3 class="text-lg font-bold text-amber-800 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                            Rate This Tea
                        </h3>
                        <div class="flex items-center gap-2 mb-3">
                            <div id="starRating" class="flex gap-1">
                                <button type="button" class="star-btn text-3xl text-gray-300 hover:text-amber-400 transition-colors" data-rating="1">★</button>
                                <button type="button" class="star-btn text-3xl text-gray-300 hover:text-amber-400 transition-colors" data-rating="2">★</button>
                                <button type="button" class="star-btn text-3xl text-gray-300 hover:text-amber-400 transition-colors" data-rating="3">★</button>
                                <button type="button" class="star-btn text-3xl text-gray-300 hover:text-amber-400 transition-colors" data-rating="4">★</button>
                                <button type="button" class="star-btn text-3xl text-gray-300 hover:text-amber-400 transition-colors" data-rating="5">★</button>
                            </div>
                            <span id="ratingText" class="text-sm text-amber-700 font-medium"></span>
                        </div>
                        <button type="button" id="submitRatingBtn" onclick="submitRating()" class="w-full btn-primary py-2 px-4 text-sm font-medium rounded-lg">
                            Submit Rating
                        </button>
                        <p id="ratingMessage" class="text-sm mt-2 text-center hidden"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function toggleFavourite(teaId, btn) {
    const icon = btn.querySelector('.favourite-icon');
    const isFavourited = icon.classList.contains('text-red-500');
    try {
        if (isFavourited) {
            await fetch(`/favourites/${teaId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            icon.classList.remove('text-red-500');
            icon.classList.add('text-gray-400');
        } else {
            await fetch('/favourites', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ tea_id: teaId })
            });
            icon.classList.remove('text-gray-400');
            icon.classList.add('text-red-500');
        }
        loadFavourites();
    } catch (e) {
        console.error('Error toggling favourite:', e);
    }
}

let currentTeaId = null;
let selectedRating = 0;

function openTeaDetails(id, name, flavor, caffeine, healthBenefit, image, shopLink, sourceUrl) {
    currentTeaId = id;
    document.getElementById('modalTeaImage').src = image;
    document.getElementById('modalTeaName').textContent = name;
    document.getElementById('modalTeaFlavor').textContent = flavor;
    document.getElementById('modalTeaFlavor2').textContent = flavor;
    document.getElementById('modalTeaCaffeine').textContent = caffeine;
    document.getElementById('modalTeaHealth').textContent = healthBenefit || 'No health benefit information available.';

    // Reset rating UI
    resetRatingUI();
    loadUserRating(id);

    document.getElementById('teaDetailsModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeTeaDetails() {
    document.getElementById('teaDetailsModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
    currentTeaId = null;
    selectedRating = 0;
}

function resetRatingUI() {
    selectedRating = 0;
    document.querySelectorAll('.star-btn').forEach(btn => {
        btn.classList.remove('text-amber-400');
        btn.classList.add('text-gray-300');
    });
    document.getElementById('ratingText').textContent = '';
    document.getElementById('ratingMessage').classList.add('hidden');
    document.getElementById('submitRatingBtn').disabled = false;
    document.getElementById('submitRatingBtn').textContent = 'Submit Rating';
}

function loadUserRating(teaId) {
    fetch(`/ratings/check/${teaId}`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.rating) {
            selectedRating = data.rating;
            updateStarDisplay(data.rating);
            document.getElementById('ratingText').textContent = `Your rating: ${data.rating}/5`;
            document.getElementById('submitRatingBtn').textContent = 'Update Rating';
        }
    })
    .catch(e => console.error('Error loading rating:', e));
}

function updateStarDisplay(rating) {
    document.querySelectorAll('.star-btn').forEach(btn => {
        const btnRating = parseInt(btn.dataset.rating);
        if (btnRating <= rating) {
            btn.classList.remove('text-gray-300');
            btn.classList.add('text-amber-400');
        } else {
            btn.classList.remove('text-amber-400');
            btn.classList.add('text-gray-300');
        }
    });
}

async function submitRating() {
    if (!currentTeaId || selectedRating === 0) {
        return;
    }

    const submitBtn = document.getElementById('submitRatingBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    try {
        const res = await fetch('/ratings', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                tea_id: currentTeaId,
                rating: selectedRating
            })
        });

        if (res.ok) {
            showMessage('Rating submitted successfully!', 'success');
            document.getElementById('submitRatingBtn').textContent = 'Update Rating';
        }
    } catch (e) {
        console.error('Error submitting rating:', e);
    } finally {
        submitBtn.disabled = false;
    }
}

function showMessage(message, type) {
    const msgEl = document.getElementById('ratingMessage');
    msgEl.textContent = message;
    msgEl.classList.remove('hidden', 'text-green-600', 'text-red-600');
    msgEl.classList.add('text-green-600');
    setTimeout(() => msgEl.classList.add('hidden'), 3000);
}

// Star click handlers
document.querySelectorAll('.star-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        selectedRating = parseInt(this.dataset.rating);
        updateStarDisplay(selectedRating);
        const ratingTexts = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
        document.getElementById('ratingText').textContent = ratingTexts[selectedRating];
    });
});

async function checkFavourites() {
    document.querySelectorAll('.favourite-btn').forEach(async btn => {
        const teaId = btn.dataset.teaId;
        try {
            const res = await fetch(`/favourites/check/${teaId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            const icon = btn.querySelector('.favourite-icon');
            if (data.is_favourite) {
                icon.classList.remove('text-gray-400');
                icon.classList.add('text-red-500');
            }
        } catch (e) {
            console.error('Error checking favourite:', e);
        }
    });
}
document.addEventListener('DOMContentLoaded', checkFavourites);
</script>

@endsection

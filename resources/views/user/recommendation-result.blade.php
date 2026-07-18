@extends('layouts.sidebar')
@section('content')

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-6" style="color: var(--text-dark);">
        🍃 Recommended Teas for You
    </h1>
    
    @if($preferences)
        <!-- Enhanced User Preferences Display -->
        <div class="tea-card p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold mb-2" style="color: var(--text-dark);">
                        🎯 Your Personalized Tea Profile
                    </h2>
                    <p class="text-sm" style="color: var(--text-light);">
                        These preferences were used to generate your recommendations
                    </p>
                </div>
                <a href="{{ route('find.tea') }}" class="btn-secondary">
                    ✏️ Update Preferences
                </a>
            </div>
            
            <!-- Main Preferences Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Flavor Preference -->
                <div class="relative overflow-hidden rounded-lg border-2" style="border-color: var(--accent-green); background: var(--cream-green);">
                    <div class="absolute top-2 right-2 w-8 h-8 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                        <span class="text-white text-sm font-bold">1</span>
                    </div>
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: var(--light-green);">
                            <span class="text-2xl text-white">🍃</span>
                        </div>
                        <h3 class="font-bold text-lg mb-2" style="color: var(--text-dark);">Flavor Profile</h3>
                        <p class="text-xl font-bold capitalize mb-2" style="color: var(--accent-green);">
                            {{ str_replace('_', ' ', $preferences->preferred_flavor) }}
                        </p>
                        <div class="text-xs px-3 py-1 rounded-full inline-block" style="background: var(--light-green);">
                            Taste Preference
                        </div>
                    </div>
                </div>
                
                <!-- Caffeine Preference -->
                <div class="relative overflow-hidden rounded-lg border-2" style="border-color: var(--accent-green); background: var(--cream-green);">
                    <div class="absolute top-2 right-2 w-8 h-8 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                        <span class="text-white text-sm font-bold">2</span>
                    </div>
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: var(--light-green);">
                            <span class="text-2xl text-white">⚡</span>
                        </div>
                        <h3 class="font-bold text-lg mb-2" style="color: var(--text-dark);">Caffeine Level</h3>
                        <p class="text-xl font-bold capitalize mb-2" style="color: var(--accent-green);">
                            {{ str_replace('_', ' ', $preferences->preferred_caffeine) }}
                        </p>
                        <div class="text-xs px-3 py-1 rounded-full inline-block" style="background: var(--light-green);">
                            Caffeine Preference
                        </div>
                    </div>
                </div>
                
                <!-- Health Goal -->
                <div class="relative overflow-hidden rounded-lg border-2" style="border-color: var(--accent-green); background: var(--cream-green);">
                    <div class="absolute top-2 right-2 w-8 h-8 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                        <span class="text-white text-sm font-bold">3</span>
                    </div>
                    <div class="p-6 text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background: var(--light-green);">
                            <span class="text-2xl text-white">🌿</span>
                        </div>
                        <h3 class="font-bold text-lg mb-2" style="color: var(--text-dark);">Wellness Goal</h3>
                        <p class="text-xl font-bold capitalize mb-2" style="color: var(--accent-green);">
                            {{ str_replace('_', ' ', $preferences->health_goal) }}
                        </p>
                        <div class="text-xs px-3 py-1 rounded-full inline-block" style="background: var(--light-green);">
                            Health Focus
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Weather Preferences (if enabled) -->
            @if($preferences->weather_based_recommendations)
                <div class="border-t pt-6" style="border-color: var(--border-color);">
                    <h3 class="font-bold text-lg mb-4" style="color: var(--text-dark);">
                        🌤️ Weather-Based Settings
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-center space-x-3 p-3 rounded-lg" style="background: var(--pale-green);">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                                <span class="text-white">📍</span>
                            </div>
                            <div>
                                <p class="font-semibold" style="color: var(--text-dark);">Location</p>
                                <p class="text-sm" style="color: var(--text-medium);">
                                    🇲🇾 {{ $preferences->city ?? 'Not set' }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3 p-3 rounded-lg" style="background: var(--pale-green);">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                                <span class="text-white">🌡️</span>
                            </div>
                            <div>
                                <p class="font-semibold" style="color: var(--text-dark);">Weather Mode</p>
                                <p class="text-sm" style="color: var(--text-medium);">
                                    {{ $preferences->weather_preference ? str_replace('_', ' ', ucfirst($preferences->weather_preference)) : 'Auto' }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-3 p-3 rounded-lg" style="background: var(--pale-green);">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                                <span class="text-white">✅</span>
                            </div>
                            <div>
                                <p class="font-semibold" style="color: var(--text-dark);">Status</p>
                                <p class="text-sm" style="color: var(--text-medium);">
                                    Weather recommendations enabled
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            
            <!-- Preference Summary -->
            <div class="mt-6 p-4 rounded-lg" style="background: var(--light-green);">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-white">
                            🎯 <strong>Your Profile:</strong> 
                            {{ str_replace('_', ' ', $preferences->preferred_flavor) }} • 
                            {{ str_replace('_', ' ', $preferences->preferred_caffeine) }} • 
                            {{ str_replace('_', ' ', $preferences->health_goal) }}
                            @if($preferences->weather_based_recommendations && $preferences->city)
                                • 🇲🇾 {{ $preferences->city }}
                            @endif
                        </p>
                    </div>
                    <div class="text-xs text-white">
                        Last updated: {{ $preferences->updated_at->format('M j, Y g:i A') }}
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Weather Information -->
    @if($preferences && $preferences->weather_based_recommendations && $preferences->city)
        @php
            $currentWeather = \App\Models\Weather::forCity($preferences->city);
            $weeklyForecast = \App\Models\Weather::weeklyForecast($preferences->city);
        @endphp
        
        @if($currentWeather)
            <div class="tea-card p-6 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold" style="color: var(--text-dark);">
                        🌤️ Weather-Based Recommendations
                    </h2>
                    <span class="text-sm" style="color: var(--text-light);">
                        {{ $currentWeather->city }}, {{ $currentWeather->country }}
                    </span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Current Weather -->
                    <div class="text-center p-4 rounded-lg" style="background: var(--cream-green);">
                        <div class="flex items-center justify-center mb-3">
                            @if($currentWeather->icon_code)
                                <img src="{{ \App\Services\WeatherService::getWeatherIconUrl($currentWeather->icon_code) }}" 
                                     alt="{{ $currentWeather->description }}" 
                                     class="w-16 h-16">
                            @else
                                <span class="text-4xl">🌤️</span>
                            @endif
                        </div>
                        <h3 class="font-semibold mb-1" style="color: var(--text-dark);">Current Weather</h3>
                        <p class="text-2xl font-bold" style="color: var(--accent-green);">
                            {{ round($currentWeather->temperature) }}°C
                        </p>
                        <p class="text-sm capitalize" style="color: var(--text-medium);">
                            {{ $currentWeather->description }}
                        </p>
                        <p class="text-xs mt-2" style="color: var(--text-light);">
                            Feels like {{ round($currentWeather->feels_like) }}°C
                        </p>
                    </div>
                    
                    <!-- Tea Recommendation -->
                    <div class="text-center p-4 rounded-lg" style="background: var(--cream-green);">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full flex items-center justify-center" style="background: var(--light-green);">
                            <span class="text-xl text-white">🍵</span>
                        </div>
                        <h3 class="font-semibold mb-1" style="color: var(--text-dark);">Recommended For</h3>
                        <p class="text-lg font-bold capitalize" style="color: var(--accent-green);">
                            {{ $currentWeather->getTeaCategory() }} Weather
                        </p>
                        <p class="text-sm" style="color: var(--text-medium);">
                            @if($currentWeather->isHot())
                                ☀️ Cooling teas recommended
                            @elseif($currentWeather->isCold())
                                ❄️ Warming teas recommended
                            @elseif($currentWeather->isRainy())
                                🌧️ Comforting teas recommended
                            @else
                                🌤️ Perfect for any tea
                            @endif
                        </p>
                    </div>
                </div>
                
                <!-- Weekly Forecast Preview -->
                @if($weeklyForecast && $weeklyForecast->count() > 1)
                    <div class="mt-6 pt-6 border-t" style="border-color: var(--border-color);">
                        <h3 class="text-lg font-semibold mb-4" style="color: var(--text-dark);">
                            📅 This Week's Tea Forecast
                        </h3>
                        <div class="grid grid-cols-3 md:grid-cols-7 gap-2">
                            @foreach($weeklyForecast->take(7) as $day)
                                <div class="text-center p-2 rounded" style="background: var(--pale-green);">
                                    <p class="text-xs font-medium" style="color: var(--text-medium);">
                                        {{ \Carbon\Carbon::parse($day->date)->format('D') }}
                                    </p>
                                    <p class="text-lg font-bold" style="color: var(--accent-green);">
                                        {{ round($day->temperature) }}°
                                    </p>
                                    <p class="text-xs" style="color: var(--text-light);">
                                        {{ $day->getTeaCategory() }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    @forelse($recommendations as $item)
        <div class="tea-card overflow-hidden group">
            @php
                $fallbackImage = 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';
                $imgSrc = $item['tea']->image
                    ? (str_starts_with($item['tea']->image, 'http') ? $item['tea']->image
                        : (str_starts_with($item['tea']->image, '//') ? 'https:'.$item['tea']->image
                        : (str_starts_with($item['tea']->image, '/storage/') ? $item['tea']->image : '/storage/'.$item['tea']->image)))
                    : $fallbackImage;
            @endphp
            
            <!-- Image Section -->
            <div class="relative h-56 overflow-hidden">
                <img src="{{ $imgSrc }}" 
                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                     alt="{{ $item['tea']->name }}"
                     onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                
                <!-- Overlay with score badge -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                
                <!-- Favourite Button -->
                <button type="button" 
                        onclick="toggleFavourite({{ $item['tea']->id }}, this)" 
                        data-tea-id="{{ $item['tea']->id }}"
                        class="favourite-btn absolute top-4 right-4 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform z-10">
                    <svg class="w-5 h-5 favourite-icon text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                </button>
                
                <!-- Score Badge -->
                <div class="absolute top-4 right-16">
                    <span class="score-badge text-sm px-3 py-1">
                        {{ round($item['contextual_score']) }}/100
                    </span>
                </div>
                
                <!-- Flavor Tag -->
                <div class="absolute top-4 left-4">
                    <span class="flavor-tag">
                        {{ $item['tea']->flavor }}
                    </span>
                </div>
            </div>

            <!-- Content Section -->
            <div class="p-6">
                <!-- Tea Name -->
                <div class="mb-3">
                    <h3 class="text-xl font-bold group-hover:text-green-700 transition-colors" style="color: var(--text-dark);">
                        {{ $item['tea']->name }}
                    </h3>
                </div>

                <!-- Preference Matching Indicators -->
                @if($preferences)
                    @php
                        $flavorMatch = str_contains(strtolower($item['tea']->flavor), str_replace('_', ' ', strtolower($preferences->preferred_flavor)));
                        $caffeineMatch = str_contains(strtolower($item['tea']->caffeine), str_replace('_', ' ', strtolower($preferences->preferred_caffeine)));
                        $healthMatch = str_contains(strtolower($item['tea']->health_benefit), str_replace('_', ' ', strtolower($preferences->health_goal)));
                    @endphp
                    <div class="flex flex-wrap gap-2 mb-4">
                        @if($flavorMatch)
                            <span class="text-xs px-2 py-1 rounded-full" style="background: var(--light-green);">
                                <span class="text-white">🍃 Flavor Match</span>
                            </span>
                        @endif
                        @if($caffeineMatch)
                            <span class="text-xs px-2 py-1 rounded-full" style="background: var(--light-green);">
                                <span class="text-white">⚡ Caffeine Match</span>
                            </span>
                        @endif
                        @if($healthMatch)
                            <span class="text-xs px-2 py-1 rounded-full" style="background: var(--light-green);">
                                <span class="text-white">🌿 Health Match</span>
                            </span>
                        @endif
                    </div>
                @endif

                <!-- Tea Details -->
                <div class="space-y-3 mb-4">
                    <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                        <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                            <span class="mr-2">🍃</span> Flavor
                        </span>
                        <span class="text-sm font-semibold {{ $flavorMatch ?? false ? 'text-green-600' : '' }}" style="color: var(--accent-green);">
                            {{ $item['tea']->flavor }}
                            @if($preferences && $flavorMatch)
                                <span class="text-xs ml-1">✓</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                        <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                            <span class="mr-2">⚡</span> Caffeine
                        </span>
                        <span class="text-sm font-semibold {{ $caffeineMatch ?? false ? 'text-green-600' : '' }}" style="color: var(--accent-green);">
                            {{ $item['tea']->caffeine_level }}
                            @if($preferences && $caffeineMatch)
                                <span class="text-xs ml-1">✓</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                        <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                            <span class="mr-2">🌿</span> Health
                        </span>
                        <span class="text-sm font-semibold {{ $healthMatch ?? false ? 'text-green-600' : '' }} line-clamp-5" style="color: var(--accent-green);">
                            {{ $item['tea']->health_benefit }}
                            @if($preferences && $healthMatch)
                                <span class="text-xs ml-1">✓</span>
                            @endif
                        </span>
                    </div>
                </div>

                
            <!-- Match Score Breakdown -->
                <div class="mb-6 p-4 rounded-lg" style="background: var(--cream-green);">
                    <div class="text-center mb-3">
                        <span class="text-sm font-medium" style="color: var(--text-dark);">
                            Match Score Breakdown
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="bg-white rounded-lg p-2">
                            <div class="text-xs font-medium mb-1" style="color: var(--text-light);">Flavor</div>
                            <div class="text-lg font-bold" style="color: var(--light-green);">
                                {{ round($item['flavor_score'] * 100) }}%
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-2">
                            <div class="text-xs font-medium mb-1" style="color: var(--text-light);">Caffeine</div>
                            <div class="text-lg font-bold" style="color: var(--light-green);">
                                {{ round($item['caffeine_score'] * 100) }}%
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-2">
                            <div class="text-xs font-medium mb-1" style="color: var(--text-light);">Health</div>
                            <div class="text-lg font-bold" style="color: var(--light-green);">
                                {{ round($item['health_score'] * 100) }}%
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <span class="text-xs font-semibold" style="color: var(--accent-green);">
                            Overall Match: {{ round($item['contextual_score']) }}/100
                        </span>
                    </div>
                </div>

                <!-- Rating Section -->
                <div class="pt-4 border-t" style="border-color: var(--border-color);">
                    @php
                        $userRating = $item['tea']->userRating(auth()->id());
                        $avgRating = $item['tea']->averageRating();
                        $totalRatings = $item['tea']->totalRatings();
                    @endphp
                    
                    <!-- Average Rating Display -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium" style="color: var(--text-medium);">
                                Community Rating
                            </span>
                            <div class="flex items-center">
                                <span class="text-sm font-bold" style="color: var(--accent-green);">
                                    {{ number_format($avgRating, 1) }}
                                </span>
                                <span class="text-yellow-500 ml-1">⭐</span>
                                <span class="text-xs" style="color: var(--text-light);">
                                    ({{ $totalRatings }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- User Rating Form -->
                    @if($userRating)
                        <div class="text-center mb-3 p-2 rounded-lg" style="background: var(--pale-green);">
                            <span class="text-sm font-medium" style="color: var(--primary-green);">
                                Your Rating: {{ $userRating->rating }} ⭐
                            </span>
                        </div>
                    @endif

                    <form action="{{ route('ratings.store') }}" method="POST" class="rating-form" data-tea-id="{{ $item['tea']->id }}">
                        @csrf
                        <input type="hidden" name="tea_id" value="{{ $item['tea']->id }}">
                        
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium" style="color: var(--text-medium);">Rate this tea:</label>
                            <select name="rating" class="search-bar text-sm py-2 px-3" style="max-width: 150px;">
                                <option value="">Select rating</option>
                                @for($i = 1; $i <= 5; $i++)
                                    <option value="{{ $i }}" {{ $userRating && $userRating->rating == $i ? 'selected' : '' }}>
                                        {{ $i }} ⭐ @if($i == 1) Poor @elseif($i == 2) Fair @elseif($i == 3) Good @elseif($i == 4) Very Good @else Excellent @endif
                                    </option>
                                @endfor
                            </select>
                        </div>
                        
                        <button type="submit" class="btn-primary w-full text-sm mb-3">
                            {{ $userRating ? '🔄 Update Rating' : '⭐ Submit Rating' }}
                        </button>
                    </form>

                    <!-- Action Buttons Row -->
                    <div class="grid grid-cols-3 gap-2">
                        <!-- View Details Button -->
                        <button type="button"
                                class="btn-secondary text-[10px] sm:text-sm py-2 px-1 text-center"
                                onclick="openTeaDetails({{ $item['tea']->id }}, '{{ addslashes($item['tea']->name) }}', '{{ addslashes($item['tea']->flavor) }}', '{{ addslashes($item['tea']->caffeine_level) }}', '{{ addslashes($item['tea']->health_benefit) }}', '{{ $imgSrc }}', '{{ $item['tea']->shopee_link ?? '' }}', '{{ $item['tea']->lazada_link ?? '' }}')">
                            Details
                        </button>

                        <a href="{{ $item['tea']->shopeeShopUrl() }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center justify-center text-[10px] sm:text-sm py-2 px-1 text-center rounded-full font-semibold transition-all" style="background:#4a7c28;color:#fff;border:1px solid #2d5016;">
                            Shopee
                        </a>
                        <a href="{{ $item['tea']->lazadaShopUrl() }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center justify-center text-[10px] sm:text-sm py-2 px-1 text-center rounded-full font-semibold transition-all" style="background:#8fb339;color:#1a1a1a;border:1px solid #4a7c28;">
                            Lazada
                        </a>
                    </div>

                    <button type="button"
                            onclick="openTeaUses({{ $item['tea']->id }}, '{{ addslashes($item['tea']->name) }}')"
                            class="btn-secondary w-full text-xs py-2 text-center mt-3">
                        ✨ What can this tea do?
                    </button>
                </div>
            </div>
        </div>
    @empty
        <p>No recommendation available.</p>
    @endforelse
</div>

<!-- Tea Details Modal -->
<div id="teaDetailsModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50 p-4" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="relative transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <!-- Modal Header -->
                <div class="relative h-72">
                    <img id="modalTeaImage" src="" alt="" class="w-full h-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
                    <button onclick="closeTeaDetails()" class="absolute top-4 right-4 w-12 h-12 rounded-full bg-white hover:bg-gray-100 flex items-center justify-center shadow-lg transition-all z-20">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="absolute bottom-4 left-6 right-6">
                        <h2 id="modalTeaName" class="text-2xl font-bold text-white mb-2"></h2>
                        <span id="modalTeaFlavor" class="inline-block px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white backdrop-blur-sm"></span>
                        <span id="modalTeaType" class="hidden ml-2 inline-block px-3 py-1 rounded-full text-sm font-medium bg-white/20 text-white backdrop-blur-sm"></span>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-5">
                    <!-- Quick Info Row -->
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

                    <!-- Health Benefits Section -->
                    <div class="p-5 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 border border-green-100">
                        <h3 class="text-lg font-bold text-green-800 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            Health Benefits
                        </h3>
                        <p id="modalTeaHealth" class="text-green-700 leading-relaxed"></p>
                    </div>

                    <!-- AI Insight (Gemini) -->
                    <div class="p-5 rounded-xl bg-gradient-to-br from-indigo-50 to-purple-50 border border-indigo-100">
                        <h3 class="text-lg font-bold text-indigo-800 mb-3 flex items-center gap-2">
                            <span class="text-xl">✨</span>
                            Why It Fits You
                            <span class="ml-auto text-[10px] font-semibold uppercase tracking-wide text-indigo-500 bg-indigo-100 px-2 py-0.5 rounded-full">AI</span>
                        </h3>
                        <p id="modalTeaAi" class="text-indigo-700 leading-relaxed whitespace-pre-line">Loading…</p>
                        <div id="modalTeaAiSources" class="mt-3 flex flex-wrap gap-2"></div>
                    </div>


                </div>
            </div>
</div>

<script>
// Toggle favourite
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
        // Refresh favourites sidebar
        loadFavourites();
    } catch (e) {
        console.error('Error toggling favourite:', e);
    }
}

// Open tea details modal
function openTeaDetails(id, name, flavor, caffeine, healthBenefit, image, shopeeLink, lazadaLink) {
    document.getElementById('modalTeaImage').src = image;
    document.getElementById('modalTeaName').textContent = name;
    document.getElementById('modalTeaFlavor').textContent = flavor;
    document.getElementById('modalTeaFlavor2').textContent = flavor;
    document.getElementById('modalTeaCaffeine').textContent = caffeine;
    document.getElementById('modalTeaHealth').textContent = healthBenefit || 'No health benefit information available.';


    // Load Gemini AI description on demand
    const aiEl = document.getElementById('modalTeaAi');
    if (aiEl) {
        aiEl.textContent = 'Generating a friendly summary…';
        const sourcesEl = document.getElementById('modalTeaAiSources');
        if (sourcesEl) sourcesEl.replaceChildren();
        fetch(`/teas/${id}/ai-description`, { headers: { 'Accept': 'application/json' } })
            .then(async r => {
                const data = await r.json();
                if (!r.ok) throw new Error(data.message || 'Could not load the AI summary right now.');
                return data;
            })
            .then(d => {
                const rawDescription = d.description || 'No AI summary available yet.';
                let teaType = null;
                let description = rawDescription;
                if (rawDescription.startsWith('Tea type:')) {
                    const nl = rawDescription.indexOf('\n');
                    const typeLine = nl === -1 ? rawDescription : rawDescription.slice(0, nl);
                    teaType = typeLine.replace(/^Tea type:\s*/, '').trim();
                    description = nl === -1 ? '' : rawDescription.slice(nl + 1).trim();
                    if (!description) description = 'No AI summary available yet.';
                }
                const typeEl = document.getElementById('modalTeaType');
                if (typeEl) {
                    if (teaType) {
                        typeEl.textContent = 'Tea type: ' + teaType;
                        typeEl.classList.remove('hidden');
                    } else {
                        typeEl.classList.add('hidden');
                    }
                }
                aiEl.textContent = description;
                if (sourcesEl && Array.isArray(d.sources)) {
                    d.sources.forEach(source => {
                        const link = document.createElement('a');
                        link.href = source.url;
                        link.target = '_blank';
                        link.rel = 'noopener noreferrer';
                        link.className = 'inline-flex max-w-full items-center rounded-full bg-white px-2.5 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100';
                        link.textContent = `Source: ${source.title || 'Learn more'}`;
                        sourcesEl.appendChild(link);
                    });
                }
            })
            .catch(error => { aiEl.textContent = error.message; });
    }

    const modal = document.getElementById('teaDetailsModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

// Close tea details modal
function closeTeaDetails() {
    const modal = document.getElementById('teaDetailsModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('teaDetailsModal');
    if (event.target === modal) {
        closeTeaDetails();
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTeaDetails();
    }
});

// Load favourites sidebar
async function loadFavourites() {
    try {
        const res = await fetch('/favourites', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        const container = document.getElementById('favouritesList');
        if (!container) return;

        if (data.favourites && data.favourites.length > 0) {
            container.innerHTML = data.favourites.map(tea => `
                <div class="flex items-center gap-3 p-3 rounded-lg hover:bg-green-50 transition-colors group">
                    <img src="${tea.image}" alt="${tea.name}" class="w-12 h-12 rounded-lg object-cover">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">${tea.name}</p>
                        <p class="text-xs text-gray-500">${tea.flavor}</p>
                    </div>
                    <button onclick="toggleFavourite(${tea.id}, this.closest('.group').querySelector('.fav-btn'))"
                            class="fav-btn p-1.5 rounded-full hover:bg-red-100 transition-colors">
                        <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="text-center py-6 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <p class="text-sm">No favourites yet</p>
                    <p class="text-xs mt-1">Click the heart icon on teas you love!</p>
                </div>
            `;
        }
    } catch (e) {
        console.error('Error loading favourites:', e);
    }
}

// Load favourites on page load
document.addEventListener('DOMContentLoaded', loadFavourites);

// Check initial favourite states
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

@include('user._tea-uses-modal')

@endsection


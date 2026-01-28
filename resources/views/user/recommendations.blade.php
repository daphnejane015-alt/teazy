@extends('layouts.sidebar')
@section('content')

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-2" style="color: var(--text-dark);">
        ⭐ My Previous Recommendations
    </h1>
    <p class="text-lg" style="color: var(--text-light);">
        Rate the teas you've tried and see your match scores
    </p>
</div>

<!-- Chosen Preferences Section -->
@if($preferences)
    <div class="tea-card p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold mb-2" style="color: var(--text-dark);">
                    🎯 Your Chosen Preferences
                </h2>
                <p class="text-sm" style="color: var(--text-light);">
                    These preferences were used for your previous recommendations
                </p>
            </div>
            <a href="{{ route('find.tea') }}" class="btn-secondary">
                ✏️ Update Preferences
            </a>
        </div>
        
        <!-- Preferences Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Flavor Preference -->
            <div class="flex items-center space-x-3 p-3 rounded-lg" style="background: var(--cream-green);">
                <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                    <span class="text-white">🍃</span>
                </div>
                <div>
                    <p class="font-semibold text-sm" style="color: var(--text-dark);">Flavor</p>
                    <p class="text-sm font-medium capitalize" style="color: var(--accent-green);">
                        {{ str_replace('_', ' ', $preferences->preferred_flavor) }}
                    </p>
                </div>
            </div>
            
            <!-- Caffeine Preference -->
            <div class="flex items-center space-x-3 p-3 rounded-lg" style="background: var(--cream-green);">
                <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                    <span class="text-white">⚡</span>
                </div>
                <div>
                    <p class="font-semibold text-sm" style="color: var(--text-dark);">Caffeine</p>
                    <p class="text-sm font-medium capitalize" style="color: var(--accent-green);">
                        {{ str_replace('_', ' ', $preferences->preferred_caffeine) }}
                    </p>
                </div>
            </div>
            
            <!-- Health Goal -->
            <div class="flex items-center space-x-3 p-3 rounded-lg" style="background: var(--cream-green);">
                <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                    <span class="text-white">🌿</span>
                </div>
                <div>
                    <p class="font-semibold text-sm" style="color: var(--text-dark);">Health Goal</p>
                    <p class="text-sm font-medium capitalize" style="color: var(--accent-green);">
                        {{ str_replace('_', ' ', $preferences->health_goal) }}
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Weather Preferences (if enabled) -->
        @if($preferences->weather_based_recommendations)
            <div class="border-t pt-4 mt-4" style="border-color: var(--border-color);">
                <h3 class="font-semibold text-sm mb-3" style="color: var(--text-dark);">🌤️ Weather Settings</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center space-x-3 p-3 rounded-lg" style="background: var(--pale-green);">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                            <span class="text-white text-xs">🇲🇾</span>
                        </div>
                        <div>
                            <p class="font-semibold text-xs" style="color: var(--text-dark);">City</p>
                            <p class="text-xs" style="color: var(--text-medium);">
                                {{ $preferences->city ?? 'Not set' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3 p-3 rounded-lg" style="background: var(--pale-green);">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                            <span class="text-white text-xs">🌡️</span>
                        </div>
                        <div>
                            <p class="font-semibold text-xs" style="color: var(--text-dark);">Weather Mode</p>
                            <p class="text-xs" style="color: var(--text-medium);">
                                {{ $preferences->weather_preference ? str_replace('_', ' ', ucfirst($preferences->weather_preference)) : 'Auto' }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-3 p-3 rounded-lg" style="background: var(--pale-green);">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center" style="background: var(--accent-green);">
                            <span class="text-white text-xs">✅</span>
                        </div>
                        <div>
                            <p class="font-semibold text-xs" style="color: var(--text-dark);">Status</p>
                            <p class="text-xs" style="color: var(--text-medium);">
                                Weather recommendations enabled
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Preference Summary -->
        <div class="mt-4 p-3 rounded-lg" style="background: var(--light-green);">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-white">
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

@if($recommendations->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($recommendations as $recommendation)
            <div class="tea-card overflow-hidden group">
                @php
                    $fallbackImage = 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';
                    $imgSrc = $recommendation['tea']->image
                        ? (str_starts_with($recommendation['tea']->image, 'http') ? $recommendation['tea']->image
                            : (str_starts_with($recommendation['tea']->image, '//') ? 'https:'.$recommendation['tea']->image
                            : (str_starts_with($recommendation['tea']->image, '/storage/') ? $recommendation['tea']->image : '/storage/'.$recommendation['tea']->image)))
                        : $fallbackImage;
                @endphp
                
                <!-- Image Section -->
                <div class="relative h-56 overflow-hidden">
                    <img src="{{ $imgSrc }}" 
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                         alt="{{ $recommendation['tea']->name }}">
                    
                    <!-- Overlay with score badge -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    
                    <!-- Favourite Button -->
                    <button type="button" 
                            onclick="toggleFavourite({{ $recommendation['tea']->id }}, this)" 
                            data-tea-id="{{ $recommendation['tea']->id }}"
                            class="favourite-btn absolute top-4 right-4 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform z-10">
                        <svg class="w-5 h-5 favourite-icon text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </button>
                    
                    <!-- Flavor Tag -->
                    <div class="absolute top-4 left-4">
                        <span class="flavor-tag">
                            {{ $recommendation['tea']->flavor }}
                        </span>
                    </div>
                </div>

                <!-- Content Section -->
                <div class="p-6">
                    <!-- Tea Name -->
                    <h3 class="text-xl font-bold mb-3 group-hover:text-green-700 transition-colors" style="color: var(--text-dark);">
                        {{ $recommendation['tea']->name }}
                    </h3>

                    <!-- Tea Details -->
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                            <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                                <span class="mr-2">🍃</span> Flavor
                            </span>
                            <span class="text-sm font-semibold" style="color: var(--accent-green);">
                                {{ $recommendation['tea']->flavor }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                            <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                                <span class="mr-2">⚡</span> Caffeine
                            </span>
                            <span class="text-sm font-semibold" style="color: var(--accent-green);">
                                {{ $recommendation['tea']->caffeine_level }}
                            </span>
                        </div>
                    </div>

                    <!-- Health Benefit -->
                    <div class="mb-4">
                        <p class="text-sm leading-relaxed line-clamp-3" style="color: var(--text-light);">
                            {{ $recommendation['tea']->health_benefit }}
                        </p>
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
                                    {{ round($recommendation['flavor_score'] * 100) }}%
                                </div>
                            </div>
                            <div class="bg-white rounded-lg p-2">
                                <div class="text-xs font-medium mb-1" style="color: var(--text-light);">Caffeine</div>
                                <div class="text-lg font-bold" style="color: var(--light-green);">
                                    {{ round($recommendation['caffeine_score'] * 100) }}%
                                </div>
                            </div>
                            <div class="bg-white rounded-lg p-2">
                                <div class="text-xs font-medium mb-1" style="color: var(--text-light);">Health</div>
                                <div class="text-lg font-bold" style="color: var(--light-green);">
                                    {{ round($recommendation['health_score'] * 100) }}%
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <!-- Rate This Tea Form -->
                        @php
                            $userRating = auth()->user()->ratings()->where('tea_id', $recommendation['tea']->id)->first();
                        @endphp
                        
                        <form action="{{ route('ratings.store') }}" method="POST" class="rating-form">
                            @csrf
                            <input type="hidden" name="tea_id" value="{{ $recommendation['tea']->id }}">
                            
                            @if($userRating)
                                <div class="text-sm text-green-600 mb-2 text-center">
                                    ⭐ You rated: {{ $userRating->rating }}/5
                                </div>
                            @else
                                <div class="text-sm text-gray-500 mb-2 text-center">
                                    💭 Rate this tea
                                </div>
                            @endif
                            
                            <div class="flex items-center justify-center space-x-2 mb-3">
                                <select name="rating" class="border rounded px-3 py-2 text-sm w-full" style="border-color: var(--border-color);">
                                    <option value="">Select rating</option>
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ $userRating && $userRating->rating == $i ? 'selected' : '' }}>
                                            {{ $i }} ⭐ @if($i == 1) Poor @elseif($i == 2) Fair @elseif($i == 3) Good @elseif($i == 4) Very Good @else Excellent @endif
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            
                            <button type="submit" class="btn-primary w-full text-sm py-2">
                                {{ $userRating ? '⭐ Update Rating' : '⭐ Rate This Tea' }}
                            </button>
                        </form>
                        
                        <!-- Quick Actions -->
                        <div class="grid grid-cols-2 gap-2">
                            @if($recommendation['tea']->shop_link)
                                <a href="{{ $recommendation['tea']->shop_link }}" target="_blank" 
                                   class="btn-secondary text-xs py-2 text-center flex items-center justify-center gap-1">
                                    Shop
                                </a>
                            @else
                                <span class="text-xs text-gray-400 py-2 text-center border border-dashed rounded-lg">
                                    No shop link
                                </span>
                            @endif
                            
                            <button type="button" 
                                    onclick="openTeaDetails({{ $recommendation['tea']->id }}, '{{ addslashes($recommendation['tea']->name) }}', '{{ addslashes($recommendation['tea']->flavor) }}', '{{ addslashes($recommendation['tea']->caffeine_level) }}', '{{ addslashes($recommendation['tea']->health_benefit) }}', '{{ $imgSrc }}', '{{ $recommendation['tea']->shop_link ?? '' }}', '{{ $recommendation['tea']->source_url ?? '' }}')"
                                    class="btn-secondary text-xs py-2 text-center">
                                👁️ Details
                            </button>
                        </div>
                        
                        <!-- Get New Recommendations Button (only on first card) -->
                        @if($loop->first)
                            <div class="pt-2 border-t" style="border-color: var(--border-color);">
                                <a href="{{ route('find.tea') }}" class="btn-secondary w-full text-xs py-2 block text-center">
                                    🔄 Get New Recommendations
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-12">
        <div class="max-w-md mx-auto">
            <div class="text-6xl mb-4">💡</div>
            <p class="text-gray-500 text-lg mb-4">No recommendations available yet.</p>
            <p class="text-gray-400 text-sm mb-6">Please set your preferences to get personalized recommendations.</p>
            <a href="{{ route('find.tea') }}" class="btn-primary">
                Set Preferences Now
            </a>
        </div>
    </div>
@endif

<!-- Tea Details Modal -->
<div id="teaModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="relative">
            <img id="modalTeaImage" src="" alt="Tea" class="w-full h-64 object-cover rounded-t-2xl">
            <button onclick="closeTeaModal()" class="absolute top-4 right-4 bg-white rounded-full p-2 shadow-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="p-6">
            <h3 id="modalTeaName" class="text-2xl font-bold mb-4" style="color: var(--text-dark);"></h3>
            
            <div class="space-y-3 mb-4">
                <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                    <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                        <span class="mr-2">🍃</span> Flavor
                    </span>
                    <span id="modalTeaFlavor" class="text-sm font-semibold" style="color: var(--accent-green);"></span>
                </div>
                <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                    <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                        <span class="mr-2">⚡</span> Caffeine
                    </span>
                    <span id="modalTeaCaffeine" class="text-sm font-semibold" style="color: var(--accent-green);"></span>
                </div>
            </div>
            
            <div class="mb-4">
                <p class="text-sm font-medium mb-2" style="color: var(--text-medium);">🌿 Health Benefits</p>
                <p id="modalTeaBenefit" class="text-sm leading-relaxed" style="color: var(--text-light);"></p>
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
            // Remove from favourites
            const res = await fetch(`/favourites/${teaId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            
            if (!res.ok) throw new Error('Failed to remove from favourites');
            
            icon.classList.remove('text-red-500');
            icon.classList.add('text-gray-400');
        } else {
            // Add to favourites
            const res = await fetch('/favourites', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ tea_id: teaId })
            });
            
            if (!res.ok) throw new Error('Failed to add to favourites');
            
            icon.classList.remove('text-gray-400');
            icon.classList.add('text-red-500');
        }
        
        // Refresh sidebar favourites
        if (typeof loadFavourites === 'function') {
            loadFavourites();
        }
    } catch (e) {
        console.error('Error toggling favourite:', e);
    }
}

function openTeaDetails(id, name, flavor, caffeine, healthBenefit, image, shopLink, sourceUrl) {
    document.getElementById('modalTeaImage').src = image;
    document.getElementById('modalTeaName').textContent = name;
    document.getElementById('modalTeaFlavor').textContent = flavor;
    document.getElementById('modalTeaCaffeine').textContent = caffeine;
    document.getElementById('modalTeaBenefit').textContent = healthBenefit;
    
    document.getElementById('teaModal').classList.remove('hidden');
    document.getElementById('teaModal').classList.add('flex');
}

function closeTeaModal() {
    document.getElementById('teaModal').classList.add('hidden');
    document.getElementById('teaModal').classList.remove('flex');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('teaModal');
    if (event.target === modal) {
        closeTeaModal();
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeTeaModal();
    }
});
</script>

@endsection

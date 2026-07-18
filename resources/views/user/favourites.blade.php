@extends('layouts.sidebar')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold mb-2" style="color: var(--text-dark);">
        ❤️ My Favourite Teas
    </h1>
    <p class="text-lg" style="color: var(--text-light);">
        All your favourite teas in one place
    </p>
</div>

@if($favourites->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach($favourites as $tea)
            <div class="tea-card overflow-hidden group">
                @php
                    $fallbackImage = 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';
                    $imgSrc = $tea->image
                        ? (str_starts_with($tea->image, 'http') ? $tea->image
                            : (str_starts_with($tea->image, '//') ? 'https:'.$tea->image
                            : (str_starts_with($tea->image, '/storage/') ? $tea->image : '/storage/'.$tea->image)))
                        : $fallbackImage;
                @endphp
                
                <!-- Image Section -->
                <div class="relative h-56 overflow-hidden">
                    <img src="{{ $imgSrc }}" 
                         class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                         alt="{{ $tea->name }}"
                         onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                    
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                    
                    <!-- Favourite Button -->
                    <button type="button" 
                            onclick="toggleFavourite({{ $tea->id }}, this)" 
                            data-tea-id="{{ $tea->id }}"
                            class="absolute top-4 right-4 w-10 h-10 bg-white rounded-full shadow-lg flex items-center justify-center hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-red-500 favourite-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                    </button>
                    
                    <!-- Flavor Tag -->
                    <div class="absolute top-4 left-4">
                        <span class="flavor-tag">
                            {{ $tea->flavor }}
                        </span>
                    </div>
                </div>

                <!-- Content Section -->
                <div class="p-6">
                    <!-- Tea Name -->
                    <h3 class="text-xl font-bold mb-3 group-hover:text-green-700 transition-colors" style="color: var(--text-dark);">
                        {{ $tea->name }}
                    </h3>

                    <!-- Tea Details -->
                    <div class="space-y-3 mb-4">
                        <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                            <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                                <span class="mr-2">🍃</span> Flavor
                            </span>
                            <span class="text-sm font-semibold" style="color: var(--accent-green);">
                                {{ $tea->flavor }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                            <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                                <span class="mr-2">⚡</span> Caffeine
                            </span>
                            <span class="text-sm font-semibold" style="color: var(--accent-green);">
                                {{ $tea->caffeine_level }}
                            </span>
                        </div>
                    </div>

                    <!-- Health Benefit -->
                    <div class="mb-4">
                        <p class="text-sm leading-relaxed line-clamp-3" style="color: var(--text-light);">
                            {{ $tea->health_benefit }}
                        </p>
                    </div>
                    
                    <!-- Rating -->
                    @php
                        $avgRating = $tea->averageRating();
                    @endphp
                    @if($avgRating > 0)
                        <div class="mb-4 p-3 rounded-lg bg-green-50">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-green-700">⭐ Rating</span>
                                <span class="text-lg font-bold text-green-600">{{ number_format($avgRating, 1) }}/5</span>
                            </div>
                            <p class="text-xs text-green-500 mt-1">{{ $tea->totalRatings() }} ratings</p>
                        </div>
                    @endif
                    
                    <!-- Action Buttons -->
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button"
                                onclick="openTeaDetails({{ $tea->id }}, '{{ addslashes($tea->name) }}', '{{ addslashes($tea->flavor) }}', '{{ addslashes($tea->caffeine_level) }}', '{{ addslashes($tea->health_benefit) }}', '{{ $imgSrc }}', '{{ $tea->shopee_link ?? '' }}', '{{ $tea->lazada_link ?? '' }}')"
                                class="btn-secondary text-[10px] sm:text-xs py-2 px-1 text-center">
                            Details
                        </button>
                        <a href="{{ $tea->shopeeShopUrl() }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center justify-center text-[10px] sm:text-xs py-2 px-1 text-center rounded-full font-semibold transition-all" style="background:#4a7c28;color:#fff;border:1px solid #2d5016;">
                            Shopee
                        </a>
                        <a href="{{ $tea->lazadaShopUrl() }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center justify-center text-[10px] sm:text-xs py-2 px-1 text-center rounded-full font-semibold transition-all" style="background:#8fb339;color:#1a1a1a;border:1px solid #4a7c28;">
                            � Lazada
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-12">
        <div class="max-w-md mx-auto">
            <div class="text-6xl mb-4">❤️</div>
            <p class="text-gray-500 text-lg mb-4">No favourites yet.</p>
            <p class="text-gray-400 text-sm mb-6">Start exploring teas and add your favourites!</p>
            <a href="{{ route('find.tea') }}" class="btn-primary">
                Find Teas
            </a>
        </div>
    </div>
@endif

<!-- Tea Details Modal -->
<div id="teaDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="relative">
            <img id="modalTeaImage" src="" alt="Tea" class="w-full h-64 object-cover rounded-t-2xl"
                 onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';">
            <button onclick="closeTeaDetails()" class="absolute top-4 right-4 bg-white rounded-full p-2 shadow-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-6">
                <div class="flex items-center gap-2">
                    <span id="modalTeaFlavor" class="flavor-tag"></span>
                    <span id="modalTeaType" class="hidden flavor-tag"></span>
                </div>
            </div>
        </div>
        <div class="p-6">
            <h3 id="modalTeaName" class="text-2xl font-bold mb-4" style="color: var(--text-dark);"></h3>
            
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div class="p-4 rounded-xl bg-gradient-to-br from-green-50 to-emerald-50 border border-green-100">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xl">⚡</span>
                        <span class="text-sm font-semibold text-green-800">Caffeine Level</span>
                    </div>
                    <p id="modalTeaCaffeine" class="text-green-700 font-medium"></p>
                </div>
                <div class="p-4 rounded-xl bg-gradient-to-br from-orange-50 to-amber-50 border border-orange-100">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xl">🍃</span>
                        <span class="text-sm font-semibold text-orange-800">Flavor</span>
                    </div>
                    <p id="modalTeaFlavor2" class="text-orange-700 font-medium"></p>
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

            <!-- AI Insight (Gemini) -->
            <div class="p-5 rounded-xl bg-gradient-to-br from-indigo-50 to-purple-50 border border-indigo-100 mb-5">
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
            
            // Remove the card from the grid
            const card = btn.closest('.tea-card');
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            setTimeout(() => {
                card.remove();
                // Check if grid is empty
                const grid = document.querySelector('.grid');
                if (grid && grid.children.length === 0) {
                    location.reload();
                }
            }, 300);
            
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
        
        // Refresh sidebar
        loadFavourites();
    } catch (e) {
        console.error('Error toggling favourite:', e);
        alert('Failed to update favourites. Please try again.');
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

    document.getElementById('teaDetailsModal').classList.remove('hidden');
    document.getElementById('teaDetailsModal').classList.add('flex');
    document.body.style.overflow = 'hidden';
}

// Close tea details modal
function closeTeaDetails() {
    document.getElementById('teaDetailsModal').classList.add('hidden');
    document.getElementById('teaDetailsModal').classList.remove('flex');
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
</script>
@endsection

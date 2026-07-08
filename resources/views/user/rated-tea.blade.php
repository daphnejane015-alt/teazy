@extends('layouts.sidebar')
@section('content')

<div class="mb-8">
    <h1 class="text-3xl font-bold mb-2" style="color: var(--text-dark);">
        ⭐ My Rated Teas
    </h1>
    <p class="text-lg" style="color: var(--text-light);">
        Manage your tea ratings and personal notes
    </p>
</div>

@if(session('success'))
    <div class="mb-6 p-4 rounded-lg border-l-4" style="background: var(--cream-green); border-color: var(--light-green);">
        <div class="flex items-center">
            <span class="text-2xl mr-3">✅</span>
            <p class="font-medium" style="color: var(--primary-green);">
                {{ session('success') }}
            </p>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
    @forelse($ratings as $rating)
        <div class="tea-card overflow-hidden group">
            <!-- Image Section -->
            <div class="relative h-48 overflow-hidden">
                @php
                    $fallbackImage = 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';
                    $tea = $rating->tea;
                    $imgSrc = $tea && $tea->image
                        ? (str_starts_with($tea->image, 'http') ? $tea->image
                            : (str_starts_with($tea->image, '//') ? 'https:'.$tea->image
                            : (str_starts_with($tea->image, '/storage/') ? $tea->image : '/storage/'.$tea->image)))
                        : $fallbackImage;
                    $teaName = $tea ? $tea->name : 'Unknown Tea';
                    $teaFlavor = $tea ? $tea->flavor : 'Various';
                @endphp
                
                <img src="{{ $imgSrc }}" 
                     class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                     alt="{{ $teaName }}"
                     onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                
                <!-- Rating Badge -->
                <div class="absolute top-4 right-4">
                    <span class="score-badge">
                        ⭐ {{ $rating->rating }}/5
                    </span>
                </div>
                
                <!-- Flavor Tag -->
                <div class="absolute top-4 left-4">
                    <span class="flavor-tag">
                        {{ $teaFlavor }}
                    </span>
                </div>
            </div>

            <!-- Content Section -->
            <div class="p-6">
                <!-- Tea Name & Favourite Button -->
                <div class="flex items-start justify-between mb-3">
                    <h3 class="text-xl font-bold group-hover:text-green-700 transition-colors" style="color: var(--text-dark);">
                        {{ $teaName }}
                    </h3>
                    @if($tea)
                    <button type="button" class="favourite-btn ml-1 p-1 rounded-full hover:bg-red-50 transition-colors"
                            data-tea-id="{{ $tea->id }}"
                            onclick="toggleFavourite({{ $tea->id }}, this)">
                        <svg class="w-5 h-5 text-gray-400 favourite-icon" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    @endif
                </div>

                <!-- Tea Details -->
                <div class="space-y-2 mb-4">
                    <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                        <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                            <span class="mr-2">🍃</span> Flavor
                        </span>
                        <span class="text-sm font-semibold" style="color: var(--accent-green);">
                            {{ $tea ? $tea->flavor : 'N/A' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                        <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                            <span class="mr-2">⚡</span> Caffeine
                        </span>
                        <span class="text-sm font-semibold" style="color: var(--accent-green);">
                            {{ $tea ? $tea->caffeine_level : 'N/A' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b" style="border-color: var(--border-color);">
                        <span class="text-sm font-medium flex items-center" style="color: var(--text-medium);">
                            <span class="mr-2">🌿</span> Health Benefit
                        </span>
                        <span class="text-sm font-semibold" style="color: var(--accent-green);">
                            {{ $tea ? Str::limit($tea->health_benefit, 30) : 'N/A' }}
                        </span>
                    </div>
                </div>

                <!-- User Notes -->
                @if($rating->description)
                    <div class="mb-4 p-3 rounded-lg" style="background: var(--cream-green);">
                        <div class="flex items-start">
                            <span class="text-lg mr-2 mt-1">📝</span>
                            <div class="flex-1">
                                <p class="text-sm leading-relaxed" style="color: var(--text-medium);">
                                    {{ Str::limit($rating->description, 100) }}
                                    @if(strlen($rating->description) > 100)
                                        <span class="text-xs" style="color: var(--text-light);">...</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mb-4 p-3 rounded-lg border-2 border-dashed" style="border-color: var(--pale-green);">
                        <div class="text-center">
                            <span class="text-2xl mb-2 block">📝</span>
                            <p class="text-sm" style="color: var(--text-light);">
                                No notes added yet
                            </p>
                        </div>
                    </div>
                @endif

                <!-- Rating Info -->
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium" style="color: var(--text-medium);">
                            Rated: {{ $rating->rating }}/5
                        </span>
                        <span class="text-xs" style="color: var(--text-light);">
                            {{ $rating->updated_at->format('M j, Y') }}
                        </span>
                    </div>
                </div>

                <!-- Action Buttons Row -->
                <div class="grid grid-cols-2 gap-2 mb-3">
                    @if($tea)
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
                    @else
                    <span class="col-span-2 text-xs text-gray-400 py-1.5 px-2 text-center border border-dashed rounded-lg">
                        Tea no longer available
                    </span>
                    @endif
                </div>

                <!-- Edit/Delete Buttons -->
                <div class="flex items-center justify-between space-x-2 pt-2 border-t" style="border-color: var(--border-color);">
                    <a href="{{ route('rated.tea.edit', $rating->id) }}" 
                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors duration-200 hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1"
                       style="background: white; border-color: var(--accent-green); color: var(--accent-green);">
                        ✏️ Edit
                    </a>
                    
                    <form action="{{ route('rated.tea.destroy', $rating->id) }}" method="POST" 
                          onsubmit="return confirm('Are you sure you want to delete this rating?')" 
                          class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors duration-200 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1"
                                style="background: white; border-color: #ef4444; color: #ef4444;">
                            🗑️ Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-span-full">
            <div class="text-center py-16">
                <div class="max-w-md mx-auto">
                    <div class="w-24 h-24 mx-auto mb-6 rounded-full flex items-center justify-center" style="background: var(--cream-green);">
                        <span class="text-4xl">⭐</span>
                    </div>
                    
                    <h3 class="text-2xl font-bold mb-4" style="color: var(--text-dark);">
                        No Rated Teas Yet
                    </h3>
                    
                    <p class="text-lg mb-6" style="color: var(--text-light);">
                        Start exploring and rating teas to build your personal collection!
                    </p>
                    
                    <div class="space-y-3">
                        <a href="{{ route('find.tea') }}" class="btn-primary block">
                            🔍 Find Teas to Rate
                        </a>
                        <a href="{{ route('user.dashboard') }}" class="btn-secondary block">
                            🏠 Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforelse
</div>

<!-- Tea Details Modal -->
<div id="teaDetailsModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" onclick="closeTeaDetails()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all max-w-2xl w-full">
                <div class="relative h-64">
                    <img id="modalTeaImage" src="" alt="" class="w-full h-full object-cover"
                         onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=600&h=400&fit=crop';">
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
                    <div class="flex gap-3 pt-2">
                        <a id="modalShopLink" href="#" target="_blank" rel="noopener noreferrer"
                           class="flex-1 btn-primary py-3 px-4 text-center flex items-center justify-center gap-2 rounded-xl">
                            Shop Now
                        </a>
                        <a id="modalSourceLink" href="#" target="_blank" rel="noopener noreferrer"
                           class="flex-1 btn-secondary py-3 px-4 text-center flex items-center justify-center gap-2 rounded-xl">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            Source
                        </a>
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

function openTeaDetails(id, name, flavor, caffeine, healthBenefit, image, shopLink, sourceUrl) {
    document.getElementById('modalTeaImage').src = image;
    document.getElementById('modalTeaName').textContent = name;
    document.getElementById('modalTeaFlavor').textContent = flavor;
    document.getElementById('modalTeaFlavor2').textContent = flavor;
    document.getElementById('modalTeaCaffeine').textContent = caffeine;
    document.getElementById('modalTeaHealth').textContent = healthBenefit || 'No health benefit information available.';

    const shopBtn = document.getElementById('modalShopLink');
    if (shopLink) {
        shopBtn.href = shopLink;
        shopBtn.style.display = 'flex';
    } else {
        shopBtn.style.display = 'none';
    }

    const sourceBtn = document.getElementById('modalSourceLink');
    if (sourceUrl) {
        sourceBtn.href = sourceUrl;
        sourceBtn.style.display = 'flex';
    } else {
        sourceBtn.style.display = 'none';
    }

    document.getElementById('teaDetailsModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeTeaDetails() {
    document.getElementById('teaDetailsModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

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

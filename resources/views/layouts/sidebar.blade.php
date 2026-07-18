<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teazy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Mobile sidebar styles - desktop unchanged */
        @media (max-width: 1023px) {
            #sidebar { display: none !important; }
            #mobileMenuBtn { display: flex !important; }
            #mobileSidebarWrapper {
                display: block;
                position: fixed;
                top: 0;
                right: 0;
                height: 100vh;
                width: 260px;
                z-index: 40;
                transform: translateX(100%);
                transition: transform 0.3s ease-in-out;
                background: var(--sidebar-bg);
            }
            #mobileSidebarWrapper.open { transform: translateX(0); }
            #mobileOverlay {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 30;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.3s ease-in-out;
            }
            #mobileOverlay.open { opacity: 1; pointer-events: auto; }
        }
        @media (min-width: 1024px) {
            #mobileMenuBtn { display: none !important; }
            #mobileSidebarWrapper { display: none !important; }
            #mobileOverlay { display: none !important; }
        }
    </style>
    @include('layouts.partials.pwa')
</head>
<body>

<!-- MOBILE MENU BUTTON -->
<button id="mobileMenuBtn" class="fixed top-4 right-4 z-50 p-3 rounded-xl shadow-lg transition-all duration-200 hover:scale-105" style="background: var(--primary-green); display: none;">
    <svg id="menuIcon" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
    <svg id="closeIcon" class="w-6 h-6 text-white hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
</button>

<!-- MOBILE OVERLAY -->
<div id="mobileOverlay"></div>

<!-- MOBILE SIDEBAR -->
<div id="mobileSidebarWrapper">
    <div class="w-64 sidebar p-6 h-full overflow-y-auto">
        <div class="mb-8">
            <h1 class="text-2xl font-bold mb-2" style="color: var(--primary-green);">🍃 Teazy</h1>
            <p class="text-sm" style="color: var(--text-light);">Discover Your Perfect Tea</p>
        </div>

        <nav class="space-y-2">
            <a href="{{ route('user.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-green-50">
                <span class="text-lg">🏠</span>
                <span class="font-medium" style="color: var(--text-medium);">Home</span>
            </a>
            <a href="{{ route('find.tea') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-green-50">
                <span class="text-lg">🔍</span>
                <span class="font-medium" style="color: var(--text-medium);">Find Tea</span>
            </a>
            <a href="{{ route('top.tea') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-green-50">
                <span class="text-lg">🏆</span>
                <span class="font-medium" style="color: var(--text-medium);">Top Tea</span>
            </a>
            <a href="{{ route('rated.tea') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-green-50">
                <span class="text-lg">⭐</span>
                <span class="font-medium" style="color: var(--text-medium);">Rated Tea</span>
            </a>
            <a href="{{ route('recommendations') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-green-50">
                <span class="text-lg">💡</span>
                <span class="font-medium" style="color: var(--text-medium);">Recommendations</span>
            </a>

            @if(config('services.telegram.link'))
                <a href="{{ config('services.telegram.link') }}" target="_blank" rel="noopener" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-white" style="background: #229ED9;">
                    <span class="text-lg">🤖</span>
                    <span class="font-medium">Tea Bot</span>
                </a>
            @endif

            <div class="pt-4 mt-4 border-t" style="border-color: var(--border-color);">
                <a href="{{ route('favourites.show') }}" class="flex items-center space-x-3 px-4 py-2 mb-2 hover:bg-green-50 rounded-lg">
                    <span class="text-lg">❤️</span>
                    <div class="flex-1">
                        <span class="font-bold text-sm" style="color: var(--primary-green);">My Favourites</span>
                    </div>
                </a>
            </div>

            <div class="pt-4 mt-4 border-t" style="border-color: var(--border-color);">
                <a href="{{ route('user.profile.show') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-green-50">
                    <span class="text-lg">👤</span>
                    <span class="font-medium" style="color: var(--text-medium);">Profile</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="flex items-center space-x-3 px-4 py-3 rounded-xl hover:bg-red-50 w-full text-left">
                        <span class="text-lg">🚪</span>
                        <span class="font-medium text-red-500">Logout</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>
</div>

<div class="flex min-h-screen">

    <!-- DESKTOP SIDEBAR (Original, unchanged) -->
    <div id="sidebar" class="w-64 sidebar p-6">
        <div class="mb-8">
            <h1 class="text-2xl font-bold mb-2" style="color: var(--primary-green);">
                🍃 Teazy
            </h1>
            <p class="text-sm" style="color: var(--text-light);">Discover Your Perfect Tea</p>
        </div>

        <nav class="space-y-2">
            <a href="{{ route('user.dashboard') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-green-50 group">
                <span class="text-lg group-hover:scale-110 transition-transform">🏠</span>
                <span style="color: var(--text-medium);" class="font-medium">Home</span>
            </a>

            <a href="{{ route('find.tea') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-green-50 group">
                <span class="text-lg group-hover:scale-110 transition-transform">🔍</span>
                <span style="color: var(--text-medium);" class="font-medium">Find Tea</span>
            </a>

            <a href="{{ route('top.tea') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-green-50 group">
                <span class="text-lg group-hover:scale-110 transition-transform">🏆</span>
                <span style="color: var(--text-medium);" class="font-medium">Top Tea</span>
            </a>

            <a href="{{ route('rated.tea') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-green-50 group">
                <span class="text-lg group-hover:scale-110 transition-transform">⭐</span>
                <span style="color: var(--text-medium);" class="font-medium">Rated Tea</span>
            </a>

            <a href="{{ route('recommendations') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-green-50 group">
                <span class="text-lg group-hover:scale-110 transition-transform">💡</span>
                <span style="color: var(--text-medium);" class="font-medium">Recommendations</span>
            </a>

            @if(config('services.telegram.link'))
                <a href="{{ config('services.telegram.link') }}" target="_blank" rel="noopener"
                   class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:opacity-90 group"
                   style="background: #229ED9;">
                    <span class="text-lg group-hover:scale-110 transition-transform">🤖</span>
                    <span class="font-medium text-white">Tea Bot on Telegram</span>
                    <svg class="w-4 h-4 text-white/80 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            @endif

            <!-- Favourites Section -->
            <div class="pt-4 mt-4 border-t" style="border-color: var(--border-color);">
                <a href="{{ route('favourites.show') }}" class="flex items-center space-x-3 px-4 py-2 mb-2 hover:bg-green-50 rounded-lg transition-all duration-200 group">
                    <span class="text-lg group-hover:scale-110 transition-transform">❤️</span>
                    <div class="flex-1">
                        <span class="font-bold text-sm" style="color: var(--primary-green);">My Favourites</span>
                        <p class="text-xs text-gray-400">View all favourites</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <div id="favouritesList" class="space-y-1 max-h-48 overflow-y-auto px-2">
                    <div class="text-center py-4 text-gray-400">
                        <svg class="w-8 h-8 mx-auto mb-1 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        <p class="text-xs">Loading favourites...</p>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t" style="border-color: var(--border-color);">
                <a href="{{ route('user.profile.show') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-green-50 group {{ request()->routeIs('user.profile.*') ? 'bg-green-50' : '' }}">
                    <span class="text-lg group-hover:scale-110 transition-transform">👤</span>
                    <span style="color: var(--text-medium);" class="font-medium">Profile</span>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 hover:bg-red-50 group w-full text-left">
                        <span class="text-lg group-hover:scale-110 transition-transform">🚪</span>
                        <span class="font-medium text-red-500">Logout</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="flex-1 p-8">
        @yield('content')
    </div>

</div>

<script>
(function() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileSidebarWrapper = document.getElementById('mobileSidebarWrapper');
    const mobileOverlay = document.getElementById('mobileOverlay');
    const menuIcon = document.getElementById('menuIcon');
    const closeIcon = document.getElementById('closeIcon');

    if (!mobileMenuBtn || !mobileSidebarWrapper || !mobileOverlay || !menuIcon || !closeIcon) return;

    function toggleMobileSidebar() {
        const isOpen = mobileSidebarWrapper.classList.contains('open');
        if (isOpen) {
            mobileSidebarWrapper.classList.remove('open');
            mobileOverlay.classList.remove('open');
            menuIcon.classList.remove('hidden');
            closeIcon.classList.add('hidden');
        } else {
            mobileSidebarWrapper.classList.add('open');
            mobileOverlay.classList.add('open');
            menuIcon.classList.add('hidden');
            closeIcon.classList.remove('hidden');
        }
    }

    mobileMenuBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleMobileSidebar();
    });

    mobileOverlay.addEventListener('click', function(e) {
        e.preventDefault();
        toggleMobileSidebar();
    });

    const mobileLinks = mobileSidebarWrapper.querySelectorAll('a, button');
    mobileLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            // Don't close if it's an external link
            if (link.getAttribute('target') === '_blank') return;
            toggleMobileSidebar();
        });
    });
})();

// Load favourites sidebar (desktop)
async function loadFavourites() {
    try {
        const res = await fetch('/favourites/api', {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        const container = document.getElementById('favouritesList');
        if (!container) return;

        if (data.favourites && data.favourites.length > 0) {
            container.innerHTML = data.favourites.map(tea => `
                <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-green-50 transition-colors group cursor-pointer"
                     onclick="window.location.href='/recommendations'">
                    <img src="${tea.image}" alt="${tea.name}" class="w-10 h-10 rounded-lg object-cover">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">${tea.name}</p>
                        <p class="text-xs text-gray-500">${tea.flavor}</p>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = `
                <div class="text-center py-4 text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-1 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <p class="text-xs">No favourites yet</p>
                </div>
            `;
        }
    } catch (e) {
        console.error('Error loading favourites:', e);
    }
}

document.addEventListener('DOMContentLoaded', loadFavourites);
</script>

</body>
</html>

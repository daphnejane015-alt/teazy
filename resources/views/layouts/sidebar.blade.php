<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Teazy</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <div class="w-64 sidebar p-6">
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
// Load favourites sidebar
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

// Load favourites on page load
document.addEventListener('DOMContentLoaded', loadFavourites);
</script>

</body>
</html>

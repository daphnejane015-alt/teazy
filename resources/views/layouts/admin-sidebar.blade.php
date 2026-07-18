<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('layouts.partials.pwa')
</head>
<body class="bg-gray-100">

<!-- Mobile Header -->
<div id="mobileAdminHeader" class="fixed top-0 left-0 right-0 z-50 h-16 bg-white border-b border-gray-200 px-4 flex items-center justify-between lg:hidden shadow-sm">
    <span class="text-lg font-bold text-gray-800 flex items-center gap-2">
        <span>🛠</span>
        <span>Admin Panel</span>
    </span>
    <button id="mobileAdminMenuBtn" class="p-2 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors" aria-label="Toggle menu">
        <svg id="adminMenuIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
        <svg id="adminCloseIcon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

<!-- Mobile Overlay -->
<div id="mobileAdminOverlay" class="fixed inset-0 bg-black/50 z-30 opacity-0 pointer-events-none transition-opacity duration-300 lg:hidden"></div>

<!-- Mobile Sidebar -->
<div id="mobileAdminSidebarWrapper" class="fixed top-16 bottom-0 right-0 w-64 bg-gray-900 text-white p-5 z-40 translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto lg:hidden">
    <div id="mobileAdminNavContainer"></div>
</div>

<div class="flex min-h-screen">

    <div id="adminSidebar" class="hidden lg:block w-64 bg-gray-900 text-white p-5 flex-shrink-0">
        <h1 class="text-2xl font-bold mb-8">🛠 Admin Panel</h1>

        @php
            $navBase = 'flex items-center space-x-3 px-3 py-2 rounded-md transition-colors duration-150';
            $navIdle = 'text-gray-300 hover:bg-gray-800 hover:text-white';
            $navActive = 'bg-blue-600 text-white';
        @endphp

        <nav id="adminNav" class="space-y-6">
            <div class="space-y-1">
                <a href="{{ route('admin.dashboard') }}"
                   class="{{ $navBase }} {{ request()->routeIs('admin.dashboard') ? $navActive : $navIdle }}">
                    <span class="w-6 text-center">📊</span><span>Dashboard</span>
                </a>
            </div>

            <div class="border-t border-gray-700 pt-4 space-y-1">
                <h3 class="text-xs uppercase tracking-wider text-gray-400 mb-2 px-3">Tea Management</h3>
                <a href="{{ route('admin.teas.manual') }}"
                   class="{{ $navBase }} {{ request()->routeIs('admin.teas.manual') || request()->routeIs('admin.teas.index') || request()->routeIs('admin.teas.edit') || request()->routeIs('admin.teas.create') ? $navActive : $navIdle }}">
                    <span class="w-6 text-center">🗂</span><span>Manage Teas</span>
                </a>
                <a href="{{ route('admin.teas.scraped') }}"
                   class="{{ $navBase }} {{ request()->routeIs('admin.teas.scraped') ? $navActive : $navIdle }}">
                    <span class="w-6 text-center">🗂</span><span>Scraped Teas</span>
                </a>
            </div>

            <div class="border-t border-gray-700 pt-4 space-y-1">
                <h3 class="text-xs uppercase tracking-wider text-gray-400 mb-2 px-3">User Management</h3>
                <a href="{{ route('admin.users.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('admin.users.index') || request()->routeIs('admin.users.show') || request()->routeIs('admin.users.edit') ? $navActive : $navIdle }}">
                    <span class="w-6 text-center">👥</span><span>Manage Users</span>
                </a>
                <a href="{{ route('admin.users.create') }}"
                   class="{{ $navBase }} {{ request()->routeIs('admin.users.create') ? $navActive : $navIdle }}">
                    <span class="w-6 text-center">➕</span><span>Add User</span>
                </a>
            </div>

            <div class="border-t border-gray-700 pt-4 space-y-1">
                <h3 class="text-xs uppercase tracking-wider text-gray-400 mb-2 px-3">Rating Management</h3>
                <a href="{{ route('admin.ratings.index') }}"
                   class="{{ $navBase }} {{ request()->routeIs('admin.ratings.*') ? $navActive : $navIdle }}">
                    <span class="w-6 text-center">⭐</span><span>Manage Ratings</span>
                </a>
            </div>

            <div class="border-t border-gray-700 pt-4">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="{{ $navBase }} w-full {{ $navIdle }} hover:!text-red-300 text-red-400">
                        <span class="w-6 text-center">🚪</span><span>Admin Logout</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>

    <div class="flex-1 p-4 pt-20 lg:p-8 w-full min-w-0">
        @yield('content')
    </div>
</div>
<script>
(function() {
    const mobileMenuBtn = document.getElementById('mobileAdminMenuBtn');
    const mobileSidebarWrapper = document.getElementById('mobileAdminSidebarWrapper');
    const mobileOverlay = document.getElementById('mobileAdminOverlay');
    const menuIcon = document.getElementById('adminMenuIcon');
    const closeIcon = document.getElementById('adminCloseIcon');
    const adminNav = document.getElementById('adminNav');
    const mobileNavContainer = document.getElementById('mobileAdminNavContainer');

    if (mobileNavContainer && adminNav) {
        mobileNavContainer.innerHTML = adminNav.innerHTML;
    }

    if (!mobileMenuBtn || !mobileSidebarWrapper || !mobileOverlay || !menuIcon || !closeIcon) return;

    function toggleMobileSidebar() {
        const isOpen = mobileSidebarWrapper.classList.contains('translate-x-0');
        if (isOpen) {
            mobileSidebarWrapper.classList.remove('translate-x-0');
            mobileSidebarWrapper.classList.add('translate-x-full');
            mobileOverlay.classList.remove('opacity-100', 'pointer-events-auto');
            mobileOverlay.classList.add('opacity-0', 'pointer-events-none');
            menuIcon.classList.remove('hidden');
            closeIcon.classList.add('hidden');
        } else {
            mobileSidebarWrapper.classList.remove('translate-x-full');
            mobileSidebarWrapper.classList.add('translate-x-0');
            mobileOverlay.classList.remove('opacity-0', 'pointer-events-none');
            mobileOverlay.classList.add('opacity-100', 'pointer-events-auto');
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
            if (link.getAttribute('target') === '_blank') return;
            toggleMobileSidebar();
        });
    });
})();
</script>
@stack('scripts')
</body>
</html>

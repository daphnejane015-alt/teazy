@php
    $navBase = 'flex items-center space-x-3 px-3 py-2 rounded-md transition-colors duration-150';
    $navIdle = 'text-gray-300 hover:bg-gray-800 hover:text-white';
    $navActive = 'bg-blue-600 text-white';
@endphp

<h1 class="text-2xl font-bold mb-8">🛠 Admin Panel</h1>

<nav class="space-y-6">
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

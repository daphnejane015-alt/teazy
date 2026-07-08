<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">

<div class="flex min-h-screen">

    <div class="w-64 bg-gray-900 text-white p-5">
        <h1 class="text-2xl font-bold mb-8">🛠 Admin Panel</h1>

        <nav class="space-y-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-2 hover:text-blue-600"><span>📊</span> Dashboard</a>

            <div class="border-t border-gray-700 pt-4">
                <h3 class="text-xs uppercase tracking-wider text-gray-400 mb-2">Tea Management</h3>
                <a href="{{ route('admin.teas.manual') }}" class="flex items-center space-x-2 hover:text-blue-600 block mb-2"><span>🗂</span> Manage Teas</a>
                <a href="{{ route('admin.teas.scraped') }}" class="flex items-center space-x-2 hover:text-blue-600"><span>🗂</span> Scraped Teas</a>
            </div>

            <div class="border-t border-gray-700 pt-4">
                <h3 class="text-xs uppercase tracking-wider text-gray-400 mb-2">User Management</h3>
                <a href="{{ route('admin.users.index') }}" class="flex items-center space-x-2 hover:text-blue-600 block mb-2"><span>👥</span> Manage Users</a>
                <a href="{{ route('admin.users.create') }}" class="flex items-center space-x-2 hover:text-blue-600"><span>➕</span> Add User</a>
            </div>

            <div class="border-t border-gray-700 pt-4">
                <h3 class="text-xs uppercase tracking-wider text-gray-400 mb-2">Rating Management</h3>
                <a href="{{ route('admin.ratings.index') }}" class="flex items-center space-x-2 hover:text-blue-600"><span>⭐</span> Manage Ratings</a>
            </div>

            <div class="border-t border-gray-700 pt-4 mt-4">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-red-400 hover:text-red-300">🚪 Admin Logout</button>
                </form>
            </div>
        </nav>
    </div>

    <div class="flex-1 p-8">
        @yield('content')
    </div>
</div>
@stack('scripts')
</body>
</html>

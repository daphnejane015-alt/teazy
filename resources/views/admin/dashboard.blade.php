@extends('layouts.admin-sidebar')

@section('content')
<div class="mb-6">
    <h1 class="text-3xl font-bold">Admin Dashboard</h1>
    <p class="text-gray-600 mt-2">Overview of your tea application</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full">
                <span class="text-2xl">🍵</span>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-700">Total Teas</h3>
                <p class="text-3xl font-bold text-blue-600">{{ $teaCount }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-green-100 rounded-full">
                <span class="text-2xl">👥</span>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-700">Users</h3>
                <p class="text-3xl font-bold text-green-600">{{ $userCount }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-3 bg-yellow-100 rounded-full">
                <span class="text-2xl">⭐</span>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-700">Ratings</h3>
                <p class="text-3xl font-bold text-yellow-600">{{ $ratingCount }}</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
        <div class="space-y-3">
            <a href="{{ route('admin.teas.index') }}" class="block w-full text-left bg-blue-50 hover:bg-blue-100 p-3 rounded transition">
                <span class="text-blue-600">📋</span> Manage Teas
            </a>
            <form action="{{ route('admin.scrape.teas') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="w-full text-left bg-green-50 hover:bg-green-100 p-3 rounded transition">
                    <span class="text-green-600">🕷️</span> Scrap New Tea Data
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold mb-4">System Status</h2>
        <div class="space-y-2">
            <div class="flex justify-between">
                <span class="text-gray-600">Database</span>
                <span class="text-green-600">✅ Connected</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Last Scrape</span>
                <span class="text-gray-500">Not available</span>
            </div>
        </div>
    </div>
</div>
@endsection

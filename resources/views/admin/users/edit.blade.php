@extends('layouts.admin-sidebar')

@section('content')
<div class="mb-6">
    <div class="flex items-center">
        <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">
            ← Back to Users
        </a>
        <h1 class="text-3xl font-bold">Edit User</h1>
    </div>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.users.update', $user->id) }}">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <!-- User Info -->
                <div class="bg-gray-50 p-4 rounded-md">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">User Information</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">User ID:</span>
                            <span class="ml-2 font-medium">{{ $user->id }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Joined:</span>
                            <span class="ml-2 font-medium">{{ $user->created_at->format('M j, Y') }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Last Login:</span>
                            <span class="ml-2 font-medium">{{ $user->last_login_at?->format('M j, Y g:i A') ?? 'Never' }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Ratings:</span>
                            <span class="ml-2 font-medium">{{ $user->ratings()->count() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $user->name) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           required>
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="{{ old('email', $user->email) }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           required>
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700">
                        User Role <span class="text-red-500">*</span>
                    </label>
                    <select id="role" 
                            name="role" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                            required>
                        <option value="user" {{ old('role', $user->role) == 'user' ? 'selected' : '' }}>
                            👤 Regular User
                        </option>
                        <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>
                            👑 Administrator
                        </option>
                    </select>
                    @error('role')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Section -->
                <div class="border-t pt-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-4">Change Password (Optional)</h3>
                    <p class="text-xs text-gray-500 mb-4">Leave blank to keep current password</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                New Password
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Minimum 8 characters</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                                Confirm New Password
                            </label>
                            <input type="password" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Warning for self-edit -->
                @if($user->id === auth()->guard('admin')->id())
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <h4 class="text-sm font-medium text-yellow-800">⚠️ Important:</h4>
                        <p class="text-xs text-yellow-700 mt-1">
                            You are editing your own account. Changing your role from admin may limit your access to the admin panel.
                        </p>
                    </div>
                @endif
            </div>

            <div class="mt-8 flex items-center justify-between">
                <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900">
                    Cancel
                </a>
                <div class="space-x-4">
                    <a href="{{ route('admin.users.show', $user->id) }}" class="text-blue-600 hover:text-blue-900">
                        View User
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                        Update User
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@extends('layouts.sidebar')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Change Password</h1>
        <p class="text-gray-600">Update your account password for better security</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <form method="POST" action="{{ route('profile.update-password') }}" class="space-y-6">
                @csrf
                
                <!-- Password Change Form -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                        <span class="text-2xl mr-2">🔐</span>
                        Security Settings
                    </h2>
                    
                    <div class="space-y-6">
                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Current Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    required
                                    class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                                    placeholder="Enter your current password"
                                >
                                <button type="button" onclick="togglePassword('current_password')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                    <span id="current_password_toggle">👁️</span>
                                </button>
                            </div>
                            @error('current_password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- New Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                New Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    required
                                    class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                                    placeholder="Enter your new password"
                                >
                                <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                    <span id="password_toggle">👁️</span>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            
                            <!-- Password Strength Indicator -->
                            <div class="mt-2">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs text-gray-500">Password Strength</span>
                                    <span id="strength_text" class="text-xs font-medium">Weak</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div id="strength_bar" class="h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirm New Password
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="password_confirmation" 
                                    name="password_confirmation" 
                                    required
                                    class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                                    placeholder="Confirm your new password"
                                >
                                <button type="button" onclick="togglePassword('password_confirmation')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                                    <span id="password_confirmation_toggle">👁️</span>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Password Requirements -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <span class="text-xl mr-2">📋</span>
                        Password Requirements
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-center gap-2">
                            <span id="req_length" class="text-gray-400">○</span>
                            <span>At least 8 characters long</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span id="req_uppercase" class="text-gray-400">○</span>
                            <span>Contains uppercase letter (A-Z)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span id="req_lowercase" class="text-gray-400">○</span>
                            <span>Contains lowercase letter (a-z)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span id="req_number" class="text-gray-400">○</span>
                            <span>Contains number (0-9)</span>
                        </li>
                        <li class="flex items-center gap-2">
                            <span id="req_special" class="text-gray-400">○</span>
                            <span>Contains special character (!@#$%^&*)</span>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition duration-200">
                        🔒 Update Password
                    </button>
                    <a href="{{ route('profile') }}" class="flex-1 bg-gray-200 text-gray-800 py-3 px-6 rounded-lg font-semibold hover:bg-gray-300 transition duration-200 text-center">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Security Tips -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <span class="text-xl mr-2">🛡️</span>
                    Security Tips
                </h3>
                <ul class="space-y-3 text-sm text-gray-700">
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 mt-1">✓</span>
                        <span>Use a unique password for each account</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 mt-1">✓</span>
                        <span>Avoid using personal information in passwords</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 mt-1">✓</span>
                        <span>Change passwords regularly (every 3 months)</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-blue-600 mt-1">✓</span>
                        <span>Never share your password with anyone</span>
                    </li>
                </ul>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('profile') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                        <span class="text-xl">👤</span>
                        <span class="text-gray-700">View Profile</span>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                        <span class="text-xl">✏️</span>
                        <span class="text-gray-700">Edit Profile</span>
                    </a>
                    <a href="{{ route('user.dashboard') }}" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition duration-200">
                        <span class="text-xl">🏠</span>
                        <span class="text-gray-700">Dashboard</span>
                    </a>
                </div>
            </div>

            <!-- Account Status -->
            <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <span class="text-xl mr-2">✅</span>
                    Account Status
                </h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Account Security</span>
                        <span class="text-green-600 font-medium">Good</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Last Password Change</span>
                        <span class="text-gray-600 font-medium">Never</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">Login Attempts</span>
                        <span class="text-green-600 font-medium">0 Failed</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = document.getElementById(fieldId + '_toggle');
    
    if (field.type === 'password') {
        field.type = 'text';
        toggle.textContent = '🙈';
    } else {
        field.type = 'password';
        toggle.textContent = '👁️';
    }
}

// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    let strengthText = 'Weak';
    let strengthColor = '#ef4444'; // red
    
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(password)
    };
    
    // Update requirement indicators
    Object.keys(requirements).forEach(req => {
        const element = document.getElementById('req_' + req);
        if (requirements[req]) {
            element.textContent = '✓';
            element.className = 'text-green-600';
            strength += 20;
        } else {
            element.textContent = '○';
            element.className = 'text-gray-400';
        }
    });
    
    // Update strength bar
    const strengthBar = document.getElementById('strength_bar');
    const strengthTextElement = document.getElementById('strength_text');
    
    if (strength <= 20) {
        strengthText = 'Very Weak';
        strengthColor = '#ef4444'; // red
    } else if (strength <= 40) {
        strengthText = 'Weak';
        strengthColor = '#f59e0b'; // yellow
    } else if (strength <= 60) {
        strengthText = 'Fair';
        strengthColor = '#f59e0b'; // yellow
    } else if (strength <= 80) {
        strengthText = 'Good';
        strengthColor = '#10b981'; // green
    } else {
        strengthText = 'Strong';
        strengthColor = '#10b981'; // green
    }
    
    strengthBar.style.width = strength + '%';
    strengthBar.style.backgroundColor = strengthColor;
    strengthTextElement.textContent = strengthText;
    strengthTextElement.style.color = strengthColor;
});

// Check password confirmation
document.getElementById('password_confirmation').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmation = this.value;
    
    if (confirmation && password !== confirmation) {
        this.style.borderColor = '#ef4444';
    } else if (confirmation && password === confirmation) {
        this.style.borderColor = '#10b981';
    } else {
        this.style.borderColor = '#d1d5db';
    }
});
</script>
@endsection

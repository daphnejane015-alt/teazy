<x-guest-layout>
    <!-- Success Messages -->
    @if (session('success'))
        <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Registration Form -->
    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <!-- Header -->
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">Create Account</h2>
            <p class="text-gray-600">Join our tea community today</p>
        </div>

        <!-- Name -->
        <div class="input-group">
            <input 
                id="name" 
                type="text" 
                name="name" 
                :value="old('name')" 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                placeholder=" "
                required 
                autofocus 
                autocomplete="name"
            >
            <label for="name" class="bg-white px-1">Full Name</label>
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Email Address -->
        <div class="input-group">
            <input 
                id="email" 
                type="email" 
                name="email" 
                :value="old('email')" 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                placeholder=" "
                required 
                autocomplete="username"
            >
            <label for="email" class="bg-white px-1">Email Address</label>
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <div class="input-group relative">
            <input 
                id="password" 
                type="password" 
                name="password" 
                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                placeholder=" "
                required 
                autocomplete="new-password"
                oninput="checkPasswordStrength(this.value)"
            >
            <label for="password" class="bg-white px-1">Password</label>
            <button type="button" class="toggle-password text-gray-500 hover:text-green-600 focus:outline-none" data-target="password" aria-label="Show password" tabindex="-1">
                <svg class="eye-open w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg class="eye-closed w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 01-1.18-2.19m15.084 9.393A10.05 10.05 0 0021.542 12C20.268 7.943 16.478 5 12 5a10.05 10.05 0 00-2.68.366M9 9l-1.63 1.63m5.53 5.53l-1.63-1.63M3 3l18 18"/>
                </svg>
            </button>
            @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <!-- Strength Bar -->
            <div class="mt-2">
                <div class="flex gap-1 mb-1">
                    <div id="bar1" class="h-1.5 flex-1 rounded bg-gray-200 transition-all duration-300"></div>
                    <div id="bar2" class="h-1.5 flex-1 rounded bg-gray-200 transition-all duration-300"></div>
                    <div id="bar3" class="h-1.5 flex-1 rounded bg-gray-200 transition-all duration-300"></div>
                    <div id="bar4" class="h-1.5 flex-1 rounded bg-gray-200 transition-all duration-300"></div>
                </div>
                <p id="strength-label" class="text-xs text-gray-400">Enter a password</p>
            </div>

            <!-- Rules Checklist -->
            <ul class="mt-2 space-y-1 text-xs">
                <li id="rule-length" class="flex items-center gap-1 text-gray-400">
                    <span id="icon-length">○</span> At least 8 characters
                </li>
                <li id="rule-upper" class="flex items-center gap-1 text-gray-400">
                    <span id="icon-upper">○</span> Uppercase letter (A–Z)
                </li>
                <li id="rule-lower" class="flex items-center gap-1 text-gray-400">
                    <span id="icon-lower">○</span> Lowercase letter (a–z)
                </li>
                <li id="rule-number" class="flex items-center gap-1 text-gray-400">
                    <span id="icon-number">○</span> Number (0–9)
                </li>
                <li id="rule-symbol" class="flex items-center gap-1 text-gray-400">
                    <span id="icon-symbol">○</span> Special character (!@#$%...)
                </li>
            </ul>
        </div>

        <!-- Confirm Password -->
        <div class="input-group relative">
            <input 
                id="password_confirmation" 
                type="password" 
                name="password_confirmation" 
                class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                placeholder=" "
                required 
                autocomplete="new-password"
                oninput="checkConfirm(this.value)"
            >
            <label for="password_confirmation" class="bg-white px-1">Confirm Password</label>
            <button type="button" class="toggle-password text-gray-500 hover:text-green-600 focus:outline-none" data-target="password_confirmation" aria-label="Show confirm password" tabindex="-1">
                <svg class="eye-open w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                <svg class="eye-closed w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a10.05 10.05 0 01-1.18-2.19m15.084 9.393A10.05 10.05 0 0021.542 12C20.268 7.943 16.478 5 12 5a10.05 10.05 0 00-2.68.366M9 9l-1.63 1.63m5.53 5.53l-1.63-1.63M3 3l18 18"/>
                </svg>
            </button>
            @error('password_confirmation')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p id="confirm-msg" class="mt-1 text-xs text-gray-400 hidden"></p>
        </div>

        <script>
        function checkPasswordStrength(val) {
            const rules = {
                length: val.length >= 8,
                upper:  /[A-Z]/.test(val),
                lower:  /[a-z]/.test(val),
                number: /[0-9]/.test(val),
                symbol: /[^A-Za-z0-9]/.test(val),
            };

            const passed = Object.values(rules).filter(Boolean).length;
            const colors = ['bg-red-500', 'bg-orange-400', 'bg-yellow-400', 'bg-green-500'];
            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
            const labelColors = ['', 'text-red-500', 'text-orange-400', 'text-yellow-500', 'text-green-600'];

            for (let i = 1; i <= 4; i++) {
                const bar = document.getElementById('bar' + i);
                bar.className = 'h-1.5 flex-1 rounded transition-all duration-300 ' +
                    (i <= passed ? colors[passed - 1] : 'bg-gray-200');
            }

            const label = document.getElementById('strength-label');
            label.textContent = passed > 0 ? labels[passed] : 'Enter a password';
            label.className = 'text-xs ' + (passed > 0 ? labelColors[passed] : 'text-gray-400');

            Object.keys(rules).forEach(key => {
                const li = document.getElementById('rule-' + key);
                const icon = document.getElementById('icon-' + key);
                if (rules[key]) {
                    li.className = 'flex items-center gap-1 text-green-600';
                    icon.textContent = '✓';
                } else {
                    li.className = 'flex items-center gap-1 text-gray-400';
                    icon.textContent = '○';
                }
            });

            checkConfirm(document.getElementById('password_confirmation').value);
        }

        function checkConfirm(val) {
            const pw = document.getElementById('password').value;
            const msg = document.getElementById('confirm-msg');
            if (!val) { msg.classList.add('hidden'); return; }
            msg.classList.remove('hidden');
            if (val === pw) {
                msg.textContent = '✓ Passwords match';
                msg.className = 'mt-1 text-xs text-green-600';
            } else {
                msg.textContent = '✗ Passwords do not match';
                msg.className = 'mt-1 text-xs text-red-500';
            }
        }
        </script>

        <input type="hidden" name="role" value="user">

        <!-- Terms and Conditions -->
        <div class="flex items-start">
            <input 
                type="checkbox" 
                id="terms" 
                name="terms" 
                required
                class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500 mt-1"
            >
            <label for="terms" class="ml-2 text-sm text-gray-600">
                I agree to the <a href="#" class="text-green-600 hover:text-green-500">Terms and Conditions</a> and <a href="#" class="text-green-600 hover:text-green-500">Privacy Policy</a>
            </label>
        </div>

        <!-- Register Button -->
        <button type="submit" class="w-full btn-primary text-white font-semibold py-3 px-4 rounded-lg transition duration-200">
            Create Account
        </button>

        <!-- Login Link -->
        <div class="text-center pt-6 border-t border-gray-200">
            <p class="text-gray-600">
                Already have an account? 
                <a href="{{ route('login') }}" class="text-green-600 hover:text-green-500 font-semibold transition duration-200">
                    Sign in here
                </a>
            </p>
        </div>
    </form>
</x-guest-layout>

<x-guest-layout>
    <!-- Header -->
    <div class="text-center mb-6">
        <div class="flex justify-center mb-4">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                <span class="text-3xl">📧</span>
            </div>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Verify Your Email</h2>
        <p class="text-gray-500 text-sm">One more step to get started</p>
    </div>

    <!-- Info Box -->
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <p class="text-sm text-green-800">
            We've sent a verification link to <span class="font-semibold">{{ auth()->user()->email }}</span>. 
            Please check your inbox and click the link to activate your account.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm text-blue-700 font-medium">
                ✅ A new verification link has been sent to your email address.
            </p>
        </div>
    @endif

    <!-- Steps -->
    <div class="space-y-3 mb-6">
        <div class="flex items-center gap-3 text-sm text-gray-600">
            <span class="w-6 h-6 rounded-full bg-green-500 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">1</span>
            <span>Open your email inbox</span>
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-600">
            <span class="w-6 h-6 rounded-full bg-green-500 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">2</span>
            <span>Find the email from <strong>Teazy</strong></span>
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-600">
            <span class="w-6 h-6 rounded-full bg-green-500 text-white flex items-center justify-center text-xs font-bold flex-shrink-0">3</span>
            <span>Click the <strong>Verify Email Address</strong> button</span>
        </div>
    </div>

    <!-- Resend Button -->
    <form method="POST" action="{{ route('verification.send') }}" class="mb-4">
        @csrf
        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200">
            Resend Verification Email
        </button>
    </form>

    <!-- Logout -->
    <div class="text-center pt-4 border-t border-gray-200">
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 underline transition duration-200">
                Use a different account (Log out)
            </button>
        </form>
    </div>
</x-guest-layout>

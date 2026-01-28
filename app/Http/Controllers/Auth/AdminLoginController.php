<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminLoginController extends Controller
{
    /**
     * Display the admin login view.
     */
    public function create(): View
    {
        return view('auth.admin-login');
    }

    /**
     * Handle an incoming admin authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Validate credentials
        $credentials = $request->only('email', 'password');
        
        // Use default auth to validate credentials
        if (!Auth::attempt($credentials)) {
            return redirect()->route('admin.login')
                ->with('error', 'Invalid credentials.');
        }
        
        // Get authenticated user
        $user = Auth::user();
        
        // Verify user has admin role
        if ($user->role !== 'admin') {
            Auth::logout();
            return redirect()->route('admin.login')
                ->with('error', 'Access denied. Admin access required.');
        }

        // Store admin ID in session (separate from user session)
        $request->session()->put('admin_id', $user->id);
        
        // Logout from default auth to avoid session conflict
        Auth::logout();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Welcome to the admin dashboard!');
    }

    /**
     * Destroy an authenticated admin session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Remove admin ID from session
        $request->session()->forget('admin_id');

        return redirect()->route('admin.login')
            ->with('success', 'Admin logged out successfully.');
    }
}

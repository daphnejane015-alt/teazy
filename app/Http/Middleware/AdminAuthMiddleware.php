<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if admin is authenticated in admin session
        if (!$this->isAdminAuthenticated($request)) {
            return redirect()->route('admin.login')
                ->with('error', 'Admin authentication required.');
        }

        // Set admin user in request for easy access
        $request->merge(['admin_user' => $this->getAdminUser($request)]);

        return $next($request);
    }

    /**
     * Check if admin is authenticated
     */
    protected function isAdminAuthenticated(Request $request): bool
    {
        $adminId = $request->session()->get('admin_id');
        
        if (!$adminId) {
            return false;
        }

        $adminUser = \App\Models\User::find($adminId);
        
        return $adminUser && $adminUser->role === 'admin';
    }

    /**
     * Get admin user from session
     */
    protected function getAdminUser(Request $request): ?\App\Models\User
    {
        $adminId = $request->session()->get('admin_id');
        
        return $adminId ? \App\Models\User::find($adminId) : null;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   public function handle($request, Closure $next)
{
    // Check if admin is authenticated using admin guard
    if (!auth()->guard('admin')->check()) {
        // Redirect to admin login instead of 403
        return redirect()->route('admin.login');
    }
    
    // Check if user has admin role
    if (auth()->guard('admin')->user()->role !== 'admin') {
        // Logout non-admin and redirect to admin login
        auth()->guard('admin')->logout();
        return redirect()->route('admin.login')
            ->with('error', 'Admin access required.');
    }

    return $next($request);
}

}

<?php

if (!function_exists('admin_auth')) {
    /**
     * Get admin authentication helper
     */
    function admin_auth()
    {
        return new class {
            public function check()
            {
                $adminId = session('admin_id');
                return $adminId && \App\Models\User::find($adminId)?->role === 'admin';
            }

            public function user()
            {
                $adminId = session('admin_id');
                return $adminId ? \App\Models\User::find($adminId) : null;
            }

            public function id()
            {
                return session('admin_id');
            }
        };
    }
}

if (!function_exists('admin_user')) {
    /**
     * Get current admin user
     */
    function admin_user()
    {
        return admin_auth()->user();
    }
}

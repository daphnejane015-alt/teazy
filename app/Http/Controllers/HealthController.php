<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index()
    {
        try {
            // Simple health check without database for now
            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'app' => 'Tea Reminder App',
                'version' => '1.0.0',
                'environment' => app()->environment()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }
}

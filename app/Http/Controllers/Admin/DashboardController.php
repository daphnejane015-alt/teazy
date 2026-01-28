<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tea;
use App\Models\User;
use App\Models\Rating;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'teaCount' => Tea::count(),
            'userCount' => User::where('role', 'user')->count(),
            'ratingCount' => Rating::count(),
        ]);
    }
}

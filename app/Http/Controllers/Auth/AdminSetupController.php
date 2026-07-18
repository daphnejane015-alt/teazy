<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminSetupController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (User::where('role', 'admin')->exists()) {
            return redirect()->route('admin.login')
                ->with('info', 'An admin account already exists. Please sign in.');
        }

        return view('auth.admin-setup');
    }

    public function store(Request $request): RedirectResponse
    {
        if (User::where('role', 'admin')->exists()) {
            return redirect()->route('admin.login')
                ->with('error', 'An admin account already exists. Please sign in.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'terms' => ['required', 'accepted'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        $user->email_verified_at = now();
        $user->save();

        $request->session()->put('admin_id', $user->id);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Admin account created. Welcome!');
    }
}

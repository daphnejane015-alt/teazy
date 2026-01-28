<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function show()
    {
        $user = Auth::user();
        
        // Get user's tea preferences and activity
        try {
            $teaRecommendations = $user->recommendations()->with('tea')->latest()->take(5)->get();
            $totalRecommendations = $user->recommendations()->count();
        } catch (\Exception $e) {
            // Fallback if recommendations relationship doesn't work
            $teaRecommendations = collect();
            $totalRecommendations = 0;
        }
        
        return view('user.profile.show', compact('user', 'teaRecommendations', 'totalRecommendations'));
    }

    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = Auth::user();
        return view('user.profile.edit', compact('user'));
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'bio' => ['nullable', 'string', 'max:500'],
            'favorite_tea_type' => ['nullable', 'string', 'max:50'],
            'caffeine_preference' => ['nullable', 'in:low,medium,high'],
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'bio' => $request->bio,
            'favorite_tea_type' => $request->favorite_tea_type,
            'caffeine_preference' => $request->caffeine_preference,
        ]);

        return redirect()->route('user.profile.show')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Show the form for changing password.
     */
    public function changePassword()
    {
        return view('user.profile.change-password');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('user.profile.show')
            ->with('success', 'Password changed successfully!');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = Auth::user();
        
        // Delete user's recommendations and other related data
        try {
            $user->recommendations()->delete();
        } catch (\Exception $e) {
            // Continue even if recommendations deletion fails
        }
        
        // Delete the user
        Auth::logout();
        $user->delete();

        return redirect()->route('login')
            ->with('success', 'Account deleted successfully.');
    }
}

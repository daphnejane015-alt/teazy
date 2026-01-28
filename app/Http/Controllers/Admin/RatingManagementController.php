<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rating;
use App\Models\User;
use App\Models\Tea;

class RatingManagementController extends Controller
{
    /**
     * Display all ratings with user information
     */
    public function index()
    {
        $ratings = Rating::with(['user', 'tea'])
            ->latest()
            ->get();
            
        $stats = [
            'total_ratings' => Rating::count(),
            'average_rating' => Rating::avg('rating'),
            'users_with_ratings' => Rating::distinct('user_id')->count('user_id'),
            'teas_with_ratings' => Rating::distinct('tea_id')->count('tea_id'),
        ];

        return view('admin.ratings.index', compact('ratings', 'stats'));
    }

    /**
     * Show the form for editing the specified rating
     */
    public function edit($id)
    {
        $rating = Rating::with(['user', 'tea'])->findOrFail($id);
        return view('admin.ratings.edit', compact('rating'));
    }

    /**
     * Update the specified rating
     */
    public function update(Request $request, $id)
    {
        $rating = Rating::findOrFail($id);

        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $rating->update([
            'rating' => $request->rating,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.ratings.index')
            ->with('success', 'Rating updated successfully!');
    }

    /**
     * Remove the specified rating
     */
    public function destroy($id)
    {
        $rating = Rating::findOrFail($id);
        $rating->delete();

        return redirect()->route('admin.ratings.index')
            ->with('success', 'Rating deleted successfully!');
    }

    /**
     * Show rating details
     */
    public function show($id)
    {
        $rating = Rating::with(['user', 'tea'])->findOrFail($id);
        return view('admin.ratings.show', compact('rating'));
    }

    /**
     * Filter ratings by user
     */
    public function byUser($userId)
    {
        $user = User::findOrFail($userId);
        $ratings = Rating::with('tea')
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return view('admin.ratings.by-user', compact('ratings', 'user'));
    }

    /**
     * Filter ratings by tea
     */
    public function byTea($teaId)
    {
        $tea = Tea::findOrFail($teaId);
        $ratings = Rating::with('user')
            ->where('tea_id', $teaId)
            ->latest()
            ->get();

        return view('admin.ratings.by-tea', compact('ratings', 'tea'));
    }
}

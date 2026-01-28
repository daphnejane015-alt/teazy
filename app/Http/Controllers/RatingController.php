<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Tea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'tea_id' => 'required|exists:teas,id',
            'rating' => 'required|integer|min:1|max:5',
            'description' => 'nullable|string|max:500'
        ]);

        // Check if user already rated this tea
        $rating = Rating::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'tea_id' => $request->tea_id
            ],
            [
                'rating' => $request->rating,
                'description' => $request->description
            ]
        );

        return redirect()->route('rated.tea')
            ->with('success', $rating->wasRecentlyCreated ? 'Rating submitted successfully!' : 'Rating updated successfully!');
    }

    /**
     * Get top rated teas - shared method used by both RatingController and TopTeaController
     * 
     * Sorting Logic:
     * 1. Primary: Average rating (highest first)
     * 2. Secondary: Number of ratings (highest first for tie-breaking)
     * 3. Filter: Only teas with at least 1 rating
     * 
     * Used by:
     * - RatingController::topRated() - dedicated top-rated page
     * - TopTeaController::index() - top-tea page (alongside weather recommendations)
     * 
     * @param int $limit Number of teas to return (default: 5)
     * @return Collection Top rated teas with ratings and user data
     */
    public static function getTopRatedTeas($limit = 5)
    {
        return Tea::with(['ratings' => function($query) {
            $query->with('user')->latest();
        }])
        ->withCount('ratings')
        ->having('ratings_count', '>', 0)
        ->get()
        ->sortByDesc(function($tea) {
            // Sort by average rating first, then by number of ratings for tie-breaking
            return [$tea->averageRating(), $tea->ratings_count];
        })
        ->take($limit);
    }

    public function topRated()
    {
        $topTeas = self::getTopRatedTeas(5);

        return view('user.top-rated', compact('topTeas'));
    }
}

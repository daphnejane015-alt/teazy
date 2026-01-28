<?php

namespace App\Http\Controllers;

use App\Models\Favourite;
use App\Models\Tea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavouriteController extends Controller
{
    /**
     * Display favourites page with all user's favourite teas
     */
    public function show()
    {
        $favourites = Auth::user()->favourites()
            ->with('ratings')
            ->get();

        return view('user.favourites', compact('favourites'));
    }

    /**
     * Get all favourites for the authenticated user (JSON API)
     */
    public function index()
    {
        $favourites = Auth::user()->favourites()
            ->with(['ratings'])
            ->get()
            ->map(function ($tea) {
                return [
                    'id' => $tea->id,
                    'name' => $tea->name,
                    'flavor' => $tea->flavor,
                    'caffeine_level' => $tea->caffeine_level,
                    'health_benefit' => $tea->health_benefit,
                    'image' => $tea->image,
                    'shop_link' => $tea->shop_link,
                    'source_url' => $tea->source_url,
                    'average_rating' => $tea->averageRating(),
                    'total_ratings' => $tea->totalRatings(),
                ];
            });

        return response()->json(['favourites' => $favourites]);
    }

    /**
     * Add a tea to favourites
     */
    public function store(Request $request)
    {
        $request->validate([
            'tea_id' => 'required|exists:teas,id',
        ]);

        $exists = Favourite::where('user_id', Auth::id())
            ->where('tea_id', $request->tea_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Tea is already in favourites'], 422);
        }

        Favourite::create([
            'user_id' => Auth::id(),
            'tea_id' => $request->tea_id,
        ]);

        return response()->json(['message' => 'Added to favourites']);
    }

    /**
     * Remove a tea from favourites
     */
    public function destroy($teaId)
    {
        $deleted = Favourite::where('user_id', Auth::id())
            ->where('tea_id', $teaId)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Removed from favourites']);
        }

        return response()->json(['message' => 'Not found in favourites'], 404);
    }

    /**
     * Check if a tea is in favourites
     */
    public function check($teaId)
    {
        $isFavourite = Favourite::where('user_id', Auth::id())
            ->where('tea_id', $teaId)
            ->exists();

        return response()->json(['is_favourite' => $isFavourite]);
    }
}

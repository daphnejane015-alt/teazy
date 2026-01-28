<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rating;

class RatedTeaController extends Controller
{
    public function index()
    {
        $ratings = auth()->user()
            ->ratings()
            ->with('tea')
            ->latest()
            ->get();

        return view('user.rated-tea', compact('ratings'));
    }

    public function edit($id)
    {
        $rating = auth()->user()
            ->ratings()
            ->with('tea')
            ->findOrFail($id);

        return view('user.edit-rating', compact('rating'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'description' => 'nullable|string|max:500'
        ]);

        $rating = auth()->user()
            ->ratings()
            ->findOrFail($id);

        $rating->update([
            'rating' => $request->rating,
            'description' => $request->description
        ]);

        return redirect()->route('rated.tea')
            ->with('success', 'Rating updated successfully!');
    }

    public function destroy($id)
    {
        $rating = auth()->user()
            ->ratings()
            ->findOrFail($id);

        $rating->delete();

        return redirect()->route('rated.tea')
            ->with('success', 'Rating deleted successfully!');
    }
}

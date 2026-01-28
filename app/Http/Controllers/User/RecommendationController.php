<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RecommendationService;

class RecommendationController extends Controller
{
   
    protected $recommendationService;

    public function __construct(RecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function index()
    {
        $recommendations = $this->recommendationService
            ->recommend(auth()->user());

        // Get user preferences for display
        $preferences = auth()->user()->preference;

        return view('user.recommendations', compact('recommendations', 'preferences'));
    }

}

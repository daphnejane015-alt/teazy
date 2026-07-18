<?php

namespace App\Http\Controllers;

use App\Models\Tea;
use App\Models\TeaAiDescription;
use App\Services\GeminiService;

class TeaAiController extends Controller
{
    public function __construct(private GeminiService $gemini)
    {
    }

    /**
     * Return the AI-generated friendly description for a tea (generated on demand).
     */
    public function description(Tea $tea)
    {
        $user = auth()->user();
        $description = $this->gemini->descriptionFor($tea, user: $user);
        $aiDescription = TeaAiDescription::where('tea_id', $tea->id)
            ->where('user_id', $user->id)
            ->first();

        if (empty($description)) {
            return response()->json([
                'tea_id' => $tea->id,
                'description' => null,
                'available' => false,
                'message' => $this->gemini->isConfigured()
                    ? $this->gemini->availabilityMessage()
                    : 'AI summaries are not configured yet.',
            ], 503);
        }

        return response()->json([
            'tea_id' => $tea->id,
            'description' => $description,
            'generated_at' => optional($aiDescription?->generated_at)->toDateTimeString(),
            'sources' => $aiDescription?->sources ?? [],
            'available' => true,
        ]);
    }

    /**
     * Return AI-generated actionable uses/recipes for a tea (generated on demand).
     */
    public function uses(Tea $tea)
    {
        $user = auth()->user();
        $uses = $this->gemini->usesFor($tea);

        if (empty($uses)) {
            return response()->json([
                'tea_id' => $tea->id,
                'uses' => null,
                'available' => false,
                'message' => $this->gemini->isConfigured()
                    ? $this->gemini->availabilityMessage()
                    : 'AI summaries are not configured yet.',
            ], 503);
        }

        return response()->json([
            'tea_id' => $tea->id,
            'uses' => $uses,
            'available' => true,
        ]);
    }
}

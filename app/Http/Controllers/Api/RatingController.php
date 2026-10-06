<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeRating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Beri atau ubah rating (1-5) untuk sebuah resep.
     */
    public function store(Request $request, Recipe $recipe): JsonResponse
    {
        $data = $request->validate([
            'score' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $user = $request->user();

        if ($recipe->user_id === $user->id) {
            return response()->json([
                'message' => 'Kamu tidak bisa memberi rating pada resepmu sendiri.',
            ], 403);
        }

        RecipeRating::updateOrCreate(
            ['user_id' => $user->id, 'recipe_id' => $recipe->id],
            ['score' => $data['score']],
        );
        $recipe->refreshRating();

        return response()->json([
            'message' => 'Terima kasih atas ratingnya!',
            'recipeId' => $recipe->id,
            'rating' => round($recipe->rating, 1),
            'ratingCount' => $recipe->rating_count,
            'myRating' => (int) $data['score'],
        ]);
    }
}

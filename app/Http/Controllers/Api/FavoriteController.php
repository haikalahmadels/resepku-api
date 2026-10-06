<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    /**
     * Resep favorit, yang terakhir disimpan tampil paling atas.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $recipes = Recipe::forViewer($user)
            ->whereHas('favoritedBy', fn ($q) => $q->whereKey($user->id))
            ->orderByDesc(
                DB::table('favorites')
                    ->select('created_at')
                    ->whereColumn('recipe_id', 'recipes.id')
                    ->where('user_id', $user->id)
                    ->limit(1)
            )
            ->get();

        return RecipeResource::collection($recipes);
    }

    public function store(Request $request, Recipe $recipe): JsonResponse
    {
        $request->user()->favoriteRecipes()->syncWithoutDetaching([$recipe->id]);

        return $this->status($request, $recipe, 'Resep disimpan ke favorit.');
    }

    public function destroy(Request $request, Recipe $recipe): JsonResponse
    {
        $request->user()->favoriteRecipes()->detach($recipe->id);

        return $this->status($request, $recipe, 'Resep dihapus dari favorit.');
    }

    private function status(Request $request, Recipe $recipe, string $message): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'message' => $message,
            'recipeId' => $recipe->id,
            'isFavorite' => $user->favoriteRecipes()->whereKey($recipe->id)->exists(),
            'favoritesCount' => $user->favoriteRecipes()->count(),
        ]);
    }
}

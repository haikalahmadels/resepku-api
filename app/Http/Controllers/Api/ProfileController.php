<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Http\Resources\UserResource;
use App\Models\Recipe;
use App\Support\UploadedImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileController extends Controller
{
    /**
     * Profil + angka statistik di halaman Profil.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => new UserResource($user),
            'stats' => [
                'myRecipes' => $user->recipes()->count(),
                'favorites' => $user->favoriteRecipes()->count(),
                'totalRecipes' => Recipe::count(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'min:3', 'max:100'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
            'avatar_index' => ['sometimes', 'required', 'integer', 'min:0', 'max:5'],
            'photo' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'password' => ['sometimes', 'required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($request->hasFile('photo')) {
            UploadedImage::delete($user->photo);
            $data['photo'] = UploadedImage::store($request->file('photo'), 'profiles');
        }

        $user->update($data);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Resep yang diunggah pengguna yang sedang login.
     */
    public function recipes(Request $request): AnonymousResourceCollection
    {
        $recipes = Recipe::forViewer($request->user())
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return RecipeResource::collection($recipes);
    }
}

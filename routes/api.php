<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\RecipeController;
use Illuminate\Support\Facades\Route;

// Publik. Jika header Authorization dikirim, isFavorite/myRating ikut terisi.
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::get('categories', [CategoryController::class, 'index']);
Route::get('banners', [BannerController::class, 'index']);
Route::get('recipes', [RecipeController::class, 'index']);
Route::get('recipes/{id}', [RecipeController::class, 'show'])->whereNumber('id');

// Butuh token: Authorization: Bearer <token>
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('me', [ProfileController::class, 'show']);
    Route::put('me', [ProfileController::class, 'update']);
    Route::get('me/recipes', [ProfileController::class, 'recipes']);

    Route::post('recipes', [RecipeController::class, 'store']);
    Route::put('recipes/{recipe}', [RecipeController::class, 'update']);
    Route::delete('recipes/{recipe}', [RecipeController::class, 'destroy']);

    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::post('recipes/{recipe}/favorite', [FavoriteController::class, 'store']);
    Route::delete('recipes/{recipe}/favorite', [FavoriteController::class, 'destroy']);

    Route::post('recipes/{recipe}/rating', [RatingController::class, 'store']);
});

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk JSON mengikuti RecipeModel.fromJson() di Flutter.
 *
 * `is_favorite` dan `my_rating` diisi lewat withExists()/withMax() di query.
 *
 * @mixin \App\Models\Recipe
 */
class RecipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => asset($this->image),
            'category' => $this->category->name,
            'categoryId' => $this->category_id,
            'cookingTimeMinutes' => $this->cooking_time_minutes,
            'rating' => round($this->rating, 1),
            'ratingCount' => $this->rating_count,
            'ingredients' => $this->ingredients->map(fn ($i) => [
                'name' => $i->name,
                'amount' => $i->amount,
            ]),
            'steps' => $this->steps->pluck('description'),
            'uploader' => new UploaderResource($this->user),
            'isFavorite' => (bool) ($this->is_favorite ?? false),
            'myRating' => isset($this->my_rating) ? (int) $this->my_rating : null,
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}

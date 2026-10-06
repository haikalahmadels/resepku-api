<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'image',
        'cooking_time_minutes',
        'rating',
        'rating_count',
    ];

    protected function casts(): array
    {
        return [
            'cooking_time_minutes' => 'integer',
            'rating' => 'float',
            'rating_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('position');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('position');
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(RecipeRating::class);
    }

    /**
     * Muat relasi yang dibutuhkan RecipeResource, plus status favorit dan
     * rating milik pengguna yang sedang login (jika ada).
     */
    public function scopeForViewer(Builder $query, ?User $viewer): Builder
    {
        $query->with(['category', 'user', 'ingredients', 'steps']);

        if ($viewer) {
            // withExists harus dipanggil sebelum addSelect agar recipes.* ikut terpilih.
            $query->withExists(['favoritedBy as is_favorite' => fn ($q) => $q->whereKey($viewer->id)])
                ->addSelect(['my_rating' => RecipeRating::select('score')
                    ->whereColumn('recipe_id', 'recipes.id')
                    ->where('user_id', $viewer->id)
                    ->limit(1)]);
        }

        return $query;
    }

    /**
     * Hitung ulang rata-rata rating dari tabel recipe_ratings.
     */
    public function refreshRating(): void
    {
        $this->forceFill([
            'rating' => round((float) $this->ratings()->avg('score'), 2),
            'rating_count' => $this->ratings()->count(),
        ])->save();
    }
}

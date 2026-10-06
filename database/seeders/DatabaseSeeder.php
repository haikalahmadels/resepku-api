<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Recipe;
use App\Models\RecipeRating;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Mengisi database dengan data yang sama seperti JSON dummy di aplikasi
 * Flutter (lib/data/dummy). Semua akun memakai kata sandi "password".
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const CATEGORIES = [
        ['name' => 'Olahan Nasi', 'icon' => 'rice_bowl'],
        ['name' => 'Olahan Ayam', 'icon' => 'lunch_dining'],
        ['name' => 'Cemilan', 'icon' => 'cookie'],
        ['name' => 'Olahan Ikan', 'icon' => 'set_meal'],
        ['name' => 'Minuman', 'icon' => 'local_cafe'],
    ];

    private const USERS = [
        [
            'name' => 'Sari Wulandari',
            'email' => 'sari.wulandari@email.com',
            'phone' => '0812-3456-7890',
            'address' => 'Surabaya, Jawa Timur',
            'birth_date' => '1998-05-14',
            'photo' => 'images/profiles/sari.jpg',
        ],
        ['name' => 'Budi Santoso', 'email' => 'budi@email.com', 'photo' => 'images/profiles/budi.jpg'],
        ['name' => 'Siti Rahma', 'email' => 'siti@email.com', 'photo' => 'images/profiles/siti.jpg'],
        ['name' => 'Rina Wulandari', 'email' => 'rina@email.com', 'photo' => 'images/profiles/rina.jpg'],
        ['name' => 'Agus Prasetyo', 'email' => 'agus@email.com', 'photo' => 'images/profiles/agus.jpg'],
        ['name' => 'Dewi Lestari', 'email' => 'dewi@email.com', 'photo' => 'images/profiles/dewi.jpg'],
    ];

    // Sesuai RecipeStore._initialFavorites dan banner di HomeScreen.
    private const SARI_FAVORITES = ['recipe_001', 'recipe_005', 'recipe_002'];

    private const BANNERS = [
        ['recipe_001', 'Favorit Minggu Ini'],
        ['recipe_002', 'Sedang Trending'],
        ['recipe_004', 'Segar & Sehat'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $i => $category) {
            Category::create($category + ['slug' => Str::slug($category['name']), 'sort_order' => $i]);
        }

        $users = collect(self::USERS)->mapWithKeys(fn ($u) => [
            $u['name'] => User::factory()->create($u + ['password' => 'password']),
        ]);

        // Map id dummy ("recipe_001") ke id database.
        $recipeIds = [];
        $json = json_decode(file_get_contents(__DIR__.'/data/recipes.json'), true);

        foreach ($json as $item) {
            $uploader = $users[$item['uploader']['name']];

            $recipe = Recipe::create([
                'user_id' => $uploader->id,
                'category_id' => Category::where('name', $item['category'])->value('id'),
                'name' => $item['name'],
                'image' => str_replace('assets/', '', $item['image']),
                'cooking_time_minutes' => $item['cookingTimeMinutes'],
            ]);

            foreach ($item['ingredients'] as $pos => $ingredient) {
                $recipe->ingredients()->create($ingredient + ['position' => $pos]);
            }
            foreach ($item['steps'] as $pos => $step) {
                $recipe->steps()->create(['description' => $step, 'position' => $pos]);
            }

            $raters = $users->except($uploader->name)->values();
            $this->seedRatings($recipe, $raters, $item['rating']);

            $recipeIds[$item['id']] = $recipe->id;
        }

        $users['Sari Wulandari']->favoriteRecipes()->attach(
            array_map(fn ($id) => $recipeIds[$id], self::SARI_FAVORITES)
        );

        foreach (self::BANNERS as $i => [$dummyId, $caption]) {
            Banner::create([
                'recipe_id' => $recipeIds[$dummyId],
                'caption' => $caption,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Buat rating asli dari pengguna lain dengan rata-rata sedekat mungkin
     * ke nilai rating di JSON dummy.
     */
    private function seedRatings(Recipe $recipe, $raters, float $target): void
    {
        $best = null;
        for ($n = $raters->count(); $n >= 2; $n--) {
            $total = (int) round($target * $n);
            $error = abs($total / $n - $target);
            if ($best === null || $error < $best[2] - 1e-9) {
                $best = [$n, $total, $error];
            }
        }
        [$n, $total] = $best;

        $base = intdiv($total, $n);
        $extra = $total % $n;

        foreach ($raters->take($n) as $i => $rater) {
            RecipeRating::create([
                'user_id' => $rater->id,
                'recipe_id' => $recipe->id,
                'score' => min(5, $base + ($i < $extra ? 1 : 0)),
            ]);
        }

        $recipe->refreshRating();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecipeResource;
use App\Models\Category;
use App\Models\Recipe;
use App\Support\UploadedImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecipeController extends Controller
{
    /**
     * Filter yang didukung (semua opsional):
     * - category  : id, slug, atau nama kategori ("Semua" = tanpa filter)
     * - search    : cari di nama resep dan nama bahan
     * - max_time  : waktu masak maksimal (menit), mis. 20 untuk "Resep Cepat"
     * - user_id   : resep milik pengguna tertentu
     * - sort      : latest (default) | rating | time | popular
     * - per_page  : aktifkan paginasi
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'max_time' => ['nullable', 'integer', 'min:1'],
            'user_id' => ['nullable', 'integer'],
            'sort' => ['nullable', Rule::in(['latest', 'rating', 'time', 'popular'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Recipe::forViewer($request->user('sanctum'));

        $category = $request->query('category');
        if ($category && $category !== 'Semua') {
            $query->whereHas('category', fn ($q) => ctype_digit((string) $category)
                ? $q->whereKey((int) $category)
                : $q->where('slug', $category)->orWhere('name', $category));
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('ingredients', fn ($i) => $i->where('name', 'like', "%{$search}%")));
        }

        if ($request->filled('max_time')) {
            $query->where('cooking_time_minutes', '<=', $request->integer('max_time'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        match ($request->query('sort', 'latest')) {
            'rating' => $query->orderByDesc('rating'),
            'time' => $query->orderBy('cooking_time_minutes'),
            'popular' => $query->withCount('favoritedBy')->orderByDesc('favorited_by_count'),
            default => $query->latest()->orderByDesc('id'),
        };

        $recipes = $request->filled('per_page')
            ? $query->paginate($request->integer('per_page'))->withQueryString()
            : $query->get();

        return RecipeResource::collection($recipes);
    }

    public function show(Request $request, int $id): RecipeResource
    {
        return new RecipeResource(
            Recipe::forViewer($request->user('sanctum'))->findOrFail($id)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request, partial: false);

        $recipe = DB::transaction(function () use ($request, $data) {
            $recipe = $request->user()->recipes()->create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'image' => $this->resolveImage($request),
                'cooking_time_minutes' => $data['cooking_time_minutes'],
            ]);
            $this->syncDetails($recipe, $data);

            return $recipe;
        });

        return response()->json([
            'message' => 'Resep berhasil disimpan!',
            'data' => new RecipeResource(Recipe::forViewer($request->user())->find($recipe->id)),
        ], 201);
    }

    /**
     * Untuk unggah foto via multipart, kirim sebagai POST dengan field _method=PUT.
     */
    public function update(Request $request, Recipe $recipe): JsonResponse
    {
        $this->authorizeOwner($request, $recipe);
        $data = $this->validated($request, partial: true);

        DB::transaction(function () use ($request, $recipe, $data) {
            $attributes = collect($data)
                ->only(['category_id', 'name', 'cooking_time_minutes'])
                ->all();

            if ($request->hasFile('image') || $request->filled('image_path')) {
                UploadedImage::delete($recipe->image);
                $attributes['image'] = $this->resolveImage($request);
            }

            $recipe->update($attributes);
            $this->syncDetails($recipe, $data);
        });

        return response()->json([
            'message' => 'Resep berhasil diperbarui.',
            'data' => new RecipeResource(Recipe::forViewer($request->user())->find($recipe->id)),
        ]);
    }

    public function destroy(Request $request, Recipe $recipe): JsonResponse
    {
        $this->authorizeOwner($request, $recipe);

        UploadedImage::delete($recipe->image);
        $recipe->delete();

        return response()->json(['message' => 'Resep berhasil dihapus.']);
    }

    private function validated(Request $request, bool $partial): array
    {
        $required = $partial ? 'sometimes' : 'required';

        $data = $request->validate([
            'name' => [$required, 'string', 'min:3', 'max:100'],
            // Kirim salah satu: category_id atau category (nama/slug).
            'category_id' => [$partial ? 'sometimes' : 'required_without:category', 'integer', 'exists:categories,id'],
            'category' => ['sometimes', 'string'],
            'cooking_time_minutes' => [$required, 'integer', 'min:5', 'max:180'],
            // Kirim salah satu: file foto (multipart) atau image_path gambar bawaan.
            'image' => [$partial ? 'sometimes' : 'required_without:image_path', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'image_path' => ['sometimes', 'string', function ($attribute, $value, $fail) {
                if (! str_starts_with($value, 'images/recipes/') || ! is_file(public_path($value))) {
                    $fail('Gambar bawaan tidak ditemukan.');
                }
            }],
            'ingredients' => [$required, 'array', 'min:1'],
            'ingredients.*.name' => ['required', 'string', 'max:100'],
            'ingredients.*.amount' => ['required', 'string', 'max:50'],
            'steps' => [$required, 'array', 'min:1'],
            'steps.*' => ['required', 'string', 'max:1000'],
        ]);

        if (! isset($data['category_id']) && isset($data['category'])) {
            $category = Category::where('name', $data['category'])
                ->orWhere('slug', $data['category'])
                ->first();

            if (! $category) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'category' => ['Kategori tidak ditemukan.'],
                ]);
            }
            $data['category_id'] = $category->id;
        }

        return $data;
    }

    private function resolveImage(Request $request): string
    {
        return $request->hasFile('image')
            ? UploadedImage::store($request->file('image'), 'recipes')
            : $request->input('image_path');
    }

    /**
     * Bahan dan langkah selalu diganti seluruhnya agar urutannya sesuai form.
     */
    private function syncDetails(Recipe $recipe, array $data): void
    {
        if (isset($data['ingredients'])) {
            $recipe->ingredients()->delete();
            $recipe->ingredients()->createMany(
                collect($data['ingredients'])->values()->map(fn ($item, $i) => [
                    'name' => trim($item['name']),
                    'amount' => trim($item['amount']),
                    'position' => $i,
                ])->all()
            );
        }

        if (isset($data['steps'])) {
            $recipe->steps()->delete();
            $recipe->steps()->createMany(
                collect($data['steps'])->values()->map(fn ($text, $i) => [
                    'description' => trim($text),
                    'position' => $i,
                ])->all()
            );
        }
    }

    private function authorizeOwner(Request $request, Recipe $recipe): void
    {
        abort_unless(
            $recipe->user_id === $request->user()->id,
            403,
            'Kamu hanya bisa mengubah resep milikmu sendiri.'
        );
    }
}

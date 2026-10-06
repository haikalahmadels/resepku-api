<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * Kategori "Semua" tidak disimpan di database; tambahkan di sisi aplikasi.
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::withCount('recipes')->orderBy('sort_order')->get()
        );
    }
}

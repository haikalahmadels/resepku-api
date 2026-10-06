<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BannerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $viewer = $request->user('sanctum');

        $banners = Banner::where('is_active', true)
            ->with(['recipe' => fn ($q) => $q->forViewer($viewer)])
            ->orderBy('sort_order')
            ->get();

        return BannerResource::collection($banners);
    }
}

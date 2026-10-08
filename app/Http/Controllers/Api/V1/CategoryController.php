<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Repositories\CategoryRepository;

class CategoryController extends Controller
{
    public function index(CategoryRepository $categories)
    {
        return CategoryResource::collection($categories->tree());
    }

    public function show(string $slug, CategoryRepository $categories)
    {
        abort_unless($category = $categories->findActiveBySlug($slug), 404);

        return new CategoryResource($category);
    }
}

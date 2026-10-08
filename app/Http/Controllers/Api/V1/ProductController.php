<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, ProductRepository $products)
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:190'],
            'sort' => ['nullable', 'string', 'max:30'],
            'mode' => ['nullable', 'in:new,bestsellers,sale'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $category = isset($data['category']) ? Category::active()->where('slug', $data['category'])->firstOrFail() : null;

        return ProductResource::collection($products->paginate([
            'q' => $data['q'] ?? null,
            'category' => $category,
            'sort' => $data['sort'] ?? null,
            'mode' => $data['mode'] ?? null,
            'min_price' => $data['min_price'] ?? null,
            'max_price' => $data['max_price'] ?? null,
            'in_stock' => $data['in_stock'] ?? false,
        ], (int) ($data['per_page'] ?? 12)));
    }

    public function show(string $slug, ProductRepository $products)
    {
        abort_unless($product = $products->findActiveBySlug($slug), 404);

        return new ProductResource($product);
    }
}

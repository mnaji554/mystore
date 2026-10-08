<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('products.index'), 'priority' => '0.9'],
            ['loc' => route('products.new'), 'priority' => '0.6'],
            ['loc' => route('products.bestsellers'), 'priority' => '0.6'],
            ['loc' => route('products.sale'), 'priority' => '0.6'],
            ['loc' => route('pages.about'), 'priority' => '0.3'],
            ['loc' => route('contact'), 'priority' => '0.3'],
            ['loc' => route('pages.privacy'), 'priority' => '0.2'],
            ['loc' => route('pages.terms'), 'priority' => '0.2'],
            ['loc' => route('pages.returns'), 'priority' => '0.2'],
        ]);

        Category::query()->active()->get(['slug', 'updated_at'])->each(fn ($c) => $urls->push([
            'loc' => route('categories.show', $c->slug), 'priority' => '0.7', 'lastmod' => $c->updated_at?->toAtomString(),
        ]));

        Product::query()->active()->get(['slug', 'updated_at'])->each(fn ($p) => $urls->push([
            'loc' => route('products.show', $p->slug), 'priority' => '0.8', 'lastmod' => $p->updated_at?->toAtomString(),
        ]));

        return response()->view('seo.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /api',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /account',
            'Disallow: /search',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain']);
    }
}

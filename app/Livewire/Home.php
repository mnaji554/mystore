<?php

namespace App\Livewire;

use App\Livewire\Concerns\InteractsWithStore;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use Livewire\Component;

class Home extends Component
{
    use InteractsWithStore;

    public function render(ProductRepository $products, CategoryRepository $categories)
    {
        return view('livewire.home', [
            'categories' => $categories->tree(),
            'featured' => $products->featured(8),
            'newest' => $products->newest(8),
            'bestsellers' => $products->bestsellers(8),
            'onSale' => $products->onSale(8),
        ])->layout('components.layouts.app', [
            'description' => setting('meta_description'),
            'canonical' => route('home'),
            'jsonLd' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => setting('store_name'),
                'url' => route('home'),
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => route('search').'?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ]);
    }
}

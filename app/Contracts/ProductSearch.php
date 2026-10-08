<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Text search abstraction. The default implementation uses the database;
 * bind another implementation (Meilisearch, Algolia, ...) in AppServiceProvider.
 */
interface ProductSearch
{
    public function apply(Builder $query, string $term): Builder;
}

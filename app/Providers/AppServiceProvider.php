<?php

namespace App\Providers;

use App\Contracts\ProductSearch;
use App\Models\User;
use App\Services\DatabaseProductSearch;
use App\Support\Permissions;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Swap this binding to move search to Meilisearch / Algolia later.
        $this->app->bind(ProductSearch::class, DatabaseProductSearch::class);
    }

    public function boot(): void
    {
        foreach (Permissions::keys() as $permission) {
            Gate::define($permission, fn (User $user) => $user->hasPermission($permission));
        }

        Model::preventLazyLoading(! $this->app->isProduction());
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
            Log::warning('Lazy loading detected: '.$model::class."::{$relation}");
        });

        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(strtolower((string) $r->input('email')).'|'.$r->ip()));
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('api-auth', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('checkout', fn (Request $r) => Limit::perMinute(10)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('forms', fn (Request $r) => Limit::perMinute(6)->by($r->ip()));
    }
}

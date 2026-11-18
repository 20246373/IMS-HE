<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)
            ->by('password-reset|'.$request->ip()));

        // Storefront chrome (guest/customer nav strip and footer) lists the active categories on every page.
        // Pages rendered without the database (e.g. bare layout tests) get an empty list instead of an error.
        View::composer('layouts.app', function ($view): void {
            if (auth('web')->check()) {
                return;
            }

            try {
                $categories = Category::active()
                    ->orderBy('category_name')
                    ->get(['category_id', 'category_name']);
            } catch (QueryException) {
                $categories = collect();
            }

            $view->with('sfCategories', $categories);
        });
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @peso($amount) prints money as ₱1,234.50 (HTML-escaped).
        Blade::directive('peso', fn (string $expression) => "<?php echo e(\\App\\Support\\Money::peso({$expression})); ?>");
    }
}

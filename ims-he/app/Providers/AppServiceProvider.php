<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Support/helpers.php');
        config(['app.timezone' => 'Asia/Manila']);
        date_default_timezone_set('Asia/Manila');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // @peso($amount) prints money as ₱1,234.50 (HTML-escaped).
        Blade::directive('peso', fn (string $expression) => "<?php echo e(\\App\\Support\\Money::peso({$expression})); ?>");

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)
            ->by('password-reset|'.$request->ip()));
    }
}

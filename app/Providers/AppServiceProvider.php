<?php

namespace App\Providers;

use App\Models\CartItem;
use App\Models\Category;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Plain-CSS pager (the framework default needs Tailwind, which this app does not load).
        Paginator::defaultView('pagination.custom');

        // Data every page needs for the storefront header: category strip + cart badge.
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $isStaff = $user && $user->isStaff();

            $cartCount = 0;
            if ($user && $user->isCustomer() && $user->customer) {
                $cartCount = (int) CartItem::whereHas('cart', fn ($q) => $q->where('customer_id', $user->customer->id))->sum('quantity');
            }

            $view->with([
                'navCategories' => $isStaff ? collect() : Category::orderBy('name')->get(),
                'cartCount'     => $cartCount,
            ]);
        });
    }
}

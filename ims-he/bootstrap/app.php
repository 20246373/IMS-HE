<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Send each guard to its own login / home page instead of the staff defaults.
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->routeIs('customer.cart.*')) {
                session()->flash('status', 'Please log in or sign up to use your cart.');
            }

            // Skip the "log in as" chooser: customer pages open the customer form, staff pages the employee form.
            return route('login', ['as' => $request->routeIs('customer.*') ? 'customer' : 'employee']);
        });
        $middleware->redirectUsersTo(fn (Request $request) => auth('customer')->check()
            ? route('home') : route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

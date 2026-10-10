<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /** Usage: ->middleware('role:president,treasurer') */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole(...$roles), 403, 'You do not have access to this module.');

        return $next($request);
    }
}

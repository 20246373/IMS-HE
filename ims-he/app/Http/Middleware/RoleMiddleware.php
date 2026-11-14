<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Usage: ->middleware('role:President,Treasurer')
     * Also ends the session if an admin locked the account while the user was logged in.
     */
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['login' => 'Your account is locked. Contact the administrator.']);
        }

        // Self-registered employees have no role yet: send them to the waiting page instead of a 403.
        if ($user->isPending()) {
            return redirect()->route('staff.pending');
        }

        abort_unless(in_array($user->role, $roles, true), 403, 'You do not have access to this module.');

        return $next($request);
    }
}

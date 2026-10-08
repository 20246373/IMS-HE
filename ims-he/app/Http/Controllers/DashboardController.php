<?php

namespace App\Http\Controllers;

use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $isPresident = auth()->user()->hasRole(User::PRESIDENT);

        return view('dashboard', [
            'accounts' => $isPresident ? User::count() : null,
            'locked'   => $isPresident ? User::where('status', User::STATUS_LOCKED)->count() : null,
        ]);
    }
}

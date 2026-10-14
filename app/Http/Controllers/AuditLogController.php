<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->user_id, fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->table_name, fn ($q, $t) => $q->where('table_name', $t))
            ->orderByDesc('log_id')
            ->paginate(25)
            ->withQueryString();

        return view('audit.index', [
            'logs'   => $logs,
            'users'  => User::orderBy('username')->get(),
            'tables' => AuditLog::select('table_name')->distinct()->orderBy('table_name')->pluck('table_name'),
        ]);
    }
}

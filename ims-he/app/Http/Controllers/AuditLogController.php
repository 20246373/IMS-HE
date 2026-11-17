<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,user_id'],
            'action' => ['nullable', 'string', 'max:100'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $logs = AuditLog::with('user')
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', $action))
            ->when($filters['start_date'] ?? null, fn ($q, $date) => $q->whereDate('logged_at', '>=', $date))
            ->when($filters['end_date'] ?? null, fn ($q, $date) => $q->whereDate('logged_at', '<=', $date))
            ->orderByDesc('log_id')
            ->paginate(25)
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'users' => User::orderBy('username')->get(),
            'actions' => AuditLog::select('action')->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}

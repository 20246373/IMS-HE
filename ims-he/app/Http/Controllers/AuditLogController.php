<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\CsvExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = $this->filtered($request)
            ->with('user.employee', 'customer')
            ->orderByDesc('log_id')
            ->paginate(25)
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'users' => User::with('employee')->orderBy('email')->get(),
            // "Completed|invoices" => "Completed online order", sorted by the words shown.
            'actions' => AuditLog::select('action', 'table_name')->distinct()->get()
                ->mapWithKeys(fn (AuditLog $log) => [$log->action.'|'.$log->table_name => AuditLog::label($log->action, $log->table_name)])
                ->sort(),
        ]);
    }

    /** Administrator export: the audit log with the same filters as the page, in the Export Center's CSV format. */
    public function export(Request $request): StreamedResponse
    {
        // Same columns as the old Export Center dataset: the user's email, or "System" for automatic entries.
        $rows = $this->filtered($request)->toBase()
            ->leftJoin('users', 'users.user_id', '=', 'audit_logs.user_id')
            ->leftJoin('customers', 'customers.customer_id', '=', 'audit_logs.customer_id')
            ->select(['audit_logs.log_id', 'audit_logs.logged_at', 'users.email', 'customers.email as customer_email', 'audit_logs.action', 'audit_logs.table_name', 'audit_logs.record_id'])
            ->orderBy('audit_logs.logged_at')
            ->orderBy('audit_logs.log_id')
            ->cursor()
            ->map(fn ($row) => [$row->log_id, $row->logged_at, $row->email ?: ($row->customer_email ?: 'System'), $row->action, $row->table_name, $row->record_id]);

        AuditLog::record('Exported', 'audit_logs');

        return CsvExporter::download(
            'audit_logs-'.now()->format('Ymd-His').'.csv',
            ['log_id', 'logged_at', 'user', 'action', 'table_name', 'record_id'],
            $rows,
        );
    }

    /** @return Builder<AuditLog> the log entries matching the Name, Action and date filters */
    private function filtered(Request $request): Builder
    {
        $filters = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,user_id'],
            'action' => ['nullable', 'string', 'max:150'],                   // "Action|table", e.g. "Completed|invoices"
            'about' => ['nullable', Rule::in(AuditLog::ACTOR_TYPES)],         // who did it: employees or customers
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        return AuditLog::query()
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('audit_logs.user_id', $id))
            ->when($filters['action'] ?? null, function ($q, $value) {
                [$action, $table] = array_pad(explode('|', $value, 2), 2, null);
                $q->where('audit_logs.action', $action)->when($table, fn ($q) => $q->where('audit_logs.table_name', $table));
            })
            ->when($filters['about'] ?? null, fn ($q, $about) => $q->whereNotNull($about === 'customers' ? 'audit_logs.customer_id' : 'audit_logs.user_id'))
            ->when($filters['start_date'] ?? null, fn ($q, $date) => $q->whereDate('audit_logs.logged_at', '>=', $date))
            ->when($filters['end_date'] ?? null, fn ($q, $date) => $q->whereDate('audit_logs.logged_at', '<=', $date));
    }
}

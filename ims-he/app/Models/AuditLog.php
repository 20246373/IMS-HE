<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'log_id';
    public $timestamps = false;              // logged_at is filled by the database default
    protected $guarded = [];

    protected function casts(): array
    {
        return ['logged_at' => 'datetime'];
    }

    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }

    /**
     * One-line audit helper for every module:
     *   AuditLog::record('Created', 'products', $product->product_id);
     * Pass $userId when nobody is logged in yet (e.g. failed login / lockout).
     */
    public static function record(string $action, string $table, ?int $recordId = null, ?int $userId = null): self
    {
        return static::create([
            'user_id'    => $userId ?? Auth::id(),
            'action'     => $action,
            'table_name' => $table,
            'record_id'  => $recordId,
        ]);
    }
}

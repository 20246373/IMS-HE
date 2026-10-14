<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const PRESIDENT = 'President';
    public const TREASURER = 'Treasurer';
    public const INVENTORY_CLERK = 'Inventory Clerk';
    public const STORE_SUPERVISOR = 'Store Supervisor';
    public const EMPLOYEE = 'Employee';

    public const ROLES = [self::PRESIDENT, self::TREASURER, self::INVENTORY_CLERK, self::STORE_SUPERVISOR, self::EMPLOYEE];

    public const STATUS_ACTIVE = 'Active';
    public const STATUS_LOCKED = 'Locked';
    public const MAX_LOGIN_ATTEMPTS = 3;

    const UPDATED_AT = null;                 // SDD 7.2.1 only has created_at

    protected $table = 'users';
    protected $primaryKey = 'user_id';
    protected $guarded = [];
    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'last_login' => 'datetime'];
    }

    // The SDD schema has no remember_token column.
    public function getRememberTokenName() { return ''; }

    public function employee() { return $this->belongsTo(Employee::class, 'employee_id', 'employee_id'); }
    public function auditLogs() { return $this->hasMany(AuditLog::class, 'user_id', 'user_id'); }

    public function hasRole(string ...$roles): bool { return in_array($this->role, $roles, true); }
    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }
    public function isLocked(): bool { return $this->status === self::STATUS_LOCKED; }
}

<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public const ADMINISTRATOR = 'Administrator';

    public const PRESIDENT = 'President';

    public const TREASURER = 'Treasurer';

    public const INVENTORY_CLERK = 'Inventory Clerk';

    public const STORE_SUPERVISOR = 'Store Supervisor';

    public const EMPLOYEE = 'Employee';

    public const PENDING = 'Pending';        // self-registered, waiting for the Administrator to assign a role

    public const ROLES = [self::ADMINISTRATOR, self::PRESIDENT, self::TREASURER, self::INVENTORY_CLERK, self::STORE_SUPERVISOR, self::EMPLOYEE];

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
    public function getRememberTokenName()
    {
        return '';
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id', 'user_id');
    }

    /** @var list<string>|null this account's permissions, loaded once per request */
    private ?array $permissionCache = null;

    /** Roles the Administrator can assign: every role in the Roles list except the Administrator. */
    public static function roleChoices(): array
    {
        return Role::where('role_name', '!=', self::ADMINISTRATOR)->orderBy('role_name')->pluck('role_name')->all();
    }

    /** True when this account's role is allowed any of the given permissions (keys from App\Support\Permissions). */
    public function hasPermission(string ...$permissions): bool
    {
        $this->permissionCache ??= Role::permissionsFor((string) $this->role);

        return array_intersect($permissions, $this->permissionCache) !== [];
    }

    /** Name shown in lists and menus: the email (emails are unique per account). */
    public function label(): string
    {
        return (string) $this->email;
    }

    /** The person's name from the linked employee record, or the email when there is none (e.g. the Administrator). */
    public function displayName(): string
    {
        return $this->employee?->full_name ?? (string) $this->email;
    }

    public function isPending(): bool
    {
        return $this->role === self::PENDING;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_LOCKED;
    }
}

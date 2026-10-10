<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    public const PRESIDENT = 'president';
    public const TREASURER = 'treasurer';
    public const INVENTORY_CLERK = 'inventory_clerk';
    public const STORE_SUPERVISOR = 'store_supervisor';
    public const EMPLOYEE = 'employee';
    public const CUSTOMER = 'customer';

    public const STAFF_ROLES = [self::PRESIDENT, self::TREASURER, self::INVENTORY_CLERK, self::STORE_SUPERVISOR, self::EMPLOYEE];
    public const MAX_LOGIN_ATTEMPTS = 3;

    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'is_locked' => 'boolean', 'last_login_at' => 'datetime'];
    }

    public function customer() { return $this->hasOne(Customer::class); }

    public function isCustomer(): bool { return $this->role === self::CUSTOMER; }

    public function hasRole(string ...$roles): bool { return in_array($this->role, $roles, true); }
    public function isStaff(): bool { return in_array($this->role, self::STAFF_ROLES, true); }
}

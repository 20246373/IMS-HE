<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * A job role. Its permissions (App\Support\Permissions keys) decide what its users can open.
 * Users point at a role by name (users.role), so renaming a role also renames it on every account.
 */
class Role extends Model
{
    const UPDATED_AT = null;

    protected $primaryKey = 'role_id';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return DB::table('role_permissions')->where('role_id', $this->role_id)->orderBy('permission')->pluck('permission')->all();
    }

    /** @param list<string> $permissions */
    public function syncPermissions(array $permissions): void
    {
        DB::table('role_permissions')->where('role_id', $this->role_id)->delete();
        DB::table('role_permissions')->insert(array_map(
            fn (string $permission) => ['role_id' => $this->role_id, 'permission' => $permission],
            array_values(array_unique($permissions)),
        ));
    }

    /** @return list<string> the permissions of the role with this name (none if it does not exist) */
    public static function permissionsFor(string $roleName): array
    {
        return DB::table('role_permissions')
            ->join('roles', 'roles.role_id', '=', 'role_permissions.role_id')
            ->where('roles.role_name', $roleName)
            ->pluck('role_permissions.permission')
            ->all();
    }

    public function usersCount(): int
    {
        return User::where('role', $this->role_name)->count();
    }
}

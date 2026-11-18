<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Administrator: create, edit and delete roles and choose what each role can open.
 * The Administrator role itself is a fixed system role; "Pending" is reserved for new sign-ups.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::orderByDesc('is_system')->orderBy('role_name')->get();
        $counts = User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');
        $permissions = DB::table('role_permissions')->get()->groupBy('role_id')->map(fn ($rows) => $rows->pluck('permission')->all());

        return view('roles.index', compact('roles', 'counts', 'permissions') + ['groups' => Permissions::GROUPS]);
    }

    public function create(): View
    {
        return view('roles.form', ['role' => new Role, 'granted' => [], 'groups' => Permissions::GROUPS]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $role = DB::transaction(function () use ($data) {
            $role = Role::create(['role_name' => $data['role_name'], 'description' => $data['description'] ?? null]);
            $role->syncPermissions($data['permissions'] ?? []);
            AuditLog::record('Created', 'roles', $role->role_id);

            return $role;
        });

        return redirect()->route('roles.index')->with('status', "Role \"{$role->role_name}\" created.");
    }

    public function edit(Role $role): View|RedirectResponse
    {
        if ($role->is_system) {
            return redirect()->route('roles.index')->with('warning', 'The Administrator role is fixed and cannot be edited.');
        }

        return view('roles.form', ['role' => $role, 'granted' => $role->permissions(), 'groups' => Permissions::GROUPS]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403, 'The Administrator role is fixed.');

        $data = $this->validated($request, $role);

        DB::transaction(function () use ($role, $data) {
            $oldName = $role->role_name;
            $role->update(['role_name' => $data['role_name'], 'description' => $data['description'] ?? null]);
            $role->syncPermissions($data['permissions'] ?? []);

            // Accounts point at the role by name, so a rename carries over to them.
            if ($oldName !== $role->role_name) {
                User::where('role', $oldName)->update(['role' => $role->role_name]);
            }

            AuditLog::record('Updated', 'roles', $role->role_id);
        });

        return redirect()->route('roles.index')->with('status', "Role \"{$role->role_name}\" updated.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403, 'The Administrator role is fixed.');

        $accounts = $role->usersCount();
        if ($accounts > 0) {
            return back()->with('warning', "\"{$role->role_name}\" still has {$accounts} ".($accounts === 1 ? 'account' : 'accounts').'. Give them another role first, then delete it.');
        }

        DB::transaction(function () use ($role) {
            AuditLog::record('Deleted', 'roles', $role->role_id);
            $role->delete();
        });

        return redirect()->route('roles.index')->with('status', "Role \"{$role->role_name}\" deleted.");
    }

    /** @return array{role_name: string, description: ?string, permissions?: list<string>} */
    private function validated(Request $request, ?Role $role = null): array
    {
        $request->merge(['role_name' => trim(preg_replace('/\s+/', ' ', (string) $request->input('role_name')))]);

        return $request->validate([
            'role_name' => [
                'required', 'string', 'max:50', 'regex:/^[\pL ]+$/u',
                function (string $attribute, string $value, \Closure $fail) use ($role): void {
                    $name = mb_strtolower($value);
                    if (in_array($name, [mb_strtolower(User::PENDING), mb_strtolower(User::ADMINISTRATOR)], true)) {
                        $fail('This name is reserved by the system.');
                    } elseif (Role::whereRaw('lower(role_name) = ?', [$name])->when($role, fn ($q) => $q->where('role_id', '!=', $role->role_id))->exists()) {
                        $fail('A role with this name already exists.');
                    }
                },
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(Permissions::keys())],
        ], [
            'role_name.regex' => 'Use letters and spaces only.',
        ]);
    }
}

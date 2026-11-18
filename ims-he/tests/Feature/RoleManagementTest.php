<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role, string $email = 'staff@example.com'): User
    {
        return User::create(['email' => $email, 'password' => 'secret123', 'role' => $role, 'status' => User::STATUS_ACTIVE]);
    }

    private function admin(): User
    {
        return $this->staff(User::ADMINISTRATOR, 'admin@example.com');
    }

    public function test_built_in_roles_keep_their_old_access(): void
    {
        $this->assertTrue($this->staff(User::INVENTORY_CLERK, 'clerk@example.com')->hasPermission('inventory.manage'));
        $this->assertFalse($this->staff(User::EMPLOYEE, 'emp@example.com')->hasPermission('inventory.manage'));
        $this->assertTrue($this->staff(User::TREASURER, 'treasurer@example.com')->hasPermission('payroll.run'));
        $this->assertFalse($this->staff(User::PRESIDENT, 'president@example.com')->hasPermission('payroll.run'));
    }

    public function test_administrator_sees_the_roles_page_and_sidebar_link(): void
    {
        $this->actingAs($this->admin())->get(route('roles.index'))->assertOk()
            ->assertSee('<h1>Roles</h1>', false)
            ->assertSee('Store Supervisor')
            ->assertSee('System role')
            ->assertSee('href="'.route('roles.index').'"', false);
    }

    public function test_each_roles_access_can_be_opened_as_a_list(): void
    {
        $employee = Role::where('role_name', User::EMPLOYEE)->firstOrFail();

        $this->actingAs($this->admin())->get(route('roles.index'))->assertOk()
            ->assertSee('id="access-'.$employee->role_id.'"', false)
            ->assertSeeInOrder(['id="access-'.$employee->role_id.'"', 'This role has access to the following:', 'Sales (POS)', 'Online orders', 'Clock in and out (own attendance)', '</dialog>'], false);
    }

    public function test_only_the_administrator_can_manage_roles(): void
    {
        $this->actingAs($this->staff(User::PRESIDENT))->get(route('roles.index'))->assertForbidden();
        $this->actingAs($this->staff(User::PRESIDENT, 'p2@example.com'))->post(route('roles.store'), ['role_name' => 'Cashier'])->assertForbidden();
        $this->assertDatabaseMissing('roles', ['role_name' => 'Cashier']);
    }

    public function test_a_new_role_gets_exactly_the_access_that_was_ticked(): void
    {
        $this->actingAs($this->admin())->post(route('roles.store'), [
            'role_name' => '  Cashier ',
            'description' => 'Front counter only',
            'permissions' => ['sales.pos', 'attendance.own'],
        ])->assertRedirect(route('roles.index'));

        $role = Role::where('role_name', 'Cashier')->firstOrFail();
        $this->assertEqualsCanonicalizing(['sales.pos', 'attendance.own'], $role->permissions());
        $this->assertDatabaseHas('audit_logs', ['action' => 'Created', 'table_name' => 'roles', 'record_id' => $role->role_id]);
        $this->assertContains('Cashier', User::roleChoices());

        $cashier = $this->staff('Cashier', 'cashier@example.com');
        $this->actingAs($cashier)->get(route('sales.create'))->assertOk();
        $this->actingAs($cashier)->get(route('products.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('dashboard'))->assertOk()
            ->assertSee(route('sales.create'), false)
            ->assertDontSee(route('products.index'), false);
    }

    public function test_editing_a_role_changes_access_and_renaming_moves_its_accounts(): void
    {
        $clerk = $this->staff(User::INVENTORY_CLERK, 'clerk@example.com');
        $role = Role::where('role_name', User::INVENTORY_CLERK)->firstOrFail();

        $this->actingAs($this->admin())->put(route('roles.update', $role), [
            'role_name' => 'Stock Keeper',
            'permissions' => ['reports.inventory'],
        ])->assertRedirect(route('roles.index'));

        $this->assertSame('Stock Keeper', $clerk->fresh()->role);
        $this->actingAs($clerk->fresh())->get(route('reports.inventory'))->assertOk();
        $this->actingAs($clerk->fresh())->get(route('products.index'))->assertForbidden();
    }

    public function test_a_role_with_accounts_cannot_be_deleted_but_an_empty_one_can(): void
    {
        $this->staff(User::EMPLOYEE, 'emp@example.com');
        $employee = Role::where('role_name', User::EMPLOYEE)->firstOrFail();
        $empty = Role::create(['role_name' => 'Seasonal Helper']);
        $admin = $this->admin();

        $this->actingAs($admin)->from(route('roles.index'))->delete(route('roles.destroy', $employee))
            ->assertRedirect(route('roles.index'))->assertSessionHas('warning');
        $this->assertModelExists($employee);

        $this->actingAs($admin)->delete(route('roles.destroy', $empty))->assertRedirect(route('roles.index'));
        $this->assertModelMissing($empty);
    }

    public function test_the_administrator_role_is_fixed_and_names_are_validated(): void
    {
        $admin = $this->admin();
        $system = Role::where('role_name', User::ADMINISTRATOR)->firstOrFail();

        $this->actingAs($admin)->get(route('roles.edit', $system))->assertRedirect(route('roles.index'));
        $this->actingAs($admin)->put(route('roles.update', $system), ['role_name' => 'Boss'])->assertForbidden();
        $this->actingAs($admin)->delete(route('roles.destroy', $system))->assertForbidden();

        $this->actingAs($admin)->post(route('roles.store'), ['role_name' => 'Pending'])->assertSessionHasErrors('role_name');
        $this->actingAs($admin)->post(route('roles.store'), ['role_name' => 'treasurer'])->assertSessionHasErrors('role_name');
        $this->actingAs($admin)->post(route('roles.store'), ['role_name' => 'Clerk 2'])->assertSessionHasErrors('role_name');
        $this->actingAs($admin)->post(route('roles.store'), ['role_name' => 'Helper', 'permissions' => ['everything']])->assertSessionHasErrors('permissions.0');
    }
}

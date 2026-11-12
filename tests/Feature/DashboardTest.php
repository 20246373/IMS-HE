<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function signInAs(string $role): void
    {
        $this->actingAs(User::create([
            'username' => 'user-'.md5($role),
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]));
    }

    public function test_president_sees_every_widget(): void
    {
        $this->signInAs(User::PRESIDENT);

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('User accounts')
            ->assertSee('Revenue this month')
            ->assertSee('Low-stock products')
            ->assertSee('Pending Purchase Orders');
    }

    public function test_treasurer_only_sees_sales(): void
    {
        $this->signInAs(User::TREASURER);

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Revenue this month')
            ->assertDontSee('User accounts')
            ->assertDontSee('Low-stock products')
            ->assertDontSee('Pending Purchase Orders');
    }

    public function test_inventory_clerk_sees_stock_and_procurement_but_not_sales_or_accounts(): void
    {
        $this->signInAs(User::INVENTORY_CLERK);

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Low-stock products')
            ->assertSee('Pending Purchase Orders')
            ->assertDontSee('Revenue this month')
            ->assertDontSee('User accounts');
    }

    public function test_store_supervisor_sees_sales_stock_and_procurement_but_not_accounts(): void
    {
        $this->signInAs(User::STORE_SUPERVISOR);

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Revenue this month')
            ->assertSee('Low-stock products')
            ->assertSee('Pending Purchase Orders')
            ->assertDontSee('User accounts');
    }

    public function test_employee_sees_no_modules(): void
    {
        $this->signInAs(User::EMPLOYEE);

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('No dashboard modules are assigned')
            ->assertDontSee('Revenue this month')
            ->assertDontSee('Low-stock products');
    }
}

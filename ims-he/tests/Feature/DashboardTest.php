<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
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

    public function test_administrator_sees_account_widgets_only(): void
    {
        $this->signInAs(User::ADMINISTRATOR);

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('User accounts')
            ->assertDontSee('Revenue this month')
            ->assertDontSee('Low-stock products')
            ->assertDontSee('Pending Purchase Orders');
    }

    public function test_president_sees_business_widgets_only(): void
    {
        $this->signInAs(User::PRESIDENT);

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Revenue this month')
            ->assertSee('Low-stock products')
            ->assertSee('Pending Purchase Orders')
            ->assertDontSee('User accounts');
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

    public function test_low_stock_widget_uses_the_shared_warning_alert_and_links_to_the_low_stock_list(): void
    {
        $this->signInAs(User::INVENTORY_CLERK);
        $category = Category::create(['category_name' => 'Hardware']);
        Product::create([
            'category_id' => $category->category_id,
            'product_name' => 'Low stock item',
            'brand' => 'Stanley',
            'unit_price' => 100,
            'stock_quantity' => 2,
            'reorder_level' => 2,
            'is_active' => true,
        ]);

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('alert-warning', false)
            ->assertSee('alert-icon', false)
            ->assertSee('Low-stock products (1)')
            ->assertSee('1 product is at or below the reorder level.')
            ->assertSee('View low-stock list')
            ->assertSee(route('stock.index', ['low' => 1]), false);
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

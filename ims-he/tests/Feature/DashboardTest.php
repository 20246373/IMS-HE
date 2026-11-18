<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Invoice;
use App\Models\Payment;
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
            'email' => 'user-'.md5($role).'@example.com',
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

    public function test_empty_account_groups_show_the_empty_state(): void
    {
        $this->signInAs(User::ADMINISTRATOR);

        $response = $this->get(route('dashboard'))->assertOk();

        $this->assertSame(2, substr_count($response->getContent(), '<p>None yet.</p>'));
        $response->assertDontSee(route('users.index').'#unassigned', false);
    }

    public function test_account_previews_keep_full_counts_and_limit_each_group_to_five_entries(): void
    {
        $this->signInAs(User::ADMINISTRATOR);

        foreach (range(1, 6) as $number) {
            User::create([
                'email' => "pending-{$number}@example.com",
                'password' => 'secret123',
                'role' => User::PENDING,
                'status' => User::STATUS_ACTIVE,
            ]);
        }

        User::create([
            'email' => 'locked@example.com',
            'password' => 'secret123',
            'role' => User::EMPLOYEE,
            'status' => User::STATUS_LOCKED,
        ]);

        $response = $this->get(route('dashboard'))->assertOk()
            ->assertSee('+1 more')
            ->assertSee('+3 more')
            ->assertSee(route('users.index').'#unassigned', false)
            ->assertDontSee('pending-6@example.com');

        $summaries = $response->viewData('accountSummaries');
        $this->assertSame(8, $summaries['all']['count']);
        $this->assertCount(5, $summaries['all']['names']);
        $this->assertSame(6, $summaries['unassigned']['count']);
        $this->assertSame([
            'pending-1@example.com', 'pending-2@example.com', 'pending-3@example.com',
            'pending-4@example.com', 'pending-5@example.com',
        ], $summaries['unassigned']['names']->all());
        $this->assertSame(1, $summaries['locked']['count']);
        $this->assertSame(['locked@example.com'], $summaries['locked']['names']->all());
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

    public function test_dashboard_sales_widget_excludes_pending_paid_invoices(): void
    {
        $this->signInAs(User::PRESIDENT);
        $completed = Invoice::create([
            'invoice_date' => today(),
            'channel' => 'In-store',
            'status' => 'Completed',
            'total_amount' => 100,
        ]);
        Payment::create([
            'invoice_id' => $completed->invoice_id,
            'payment_date' => today(),
            'amount_paid' => 100,
            'change_amount' => 0,
            'payment_method' => 'Cash',
        ]);
        $pending = Invoice::create([
            'invoice_date' => today(),
            'channel' => 'Online',
            'status' => 'Pending',
            'total_amount' => 900,
        ]);
        Payment::create([
            'invoice_id' => $pending->invoice_id,
            'payment_date' => today(),
            'amount_paid' => 900,
            'change_amount' => 0,
            'payment_method' => 'Cash',
        ]);

        $this->assertSame('Completed', $completed->fresh()->status);
        $this->assertSame(today()->toDateString(), $completed->fresh()->invoice_date->toDateString());
        $this->assertSame(1, Invoice::where('status', 'Completed')->count());
        $this->assertSame(1, Invoice::where('status', 'Completed')
            ->whereDate('invoice_date', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('invoice_date', '<=', now()->toDateString())
            ->count());

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('100.00')
            ->assertDontSee('900.00')
            ->assertSee('Completed sales this month');
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

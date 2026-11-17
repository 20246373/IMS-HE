<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Task J4: staff manage online orders. Pending -> Completed or Cancelled; cancelling restores stock.
class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private Category $cat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cat = Category::create(['category_name' => 'Hand Tools']);
    }

    private function staff(string $role): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'username' => "staff{$n}", 'email' => "staff{$n}@example.com", 'password' => 'secret123',
            'role' => $role, 'status' => User::STATUS_ACTIVE,
        ]);
    }

    private function product(string $name, int $stock, float $price): Product
    {
        return Product::create([
            'category_id' => $this->cat->category_id, 'product_name' => $name, 'unit_price' => $price,
            'stock_quantity' => $stock, 'reorder_level' => 0,
        ]);
    }

    // A real online order placed through checkout: invoice, items, payment and OUT movements.
    private function onlineOrder(array $lines): Invoice
    {
        static $n = 0;
        $n++;
        $customer = Customer::create([
            'email' => "buyer{$n}@example.com", 'password' => 'secret123', 'email_verified_at' => now(),
            'first_name' => 'Juan', 'last_name' => 'Cruz', 'contact_number' => '09171234567', 'address' => 'Baguio City',
        ]);
        foreach ($lines as [$product, $qty]) {
            app(CartService::class)->add($customer, $product, $qty);
        }

        return app(CheckoutService::class)->placeOrder($customer, $customer->only(['first_name', 'last_name', 'contact_number', 'address']), 'Cash');
    }

    public function test_only_president_supervisor_and_employee_can_open_online_orders(): void
    {
        foreach ([User::PRESIDENT, User::STORE_SUPERVISOR, User::EMPLOYEE] as $role) {
            $this->actingAs($this->staff($role))->get(route('online-orders.index'))->assertOk();
        }
        foreach ([User::ADMINISTRATOR, User::TREASURER, User::INVENTORY_CLERK] as $role) {
            $this->actingAs($this->staff($role))->get(route('online-orders.index'))->assertForbidden();
        }
    }

    public function test_guests_and_customers_cannot_reach_it(): void
    {
        $this->get(route('online-orders.index'))->assertRedirect(route('login'));
    }

    public function test_list_shows_online_orders_filtered_by_status_and_hides_in_store_sales(): void
    {
        $pending = $this->onlineOrder([[$this->product('Claw Hammer', 10, 1500), 1]]);
        $done = $this->onlineOrder([[$this->product('Drill', 10, 2750.5), 1]]);
        $done->update(['status' => 'Completed']);
        $inStore = Invoice::create(['invoice_date' => today(), 'channel' => 'In-store', 'status' => 'Completed', 'total_amount' => 999]);
        $supervisor = $this->staff(User::STORE_SUPERVISOR);

        // Pending is the default view.
        $this->actingAs($supervisor)->get(route('online-orders.index'))
            ->assertSee('#'.$pending->invoice_id)->assertSee('₱1,500.00')->assertDontSee('₱2,750.50');

        $this->actingAs($supervisor)->get(route('online-orders.index', ['status' => 'All']))
            ->assertSee('₱1,500.00')->assertSee('₱2,750.50')->assertDontSee('₱999.00');

        $this->actingAs($supervisor)->get(route('online-orders.show', $inStore))->assertNotFound();
    }

    public function test_completing_a_pending_order_is_audited(): void
    {
        $order = $this->onlineOrder([[$product = $this->product('Claw Hammer', 10, 350), 2]]);
        $employee = $this->staff(User::EMPLOYEE);

        $this->actingAs($employee)->patch(route('online-orders.complete', $order))
            ->assertRedirect(route('online-orders.show', $order))->assertSessionHasNoErrors();

        $this->assertSame('Completed', $order->fresh()->status);
        $this->assertSame(8, (int) $product->fresh()->stock_quantity);      // stock stays deducted
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $employee->user_id, 'action' => 'Completed', 'table_name' => 'invoices', 'record_id' => $order->invoice_id,
        ]);
    }

    public function test_cancelling_restores_stock_with_in_transactions_and_is_audited(): void
    {
        $hammer = $this->product('Claw Hammer', 10, 350);
        $drill = $this->product('Drill', 5, 1200);
        $order = $this->onlineOrder([[$hammer, 3], [$drill, 1]]);
        $this->assertSame(7, (int) $hammer->fresh()->stock_quantity);
        $president = $this->staff(User::PRESIDENT);

        $this->actingAs($president)->patch(route('online-orders.cancel', $order))->assertSessionHasNoErrors();

        $this->assertSame('Cancelled', $order->fresh()->status);
        $this->assertSame(10, (int) $hammer->fresh()->stock_quantity);
        $this->assertSame(5, (int) $drill->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $hammer->product_id, 'transaction_type' => 'IN', 'quantity' => 3,
            'reference_type' => 'Invoice', 'reference_id' => $order->invoice_id,
        ]);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $drill->product_id, 'transaction_type' => 'IN', 'quantity' => 1,
            'reference_type' => 'Invoice', 'reference_id' => $order->invoice_id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $president->user_id, 'action' => 'Cancelled', 'table_name' => 'invoices', 'record_id' => $order->invoice_id,
        ]);
    }

    public function test_only_pending_orders_can_change(): void
    {
        $order = $this->onlineOrder([[$product = $this->product('Claw Hammer', 10, 350), 2]]);
        $supervisor = $this->staff(User::STORE_SUPERVISOR);
        $this->actingAs($supervisor)->patch(route('online-orders.cancel', $order));

        // Cancelling twice must not put the stock back twice; a cancelled order cannot be completed.
        $this->actingAs($supervisor)->patch(route('online-orders.cancel', $order))->assertSessionHasErrors('status');
        $this->actingAs($supervisor)->patch(route('online-orders.complete', $order))->assertSessionHasErrors('status');

        $this->assertSame('Cancelled', $order->fresh()->status);
        $this->assertSame(10, (int) $product->fresh()->stock_quantity);
        $this->assertSame(1, InventoryTransaction::where('transaction_type', 'IN')->count());
    }

    public function test_detail_page_shows_actions_only_while_pending(): void
    {
        $order = $this->onlineOrder([[$this->product('Claw Hammer', 10, 1234.5), 1]]);
        $employee = $this->staff(User::EMPLOYEE);

        $this->actingAs($employee)->get(route('online-orders.show', $order))->assertOk()
            ->assertSee('Claw Hammer')->assertSee('₱1,234.50')->assertSee('Mark as Completed')->assertSee('Cancel order');

        $order->update(['status' => 'Completed']);
        $this->actingAs($employee)->get(route('online-orders.show', $order))
            ->assertDontSee('Mark as Completed')->assertSee('can no longer be changed');
    }

    public function test_customer_sees_the_new_status_in_order_history(): void
    {
        $order = $this->onlineOrder([[$this->product('Claw Hammer', 10, 350), 1]]);
        $this->actingAs($this->staff(User::EMPLOYEE))->patch(route('online-orders.cancel', $order));

        $this->actingAs($order->customer, 'customer')->get(route('customer.orders.show', $order))
            ->assertSee('Cancelled')->assertSee('This order was cancelled.');
    }
}

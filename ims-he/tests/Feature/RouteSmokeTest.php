<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\SaleService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\TestCase;

/**
 * Opens every page (GET route) as a guest, a customer and every staff role, with the sample data plus one of
 * each record type, and fails on any server error. Access rules (403, redirects) are checked elsewhere;
 * this only makes sure no page crashes for anyone.
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** Routes this test cannot open in a plain GET (signed links, file streams checked in their own tests). */
    private const SKIP = [
        'customer.verification.verify', 'password.reset',   // need a valid signed link
        'exports.backup',                                    // SQLite file download, covered in ExportTest
    ];

    public function test_no_page_returns_a_server_error_for_any_kind_of_user(): void
    {
        ini_set('memory_limit', '1G');   // the PDF reports are generated once per role
        $this->seed(DatabaseSeeder::class);
        $ids = $this->sampleRecords();

        $visitors = ['guest' => null, 'customer' => Customer::where('email', 'customer1@example.com')->firstOrFail()];
        foreach (Role::pluck('role_name') as $role) {
            $visitors[$role] = User::where('role', $role)->first();
        }
        $visitors['Pending'] = User::where('role', User::PENDING)->first();

        $failures = [];
        foreach (Router::getRoutes() as $route) {
            /** @var Route $route */
            if (! in_array('GET', $route->methods(), true) || ! $route->getName() || in_array($route->getName(), self::SKIP, true)
                || str_starts_with($route->uri(), '_') || in_array($route->uri(), ['up', 'storage/{path}'], true)) {
                continue;
            }

            $url = route($route->getName(), $this->parametersFor($route, $ids));

            foreach ($visitors as $who => $account) {
                if ($account === null && $who !== 'guest') {
                    continue;   // no seeded account for this role
                }

                // A clean login and session per visit.
                $this->app['auth']->forgetGuards();
                $this->app['auth']->shouldUse('web');
                $this->flushSession();
                if ($account instanceof Customer) {
                    $this->actingAs($account, 'customer');
                } elseif ($account instanceof User) {
                    $this->actingAs($account, 'web');
                }

                $status = $this->get($url)->getStatusCode();
                if ($status >= 500) {
                    $failures[] = "{$status}  {$who}  GET {$url}";
                }
            }
        }

        $this->assertSame([], $failures, "Pages that crashed:\n".implode("\n", $failures));
    }

    /** One of every record a page can show: an online order, an in-store sale, a purchase order, a payroll. */
    private function sampleRecords(): array
    {
        $customer = Customer::where('email', 'customer1@example.com')->firstOrFail();
        $product = Product::firstOrFail();

        app(CartService::class)->add($customer, $product, 1);
        $order = app(CheckoutService::class)->placeOrder($customer, $customer->only(['first_name', 'last_name', 'contact_number', 'address']), 'Cash', 'Delivery');
        app(CartService::class)->add($customer, $product, 1);   // so the cart and checkout pages have something to show

        $cashier = User::where('role', User::EMPLOYEE)->firstOrFail();
        $sale = app(SaleService::class)->sell([['product_id' => $product->product_id, 'quantity' => 1]], 'Cash', 10000, $cashier);

        $supplier = Supplier::firstOrFail();
        $po = PurchaseOrder::create(['supplier_id' => $supplier->supplier_id, 'order_date' => today(), 'status' => 'Pending']);
        $po->items()->create(['product_id' => $product->product_id, 'quantity' => 5, 'unit_cost' => 100, 'subtotal' => 500]);

        $treasurer = User::where('role', User::TREASURER)->firstOrFail();
        $employee = Employee::firstOrFail();
        $payroll = Payroll::create(['period_start' => today()->subWeek(), 'period_end' => today(), 'processed_by' => $treasurer->user_id, 'status' => 'Draft']);
        $payslip = Payslip::create(['payroll_id' => $payroll->payroll_id, 'employee_id' => $employee->employee_id, 'days_worked' => 5, 'gross_pay' => 3000, 'net_pay' => 3000]);

        AuditLog::recordByCustomer('Placed', 'invoices', $order->invoice_id, $customer->customer_id);

        return [
            'customer' => $customer->customer_id,
            'invoice' => $order->invoice_id,
            'sale' => $sale->invoice_id,
            'product' => $product->product_id,
            'purchase_order' => $po->po_id,
            'payroll' => $payroll->payroll_id,
            'payslip' => $payslip->payslip_id,
            'employee' => $employee->employee_id,
            'user' => $cashier->user_id,
            'role' => Role::where('role_name', User::EMPLOYEE)->value('role_id'),
            'dataset' => 'products',
        ];
    }

    private function parametersFor(Route $route, array $ids): array
    {
        $parameters = [];
        foreach ($route->parameterNames() as $name) {
            // In-store sale pages show the POS sale; every other {invoice} is the online order.
            $parameters[$name] = $name === 'invoice' && str_starts_with($route->getName(), 'sales.') ? $ids['sale'] : $ids[$name];
        }

        return $parameters;
    }
}

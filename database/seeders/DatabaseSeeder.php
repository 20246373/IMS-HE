<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // DEV ONLY - sample data and shared passwords. Remove before any real deployment.

        // Staff: [username, role, first, last, position, daily_rate]
        foreach ([
            ['president',  User::PRESIDENT,        'Pedro',  'Reyes',   'President',        1000],
            ['treasurer',  User::TREASURER,        'Maria',  'Santos',  'Treasurer',         800],
            ['clerk',      User::INVENTORY_CLERK,  'Jose',   'Garcia',  'Inventory Clerk',   650],
            ['supervisor', User::STORE_SUPERVISOR, 'Ana',    'Lopez',   'Store Supervisor',  750],
            ['employee',   User::EMPLOYEE,         'Luis',   'Mendoza', 'Sales Associate',   610],
        ] as [$username, $role, $first, $last, $position, $rate]) {
            $emp = Employee::firstOrCreate(
                ['first_name' => $first, 'last_name' => $last],
                ['position' => $position, 'daily_rate' => $rate, 'date_hired' => now()->subYear()->toDateString(), 'status' => 'Active']
            );

            User::updateOrCreate(
                ['username' => $username],
                ['employee_id' => $emp->employee_id, 'role' => $role, 'password' => 'password123', 'status' => User::STATUS_ACTIVE, 'login_attempt_count' => 0]
            );
        }

        // An employee without an account (shows up in the "link employee" dropdown)
        Employee::firstOrCreate(
            ['first_name' => 'Pedro', 'last_name' => 'Cruz'],
            ['position' => 'Warehouse Helper', 'daily_rate' => 570, 'date_hired' => now()->subMonths(6)->toDateString(), 'status' => 'Active']
        );

        // Categories + products (stock_quantity / reorder_level per SDD 7.2.4)
        $cats = [];
        foreach (['Hand Tools', 'Power Tools', 'Plumbing', 'Electrical', 'Paint', 'Fasteners'] as $name) {
            $cats[$name] = Category::firstOrCreate(['category_name' => $name])->category_id;
        }

        foreach ([
            ['Claw Hammer 16oz',       'Hand Tools',  350,   40, 10],
            ['Cordless Drill 12V',     'Power Tools', 3200,   8,  3],
            ['PVC Pipe 1/2" x 3m',     'Plumbing',    120,  100, 20],
            ['THHN Wire #12 (per m)',  'Electrical',   38,  300, 50],
            ['Latex Paint White 4L',   'Paint',       780,    5,  6],   // starts below reorder level
            ['Common Nails 3" (kg)',   'Fasteners',    85,   60, 15],
        ] as [$name, $cat, $price, $stock, $reorder]) {
            Product::firstOrCreate(['product_name' => $name], [
                'category_id' => $cats[$cat], 'description' => $name, 'unit_price' => $price,
                'stock_quantity' => $stock, 'reorder_level' => $reorder, 'is_active' => true,
            ]);
        }

        foreach ([
            ['Sample Hardware Supply', '09170000001', 'Baguio City'],
            ['Cordillera Building Materials', '09170000002', 'La Trinidad, Benguet'],
        ] as [$name, $contact, $address]) {
            Supplier::firstOrCreate(['supplier_name' => $name], ['contact_number' => $contact, 'address' => $address]);
        }

        // Verified sample customer for storefront testing (guard: customer)
        Customer::updateOrCreate(['username' => 'customer1'], [
            'first_name' => 'Sample', 'last_name' => 'Customer', 'email' => 'customer1@example.com',
            'email_verified_at' => now(), 'contact_number' => '09171234567', 'address' => 'Baguio City',
            'password' => 'password123',
        ]);
    }
}

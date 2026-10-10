<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // DEV ONLY - change/remove these accounts before any real deployment.
        foreach ([
            ['president', 'President', User::PRESIDENT],
            ['treasurer', 'Treasurer', User::TREASURER],
            ['clerk', 'Inventory Clerk', User::INVENTORY_CLERK],
            ['supervisor', 'Store Supervisor', User::STORE_SUPERVISOR],
            ['employee', 'Sales Employee', User::EMPLOYEE],
        ] as [$username, $name, $role]) {
            User::updateOrCreate(['username' => $username], ['name' => $name, 'role' => $role, 'password' => 'password123']);
        }

        $cats = collect(['Hand Tools', 'Power Tools', 'Plumbing', 'Electrical', 'Paint', 'Fasteners'])
            ->mapWithKeys(fn ($n) => [$n => Category::firstOrCreate(['name' => $n])->id]);

        foreach ([
            ['Claw Hammer 16oz', 'Stanley', 'Hand Tools', 350, 40, 10],
            ['Cordless Drill 12V', 'Bosch', 'Power Tools', 3200, 8, 3],
            ['PVC Pipe 1/2" x 3m', 'Neltex', 'Plumbing', 120, 100, 20],
            ['THHN Wire #12 (per m)', 'Phelps Dodge', 'Electrical', 38, 300, 50],
            ['Latex Paint White 4L', 'Boysen', 'Paint', 780, 5, 6],
            ['Common Nails 3" (kg)', 'Generic', 'Fasteners', 85, 60, 15],
            ['Adjustable Wrench 10"', 'Stanley', 'Hand Tools', 420, 25, 8],
            ['Angle Grinder 4"', 'Makita', 'Power Tools', 2650, 2, 3],
            ['PVC Elbow 1/2"', 'Neltex', 'Plumbing', 18, 150, 40],
            ['Wall Switch 1-Gang', 'Panasonic', 'Electrical', 65, 0, 10],
        ] as [$name, $brand, $cat, $price, $stock, $reorder]) {
            Product::firstOrCreate(['name' => $name], [
                'brand' => $brand, 'category_id' => $cats[$cat], 'unit_price' => $price,
                'stock_quantity' => $stock, 'reorder_point' => $reorder,
            ]);
        }

        Supplier::firstOrCreate(['name' => 'Sample Hardware Supply'], ['contact_number' => '09170000000', 'address' => 'Baguio City']);
        Employee::firstOrCreate(['first_name' => 'Sample', 'last_name' => 'Employee'], ['position' => 'Cashier', 'daily_rate' => 610, 'date_hired' => now()->subYear()]);
    }
}

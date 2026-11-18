<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Photos live in storage/app/public/products/.
     * By default a product's photo is found by its slug, e.g. "Claw Hammer 16oz" -> claw-hammer-16oz.jpg
     * (.jpg .jpeg .png .webp). If your files are named differently, map them here:
     *   'Claw Hammer 16oz' => 'hammer.jpg',
     */
    private array $photoFiles = [
        'Claw Hammer 16oz' => 'clawhammer.jpg',
        'Cordless Drill 12V' => 'cordlessdrill.jpg',
        'PVC Pipe 1/2" x 3m' => 'pvcpipe.jpg',
        'THHN Wire #12 (per m)' => 'thhnwire.jpg',
        'Latex Paint White 4L' => 'latexpaint.jpg',
        'Common Nails 3" (kg)' => 'commonnail.jpg',
    ];

    /** Sample SSS, PhilHealth, Pag-IBIG and TIN numbers (required for every employee). */
    private function govIds(int $n): array
    {
        return [
            'sss_no' => sprintf('34-%07d-%d', 1000000 + $n, $n % 10),
            'philhealth_no' => sprintf('12-%09d-%d', 100000000 + $n, $n % 10),
            'pagibig_no' => sprintf('1210-%04d-%04d', $n, 1000 + $n),
            'tin_no' => sprintf('123-456-%03d-000', $n),
        ];
    }

    public function run(): void
    {
        // DEV ONLY - sample data and shared passwords. Remove before any real deployment.

        // The Administrator's name comes from a linked employee record, like every other staff account.
        $administrator = Employee::firstOrCreate(
            ['first_name' => 'Anton', 'last_name' => 'Sanchez'],
            ['position' => 'Administrator', 'daily_rate' => 700, 'date_hired' => now()->subYear()->toDateString(), 'status' => 'Active'] + $this->govIds(1)
        );

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'employee_id' => $administrator->employee_id,
                'role' => User::ADMINISTRATOR,
                'password' => 'password123',
                'status' => User::STATUS_ACTIVE,
                'login_attempt_count' => 0,
            ]
        );

        // Staff: [email name, role, first, last, position, daily_rate]
        foreach ([
            ['president',  User::PRESIDENT,        'Pedro',  'Reyes',   'President',        1000],
            ['treasurer',  User::TREASURER,        'Maria',  'Santos',  'Treasurer',         800],
            ['clerk',      User::INVENTORY_CLERK,  'Jose',   'Garcia',  'Inventory Clerk',   650],
            ['supervisor', User::STORE_SUPERVISOR, 'Ana',    'Lopez',   'Store Supervisor',  750],
            ['employee',   User::EMPLOYEE,         'Luis',   'Mendoza', 'Sales Associate',   610],
        ] as $i => [$emailName, $role, $first, $last, $position, $rate]) {
            $emp = Employee::firstOrCreate(
                ['first_name' => $first, 'last_name' => $last],
                ['position' => $position, 'daily_rate' => $rate, 'date_hired' => now()->subYear()->toDateString(), 'status' => 'Active'] + $this->govIds($i + 2)
            );

            User::updateOrCreate(
                ['email' => "{$emailName}@example.com"],
                ['employee_id' => $emp->employee_id, 'role' => $role, 'password' => 'password123', 'status' => User::STATUS_ACTIVE, 'login_attempt_count' => 0]
            );
        }

        // A new employee waiting for the Administrator to give a role (try it under User Accounts).
        // Every employee has an email, because it is their login.
        $newHire = Employee::firstOrCreate(
            ['first_name' => 'Pedro', 'last_name' => 'Cruz'],
            ['position' => 'Warehouse Helper', 'daily_rate' => 570, 'date_hired' => now()->subMonths(6)->toDateString(), 'status' => 'Active'] + $this->govIds(9)
        );
        User::updateOrCreate(
            ['email' => 'newhire@example.com'],
            ['employee_id' => $newHire->employee_id, 'role' => User::PENDING, 'password' => 'password123', 'status' => User::STATUS_ACTIVE, 'login_attempt_count' => 0]
        );

        // Categories + products (stock_quantity / reorder_level per SDD 7.2.4)
        $cats = [];
        foreach (['Hand Tools', 'Power Tools', 'Plumbing', 'Electrical', 'Paint', 'Fasteners'] as $name) {
            $cats[$name] = Category::firstOrCreate(['category_name' => $name])->category_id;
        }

        foreach ([
            ['Claw Hammer 16oz',       'Hand Tools',  350,   40, 10, 'Stanley'],
            ['Cordless Drill 12V',     'Power Tools', 3200,   8,  3, 'Bosch'],
            ['PVC Pipe 1/2" x 3m',     'Plumbing',    120,  100, 20, 'Apex'],
            ['THHN Wire #12 (per m)',  'Electrical',   38,  300, 50, 'Philflex'],
            ['Latex Paint White 4L',   'Paint',       780,    5,  6, 'Boysen'],
            ['Common Nails 3" (kg)',   'Fasteners',    85,   60, 15, 'Prime'],
        ] as [$name, $cat, $price, $stock, $reorder, $brand]) {
            $product = Product::firstOrCreate(['product_name' => $name], [
                'category_id' => $cats[$cat], 'description' => $name, 'brand' => $brand,
                'unit_price' => $price,
                'stock_quantity' => $stock, 'reorder_level' => $reorder, 'is_active' => true,
            ]);

            // Backfill the brand on rows seeded before products.brand existed, without touching stock or price.
            if (! $product->brand) {
                $product->update(['brand' => $brand]);
            }

            // Fill a missing photo path without touching stock or price, so `php artisan db:seed` is safe to re-run.
            if (! $product->image_path) {
                $photo = $this->photoFor($name);

                if ($photo) {
                    $product->update(['image_path' => $photo]);
                } else {
                    $this->command?->warn("No photo found for \"{$name}\" - expected storage/app/public/products/".Str::slug($name).'.jpg');
                }
            }
        }

        foreach ([
            ['Sample Hardware Supply', '09170000001', 'Baguio City'],
            ['Cordillera Building Materials', '09170000002', 'La Trinidad, Benguet'],
        ] as [$name, $contact, $address]) {
            Supplier::firstOrCreate(['supplier_name' => $name], ['contact_number' => $contact, 'address' => $address]);
        }

        // Verified sample customer for storefront testing (guard: customer)
        Customer::updateOrCreate(['email' => 'customer1@example.com'], [
            'first_name' => 'Sample', 'last_name' => 'Customer',
            'email_verified_at' => now(), 'contact_number' => '09171234567', 'address' => 'Baguio City',
            'password' => 'password123',
        ]);
    }

    /** Returns "products/<file>" if the photo exists on disk, otherwise null. */
    private function photoFor(string $productName): ?string
    {
        $dir = storage_path('app/public/products');

        if (isset($this->photoFiles[$productName]) && is_file($dir.'/'.$this->photoFiles[$productName])) {
            return 'products/'.$this->photoFiles[$productName];
        }

        $slug = Str::slug($productName);
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            if (is_file("{$dir}/{$slug}.{$ext}")) {
                return "products/{$slug}.{$ext}";
            }
        }

        return null;
    }
}

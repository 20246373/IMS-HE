<?php

namespace Tests\Feature;

use App\Exports\ExportRegistry;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        static $number = 0;
        $number++;

        return User::create([
            'email' => "export{$number}@example.com",
            'password' => 'secret123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_administrator_can_download_users_without_password_column(): void
    {
        $admin = $this->user(User::ADMINISTRATOR);

        $response = $this->actingAs($admin)->get(route('exports.download', 'users'));

        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFuser_id,email,role,status,employee_name,last_login,created_at", $csv);
        $this->assertStringNotContainsString('password', strtolower($csv));
        $this->assertDatabaseHas('audit_logs', ['action' => 'Exported', 'table_name' => 'users']);
    }

    public function test_users_and_audit_logs_are_hidden_from_store_supervisor(): void
    {
        $supervisor = $this->user(User::STORE_SUPERVISOR);

        $this->actingAs($supervisor)->get(route('exports.download', 'users'))->assertNotFound();
        $this->actingAs($supervisor)->get(route('exports.download', 'audit_logs'))->assertNotFound();
    }

    public function test_president_cannot_download_database_backup(): void
    {
        $this->actingAs($this->user(User::PRESIDENT))
            ->get(route('exports.backup'))
            ->assertForbidden();
    }

    public function test_administrator_export_page_has_a_download_column_and_no_backup_button(): void
    {
        $this->actingAs($this->user(User::ADMINISTRATOR))->get(route('exports.index'))->assertOk()
            ->assertSeeInOrder(['<th>Dataset</th>', '<th>Filters</th>', '<th>Download</th>'], false)
            ->assertDontSee('Database backup');
    }

    public function test_status_filter_is_a_dropdown_of_real_statuses(): void
    {
        $administrator = $this->user(User::ADMINISTRATOR);

        $this->actingAs($administrator)->get(route('exports.index'))->assertOk()
            ->assertDontSee('<input id="status"', false)
            ->assertSee('<option value="Delivered"', false)
            ->assertSee('<option value="Completed"', false);

        $this->actingAs($administrator)->get(route('exports.download', ['dataset' => 'invoices', 'status' => 'Completed']))->assertOk();
        $this->actingAs($administrator)->get(route('exports.download', ['dataset' => 'invoices', 'status' => 'Delivered']))
            ->assertSessionHasErrors('status');      // Delivered is a purchase order status, not an invoice one
    }

    public function test_page_lists_only_datasets_allowed_for_the_role(): void
    {
        $response = $this->actingAs($this->user(User::STORE_SUPERVISOR))->get(route('exports.index'));

        $response->assertOk()
            ->assertSee('Products')
            ->assertSee('Invoices')
            ->assertDontSee('Users')
            ->assertDontSee('Audit logs');
    }

    public function test_zip_contains_one_csv_for_each_allowed_dataset(): void
    {
        $admin = $this->user(User::ADMINISTRATOR);

        $response = $this->actingAs($admin)->post(route('exports.all'));

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertSame(true, $zip->open($path));
        $this->assertSame(count(ExportRegistry::forUser($admin)), $zip->numFiles);
        $this->assertSame('categories.csv', $zip->getNameIndex(0));
        $zip->close();
        File::delete($path);
    }

    public function test_date_filter_returns_only_rows_in_range(): void
    {
        $category = Category::create(['category_name' => 'Export category']);
        $product = Product::create(['category_id' => $category->category_id, 'product_name' => 'Filtered product']);
        InventoryTransaction::create([
            'product_id' => $product->product_id,
            'transaction_type' => 'In',
            'quantity' => 1,
            'transaction_date' => '2026-01-05',
            'reference_type' => 'Test',
            'reference_id' => 1,
        ]);
        InventoryTransaction::create([
            'product_id' => $product->product_id,
            'transaction_type' => 'Out',
            'quantity' => 2,
            'transaction_date' => '2026-02-05',
            'reference_type' => 'Test',
            'reference_id' => 2,
        ]);

        $csv = $this->actingAs($this->user(User::PRESIDENT))
            ->get(route('exports.download', ['dataset' => 'inventory_transactions', 'start_date' => '2026-01-01', 'end_date' => '2026-01-31']))
            ->streamedContent();

        $this->assertStringContainsString('2026-01-05', $csv);
        $this->assertStringNotContainsString('2026-02-05', $csv);
    }

    public function test_formula_like_product_names_are_escaped(): void
    {
        $category = Category::create(['category_name' => 'Export category']);
        Product::create(['category_id' => $category->category_id, 'product_name' => '=SUM(A1:A2)']);

        $csv = $this->actingAs($this->user(User::PRESIDENT))
            ->get(route('exports.download', 'products'))
            ->streamedContent();

        $this->assertStringContainsString("'=SUM(A1:A2)", $csv);
    }

    public function test_empty_dataset_still_exports_headers(): void
    {
        $csv = $this->actingAs($this->user(User::ADMINISTRATOR))
            ->get(route('exports.download', 'categories'))
            ->streamedContent();

        $this->assertSame("\xEF\xBB\xBFcategory_id,category_name\n", $csv);
    }
}

<?php

namespace Tests\Feature;

use App\Exports\ExportRegistry;
use App\Models\AuditLog;
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

    public function test_administrator_has_no_export_center(): void
    {
        $admin = $this->user(User::ADMINISTRATOR);

        // The Administrator account is for account management only.
        $this->actingAs($admin)->get(route('exports.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('exports.download', 'products'))->assertForbidden();
        $this->actingAs($admin)->post(route('exports.all'))->assertForbidden();
        $this->actingAs($admin)->get(route('users.index'))->assertOk()->assertDontSee('href="'.route('exports.index').'"', false);   // no sidebar link
    }

    public function test_accounts_and_audit_logs_cannot_be_exported(): void
    {
        $president = $this->user(User::PRESIDENT);

        $this->actingAs($president)->get(route('exports.download', 'users'))->assertNotFound();
        $this->actingAs($president)->get(route('exports.download', 'audit_logs'))->assertNotFound();
    }

    public function test_president_cannot_download_database_backup(): void
    {
        $this->actingAs($this->user(User::PRESIDENT))
            ->get(route('exports.backup'))
            ->assertForbidden();
    }

    public function test_export_page_has_a_download_column_and_no_backup_button(): void
    {
        $this->actingAs($this->user(User::PRESIDENT))->get(route('exports.index'))->assertOk()
            ->assertSeeInOrder(['<th>Dataset</th>', '<th>Filters</th>', '<th>Download</th>'], false)
            ->assertDontSee('Database backup');
    }

    public function test_status_filter_is_a_dropdown_of_real_statuses(): void
    {
        $president = $this->user(User::PRESIDENT);

        $this->actingAs($president)->get(route('exports.index'))->assertOk()
            ->assertDontSee('<input id="status"', false)
            ->assertSee('<option value="Delivered"', false)
            ->assertSee('<option value="Completed"', false);

        $this->actingAs($president)->get(route('exports.download', ['dataset' => 'invoices', 'status' => 'Completed']))->assertOk();
        $this->actingAs($president)->get(route('exports.download', ['dataset' => 'invoices', 'status' => 'Delivered']))
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
        $president = $this->user(User::PRESIDENT);

        $response = $this->actingAs($president)->post(route('exports.all'));

        $path = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertSame(true, $zip->open($path));
        $this->assertSame(count(ExportRegistry::forUser($president)), $zip->numFiles);
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
        $csv = $this->actingAs($this->user(User::PRESIDENT))
            ->get(route('exports.download', 'categories'))
            ->streamedContent();

        $this->assertSame("\xEF\xBB\xBFcategory_id,category_name\n", $csv);
    }

    public function test_administrator_exports_the_audit_log_in_the_export_format(): void
    {
        $admin = $this->user(User::ADMINISTRATOR);
        AuditLog::record('Locked', 'users', 1, $admin->user_id);
        AuditLog::record('Completed', 'invoices', 12, $admin->user_id);

        // Same columns as before; the page's filters apply; the user is shown by email.
        $csv = $this->actingAs($admin)->get(route('audit.export', ['action' => 'Locked|users']))
            ->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBFlog_id,logged_at,user,action,table_name,record_id\n", $csv);
        $this->assertStringContainsString(','.$admin->email.',Locked,users,1', $csv);
        $this->assertStringNotContainsString('invoices', $csv);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Exported', 'table_name' => 'audit_logs']);

        // The only download button is on the Audit Log; the account pages have none.
        $this->actingAs($admin)->get(route('audit.index'))->assertSee(route('audit.export'), false);
        $this->actingAs($admin)->get(route('users.index'))->assertDontSee('Download CSV');
        $this->actingAs($admin)->get(route('customer-accounts.index'))->assertDontSee('Download CSV');
    }

    public function test_only_the_administrator_can_export_the_audit_log(): void
    {
        $this->actingAs($this->user(User::PRESIDENT))->get(route('audit.export'))->assertForbidden();
    }
}

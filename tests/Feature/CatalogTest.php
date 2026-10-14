<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private Category $tools;

    private Category $paint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tools = Category::create(['category_name' => 'Hand Tools']);
        $this->paint = Category::create(['category_name' => 'Paint']);
    }

    private function product(string $name, Category $cat, int $stock = 10, bool $active = true): Product
    {
        return Product::create([
            'category_id' => $cat->category_id, 'product_name' => $name, 'unit_price' => 100,
            'stock_quantity' => $stock, 'reorder_level' => 0, 'is_active' => $active,
        ]);
    }

    public function test_guest_sees_landing_page_with_featured_in_stock_products(): void
    {
        $this->product('Claw Hammer', $this->tools);
        $this->product('Empty Shelf Item', $this->tools, stock: 0);
        $this->product('Hidden Item', $this->tools, active: false);

        $this->get('/')->assertOk()
            ->assertSee('Claw Hammer')
            ->assertDontSee('Empty Shelf Item')
            ->assertDontSee('Hidden Item');
    }

    public function test_catalog_searches_by_name_and_filters_by_category(): void
    {
        $this->product('Claw Hammer', $this->tools);
        $this->product('Rubber Mallet', $this->tools);
        $this->product('Latex Paint', $this->paint);

        $this->get('/shop?q=hammer')->assertOk()
            ->assertSee('Claw Hammer')->assertDontSee('Rubber Mallet')->assertDontSee('Latex Paint');

        $this->get('/shop?category='.$this->paint->category_id)->assertOk()
            ->assertSee('Latex Paint')->assertDontSee('Claw Hammer');
    }

    public function test_catalog_shows_stock_status_and_hides_inactive(): void
    {
        $this->product('Claw Hammer', $this->tools);
        $this->product('Latex Paint', $this->paint, stock: 0);
        $this->product('Hidden Item', $this->tools, active: false);

        $this->get('/shop')->assertOk()
            ->assertSee('In stock')->assertSee('Out of stock')->assertDontSee('Hidden Item');
    }

    public function test_product_details_page(): void
    {
        $product = $this->product('Claw Hammer', $this->tools, stock: 0);

        $this->get('/shop/'.$product->product_id)->assertOk()
            ->assertSee('Claw Hammer')->assertSee('Hand Tools')->assertSee('Out of stock');
    }

    public function test_inactive_or_missing_product_is_404(): void
    {
        $hidden = $this->product('Hidden Item', $this->tools, active: false);

        $this->get('/shop/'.$hidden->product_id)->assertNotFound();
        $this->get('/shop/999999')->assertNotFound();
    }
}

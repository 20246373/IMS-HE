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
        $this->product('Inactive Item', $this->tools, active: false);

        $this->get('/')->assertOk()
            ->assertSee('Claw Hammer')
            ->assertSee('Inactive Item')
            ->assertDontSee('Empty Shelf Item');
    }

    public function test_invalid_catalog_filters_show_errors_and_return_to_the_catalog(): void
    {
        $catalog = route('catalog.index');

        $this->from($catalog)->get(route('catalog.index', [
            'q' => str_repeat('a', 101),
            'category' => 999999,
            'brand' => str_repeat('b', 51),
        ]))->assertRedirect($catalog)->assertSessionHasErrors(['q', 'category', 'brand']);

        $this->get($catalog)->assertOk()->assertSee('alert-error', false);
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

    public function test_catalog_shows_every_product_with_its_stock_status(): void
    {
        $this->product('Claw Hammer', $this->tools);
        $this->product('Latex Paint', $this->paint, stock: 0);
        $this->product('Inactive Item', $this->tools, active: false);

        $this->get('/shop')->assertOk()
            ->assertSee('In stock')->assertSee('Out of stock')
            ->assertSee('Inactive Item');
    }

    public function test_product_details_page(): void
    {
        $product = $this->product('Claw Hammer', $this->tools, stock: 0);

        $this->get('/shop/'.$product->product_id)->assertOk()
            ->assertSee('Claw Hammer')->assertSee('Hand Tools')->assertSee('Out of stock');
    }

    public function test_a_product_marked_inactive_still_has_a_details_page(): void
    {
        $inactive = $this->product('Inactive Item', $this->tools, active: false);

        $this->get('/shop/'.$inactive->product_id)->assertOk()->assertSee('Inactive Item');
        $this->get('/shop/999999')->assertNotFound();
    }

    // Task J5: brand (defense comment 6), money format, and the three-level stock badge.
    private function branded(string $name, ?string $brand, Category $cat, float $price = 100, int $stock = 10, int $reorder = 0): Product
    {
        return Product::create([
            'category_id' => $cat->category_id, 'product_name' => $name, 'brand' => $brand, 'unit_price' => $price,
            'stock_quantity' => $stock, 'reorder_level' => $reorder,
        ]);
    }

    public function test_card_and_details_show_brand_and_money_format(): void
    {
        $p = $this->branded('Claw Hammer 16oz', 'Stanley', $this->tools, 1234.5);

        $this->get('/shop')->assertOk()->assertSee('Stanley')->assertSee('₱1,234.50');
        $this->get('/')->assertSee('Stanley')->assertSee('₱1,234.50');
        $this->get('/shop/'.$p->product_id)->assertOk()->assertSee('Brand:')->assertSee('Stanley')->assertSee('₱1,234.50');
    }

    public function test_search_matches_brand_as_well_as_name(): void
    {
        $this->branded('Claw Hammer', 'Stanley', $this->tools);
        $this->branded('Cordless Drill', 'Makita', $this->tools);
        $this->branded('Latex Paint', 'Boysen', $this->paint);

        $this->get('/shop?q=makita')->assertSee('Cordless Drill')->assertDontSee('Claw Hammer');
        $this->get('/shop?q=hammer')->assertSee('Claw Hammer')->assertDontSee('Cordless Drill');
    }

    public function test_brand_filter_lists_brands_and_combines_with_category(): void
    {
        $this->branded('Claw Hammer', 'Stanley', $this->tools);
        $this->branded('Tape Measure', 'Stanley', $this->tools);
        $this->branded('Stanley Paint Brush', 'Stanley', $this->paint);
        $this->branded('Cordless Drill', 'Makita', $this->tools);
        $this->branded('No Brand Nails', null, $this->tools);

        $this->get('/shop')->assertSee('<option value="Makita"', false)->assertSee('<option value="Stanley"', false);

        $this->get('/shop?brand=Stanley')
            ->assertSee('Claw Hammer')->assertSee('Tape Measure')->assertSee('Stanley Paint Brush')
            ->assertDontSee('Cordless Drill')->assertDontSee('No Brand Nails');

        $this->get('/shop?brand=Stanley&category='.$this->tools->category_id)
            ->assertSee('Claw Hammer')->assertDontSee('Stanley Paint Brush');
    }

    public function test_stock_badge_reads_in_low_or_out_of_stock(): void
    {
        $in = $this->branded('Plenty', null, $this->tools, stock: 50, reorder: 10);
        $low = $this->branded('Running Low', null, $this->tools, stock: 3, reorder: 10);
        $out = $this->branded('Sold Out', null, $this->tools, stock: 0, reorder: 10);

        $this->get('/shop/'.$in->product_id)->assertSee('In stock')->assertDontSee('Low stock');
        $this->get('/shop/'.$low->product_id)->assertSee('Low stock')->assertSee('Add to cart');
        $this->get('/shop/'.$out->product_id)->assertSee('Out of stock')->assertDontSee('Add to cart');
        $this->get('/shop')->assertSee('In stock')->assertSee('Low stock')->assertSee('Out of stock');
    }

    public function test_pagination_keeps_the_filters(): void
    {
        foreach (range(1, 14) as $i) {
            $this->branded(sprintf('Stanley Item %02d', $i), 'Stanley', $this->tools);
        }

        $this->get('/shop?brand=Stanley')->assertSee('Page 1 of 2')->assertSee('brand=Stanley&amp;page=2', false);
        $this->get('/shop?brand=Stanley&page=2')->assertSee('Stanley Item 14');
    }
}

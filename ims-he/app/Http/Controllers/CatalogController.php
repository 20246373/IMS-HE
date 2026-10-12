<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

// Product Catalog Module (SDD 4.1): public storefront for guests and customers. No login needed to browse.
class CatalogController extends Controller
{
    public const FEATURED_COUNT = 8;
    public const PER_PAGE = 12;

    public function home()
    {
        // Staff keep landing on their dashboard.
        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        // The schema has no "featured" flag, so feature the newest sellable products that are in stock.
        $featured = Product::active()->where('stock_quantity', '>', 0)
            ->with('category')->latest('product_id')->take(self::FEATURED_COUNT)->get();

        return view('catalog.home', [
            'featured' => $featured,
            'categories' => Category::withCount(['products' => fn ($q) => $q->where('is_active', true)])->orderBy('category_name')->get(),
            // category_id => image_path of its first product that has a photo (used for the category tiles)
            'categoryThumbs' => Product::active()->whereNotNull('image_path')->orderBy('product_id')
                ->get(['category_id', 'image_path'])->unique('category_id')->pluck('image_path', 'category_id'),
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|integer|exists:categories,category_id',
        ]);

        $products = Product::active()->with('category')
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where('product_name', 'like', '%' . addcslashes($q, '%_\\') . '%'))
            ->when($filters['category'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->orderBy('product_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('catalog.index', [
            'products' => $products,
            'categories' => Category::orderBy('category_name')->get(),
            'q' => $filters['q'] ?? '',
            'categoryId' => (int) ($filters['category'] ?? 0),
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        return view('catalog.show', ['product' => $product->load('category')]);
    }
}

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

        // The schema has no "featured" flag, so feature the newest products that are in stock.
        $featured = Product::where('stock_quantity', '>', 0)
            ->with('category')->latest('product_id')->take(self::FEATURED_COUNT)->get();

        return view('catalog.home', [
            'featured' => $featured,
            'categories' => Category::active()
                ->withCount('products')
                ->orderBy('category_name')
                ->get(),
            // category_id => image_path of its first product that has a photo (used for the category tiles)
            'categoryThumbs' => Product::whereNotNull('image_path')->orderBy('product_id')
                ->get(['category_id', 'image_path'])->unique('category_id')->pluck('image_path', 'category_id'),
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|integer|exists:categories,category_id',
            'brand' => 'nullable|string|max:50',
        ]);

        $products = Product::with('category')
            // Search matches the product name or its brand (defense comment 6).
            ->when($filters['q'] ?? null, function ($query, $q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn ($w) => $w->where('product_name', 'like', $like)->orWhere('brand', 'like', $like));
            })
            ->when($filters['category'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->when($filters['brand'] ?? null, fn ($query, $brand) => $query->where('brand', $brand))
            ->orderBy('product_name')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('catalog.index', [
            'products' => $products,
            'categories' => Category::active()->orderBy('category_name')->get(),
            'brands' => Product::whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand'),
            'q' => $filters['q'] ?? '',
            'categoryId' => (int) ($filters['category'] ?? 0),
            'brand' => $filters['brand'] ?? '',
        ]);
    }

    public function show(Product $product)
    {
        return view('catalog.show', ['product' => $product->load('category')]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $sort = in_array($request->sort, ['popular', 'latest', 'price_asc', 'price_desc'], true) ? $request->sort : 'popular';

        $products = Product::active()->with('category')
            ->withSum('invoiceItems', 'quantity')
            ->when($request->q, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->category, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->boolean('in_stock'), fn ($q) => $q->where('stock_quantity', '>', 0))
            ->when($sort === 'popular', fn ($q) => $q->orderByDesc('invoice_items_sum_quantity')->orderBy('name'))
            ->when($sort === 'latest', fn ($q) => $q->latest('id'))
            ->when($sort === 'price_asc', fn ($q) => $q->orderBy('unit_price'))
            ->when($sort === 'price_desc', fn ($q) => $q->orderByDesc('unit_price'))
            ->paginate(12)
            ->withQueryString();

        return view('catalog.index', [
            'products'   => $products,
            'categories' => Category::orderBy('name')->get(),
            'sort'       => $sort,
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        $product->load('category')->loadSum('invoiceItems', 'quantity');

        $related = Product::active()->with('category')->withSum('invoiceItems', 'quantity')
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
            ->inRandomOrder()->limit(6)->get();

        return view('catalog.show', ['product' => $product, 'related' => $related]);
    }
}

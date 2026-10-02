<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('brand', 'like', "%{$s}%")))
            ->when($request->category, fn ($q, $c) => $q->where('category_id', $c))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('catalog.index', ['products' => $products, 'categories' => Category::orderBy('name')->get()]);
    }

    public function show(Product $product)
    {
        return view('catalog.show', ['product' => $product->load('category')]);
    }
}

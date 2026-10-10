<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    /** Landing page: featured = best sellers first, then newest, in-stock only. */
    public function index()
    {
        $featured = Product::active()->with('category')
            ->where('stock_quantity', '>', 0)
            ->withSum('invoiceItems', 'quantity')
            ->orderByDesc('invoice_items_sum_quantity')->latest('id')
            ->limit(12)->get();

        $categories = Category::withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')->get();

        return view('home', ['featured' => $featured, 'categories' => $categories]);
    }
}

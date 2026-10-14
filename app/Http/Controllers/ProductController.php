<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request)
    {
        return view('products.index', [
            'products' => Product::with('category')
                ->when($request->q, fn ($q, $s) => $q->where('product_name', 'like', "%{$s}%"))
                ->when($request->category, fn ($q, $c) => $q->where('category_id', $c))
                ->orderByDesc('is_active')->orderBy('product_name')
                ->paginate(15)->withQueryString(),
            'categories' => Category::orderBy('category_name')->get(),
        ]);
    }

    public function create()
    {
        return view('products.create', ['categories' => Category::orderBy('category_name')->get()]);
    }

    private function rules(?Product $product = null): array
    {
        return [
            'product_name' => ['required', 'max:100', Rule::unique('products', 'product_name')->ignore($product?->product_id, 'product_id')],
            'brand' => 'nullable|max:100',
            'category_id' => 'nullable|exists:categories,category_id',
            'description' => 'nullable|max:255',
            'unit_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        DB::transaction(function () use ($data) {
            $product = Product::create(Arr::except($data, 'stock_quantity') + ['stock_quantity' => 0]);
            if ((int) $data['stock_quantity'] > 0) {
                $product->update(['stock_quantity' => (int) $data['stock_quantity']]);

                \App\Models\StockAdjustment::create([
                    'product_id' => $product->product_id,
                    'quantity_change' => (int) $data['stock_quantity'],
                    'quantity_before' => 0,
                    'quantity_after' => (int) $data['stock_quantity'],
                    'reason' => 'Initial stock',
                    'adjusted_by' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('products.index')->with('status', 'Product added.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', ['product' => $product, 'categories' => Category::orderBy('category_name')->get()]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate($this->rules($product));

        DB::transaction(function () use ($product, $data) {
            $quantityBefore = $product->stock_quantity;
            $diff = (int) $data['stock_quantity'] - $product->stock_quantity;

            $product->update(Arr::except($data, 'stock_quantity'));

            if ($diff !== 0) {
                $product->update(['stock_quantity' => (int) $data['stock_quantity']]);

                \App\Models\StockAdjustment::create([
                    'product_id' => $product->product_id,
                    'quantity_change' => $diff,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => (int) $data['stock_quantity'],
                    'reason' => 'Product update',
                    'adjusted_by' => auth()->id(),
                ]);
            }
        });

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    public function toggle(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('status', $product->is_active ? 'Product is visible again.' : 'Product hidden from catalog and POS.');
    }
}

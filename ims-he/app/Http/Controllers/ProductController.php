<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
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
            'categories' => Category::active()->orderBy('category_name')->get(),
        ]);
    }

    public function create()
    {
        return view('products.create', ['categories' => Category::active()->orderBy('category_name')->get()]);
    }

    private function rules(?Product $product = null): array
    {
        return [
            'product_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'product_name')->ignore($product?->product_id, 'product_id'),
            ],
            'category_id' => ['required', 'integer', 'exists:categories,category_id'],
            'description' => 'nullable|string|max:255',
            'unit_price' => 'required|numeric|min:0|max:99999999.99',
            'stock_quantity' => 'required|integer|min:0|max:4294967295',
            'reorder_level' => 'required|integer|min:0|max:4294967295',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        DB::transaction(function () use ($data) {
            // Create at 0 and let InventoryService add the opening stock so the movement is logged.
            $product = Product::create(Arr::except($data, 'stock_quantity') + ['stock_quantity' => 0]);
            if ((int) $data['stock_quantity'] > 0) {
                $this->inventory->add($product->product_id, (int) $data['stock_quantity'], 'Adjustment', $product->product_id);
            }
        });

        return redirect()->route('products.index')->with('status', 'Product added.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => Category::where('is_active', true)
                ->orWhere('category_id', $product->category_id)
                ->orderBy('category_name')
                ->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate($this->rules($product));

        DB::transaction(function () use ($product, $data) {
            $lockedProduct = Product::lockForUpdate()->findOrFail($product->product_id);
            $stockDifference = (int) $data['stock_quantity'] - (int) $lockedProduct->stock_quantity;
            $lockedProduct->update(Arr::except($data, 'stock_quantity'));

            if ($stockDifference > 0) {
                $this->inventory->add($lockedProduct->product_id, $stockDifference, 'Adjustment', $lockedProduct->product_id);
            } elseif ($stockDifference < 0) {
                $this->inventory->deduct($lockedProduct->product_id, abs($stockDifference), 'Adjustment', $lockedProduct->product_id);
            }
        });

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    /** Hide / show a product (is_active) instead of deleting it. */
    public function toggle(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);
        AuditLog::record(
            $product->is_active ? 'product.activated' : 'product.deactivated',
            'products',
            $product->product_id,
        );

        return back()->with('status', $product->is_active ? 'Product is visible again.' : 'Product hidden from catalog and POS.');
    }
}

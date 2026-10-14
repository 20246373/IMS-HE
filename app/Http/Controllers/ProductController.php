<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\InventoryService;
use App\Support\AuditLog;
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
                ->when($request->q, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->when($request->category, fn ($q, $c) => $q->where('category_id', $c))
                ->orderByDesc('is_active')->orderBy('name')
                ->paginate(15)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('products.create', ['categories' => Category::orderBy('name')->get()]);
    }

    private function rules(?Product $product = null): array
    {
        return [
            'name'           => ['required', 'max:100', Rule::unique('products', 'name')->ignore($product?->id)],
            'brand'          => 'nullable|max:100',
            'category_id'    => 'nullable|exists:categories,id',
            'description'    => 'nullable|max:2000',
            'unit_price'     => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'reorder_point'  => 'required|integer|min:0',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());

        DB::transaction(function () use ($data) {
            // Create at 0 and let InventoryService add the opening stock so the movement is logged.
            $product = Product::create(Arr::except($data, 'stock_quantity') + ['stock_quantity' => 0]);
            if ((int) $data['stock_quantity'] > 0) {
                $this->inventory->add($product->id, (int) $data['stock_quantity'], 'Adjustment');
            }
        });

        return redirect()->route('products.index')->with('status', 'Product added.');
    }

    public function edit(Product $product)
    {
        return view('products.edit', ['product' => $product, 'categories' => Category::orderBy('name')->get()]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate($this->rules($product));

        DB::transaction(function () use ($product, $data) {
            $product->update(Arr::except($data, 'stock_quantity'));

            $diff = (int) $data['stock_quantity'] - $product->stock_quantity; // manual correction
            if ($diff > 0) {
                $this->inventory->add($product->id, $diff, 'Adjustment');
            } elseif ($diff < 0) {
                $this->inventory->deduct($product->id, abs($diff), 'Adjustment');
            }
        });

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    /** Hide / show a product (is_active) instead of deleting it. */
    public function toggle(Product $product)
    {
        $product->update(['is_active' => ! $product->is_active]);
        AuditLog::record($product->is_active ? 'product.activated' : 'product.deactivated', $product, $product->name);

        return back()->with('status', $product->is_active ? 'Product is visible again.' : 'Product hidden from catalog and POS.');
    }
}

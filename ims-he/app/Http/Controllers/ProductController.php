<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        return view('products.index', [
            'products'   => Product::with('category')->when($request->q, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))->orderBy('name')->paginate(15)->withQueryString(),
            'categories' => Category::orderBy('name')->get(),
        ]);
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
            $product = Product::create($data);
            if ($product->stock_quantity > 0) {
                $this->logMovement($product->id, 'IN', $product->stock_quantity, 'Adjustment');
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
            $diff = (int) $data['stock_quantity'] - $product->stock_quantity;
            $product->update($data);
            if ($diff !== 0) { // manual stock correction -> keep the movement log accurate
                $this->logMovement($product->id, $diff > 0 ? 'IN' : 'OUT', abs($diff), 'Adjustment');
            }
        });

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        if ($product->exists && (\App\Models\InvoiceItem::where('product_id', $product->id)->exists()
            || \App\Models\PurchaseOrderItem::where('product_id', $product->id)->exists())) {
            return back()->withErrors(['product' => 'Product has sales/PO history and cannot be deleted.']);
        }
        InventoryTransaction::where('product_id', $product->id)->delete();
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Product deleted.');
    }

    private function logMovement(int $productId, string $type, int $qty, string $ref): void
    {
        InventoryTransaction::create([
            'product_id' => $productId, 'type' => $type, 'quantity' => $qty,
            'transaction_date' => today(), 'reference_type' => $ref, 'reference_id' => null,
        ]);
    }
}

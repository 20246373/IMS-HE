<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request)
    {
        $products = Product::active()->with('category')
            ->when($request->boolean('low'), fn ($q) => $q->whereColumn('stock_quantity', '<=', 'reorder_level'))
            ->orderByRaw('(stock_quantity <= reorder_level) desc')->orderBy('product_name')
            ->get();

        return view('stock.index', [
            'products' => $products,
            'adjustments' => StockAdjustment::with('product', 'adjustedBy')->latest('id')->limit(15)->get(),
        ]);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'quantity' => 'required|integer',
            'type' => 'required|in:add,deduct',
            'reason' => 'required|string|max:255',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $quantityBefore = $product->stock_quantity;
        $quantityChange = $data['type'] === 'add' ? abs($data['quantity']) : -abs($data['quantity']);
        $quantityAfter = $quantityBefore + $quantityChange;

        if ($quantityAfter < 0) {
            return back()->withErrors(['quantity' => 'Cannot deduct more than current stock.'])->withInput();
        }

        DB::transaction(function () use ($product, $quantityChange, $quantityBefore, $quantityAfter, $data) {
            $product->update(['stock_quantity' => $quantityAfter]);

            StockAdjustment::create([
                'product_id' => $product->product_id,
                'quantity_change' => $quantityChange,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $data['reason'],
                'adjusted_by' => auth()->id(),
            ]);
        });

        return back()->with('status', 'Stock adjusted.');
    }
}

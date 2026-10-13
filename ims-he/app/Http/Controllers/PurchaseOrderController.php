<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    private const MAX_LINE_SUBTOTAL = 99999999.99;

    public function index(): View
    {
        return view('purchase-orders.index', [
            'purchaseOrders' => PurchaseOrder::with(['supplier', 'items'])
                ->orderByDesc('order_date')
                ->orderByDesc('po_id')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('purchase-orders.create', [
            'suppliers' => Supplier::orderBy('supplier_name')->get(),
            'products' => Product::orderBy('product_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,supplier_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,product_id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'items.*.unit_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ]);

        $lineSubtotals = [];
        foreach ($data['items'] as $index => $item) {
            $subtotal = round((int) $item['quantity'] * (float) $item['unit_cost'], 2);
            if ($subtotal > self::MAX_LINE_SUBTOTAL) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_cost" => 'The line subtotal cannot exceed 99,999,999.99.',
                ]);
            }

            $lineSubtotals[$index] = $subtotal;
        }

        $purchaseOrder = DB::transaction(function () use ($data, $lineSubtotals): PurchaseOrder {
            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'order_date' => today()->toDateString(),
                'status' => 'Pending',
            ]);

            foreach ($data['items'] as $index => $item) {
                PurchaseOrderItem::create([
                    'po_id' => $purchaseOrder->po_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'quantity_received' => 0,
                    'unit_cost' => $item['unit_cost'],
                    'subtotal' => $lineSubtotals[$index],
                ]);
            }

            return $purchaseOrder;
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('status', 'Purchase order created.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'items.product']);

        return view('purchase-orders.show', ['purchaseOrder' => $purchaseOrder]);
    }
}

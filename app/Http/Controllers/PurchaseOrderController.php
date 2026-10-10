<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ProcurementService;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('purchase_orders.index', [
            'orders' => PurchaseOrder::with('supplier', 'items')->latest('id')->paginate(15),
        ]);
    }

    public function create()
    {
        return view('purchase_orders.create', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'products'  => Product::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, ProcurementService $procurement)
    {
        $d = $request->validate([
            'supplier_id'         => 'required|exists:suppliers,id',
            'lines'               => 'required|array|min:1',
            'lines.*.product_id'  => 'required|exists:products,id',
            'lines.*.quantity'    => 'required|integer|min:1',
            'lines.*.unit_cost'   => 'required|numeric|min:0',
        ]);

        $po = $procurement->create((int) $d['supplier_id'], $d['lines']);

        return redirect()->route('purchase-orders.show', $po)->with('status', "PO #{$po->id} created (Pending).");
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        return view('purchase_orders.show', ['po' => $purchaseOrder->load('supplier', 'items.product')]);
    }
}

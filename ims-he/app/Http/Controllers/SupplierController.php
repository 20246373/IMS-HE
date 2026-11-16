<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    private const MESSAGES = ['supplier_name.unique' => 'A record with this name already exists.'];

    public function index(): View
    {
        return view('suppliers.index', [
            'suppliers' => Supplier::withCount('purchaseOrders')
                ->orderBy('supplier_name')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validate([
            'supplier_name' => ['required', 'max:100', 'unique:suppliers,supplier_name'],
            'contact_number' => ['required', 'max:14'],
            'address' => ['required', 'max:100'],
        ], self::MESSAGES));
        AuditLog::record('Created', 'suppliers', $supplier->supplier_id);

        return redirect()->route('suppliers.index')->with('status', 'Supplier added.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.form', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validate([
            'supplier_name' => ['required', 'max:100', 'unique:suppliers,supplier_name,'.$supplier->supplier_id.',supplier_id'],
            'contact_number' => ['required', 'max:14'],
            'address' => ['required', 'max:100'],
        ], self::MESSAGES));
        AuditLog::record('Updated', 'suppliers', $supplier->supplier_id);

        return redirect()->route('suppliers.index')->with('status', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchaseOrders()->exists()) {
            return back()->withErrors(['supplier' => 'Cannot delete — this supplier is linked to existing purchase orders.']);
        }

        $id = $supplier->supplier_id;
        $supplier->delete();
        AuditLog::record('Deleted', 'suppliers', $id);

        return redirect()->route('suppliers.index')->with('status', 'Supplier deleted.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
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
        Supplier::create($request->validate([
            'supplier_name' => ['required', 'max:100', 'unique:suppliers,supplier_name'],
            'contact_number' => ['required', 'max:14'],
            'address' => ['required', 'max:100'],
        ]));

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
        ]));

        return redirect()->route('suppliers.index')->with('status', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->purchaseOrders()->exists()) {
            return back()->withErrors(['supplier' => 'This supplier has purchase orders and cannot be deleted.']);
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('status', 'Supplier deleted.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    private function rules(?Supplier $s = null): array
    {
        return [
            'name'           => ['required', 'max:100', Rule::unique('suppliers', 'name')->ignore($s?->id)],
            'contact_number' => 'required|numeric|digits_between:7,14',
            'address'        => 'required|max:100',
        ];
    }

    public function index()
    {
        return view('suppliers.index', ['suppliers' => Supplier::orderBy('name')->paginate(15)]);
    }

    public function store(Request $request)
    {
        Supplier::create($request->validate($this->rules()));

        return redirect()->route('suppliers.index')->with('status', 'Supplier added.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($request->validate($this->rules($supplier)));

        return redirect()->route('suppliers.index')->with('status', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchaseOrders()->exists()) {
            return back()->withErrors(['supplier' => 'Supplier has purchase orders and cannot be deleted.']);
        }
        $supplier->delete();

        return redirect()->route('suppliers.index')->with('status', 'Supplier deleted.');
    }
}

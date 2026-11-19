<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view('customers.index', [
            'customers' => Customer::withCount('invoices')
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                    $query->where('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                }))
                ->orderBy('email')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function show(Customer $customer): View
    {
        return view('customers.show', [
            'customer' => $customer,
            'orders' => $customer->invoices()->orderByDesc('invoice_id')->get(),
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:100', Rule::unique('customers', 'email')->ignore($customer->customer_id, 'customer_id'), Rule::unique('users', 'email')],
            'first_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'contact_number' => ['nullable', 'digits_between:7,14'],
            'address' => ['nullable', 'string', 'max:100'],
        ], ['email.unique' => 'This email is already used by another account.']);

        DB::transaction(function () use ($customer, $data): void {
            $customer->update($data);
            AuditLog::record('Updated', 'customers', $customer->customer_id);
        });

        return redirect()->route('customers.show', $customer)->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $id = $customer->customer_id;
        $name = $customer->display_name;

        $customer->delete();
        AuditLog::record('Deleted', 'customers', $id);

        return redirect()->route('customers.index')
            ->with('status', "Customer {$name} deleted.");
    }
}

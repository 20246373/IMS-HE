<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * SDD 4.1 User Account Management: the Administrator manages every account in the system, including
 * the customers' storefront logins. Only account data is shown here; order history stays on the
 * business Customers page (President and Store Supervisor), keeping the Administrator out of business records.
 */
class CustomerAccountController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view('customer-accounts.index', [
            'customers' => Customer::query()
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

    public function edit(Customer $customer): View
    {
        return view('customer-accounts.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:100', Rule::unique('customers', 'email')->ignore($customer->customer_id, 'customer_id'), Rule::unique('users', 'email')],
            'first_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
        ], ['email.unique' => 'This email is already used by another account.']);

        DB::transaction(function () use ($customer, $data): void {
            $customer->update($data);
            AuditLog::record('Updated', 'customers', $customer->customer_id);
        });

        return redirect()->route('customer-accounts.index')->with('status', 'Customer account updated.');
    }

    /** Marks the email as verified, e.g. when a customer never received the verification email. */
    public function verify(Customer $customer): RedirectResponse
    {
        if (! $customer->hasVerifiedEmail()) {
            $customer->markEmailAsVerified();
            AuditLog::record('Verified', 'customers', $customer->customer_id);
        }

        return back()->with('status', "{$customer->email} can now log in.");
    }

    public function resetPassword(Request $request, Customer $customer): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'max:72', 'confirmed']]);

        $customer->update(['password' => $data['password']]);   // hashed by the model cast
        AuditLog::record('Password Reset', 'customers', $customer->customer_id);

        return back()->with('status', "Password reset for {$customer->email}.");
    }

    /** Past orders are kept (their customer_id becomes empty); the cart is removed with the account. */
    public function destroy(Customer $customer): RedirectResponse
    {
        $id = $customer->customer_id;
        $email = $customer->email;

        $customer->delete();
        AuditLog::record('Deleted', 'customers', $id);

        return redirect()->route('customer-accounts.index')->with('status', "Customer account {$email} deleted.");
    }
}

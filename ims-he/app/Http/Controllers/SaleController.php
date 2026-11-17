<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\User;
use App\Services\SaleService;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function create(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,category_id'],
        ]);
        $products = Product::with('category')
            ->where('is_active', true)
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('product_name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when(
                isset($filters['category_id']),
                fn ($query) => $query->where('category_id', $filters['category_id']),
            )
            ->orderBy('product_name')
            ->get();

        return view('sales.create', [
            'products' => $products,
            'categories' => Category::orderBy('category_name')->get(),
            'customers' => Customer::orderBy('first_name')->orderBy('last_name')->get(),
            'filters' => $filters,
        ]);
    }

    public function store(Request $request, SaleService $sales): RedirectResponse
    {
        $request->merge(['amount_paid' => Money::parse($request->input('amount_paid'))]);
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,product_id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::in(['Cash', 'GCash'])],
            'amount_paid' => ['required', 'numeric', 'gt:0'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,customer_id'],
        ]);

        $invoice = $sales->sell(
            $data['items'],
            $data['payment_method'],
            (float) $data['amount_paid'],
            $request->user(),
            isset($data['customer_id']) ? Customer::find($data['customer_id']) : null,
        );

        return redirect()->route('sales.show', $invoice)
            ->with('status', "Sale #{$invoice->invoice_id} recorded.");
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $invoices = Invoice::with(['customer', 'payment', 'employee'])
            ->where('channel', 'In-store')
            ->when(
                ! $user->hasRole(User::STORE_SUPERVISOR),
                fn ($query) => $user->employee_id === null
                    ? $query->whereNull('employee_id')
                    : $query->where('employee_id', $user->employee_id),
            )
            ->orderByDesc('invoice_id')
            ->paginate(20);

        return view('sales.index', ['invoices' => $invoices]);
    }

    public function show(Request $request, Invoice $invoice): View
    {
        abort_unless($invoice->channel === 'In-store', 404);

        $user = $request->user();
        abort_unless(
            $user->hasRole(User::STORE_SUPERVISOR)
                || (int) $invoice->employee_id === (int) $user->employee_id,
            403,
        );

        $cashier = $invoice->employee?->user;

        return view('sales.show', [
            'invoice' => $invoice->load('items.product', 'payment', 'customer', 'employee.user'),
            'cashierName' => $cashier?->label() ?: ($user->label() ?: 'System'),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosController extends Controller
{
    public function __construct(private SaleService $sale) {}

    public function index()
    {
        $submittedCarts = Cart::with(['customer', 'items.product'])
            ->where('status', 'Submitted')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('pos.index', [
            'submittedCarts' => $submittedCarts,
        ]);
    }

    public function processSubmittedCart(Request $request, $cartId)
    {
        $cart = Cart::with(['customer', 'items.product'])->findOrFail($cartId);

        if ($cart->status !== 'Submitted') {
            return back()->with('error', 'This cart is not available for processing.');
        }

        $request->validate([
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'required|in:Cash,Card,GCash,PayMaya',
        ]);

        try {
            $invoice = DB::transaction(function () use ($request, $cart) {
                $items = [];
                $totalAmount = 0;

                foreach ($cart->items as $item) {
                    $product = $item->product;

                    if ($item->quantity > $product->stock_quantity) {
                        throw new \Exception("Not enough stock for {$product->product_name}.");
                    }

                    $items[] = [
                        'product_id' => $product->product_id,
                        'quantity' => $item->quantity,
                    ];

                    $totalAmount += $item->quantity * $product->unit_price;
                }

                $invoice = $this->sale->checkout(
                    $items,
                    (float) $request->amount_paid,
                    $request->payment_method,
                    $cart->customer_id,
                    auth()->user()->employee_id ?? null,
                    auth()->id(),
                    'Online'
                );

                $invoice->update(['status' => 'Completed']);

                $cart->items()->delete();
                $cart->delete();

                return $invoice;
            });

            return redirect()->route('sales.show', $invoice)->with('status', 'Customer order processed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

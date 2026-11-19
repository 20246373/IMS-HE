<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

// Inventory Module, UC12 Add / UC13 Edit / UC14 Delete for products (SDD 4.1, 7.2.4).
// Roles: Inventory Clerk, Store Supervisor (see routes/web.php).
class ProductController extends Controller
{
    private const MESSAGES = [
        'product_name.unique' => 'A record with this name already exists.',
    ];

    public function __construct(private InventoryService $inventory) {}

    public function index(Request $request): View
    {
        return view('products.index', [
            'products' => Product::with('category')
                ->when($request->query('q'), fn ($q, $s) => $q->where(function ($query) use ($s) {
                    $query->where('product_name', 'like', "%{$s}%")
                        ->orWhere('brand', 'like', "%{$s}%");
                }))
                ->when($request->query('category'), fn ($q, $c) => $q->where('category_id', $c))
                ->orderBy('product_name')
                ->paginate(15)->withQueryString(),
            'categories' => Category::orderBy('category_name')->get(),
        ]);
    }

    public function stockHistory(Product $product): View
    {
        $transactions = InventoryTransaction::where('product_id', $product->product_id)
            ->orderByDesc('transaction_id')
            ->paginate(20);

        if ($transactions->isNotEmpty()) {
            $newerNet = (int) InventoryTransaction::where('product_id', $product->product_id)
                ->where('transaction_id', '>', $transactions->first()->transaction_id)
                ->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'IN' THEN quantity ELSE -quantity END), 0) AS net")
                ->value('net');

            $balance = (int) $product->stock_quantity - $newerNet;

            foreach ($transactions as $transaction) {
                $transaction->setAttribute('resulting_stock', $balance);
                $effect = $transaction->transaction_type === 'IN'
                    ? (int) $transaction->quantity
                    : -(int) $transaction->quantity;
                $balance -= $effect;
            }
        }

        return view('products.stock-history', compact('product', 'transactions'));
    }

    public function create(): View
    {
        return view('products.create', [
            'categories' => Category::orderBy('category_name')->get(),
            'suppliers' => Supplier::orderBy('supplier_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Guard against PHP engine upload limits (e.g. post_max_size exceeded)
        if ($message = $this->failedUploadMessage($request)) {
            return back()->withErrors(['image' => $message])->withInput();
        }

        $data = $request->validate($this->rules(), self::MESSAGES);
        $opening = (int) $data['stock_quantity'];

        $product = DB::transaction(function () use ($request, $data, $opening) {
            // Handle public disk upload
            $imagePath = null;
            if ($request->hasFile('image') && $request->file('image')->isValid()) {
                $imagePath = $request->file('image')->store('products', 'public');
            }

            // Create product record
            $product = Product::create(Arr::except($data, ['stock_quantity', 'image']) + [
                'stock_quantity' => 0,
                'image_path' => $imagePath,
            ]);

            if ($opening > 0) {
                $this->inventory->add($product->product_id, $opening, 'Adjustment', $product->product_id);
            }

            return $product;
        });

        AuditLog::record('Created', 'products', $product->product_id);

        return redirect()->route('products.index')->with('status', "Product {$product->product_name} added.");
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product' => $product,
            'categories' => Category::orderBy('category_name')->get(),
            'suppliers' => Supplier::orderBy('supplier_name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        // Product ID and stock quantity are not editable. Only validated fields are returned,
        // and the explicit exclusion keeps a tampered request from changing them.
        if ($message = $this->failedUploadMessage($request)) {
            return back()->withErrors(['image' => $message])->withInput();
        }

        $data = $request->validate($this->rules($product), self::MESSAGES);
        $attributes = Arr::except($data, ['image', 'stock_quantity', 'product_id']);

        if ($request->hasFile('image')) {
            $attributes['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($attributes);

        AuditLog::record('Updated', 'products', $product->product_id);

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Referential integrity (UC14): keep the product while any record still points at it,
        // and tell the user which kind of record is in the way.
        if ($blockedBy = $this->deletionBlocker($product)) {
            return back()->withErrors(['product' => "Cannot delete — this product is {$blockedBy}."]);
        }

        $id = $product->product_id;

        try {
            DB::transaction(function () use ($product) {
                InventoryTransaction::where('product_id', $product->product_id)->delete();   // only its own Adjustment rows remain
                $product->delete();
            });
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['product' => 'The product could not be deleted. Please try again.']);
        }

        AuditLog::record('Deleted', 'products', $id);

        return redirect()->route('products.index')->with('status', 'Product deleted.');
    }

    /** Returns what still references the product, or null when it is safe to delete. */
    private function deletionBlocker(Product $product): ?string
    {
        if (InvoiceItem::where('product_id', $product->product_id)->exists()) {
            return 'used in existing invoice items';
        }

        if (CartItem::where('product_id', $product->product_id)->exists()) {
            return 'in a customer cart';
        }

        if (PurchaseOrderItem::where('product_id', $product->product_id)->exists()) {
            return 'used in existing purchase order items';
        }

        if (InventoryTransaction::where('product_id', $product->product_id)->where('reference_type', '!=', 'Adjustment')->exists()) {
            return 'linked to existing stock movements from sales, purchases or deliveries';
        }

        return null;
    }

    // Returns a user-facing message when PHP rejected the upload before Laravel saw it
    // (size limits, partial upload, no temp dir), or null when the upload is fine/absent.
    private function failedUploadMessage(Request $request): ?string
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            if (! $file->isValid()) {
                // This will tell us the exact PHP upload error code
                return 'Upload failed with PHP error code: '.$file->getError().' ('.$file->getErrorMessage().')';
            }
        }

        // Check if POST data was completely emptied (happens when post_max_size is exceeded)
        if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            return 'The uploaded file exceeds the post_max_size directive in php.ini.';
        }

        return null;
    }

    private function rules(?Product $product = null): array
    {
        $rules = [
            'product_name' => ['required', 'string', 'max:100', Rule::unique('products', 'product_name')->ignore($product?->product_id, 'product_id')],
            'brand' => ['required', 'string', 'max:50'],
            'category_id' => ['required', 'integer', 'exists:categories,category_id'],
            'description' => ['nullable', 'string', 'max:255'],
            'unit_price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,supplier_id'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];

        // Opening stock is only entered when the product is created. On edit it is read-only.
        if ($product === null) {
            $rules['stock_quantity'] = ['required', 'integer', 'min:0', 'max:4294967295'];
        }

        return $rules;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
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
                ->when($request->query('q'), fn ($q, $s) => $q->where('product_name', 'like', "%{$s}%"))
                ->when($request->query('category'), fn ($q, $c) => $q->where('category_id', $c))
                ->orderByDesc('is_active')->orderBy('product_name')
                ->paginate(15)->withQueryString(),
            'categories' => Category::orderBy('category_name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('products.create', ['categories' => Category::orderBy('category_name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(), self::MESSAGES);
        $opening = (int) $data['stock_quantity'];

        $product = DB::transaction(function () use ($request, $data, $opening) {
            // Create at 0, then let InventoryService add the opening stock so the movement is logged (UC12 includes UC05).
            $product = Product::create(Arr::except($data, ['stock_quantity', 'image']) + [
                'stock_quantity' => 0,
                'is_active' => true,
                'image_path' => $request->hasFile('image') ? $request->file('image')->store('products', 'public') : null,
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
        return view('products.edit', ['product' => $product, 'categories' => Category::orderBy('category_name')->get()]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate($this->rules($product), self::MESSAGES);
        $target = (int) $data['stock_quantity'];

        DB::transaction(function () use ($request, $product, $data, $target) {
            $attributes = Arr::except($data, ['stock_quantity', 'image']);
            if ($request->hasFile('image')) {
                $attributes['image_path'] = $request->file('image')->store('products', 'public');
            }
            $product->update($attributes);

            // Manual stock correction goes through InventoryService so inventory_transactions stays accurate.
            $diff = $target - (int) $product->refresh()->stock_quantity;
            if ($diff > 0) {
                $this->inventory->add($product->product_id, $diff, 'Adjustment', $product->product_id);
            } elseif ($diff < 0) {
                $this->inventory->deduct($product->product_id, abs($diff), 'Adjustment', $product->product_id);
            }
        });

        AuditLog::record('Updated', 'products', $product->product_id);

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    /** Hide / show a product (is_active) without deleting it. */
    public function toggle(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);
        AuditLog::record($product->is_active ? 'Activated' : 'Deactivated', 'products', $product->product_id);

        return back()->with('status', $product->is_active ? 'Product is visible again.' : 'Product hidden from the catalog.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Referential integrity (UC14): sales lines, purchase order lines and non-adjustment stock movements block deletion.
        $linked = InvoiceItem::where('product_id', $product->product_id)->exists()
            || PurchaseOrderItem::where('product_id', $product->product_id)->exists()
            || InventoryTransaction::where('product_id', $product->product_id)->where('reference_type', '!=', 'Adjustment')->exists();

        if ($linked) {
            return back()->withErrors(['product' => 'Cannot delete — this product is linked to existing transactions.']);
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

    private function rules(?Product $product = null): array
    {
        return [
            'product_name'   => ['required', 'string', 'max:100', Rule::unique('products', 'product_name')->ignore($product?->product_id, 'product_id')],
            'category_id'    => ['required', 'integer', 'exists:categories,category_id'],
            'description'    => ['nullable', 'string', 'max:255'],
            'unit_price'     => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'reorder_level'  => ['required', 'integer', 'min:0', 'max:4294967295'],
            'image'          => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}

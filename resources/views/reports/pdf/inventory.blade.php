<!doctype html>
<html lang="en">
<head>
 <meta charset="utf-8">
 <style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
  h1 { font-size: 18px; margin-bottom: 4px; }
  p { color: #555; margin-top: 0; }
  .summary { margin: 18px 0; }
  .summary span { display: inline-block; margin-right: 28px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border-bottom: 1px solid #ddd; padding: 6px; text-align: left; }
  th { background: #f0f2f5; }
  .amount, .quantity { text-align: right; }
  .low { color: #a33; font-weight: bold; }
 </style>
</head>
<body>
 <h1>Inventory / stock summary</h1>
 <p>Generated {{ now()->format('M d, Y') }}</p>
 <div class="summary">
  <span><strong>Total stock valuation:</strong> &#8369;{{ number_format((float) $report['totalValuation'], 2) }}</span>
  <span><strong>Low-stock alerts:</strong> {{ number_format($report['lowStockCount']) }}</span>
 </div>
 <table>
  <thead><tr><th>Product</th><th>Category</th><th class="quantity">Stock on hand</th><th class="quantity">Reorder level</th><th class="amount">Unit price</th><th class="amount">Stock value</th><th>Status</th></tr></thead>
  <tbody>
   @forelse($report['products'] as $product)
   @php($isLowStock = $product->stock_quantity <= $product->reorder_level)
   <tr>
    <td>{{ $product->product_name }}</td>
    <td>{{ $product->category_name ?? 'Uncategorized' }}</td>
    <td class="quantity">{{ number_format($product->stock_quantity) }}</td>
    <td class="quantity">{{ number_format($product->reorder_level) }}</td>
    <td class="amount">&#8369;{{ number_format((float) $product->unit_price, 2) }}</td>
    <td class="amount">&#8369;{{ number_format((float) $product->stock_value, 2) }}</td>
    <td class="{{ $isLowStock ? 'low' : '' }}">{{ $isLowStock ? 'Low stock' : 'In stock' }}</td>
   </tr>
   @empty
   <tr><td colspan="7">No products found.</td></tr>
   @endforelse
  </tbody>
 </table>
</body>
</html>

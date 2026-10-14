@extends('layouts.app')
@section('title', 'Inventory report')
@section('content')
<div class="card">
 <div class="report-heading">
  <h2>Inventory / stock summary</h2>
  <a class="btn no-print" href="{{ route('reports.inventory.download') }}">Save as PDF</a>
 </div>
 <div class="report-summary">
  <div><strong>Total stock valuation</strong><span>&#8369;{{ number_format((float) $report['totalValuation'], 2) }}</span></div>
  <div><strong>Low-stock alerts</strong><span>{{ number_format($report['lowStockCount']) }}</span></div>
 </div>
 <table>
  <thead><tr><th>Product</th><th>Category</th><th>Stock on hand</th><th>Reorder level</th><th>Unit price</th><th>Stock value</th><th>Status</th></tr></thead>
  <tbody>
   @forelse($report['products'] as $product)
   @php($isLowStock = $product->stock_quantity <= $product->reorder_level)
   <tr>
    <td>{{ $product->product_name }}</td>
    <td>{{ $product->category_name ?? 'Uncategorized' }}</td>
    <td>{{ number_format($product->stock_quantity) }}</td>
    <td>{{ number_format($product->reorder_level) }}</td>
    <td>&#8369;{{ number_format((float) $product->unit_price, 2) }}</td>
    <td>&#8369;{{ number_format((float) $product->stock_value, 2) }}</td>
    <td class="{{ $isLowStock ? 'low' : '' }}">{{ $isLowStock ? 'Low stock' : 'In stock' }}</td>
   </tr>
   @empty
   <tr><td colspan="7">No products found.</td></tr>
   @endforelse
  </tbody>
 </table>
</div>
@endsection

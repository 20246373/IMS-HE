@extends('layouts.app')
@section('title', 'Inventory report')
@section('heading', 'Inventory Report')
@section('page-actions')
  <a class="btn"
     href="{{ route('reports.inventory.download', array_filter(['category_id' => $categoryId])) }}">
   Save as PDF
  </a>
@endsection
@section('content')
<div class="card">
 <form method="GET" action="{{ route('reports.inventory') }}" class="no-print report-filter">
  <label>Category
   <select name="category_id">
    <option value="">All categories</option>
    @foreach($categories as $category)
     <option value="{{ $category->category_id }}"
       @selected((int) $categoryId === (int) $category->category_id)>
      {{ $category->category_name }}
     </option>
    @endforeach
   </select>
  </label>
  <button type="submit">Apply filter</button>
 </form>
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

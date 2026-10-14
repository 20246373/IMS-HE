@extends('layouts.app')
@section('title','Inventory Report')
@section('content')
<div class="card">
 <h3>Inventory Report</h3>
 <form method="GET" action="{{ route('reports.inventory') }}" style="margin-bottom:1rem">
  <label><input type="checkbox" name="low" value="1" {{ request('low') ? 'checked' : '' }}> Low stock only</label>
  <button type="submit">Filter</button>
  <a href="{{ route('reports.inventory') }}" class="btn btn-alt">Clear</a>
 </form>
 <p><strong>Total Stock:</strong> {{ $totalStock }} units</p>
 <p><strong>Total Value:</strong> {{ number_format($totalValue, 2) }}</p>
 <p><strong>Low Stock Items:</strong> {{ $lowStockCount }}</p>
 <table>
  <thead><tr><th>Product</th><th>Category</th><th>Stock</th><th>Reorder Level</th><th>Unit Price</th><th>Total Value</th></tr></thead>
  <tbody>
   @foreach($products as $product)
    <tr @if($product->isLowStock()) class="low"@endif>
     <td>{{ $product->product_name }}</td>
     <td>{{ $product->category?->category_name ?? 'N/A' }}</td>
     <td>{{ $product->stock_quantity }}</td>
     <td>{{ $product->reorder_level }}</td>
     <td>{{ number_format($product->unit_price, 2) }}</td>
     <td>{{ number_format($product->stock_quantity * $product->unit_price, 2) }}</td>
    </tr>
   @endforeach
  </tbody>
 </table>
 <p style="margin-top:1rem">
  <button onclick="window.print()">Print</button>
  @if(auth()->user()->hasRole('Admin'))
   <a href="{{ route('reports.export', ['type' => 'inventory']) }}" class="btn btn-alt">Export CSV</a>
  @endif
 </p>
</div>
@endsection

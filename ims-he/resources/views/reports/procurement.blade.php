@extends('layouts.app')
@section('title', 'Procurement report')
@section('content')
<div class="card">
 <div class="report-heading">
  <h2>Procurement report</h2>
  <span class="no-print">
   <button type="button" onclick="window.print()">Print</button>
   <a class="btn"
     href="{{ route('reports.procurement.download', array_filter([
         'start_date' => $startDate,
         'end_date' => $endDate,
         'supplier_id' => $supplierId,
         'category_id' => $categoryId,
     ])) }}">
    Save as PDF
   </a>
  </span>
 </div>

 <form method="GET" action="{{ route('reports.procurement') }}" class="no-print report-filter">
  <label>From
   <input type="date" name="start_date" value="{{ $startDate }}" required>
  </label>
  <label>To
   <input type="date" name="end_date" value="{{ $endDate }}" required>
  </label>
  <label>Supplier
   <select name="supplier_id">
    <option value="">All suppliers</option>
    @foreach($suppliers as $supplier)
     <option value="{{ $supplier->supplier_id }}"
       @selected((int) $supplierId === (int) $supplier->supplier_id)>
      {{ $supplier->supplier_name }}
     </option>
    @endforeach
   </select>
  </label>
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
  <div><strong>Purchase orders</strong><span>{{ number_format($report['summary']['purchaseOrderCount']) }}</span></div>
  <div><strong>Ordered value</strong><span>{{ money($report['summary']['orderedValue']) }}</span></div>
  <div><strong>Received value</strong><span>{{ money($report['summary']['receivedValue']) }}</span></div>
  <div><strong>Deliveries</strong><span>{{ number_format($report['summary']['deliveryCount']) }}</span></div>
  <div><strong>Units delivered</strong><span>{{ number_format($report['summary']['deliveredUnits']) }}</span></div>
 </div>

 <h3>Purchase orders</h3>
 <table>
  <thead>
   <tr>
    <th>PO #</th>
    <th>Supplier</th>
    <th>Order date</th>
    <th>Status</th>
    <th>Ordered qty</th>
    <th>Received qty</th>
    <th>Ordered value</th>
    <th>Received value</th>
   </tr>
  </thead>
  <tbody>
   @forelse($report['purchaseOrders'] as $purchaseOrder)
    <tr>
     <td><a href="{{ route('purchase-orders.show', $purchaseOrder->po_id) }}">PO #{{ $purchaseOrder->po_id }}</a></td>
     <td>{{ $purchaseOrder->supplier_name }}</td>
     <td>{{ \Illuminate\Support\Carbon::parse($purchaseOrder->order_date)->format('M d, Y') }}</td>
     <td>{{ $purchaseOrder->status }}</td>
     <td>{{ number_format($purchaseOrder->ordered_quantity) }}</td>
     <td>{{ number_format($purchaseOrder->received_quantity) }}</td>
     <td>{{ money($purchaseOrder->ordered_value) }}</td>
     <td>{{ money($purchaseOrder->received_value) }}</td>
    </tr>
   @empty
    <tr><td colspan="8">No purchase orders found.</td></tr>
   @endforelse
  </tbody>
 </table>

 <h3>Purchase order items</h3>
 <table>
  <thead>
   <tr>
    <th>PO #</th>
    <th>Product</th>
    <th>Category</th>
    <th>Ordered</th>
    <th>Received</th>
    <th>Unit cost</th>
    <th>Subtotal</th>
   </tr>
  </thead>
  <tbody>
   @forelse($report['orderItems'] as $item)
    <tr>
     <td>PO #{{ $item->po_id }}</td>
     <td>{{ $item->product_name }}</td>
     <td>{{ $item->category_name ?? 'Uncategorized' }}</td>
     <td>{{ number_format($item->quantity) }}</td>
     <td>{{ number_format($item->quantity_received) }}</td>
     <td>{{ money($item->unit_cost) }}</td>
     <td>{{ money($item->subtotal) }}</td>
    </tr>
   @empty
    <tr><td colspan="7">No purchase order items found.</td></tr>
   @endforelse
  </tbody>
 </table>

 <h3>Deliveries</h3>
 <table>
  <thead>
   <tr>
    <th>Date</th>
    <th>PO #</th>
    <th>Supplier</th>
    <th>Product</th>
    <th>Category</th>
    <th>Quantity</th>
   </tr>
  </thead>
  <tbody>
   @forelse($report['deliveries'] as $delivery)
    <tr>
     <td>{{ \Illuminate\Support\Carbon::parse($delivery->transaction_date)->format('M d, Y') }}</td>
     <td>PO #{{ $delivery->po_id }}</td>
     <td>{{ $delivery->supplier_name }}</td>
     <td>{{ $delivery->product_name }}</td>
     <td>{{ $delivery->category_name ?? 'Uncategorized' }}</td>
     <td>{{ number_format($delivery->quantity) }}</td>
    </tr>
   @empty
    <tr><td colspan="6">No deliveries found.</td></tr>
   @endforelse
  </tbody>
 </table>

 <h3>Supplier spend</h3>
 <table>
  <thead>
   <tr>
    <th>Supplier</th>
    <th>Purchase orders</th>
    <th>Ordered value</th>
    <th>Received value</th>
   </tr>
  </thead>
  <tbody>
   @forelse($report['supplierSpend'] as $supplier)
    <tr>
     <td>{{ $supplier->supplier_name }}</td>
     <td>{{ number_format($supplier->po_count) }}</td>
     <td>{{ money($supplier->ordered_value) }}</td>
     <td>{{ money($supplier->received_value) }}</td>
    </tr>
   @empty
    <tr><td colspan="4">No supplier spend found.</td></tr>
   @endforelse
  </tbody>
 </table>
</div>
@endsection

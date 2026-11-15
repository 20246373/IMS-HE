<!doctype html>
<html lang="en">
<head>
 <meta charset="utf-8">
 <style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #222; }
  h1 { font-size: 18px; margin-bottom: 4px; }
  h2 { font-size: 12px; margin: 16px 0 6px; }
  p { color: #555; margin-top: 0; }
  .summary { margin: 14px 0; }
  .summary span { display: inline-block; margin-right: 20px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
  th, td { border-bottom: 1px solid #ddd; padding: 5px; text-align: left; }
  th { background: #f0f2f5; }
  .amount, .quantity { text-align: right; }
 </style>
</head>
<body>
 <h1>Procurement report</h1>
 <p>{{ \Illuminate\Support\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($endDate)->format('M d, Y') }}</p>

 <div class="summary">
  <span><strong>Purchase orders:</strong> {{ number_format($report['summary']['purchaseOrderCount']) }}</span>
  <span><strong>Ordered value:</strong> {{ money($report['summary']['orderedValue']) }}</span>
  <span><strong>Received value:</strong> {{ money($report['summary']['receivedValue']) }}</span>
  <span><strong>Deliveries:</strong> {{ number_format($report['summary']['deliveryCount']) }}</span>
  <span><strong>Units delivered:</strong> {{ number_format($report['summary']['deliveredUnits']) }}</span>
 </div>

 <h2>Purchase orders</h2>
 <table>
  <thead>
   <tr>
    <th>PO #</th>
    <th>Supplier</th>
    <th>Order date</th>
    <th>Status</th>
    <th class="quantity">Ordered qty</th>
    <th class="quantity">Received qty</th>
    <th class="amount">Ordered value</th>
    <th class="amount">Received value</th>
   </tr>
  </thead>
  <tbody>
   @forelse($report['purchaseOrders'] as $purchaseOrder)
    <tr>
     <td>PO #{{ $purchaseOrder->po_id }}</td>
     <td>{{ $purchaseOrder->supplier_name }}</td>
     <td>{{ \Illuminate\Support\Carbon::parse($purchaseOrder->order_date)->format('M d, Y') }}</td>
     <td>{{ $purchaseOrder->status }}</td>
     <td class="quantity">{{ number_format($purchaseOrder->ordered_quantity) }}</td>
     <td class="quantity">{{ number_format($purchaseOrder->received_quantity) }}</td>
     <td class="amount">{{ money($purchaseOrder->ordered_value) }}</td>
     <td class="amount">{{ money($purchaseOrder->received_value) }}</td>
    </tr>
   @empty
    <tr><td colspan="8">No purchase orders found.</td></tr>
   @endforelse
  </tbody>
 </table>

 <h2>Purchase order items</h2>
 <table>
  <thead>
   <tr>
    <th>PO #</th>
    <th>Product</th>
    <th>Category</th>
    <th class="quantity">Ordered</th>
    <th class="quantity">Received</th>
    <th class="amount">Unit cost</th>
    <th class="amount">Subtotal</th>
   </tr>
  </thead>
  <tbody>
   @forelse($report['orderItems'] as $item)
    <tr>
     <td>PO #{{ $item->po_id }}</td>
     <td>{{ $item->product_name }}</td>
     <td>{{ $item->category_name ?? 'Uncategorized' }}</td>
     <td class="quantity">{{ number_format($item->quantity) }}</td>
     <td class="quantity">{{ number_format($item->quantity_received) }}</td>
     <td class="amount">{{ money($item->unit_cost) }}</td>
     <td class="amount">{{ money($item->subtotal) }}</td>
    </tr>
   @empty
    <tr><td colspan="7">No purchase order items found.</td></tr>
   @endforelse
  </tbody>
 </table>

 <h2>Deliveries</h2>
 <table>
  <thead>
   <tr>
    <th>Date</th>
    <th>PO #</th>
    <th>Supplier</th>
    <th>Product</th>
    <th>Category</th>
    <th class="quantity">Quantity</th>
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
     <td class="quantity">{{ number_format($delivery->quantity) }}</td>
    </tr>
   @empty
    <tr><td colspan="6">No deliveries found.</td></tr>
   @endforelse
  </tbody>
 </table>

 <h2>Supplier spend</h2>
 <table>
  <thead>
   <tr>
    <th>Supplier</th>
    <th class="quantity">Purchase orders</th>
    <th class="amount">Ordered value</th>
    <th class="amount">Received value</th>
   </tr>
  </thead>
  <tbody>
   @forelse($report['supplierSpend'] as $supplier)
    <tr>
     <td>{{ $supplier->supplier_name }}</td>
     <td class="quantity">{{ number_format($supplier->po_count) }}</td>
     <td class="amount">{{ money($supplier->ordered_value) }}</td>
     <td class="amount">{{ money($supplier->received_value) }}</td>
    </tr>
   @empty
    <tr><td colspan="4">No supplier spend found.</td></tr>
   @endforelse
  </tbody>
 </table>
</body>
</html>

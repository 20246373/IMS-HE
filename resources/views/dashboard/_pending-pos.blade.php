<div class="card">
 <h3>Pending Purchase Orders <span>({{ $pendingPurchaseOrderCount }})</span></h3>
 @if($pendingPurchaseOrders->isEmpty())
  <p>No pending purchase orders.</p>
 @else
  <table>
   <tr><th>PO</th><th>Supplier</th><th>Date</th><th>Total</th></tr>
   @foreach($pendingPurchaseOrders as $purchaseOrder)
   <tr>
    <td><a href="{{ route('purchase-orders.show', $purchaseOrder) }}">PO #{{ $purchaseOrder->po_id }}</a></td>
    <td>{{ $purchaseOrder->supplier->supplier_name }}</td>
    <td>{{ $purchaseOrder->order_date->format('M d, Y') }}</td>
    <td>&#8369;{{ number_format($purchaseOrder->items->sum('subtotal'), 2) }}</td>
   </tr>
   @endforeach
  </table>
 @endif
</div>

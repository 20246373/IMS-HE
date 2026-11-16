@extends('layouts.app')
@section('title','Receive delivery')
@section('content')
<div class="card">
 <h2>Receive delivery</h2>
 <form method="GET" action="{{ route('deliveries.create') }}">
  <label>Pending purchase order
   <select name="purchase_order_id" required>
    <option value="">Select a purchase order</option>
    @foreach($purchaseOrders as $purchaseOrder)
     <option value="{{ $purchaseOrder->po_id }}" @selected(request('purchase_order_id') == $purchaseOrder->po_id)>
      PO #{{ $purchaseOrder->po_id }} — {{ $purchaseOrder->supplier->supplier_name }} ({{ $purchaseOrder->order_date->format('M d, Y') }})
     </option>
    @endforeach
   </select>
  </label>
  <button>Load purchase order</button>
 </form>
 @if($purchaseOrders->isEmpty())
  <p>There are no pending purchase orders to receive.</p>
 @endif

 @if($selectedOrder)
 <h3>PO #{{ $selectedOrder->po_id }} — {{ $selectedOrder->supplier->supplier_name }}</h3>
 <form method="POST" action="{{ route('deliveries.store') }}" id="delivery-form">
  @csrf
  <input type="hidden" name="purchase_order_id" value="{{ $selectedOrder->po_id }}">
  <table>
   <tr><th>Product</th><th>Ordered</th><th>Previously received</th><th>Remaining</th><th>Receive now</th></tr>
   @foreach($selectedOrder->items as $index => $item)
   @php($remaining = max(0, $item->quantity - $item->quantity_received))
   <tr>
    <td>{{ $item->product->product_name }}</td>
    <td>{{ $item->quantity }}</td>
    <td>{{ $item->quantity_received }}</td>
    <td>{{ $remaining }}</td>
    <td>
     <input type="hidden" name="items[{{ $index }}][po_item_id]" value="{{ $item->po_item_id }}">
     <input
      type="number"
      name="items[{{ $index }}][quantity_received]"
      min="0"
      max="{{ $remaining }}"
      step="1"
      value="{{ old('items.' . $index . '.quantity_received', 0) }}"
      data-remaining="{{ $remaining }}"
      aria-describedby="delivery-error-{{ $index }}"
      required
     >
     <small id="delivery-error-{{ $index }}" class="low delivery-error" aria-live="polite"></small>
    </td>
   </tr>
   @endforeach
  </table>
  <p><button>Confirm delivery</button></p>
 </form>
 <script>
  (() => {
   const form = document.getElementById('delivery-form');
   const quantityInputs = form.querySelectorAll('input[name$="[quantity_received]"]');

   const validateQuantity = (input) => {
    const remaining = Number(input.dataset.remaining);
    const quantity = Number(input.value);
    const invalid = !Number.isInteger(quantity) || quantity < 0 || quantity > remaining;
    input.setCustomValidity(invalid ? `Enter a whole quantity from 0 to ${remaining}.` : '');
    input.closest('td').querySelector('.delivery-error').textContent = invalid
     ? `Cannot receive more than ${remaining} remaining.`
     : '';
    return !invalid;
   };

   quantityInputs.forEach((input) => input.addEventListener('input', () => validateQuantity(input)));
   form.addEventListener('submit', (event) => {
    if (![...quantityInputs].every(validateQuantity)) {
     event.preventDefault();
     form.reportValidity();
    }
   });
  })();
 </script>
 @endif
</div>
@endsection

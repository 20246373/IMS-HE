@extends('layouts.app')
@section('title','New purchase order')
@section('content')
<div class="card">
 <h2>New purchase order</h2>
 @if($suppliers->isEmpty() || $products->isEmpty())
  <p>A supplier and at least one product are required before creating a purchase order.</p>
 @else
 <form method="POST" action="{{ route('purchase-orders.store') }}" id="purchase-order-form">
  @csrf
  <label>Supplier
   <select name="supplier_id" required>
    <option value="">Select a supplier</option>
    @foreach($suppliers as $supplier)
     <option value="{{ $supplier->supplier_id }}" @selected(old('supplier_id') == $supplier->supplier_id)>{{ $supplier->supplier_name }}</option>
    @endforeach
   </select>
  </label>
  <h3>Products</h3>
  <table>
   <thead><tr><th>Product</th><th>Quantity</th><th>Unit cost</th><th>Subtotal</th><th></th></tr></thead>
   <tbody id="purchase-order-lines">
    @php($oldItems = old('items'))
    @if(!is_array($oldItems) || $oldItems === [])
     @php($oldItems = [['product_id' => '', 'quantity' => 1, 'unit_cost' => '0.00']])
    @endif
    @php($lineIndex = 0)
    @foreach($oldItems as $line)
    @php($line = is_array($line) ? $line : [])
    <tr class="purchase-order-line">
     <td>
      <select name="items[{{ $lineIndex }}][product_id]" required>
       <option value="">Select a product</option>
       @foreach($products as $product)
        <option value="{{ $product->product_id }}" @selected(($line['product_id'] ?? '') == $product->product_id)>{{ $product->product_name }}</option>
       @endforeach
      </select>
     </td>
     <td><input type="number" name="items[{{ $lineIndex }}][quantity]" min="1" step="1" value="{{ $line['quantity'] ?? 1 }}" required></td>
     <td><input type="number" name="items[{{ $lineIndex }}][unit_cost]" min="0" max="99999999.99" step="0.01" value="{{ $line['unit_cost'] ?? '0.00' }}" required></td>
     <td class="line-subtotal">&#8369;0.00</td>
     <td><button type="button" class="danger remove-line">Remove</button></td>
    </tr>
    @php($lineIndex++)
    @endforeach
   </tbody>
  </table>
  <p><button type="button" id="add-line">Add product line</button></p>
  <p><strong>PO total: <span id="purchase-order-total">&#8369;0.00</span></strong></p>
  <button>Create purchase order</button>
  <a class="btn" href="{{ route('purchase-orders.index') }}">Cancel</a>
 </form>
 <template id="purchase-order-line-template">
  <tr class="purchase-order-line">
   <td>
    <select required>
     <option value="">Select a product</option>
     @foreach($products as $product)
      <option value="{{ $product->product_id }}">{{ $product->product_name }}</option>
     @endforeach
    </select>
   </td>
   <td><input type="number" min="1" step="1" value="1" required></td>
   <td><input type="number" min="0" max="99999999.99" step="0.01" value="0.00" required></td>
   <td class="line-subtotal">&#8369;0.00</td>
   <td><button type="button" class="danger remove-line">Remove</button></td>
  </tr>
 </template>
 <script>
  (() => {
   const lines = document.getElementById('purchase-order-lines');
   const template = document.getElementById('purchase-order-line-template');
   let nextIndex = {{ $lineIndex }};

   const updateTotals = () => {
    let total = 0;
    lines.querySelectorAll('.purchase-order-line').forEach((line) => {
     const quantity = Number(line.querySelector('[name$="[quantity]"]').value) || 0;
     const unitCost = Number(line.querySelector('[name$="[unit_cost]"]').value) || 0;
     const subtotal = quantity * unitCost;
     total += subtotal;
     line.querySelector('.line-subtotal').textContent = '\u20b9' + subtotal.toFixed(2);
    });
    document.getElementById('purchase-order-total').textContent = '\u20b9' + total.toFixed(2);
   };

   document.getElementById('add-line').addEventListener('click', () => {
    const row = template.content.firstElementChild.cloneNode(true);
    row.querySelector('select').name = `items[${nextIndex}][product_id]`;
    row.querySelectorAll('input')[0].name = `items[${nextIndex}][quantity]`;
    row.querySelectorAll('input')[1].name = `items[${nextIndex}][unit_cost]`;
    nextIndex += 1;
    lines.appendChild(row);
    updateTotals();
   });

   lines.addEventListener('input', updateTotals);
   lines.addEventListener('click', (event) => {
    if (event.target.matches('.remove-line')) {
     if (lines.querySelectorAll('.purchase-order-line').length > 1) {
      event.target.closest('.purchase-order-line').remove();
      updateTotals();
     }
    }
   });

   updateTotals();
  })();
 </script>
 @endif
</div>
@endsection

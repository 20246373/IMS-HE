@extends('layouts.app')
@section('title', 'Sales POS')
@section('content')
<div class="card">
 <h2>Sales (POS)</h2>
 <x-required-note />
 <form method="GET" action="{{ route('sales.create') }}" class="report-filter no-print">
  <x-field label="Search product" name="q" :value="$filters['q'] ?? null" :required="false" placeholder="Name or brand" />
  <x-select label="Category" name="category_id" :required="false">
   <option value="">All categories</option>
   @foreach($categories as $category)
    <option value="{{ $category->category_id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->category_id)>{{ $category->category_name }}</option>
   @endforeach
  </x-select>
  <button type="submit">Search</button>
 </form>

 <div class="pos-grid">
  <section>
   <h3>Products</h3>
   <div class="pos-products">
    @forelse($products as $product)
     <div class="pos-product">
      <strong>{{ $product->product_name }}</strong>
      <small>{{ $product->brand ?: 'No brand' }} &middot; {{ $product->category?->category_name }}</small>
      <span>{{ money($product->unit_price) }} &middot; Stock: {{ $product->stock_quantity }}</span>
      @if($product->stock_quantity > 0)
       <button type="button" class="add-product" data-product-id="{{ $product->product_id }}" data-product-name="{{ $product->product_name }}" data-price="{{ $product->unit_price }}">Add</button>
      @else
       <button type="button" disabled>Out of stock</button>
      @endif
     </div>
    @empty
     <p>No products found.</p>
    @endforelse
   </div>
  </section>

  <section>
   <h3>Current sale</h3>
   <form method="POST" action="{{ route('sales.store') }}" id="sale-form">
    @csrf
    <table>
     <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
     <tbody id="sale-lines"><tr id="empty-lines"><td colspan="5">Add products to begin.</td></tr></tbody>
     <tfoot><tr><th colspan="3" style="text-align:right">Total</th><th id="sale-total">₱0.00</th><th></th></tr></tfoot>
    </table>
    <div id="sale-fields"></div>
    <div class="form-grid">
     <x-field label="Amount paid" name="amount_paid" prefix="₱" inputmode="decimal" />
     <x-select label="Payment method" name="payment_method"><option value="Cash">Cash</option><option value="GCash">GCash</option></x-select>
     <x-select label="Registered customer" name="customer_id" :required="false">
      <option value="">Walk-in customer</option>
      @foreach($customers as $customer)
       <option value="{{ $customer->customer_id }}">{{ $customer->display_name }} ({{ $customer->email }})</option>
      @endforeach
     </x-select>
    </div>
    <p>Change due: <strong id="change-due">₱0.00</strong></p>
    <button type="submit">Complete sale</button>
   </form>
  </section>
 </div>
</div>
@push('head')
<style>
 .pos-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.3fr);gap:1rem}
 .pos-products{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:.6rem}
 .pos-product{display:grid;gap:.25rem;padding:.7rem;border:1px solid #ddd;border-radius:6px}
 .pos-product small{color:#666}.pos-product button{margin-top:.25rem}
 @media(max-width:800px){.pos-grid{grid-template-columns:1fr}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
 const lines = new Map(), tbody = document.getElementById('sale-lines'), fields = document.getElementById('sale-fields');
 const paid = document.querySelector('[name="amount_paid"]'), total = document.getElementById('sale-total'), change = document.getElementById('change-due');
 const money = value => new Intl.NumberFormat('en-PH', {style:'currency', currency:'PHP'}).format(value);
 function render() {
  tbody.innerHTML = ''; fields.innerHTML = ''; let sum = 0, index = 0;
  lines.forEach((line, id) => {
   const subtotal = line.price * line.quantity; sum += subtotal;
   const row = document.createElement('tr');
   row.innerHTML = `<td>${line.name}</td><td>${money(line.price)}</td><td><input type="number" min="1" value="${line.quantity}" data-qty="${id}" style="width:4rem"></td><td>${money(subtotal)}</td><td><button type="button" class="danger" data-remove="${id}">Remove</button></td>`;
   tbody.appendChild(row);
   fields.insertAdjacentHTML('beforeend', `<input type="hidden" name="items[${index}][product_id]" value="${id}"><input type="hidden" name="items[${index}][quantity]" value="${line.quantity}">`);
   index++;
  });
  if (!lines.size) tbody.innerHTML = '<tr id="empty-lines"><td colspan="5">Add products to begin.</td></tr>';
  total.textContent = money(sum); const tendered = parseFloat((paid.value || '').replace(/[₱,\s]/g, '')) || 0;
  change.textContent = money(Math.max(0, tendered - sum));
 }
 document.querySelectorAll('.add-product').forEach(button => button.addEventListener('click', () => {
  const id = button.dataset.productId, line = lines.get(id) || {name:button.dataset.productName, price:parseFloat(button.dataset.price), quantity:0};
  line.quantity++; lines.set(id, line); render();
 }));
 tbody.addEventListener('click', event => { if (event.target.dataset.remove) { lines.delete(event.target.dataset.remove); render(); } });
 tbody.addEventListener('input', event => { if (event.target.dataset.qty) { lines.get(event.target.dataset.qty).quantity = Math.max(1, parseInt(event.target.value || '1', 10)); render(); } });
 paid.addEventListener('input', render); render();
});
</script>
@endpush
@endsection

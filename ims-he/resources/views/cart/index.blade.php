@extends('layouts.app')
@section('title', 'My Cart')
@section('heading', 'My Cart')

@section('content')
<div class="grid-checkout">
    {{-- Left: Items List --}}
    <div class="checkout-card">
        @if($warnings)
            <x-alert type="warning" title="Your cart was updated">
                <ul>@foreach($warnings as $w)<li>{{ $w }}</li>@endforeach</ul>
            </x-alert>
        @endif

        @if($items->isEmpty())
            <div style="text-align: center; padding: 2.5rem 1rem;">
                <p style="font-size: 1.1rem; color: #6b7280;">Your shopping cart is currently empty.</p>
                <a class="btn-primary" href="{{ route('catalog.index') }}" style="margin-top: 1rem;">Browse Hardware Inventory</a>
            </div>
        @else
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem;">
                <h3 style="margin:0; font-size: 1.1rem;">Cart Items ({{ $items->count() }})</h3>
                <a href="{{ route('catalog.index') }}" style="color: #1f3a5f; text-decoration: none; font-size: 0.875rem; font-weight: 600;">Continue Shopping</a>
            </div>

            <table class="cart-table-modern">
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <th>Price</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        @php($p = $item->product)
                        <tr data-price="{{ $p->unit_price }}">
                            <td>
                                <strong><a href="{{ route('catalog.show', $p) }}" style="color: #111827; text-decoration: none;">{{ $p->product_name }}</a></strong>
                                @if($p->brand)
                                    <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.15rem;">Brand: {{ $p->brand }}</div>
                                @endif
                                <div class="low line-error" style="font-size: 0.8rem; margin-top: 0.25rem;" hidden></div>
                            </td>
                            <td style="white-space: nowrap;">{{ money($p->unit_price) }}</td>
                            <td style="text-align: center;">
                                {{-- Saves automatically on - / + or typing (script below); Enter also submits. --}}
                                <form method="POST" action="{{ route('customer.cart.update', $item) }}" class="qty cart-qty" data-saved="{{ $item->quantity }}" style="display: inline-block;">
                                    @csrf 
                                    @method('PATCH')
                                    @include('partials.qty', ['value' => $item->quantity, 'max' => max(1, $p->stock_quantity)])
                                </form>
                            </td>
                            <td class="line-subtotal" style="text-align: right; font-weight: 600; white-space: nowrap;">
                                {{ money($item->quantity * $p->unit_price) }}
                            </td>
                            <td style="text-align: right;">
                                <form method="POST" action="{{ route('customer.cart.remove', $item) }}" style="display: inline;">
                                    @csrf 
                                    @method('DELETE')
                                    <button class="btn-danger-sm" type="submit" title="Remove Item">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Right: Order Summary Sidebar --}}
    @if(!$items->isEmpty())
        <div>
            <div class="checkout-summary-box">
                <h3 style="margin-top:0; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.75rem; font-size: 1.1rem;">Order Summary</h3>
                
                <div style="display: flex; justify-content: space-between; margin: 0.75rem 0; font-size: 0.95rem;">
                    <span style="color: #4b5563;">Subtotal</span>
                    <strong id="cart-subtotal" style="color: #111827;">{{ money($total) }}</strong>
                </div>

                <div style="border-top: 2px dashed #e5e7eb; padding-top: 0.75rem; margin-top: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 1rem; font-weight: 700;">Total</span>
                    <span id="cart-total" style="font-size: 1.25rem; font-weight: 800; color: #1f3a5f;">{{ money($total) }}</span>
                </div>

                <a href="{{ route('customer.checkout.show') }}" class="btn-primary" style="width: 100%; box-sizing: border-box; margin-top: 1.25rem;">
                    Proceed to Checkout
                </a>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
 // Cart auto-update: recalculates each line's subtotal and the summary right away, then saves in the background.
 // The server re-checks stock; if it refuses, the quantity goes back to the last saved value.
 (function () {
  var peso = function (n) { return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
  var showTotal = function (amount) {
   document.getElementById('cart-subtotal').textContent = peso(amount);
   document.getElementById('cart-total').textContent = peso(amount);
  };

  function recalc() {
   var total = 0;
   document.querySelectorAll('tr[data-price]').forEach(function (row) {
    var qty = Number(row.querySelector('.cart-qty input[name=quantity]').value) || 0;
    var sub = qty * Number(row.dataset.price);
    row.querySelector('.line-subtotal').textContent = peso(sub);
    total += sub;
   });
   showTotal(total);
  }

  function reject(form, message) {
   var error = form.closest('tr').querySelector('.line-error');
   form.querySelector('input[name=quantity]').value = form.dataset.saved;
   error.textContent = message;
   error.hidden = false;
   recalc();
  }

  function save(form) {
   var row = form.closest('tr'), input = form.querySelector('input[name=quantity]'), error = row.querySelector('.line-error');

   // Typed values outside 1..stock are refused here, the same way the server would refuse them.
   if (input.validity.rangeOverflow) return reject(form, 'Only ' + input.max + ' in stock.');
   if (!input.checkValidity()) return reject(form, 'Enter a quantity of at least 1.');

   fetch(form.action, {
    method: 'POST',                                   // _method=PATCH travels in the form data
    body: new FormData(form),
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
   }).then(function (res) {
    return res.json().then(function (data) { return { ok: res.ok, data: data }; });
   }).then(function (r) {
    if (!r.ok) throw new Error(r.data.errors && r.data.errors.quantity ? r.data.errors.quantity[0] : (r.data.message || 'Could not update the cart.'));
    form.dataset.saved = r.data.quantity;
    error.hidden = true;
    row.querySelector('.line-subtotal').textContent = peso(r.data.subtotal);
    showTotal(r.data.total);
    var count = document.getElementById('cart-count');
    if (count) count.textContent = r.data.count;
   }).catch(function (e) { reject(form, e.message); });
  }

  var timers = new WeakMap();
  document.querySelectorAll('.cart-qty').forEach(function (form) {
   form.addEventListener('submit', function (e) { e.preventDefault(); save(form); });   // Enter key
   form.querySelector('input[name=quantity]').addEventListener('input', function () {
    recalc();
    clearTimeout(timers.get(form));
    timers.set(form, setTimeout(function () { save(form); }, 400));   // wait for fast clicks / typing
   });
  });
 })();
</script>
@endpush

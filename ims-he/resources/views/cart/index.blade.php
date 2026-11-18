@extends('layouts.app')
@section('title','My cart')
@section('heading', 'My Cart')
@section('content')
<div class="card">
 @if($warnings)
  <x-alert type="warning" title="Your cart was updated">
   <ul>@foreach($warnings as $w)<li>{{ $w }}</li>@endforeach</ul>
  </x-alert>
 @endif
 @if($items->isEmpty())
  <p>Your cart is empty.</p>
  <a class="btn" href="{{ route('catalog.index') }}">Browse products</a>
 @else
  <table class="cart-table">
   <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr>
   @foreach($items as $item)
    @php($p = $item->product)
    <tr data-price="{{ $p->unit_price }}">
     <td>
      <a href="{{ route('catalog.show', $p) }}">{{ $p->product_name }}</a>
      <div class="low line-error" hidden></div>
     </td>
     <td>{{ money($p->unit_price) }}</td>
     <td>
      {{-- Saves automatically on - / + or typing (script below); Enter also submits. --}}
      <form method="POST" action="{{ route('customer.cart.update', $item) }}" class="qty cart-qty" data-saved="{{ $item->quantity }}">@csrf @method('PATCH')
       @include('partials.qty', ['value' => $item->quantity, 'max' => max(1, $p->stock_quantity)])
      </form>
     </td>
     <td class="line-subtotal">{{ money($item->quantity * $p->unit_price) }}</td>
     <td>
      <form method="POST" action="{{ route('customer.cart.remove', $item) }}" class="inline">@csrf @method('DELETE')
       <button class="danger">Remove</button>
      </form>
     </td>
    </tr>
   @endforeach
   <tr class="cart-total-row"><th colspan="3">Total</th><th id="cart-total">{{ money($total) }}</th><th></th></tr>
  </table>
  <p>
   <a class="btn" href="{{ route('customer.checkout.show') }}">Checkout</a>
   <a href="{{ route('catalog.index') }}">Continue shopping</a>
  </p>
 @endif
</div>
@endsection

@push('scripts')
<script>
 // Cart auto-update: recalculates subtotal/total on the page right away, then saves in the background.
 // The server re-checks stock; if it refuses, the quantity goes back to the last saved value.
 (function () {
  var peso = function (n) { return '\u20B1' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };

  function recalc() {
   var total = 0;
   document.querySelectorAll('tr[data-price]').forEach(function (row) {
    var qty = Number(row.querySelector('.cart-qty input[name=quantity]').value) || 0;
    var sub = qty * Number(row.dataset.price);
    row.querySelector('.line-subtotal').textContent = peso(sub);
    total += sub;
   });
   document.getElementById('cart-total').textContent = peso(total);
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
    document.getElementById('cart-total').textContent = peso(r.data.total);
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

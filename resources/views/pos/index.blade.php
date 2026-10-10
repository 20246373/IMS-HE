@extends('layouts.app')
@section('title','POS')
@section('content')
<form method="POST" action="{{ route('pos.store') }}" id="sale">@csrf
<div class="card"><h3>New sale <span class="badge">In-store</span></h3>
 <label>Customer
  <select name="customer_id">
   <option value="">Walk-in customer</option>
   @foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id')==$c->id)>{{ $c->last_name }}, {{ $c->first_name }}</option>@endforeach
  </select>
 </label>
 <table id="lines"><tr><th>Product</th><th>Qty</th><th>Stock</th><th>Subtotal</th><th></th></tr></table>
 <p><button type="button" id="add">+ Add item</button></p>
 <h2 style="text-align:right">Total: <span id="total">&#8369;0.00</span></h2>
</div>
<div class="card"><h3>Payment</h3>
 <div class="row">
  <label>Amount paid <input type="number" step="0.01" min="0" name="amount_paid" id="paid" value="{{ old('amount_paid') }}" required></label>
  <label>Method <select name="payment_method"><option>Cash</option><option>GCash</option></select></label>
 </div>
 <p>Change: <strong id="change">&#8369;0.00</strong> <span id="hint" class="low"></span></p>
 <p><button id="submit" disabled>Complete sale</button></p>
</div>
</form>

<template id="row">
 <tr class="line">
  <td><select data-n="product_id" required>
   @foreach($products as $p)<option value="{{ $p->id }}" data-price="{{ $p->unit_price }}" data-stock="{{ $p->stock_quantity }}">{{ $p->name }} - &#8369;{{ number_format($p->unit_price,2) }}</option>@endforeach
  </select></td>
  <td><input type="number" min="1" value="1" data-n="quantity" required style="width:70px"></td>
  <td class="stk"></td><td class="sub">&#8369;0.00</td>
  <td><button type="button" class="danger rm">x</button></td>
 </tr>
</template>
<script>
 const lines = document.getElementById('lines'), tpl = document.getElementById('row');
 const paid = document.getElementById('paid'), submit = document.getElementById('submit');
 const peso = n => '\u20B1' + n.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
 let i = 0;

 function recalc() {
  let total = 0, rows = 0, ok = true;
  lines.querySelectorAll('tr.line').forEach(tr => {
   const opt = tr.querySelector('select').selectedOptions[0];
   const price = parseFloat(opt.dataset.price), stock = parseInt(opt.dataset.stock);
   const qty = tr.querySelector('input');
   qty.max = stock;
   let q = parseInt(qty.value) || 0;
   if (q > stock) { q = stock; qty.value = stock; }   // block quantities above stock
   if (q < 1) ok = false;
   tr.querySelector('.stk').textContent = stock + ' available';
   tr.querySelector('.sub').textContent = peso(price * q);
   total += price * q; rows++;
  });
  total = Math.round(total * 100) / 100;
  document.getElementById('total').textContent = peso(total);
  const p = parseFloat(paid.value) || 0;
  const short = p < total;
  document.getElementById('change').textContent = peso(short ? 0 : p - total);
  document.getElementById('hint').textContent = (rows && short) ? 'Amount paid must be at least the total.' : '';
  submit.disabled = !(rows > 0 && ok && !short);
 }

 function addRow() {
  const tr = tpl.content.firstElementChild.cloneNode(true);
  tr.querySelectorAll('[data-n]').forEach(el => el.name = `items[${i}][${el.dataset.n}]`);
  tr.querySelector('.rm').onclick = () => { tr.remove(); recalc(); };
  tr.querySelectorAll('select,input').forEach(el => el.addEventListener('input', recalc));
  lines.appendChild(tr); i++; recalc();
 }
 document.getElementById('add').onclick = addRow;
 paid.addEventListener('input', recalc);
 addRow();
</script>
@endsection

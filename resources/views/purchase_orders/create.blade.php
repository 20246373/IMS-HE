@extends('layouts.app')
@section('title','New purchase order')
@section('content')
<form method="POST" action="{{ route('purchase-orders.store') }}">@csrf
<div class="card"><h3>New purchase order <span class="badge amber">Pending</span></h3>
 <label>Supplier <select name="supplier_id" required>
  <option value="">- choose -</option>
  @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>@endforeach
 </select></label>
 <table id="lines"><tr><th>Product</th><th>Qty</th><th>Unit cost</th><th>Subtotal</th><th></th></tr></table>
 <p><button type="button" id="add">+ Add line</button></p>
 <h3 style="text-align:right">Total: <span id="total">&#8369;0.00</span></h3>
 <p><button>Create PO</button> <a class="btn muted" href="{{ route('purchase-orders.index') }}">Cancel</a></p>
</div>
</form>
<template id="row">
 <tr class="line">
  <td><select data-n="product_id" required>
   @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
  </select></td>
  <td><input type="number" min="1" value="1" data-n="quantity" required style="width:80px"></td>
  <td><input type="number" min="0" step="0.01" value="0" data-n="unit_cost" required style="width:100px"></td>
  <td class="sub">&#8369;0.00</td>
  <td><button type="button" class="danger rm">x</button></td>
 </tr>
</template>
<script>
 const lines = document.getElementById('lines'), tpl = document.getElementById('row');
 const peso = n => '\u20B1' + n.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
 let i = 0;
 function recalc() {
  let total = 0;
  lines.querySelectorAll('tr.line').forEach(tr => {
   const q = parseInt(tr.querySelector('[data-n=quantity]').value) || 0;
   const c = parseFloat(tr.querySelector('[data-n=unit_cost]').value) || 0;
   tr.querySelector('.sub').textContent = peso(q * c);
   total += q * c;
  });
  document.getElementById('total').textContent = peso(total);
 }
 function addRow() {
  const tr = tpl.content.firstElementChild.cloneNode(true);
  tr.querySelectorAll('[data-n]').forEach(el => el.name = `lines[${i}][${el.dataset.n}]`);
  tr.querySelector('.rm').onclick = () => { tr.remove(); recalc(); };
  tr.querySelectorAll('input').forEach(el => el.addEventListener('input', recalc));
  lines.appendChild(tr); i++; recalc();
 }
 document.getElementById('add').onclick = addRow;
 addRow();
</script>
@endsection

@extends('layouts.app')
@section('title','POS')
@section('content')
<div class="card"><h3>New sale</h3>
 <form method="POST" action="{{ route('pos.store') }}" id="sale">@csrf
  <table id="lines"><tr><th>Product</th><th>Qty</th><th></th></tr></table>
  <p><button type="button" id="add">+ Add item</button></p>
  <label>Amount paid <input type="number" step="0.01" min="0" name="amount_paid" required></label>
  <label>Method <select name="payment_method"><option>Cash</option><option>GCash</option></select></label>
  <p><button>Complete sale</button></p>
 </form>
</div>
<template id="row">
 <tr>
  <td><select data-n="product_id" required>
   @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} - &#8369;{{ number_format($p->unit_price,2) }} ({{ $p->stock_quantity }} left)</option>@endforeach
  </select></td>
  <td><input type="number" min="1" value="1" data-n="quantity" required style="width:70px"></td>
  <td><button type="button" class="danger rm">x</button></td>
 </tr>
</template>
<script>
 const lines = document.getElementById('lines'), tpl = document.getElementById('row');
 let i = 0;
 function addRow(){
  const tr = tpl.content.firstElementChild.cloneNode(true);
  tr.querySelectorAll('[data-n]').forEach(el => el.name = `items[${i}][${el.dataset.n}]`);
  tr.querySelector('.rm').onclick = () => tr.remove();
  lines.appendChild(tr); i++;
 }
 document.getElementById('add').onclick = addRow; addRow();
</script>
@endsection

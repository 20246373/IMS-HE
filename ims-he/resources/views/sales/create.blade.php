@extends('layouts.app')
@section('title','New sale')
@section('content')
<div class="card" style="max-width:520px"><h3>New sale</h3>
 <p><small>All fields are required.</small></p>
 <form method="POST" action="{{ route('sales.store') }}">@csrf
  <label>Product <span style="color:red">*</span>
   <select name="items[0][product_id]" required>
    <option value="">-</option>
    @foreach($products as $p)
     <option value="{{ $p->product_id }}" @selected((string) old('items.0.product_id') === (string) $p->product_id)>{{ $p->product_name }} (stock: {{ $p->stock_quantity }})</option>
    @endforeach
   </select></label>
  <label>Quantity <span style="color:red">*</span> <input type="number" min="1" name="items[0][quantity]" value="{{ old('items.0.quantity', 1) }}" required></label>
  <label>Amount paid <span style="color:red">*</span> <input type="number" step="0.01" min="0" name="amount_paid" value="{{ old('amount_paid') }}" required></label>
  <label>Payment method <span style="color:red">*</span>
   <select name="payment_method" required>
    <option value="Cash" @selected(old('payment_method') === 'Cash')>Cash</option>
    <option value="GCash" @selected(old('payment_method') === 'GCash')>GCash</option>
   </select></label>
  <p><button>Complete sale</button> <a class="btn muted" href="{{ route('sales.index') }}">Cancel</a></p>
 </form>
</div>
@endsection

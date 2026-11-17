@extends('layouts.app')
@section('title','New sale')
@section('content')
<div class="card" style="max-width:560px">
 <h2>New sale</h2>
 <x-required-note />
 <p><small>The full POS screen is coming in a later task &mdash; this is the minimal working form.</small></p>
 <form method="POST" action="{{ route('sales.store') }}">
  @csrf
  <div class="form-grid">
   <x-select label="Product" name="items[0][product_id]">
    <option value="">Select a product</option>
    @foreach($products as $p)
     <option value="{{ $p->product_id }}" @selected((string) old('items.0.product_id') === (string) $p->product_id)>
      {{ $p->product_name }} (stock: {{ $p->stock_quantity }})
     </option>
    @endforeach
   </x-select>
   <x-field label="Quantity" name="items[0][quantity]" type="number" :value="old('items.0.quantity', 1)" min="1" />
   <x-field label="Amount paid" name="amount_paid" type="number" :value="old('amount_paid')" step="0.01" min="0" />
   <x-select label="Payment method" name="payment_method">
    <option value="Cash" @selected(old('payment_method', 'Cash') === 'Cash')>Cash</option>
    <option value="GCash" @selected(old('payment_method') === 'GCash')>GCash</option>
   </x-select>
  </div>
  <p>
   <button>Complete sale</button>
   <a class="btn btn-alt" href="{{ route('sales.index') }}">Cancel</a>
  </p>
 </form>
</div>
@endsection

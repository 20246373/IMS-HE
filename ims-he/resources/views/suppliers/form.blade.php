@extends('layouts.app')
@section('title', $supplier->exists ? 'Edit supplier' : 'Add supplier')
@section('content')
<div class="card" style="max-width:560px">
 <h2>{{ $supplier->exists ? 'Edit supplier' : 'Add supplier' }}</h2>
 <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
  @csrf
  @if($supplier->exists) @method('PUT') @endif
  <label>Supplier name
   <input name="supplier_name" value="{{ old('supplier_name', $supplier->supplier_name) }}" maxlength="100" required>
  </label>
  <label>Contact number
   <input name="contact_number" value="{{ old('contact_number', $supplier->contact_number) }}" maxlength="14" required>
  </label>
  <label>Address
   <input name="address" value="{{ old('address', $supplier->address) }}" maxlength="100" required>
  </label>
  <p>
   <button>{{ $supplier->exists ? 'Save changes' : 'Add supplier' }}</button>
   <a class="btn" href="{{ route('suppliers.index') }}">Cancel</a>
  </p>
 </form>
</div>
@endsection

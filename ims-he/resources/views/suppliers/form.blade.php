@extends('layouts.app')
@section('title', $supplier->exists ? 'Edit supplier' : 'Add supplier')
@section('heading'){{ $supplier->exists ? $supplier->supplier_name : 'Add supplier' }}@endsection
@section('content')
<div class="card">
 <form method="POST" action="{{ $supplier->exists ? route('suppliers.update', $supplier) : route('suppliers.store') }}" class="plain-labels">
  @csrf
  @if($supplier->exists) @method('PUT') @endif
  <div class="form-grid">
   @unless($supplier->exists)
    <x-field label="Supplier name" name="supplier_name" maxlength="100" />
   @endunless
   <x-field label="Contact number" name="contact_number" :value="$supplier->contact_number" maxlength="14" />
   <x-field label="Address" name="address" :value="$supplier->address" maxlength="100" />
  </div>
  <div class="actions">
   <button>{{ $supplier->exists ? 'Save changes' : 'Add supplier' }}</button>
   <a class="btn btn-alt" href="{{ route('suppliers.index') }}">Cancel</a>
  </div>
 </form>
</div>
@endsection

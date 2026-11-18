@extends('layouts.app')
@section('title', 'New ledger entry')
@section('heading', 'New Ledger Entry')
@section('content')
<div class="card">
 <x-required-note />
 <form method="POST" action="{{ route('ledger.store') }}">
  @csrf
  <div class="form-grid">
   <x-select label="Type" name="entry_type"><option value="">Select type</option><option value="Income" @selected(old('entry_type') === 'Income')>Income</option><option value="Expense" @selected(old('entry_type') === 'Expense')>Expense</option></x-select>
   <x-field label="Amount" name="amount" prefix="₱" inputmode="decimal" />
   <x-field label="Description" name="description" maxlength="255" />
  </div>
  <button type="submit">Record entry</button>
  <a class="btn btn-alt" href="{{ route('ledger.index') }}">Cancel</a>
 </form>
</div>
@endsection

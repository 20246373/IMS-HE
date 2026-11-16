@extends('layouts.app')
@section('title', 'Log income/expense')
@section('content')
<div class="card" style="max-width:560px">
 <h2>Log income/expense</h2>
 <form method="POST" action="{{ route('ledger.store') }}">
  @csrf
  <label>Type
   <select name="entry_type" required>
    <option value="Income" @selected(old('entry_type') === 'Income')>Income</option>
    <option value="Expense" @selected(old('entry_type') === 'Expense')>Expense</option>
   </select>
  </label>
  <label>Amount (&#8369;)
   <input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required>
  </label>
  <label>Description
   <input name="description" maxlength="255" value="{{ old('description') }}" required>
  </label>
  <label>Date <small class="optional">(defaults to today)</small>
   <input type="date" name="entry_date" value="{{ old('entry_date') }}" max="{{ today()->toDateString() }}">
  </label>
  <p>
   <button type="submit">Record entry</button>
   <a class="btn" href="{{ route('ledger.index') }}">Cancel</a>
  </p>
 </form>
</div>
@endsection

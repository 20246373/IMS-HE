@extends('layouts.app')
@section('title','Generate Payroll')
@section('content')
<div class="card" style="max-width:480px">
 <h3>Generate Payroll</h3>
 <form method="POST" action="{{ route('payrolls.store') }}">@csrf
  <label>Period Start <input type="date" name="period_start" required></label>
  <label>Period End <input type="date" name="period_end" required></label>
  <p><button>Generate</button> <a href="{{ route('payrolls.index') }}" class="btn btn-alt">Cancel</a></p>
 </form>
</div>
@endsection

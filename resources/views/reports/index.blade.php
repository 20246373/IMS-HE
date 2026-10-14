@extends('layouts.app')
@section('title','Reports')
@section('content')
<div class="card">
 <h3>Reports</h3>
 @if(auth()->user()->hasRole('Admin'))
  <p><a href="{{ route('reports.company') }}" class="btn">Company Report</a></p>
 @endif
 @if(auth()->user()->hasRole('Treasurer'))
  <p><a href="{{ route('reports.financial') }}" class="btn">Financial Report</a></p>
  <p><a href="{{ route('reports.payroll') }}" class="btn">Payroll Report</a></p>
 @endif
 @if(auth()->user()->hasRole('Store Supervisor'))
  <p><a href="{{ route('reports.sales') }}" class="btn">Sales Report</a></p>
  <p><a href="{{ route('reports.inventory') }}" class="btn">Inventory Report</a></p>
 @endif
</div>
@endsection

@extends('layouts.app')
@section('title', 'Customers')
@section('heading', 'Customers')
@section('content')
<div class="card">
 <form method="GET" action="{{ route('customers.index') }}" class="report-filter">
  <label>Search email or name <input type="search" name="q" value="{{ $search }}" placeholder="Email or name"></label>
  <button type="submit">Search</button>
  @if($search !== '')<a class="btn btn-alt" href="{{ route('customers.index') }}">Clear</a>@endif
 </form>
 <table>
  <thead><tr><th>Email</th><th>Name</th><th>Contact number</th><th>Orders</th><th>Email status</th><th>Actions</th></tr></thead>
  <tbody>
   @forelse($customers as $customer)
    <tr>
     <td>{{ $customer->email }}</td>
     <td>{{ trim($customer->first_name.' '.$customer->last_name) ?: '—' }}</td>
     <td>{{ $customer->contact_number ?: '—' }}</td>
     <td>{{ $customer->invoices_count }}</td>
     <td>{{ $customer->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}</td>
     <td><a class="btn" href="{{ route('customers.show', $customer) }}">View</a></td>
    </tr>
   @empty
    <tr><td colspan="6">No customers found.</td></tr>
   @endforelse
  </tbody>
 </table>
 {{ $customers->links() }}
</div>
@endsection

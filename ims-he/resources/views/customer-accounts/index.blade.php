@extends('layouts.app')
@section('title', 'Customer Accounts')
@section('heading', 'Customer Accounts')
@section('content')
<div class="card">
 <form method="GET" action="{{ route('customer-accounts.index') }}" class="report-filter">
  <label>Search <input type="search" name="q" value="{{ $search }}" placeholder="Email or name" maxlength="100"></label>
  <button type="submit">Search</button>
  @if($search !== '')<a class="btn btn-alt" href="{{ route('customer-accounts.index') }}">Clear</a>@endif
 </form>
 <table>
  <tr><th>Name</th><th>Email</th><th>Email Status</th><th>Member Since</th><th>Actions</th></tr>
  @forelse($customers as $customer)
   <tr>
    <td>{{ trim($customer->first_name.' '.$customer->last_name) ?: '-' }}</td>
    <td>{{ $customer->email }}</td>
    <td class="{{ $customer->hasVerifiedEmail() ? '' : 'low' }}">{{ $customer->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}</td>
    <td>{{ $customer->created_at?->format('M d, Y') }}</td>
    <td>
     <div class="actions">
      <a class="btn" href="{{ route('customer-accounts.edit', $customer) }}">Edit</a>
      @unless($customer->hasVerifiedEmail())
       <form method="POST" action="{{ route('customer-accounts.verify', $customer) }}">@csrf<button>Verify</button></form>
      @endunless
      <form method="POST" action="{{ route('customer-accounts.destroy', $customer) }}" onsubmit="return confirm('Delete this customer account? Past orders are kept.')">@csrf @method('DELETE')<button class="danger">Delete</button></form>
     </div>
    </td>
   </tr>
  @empty
   <tr><td colspan="5">No customer accounts found.</td></tr>
  @endforelse
 </table>
 {{ $customers->links() }}
</div>
@endsection

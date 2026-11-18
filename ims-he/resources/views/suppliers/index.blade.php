@extends('layouts.app')
@section('title','Suppliers')
@section('heading', 'Suppliers')
@section('page-actions')<a class="btn" href="{{ route('suppliers.create') }}">+ Add supplier</a>@endsection
@section('content')
<div class="card">
 <table>
  <tr><th>Name</th><th>Contact number</th><th>Address</th><th>Purchase orders</th><th>Actions</th></tr>
  @forelse($suppliers as $supplier)
  <tr>
   <td>{{ $supplier->supplier_name }}</td>
   <td>{{ $supplier->contact_number }}</td>
   <td>{{ $supplier->address }}</td>
   <td>{{ $supplier->purchase_orders_count }}</td>
   <td>
    <a class="btn" href="{{ route('suppliers.edit', $supplier) }}">Edit</a>
    <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this record? This action cannot be undone.')">
     @csrf @method('DELETE')
     @if(auth()->user()->hasPermission('suppliers.delete'))<button class="danger">Delete</button>@endif
    </form>
   </td>
  </tr>
  @empty
  <tr><td colspan="5">No suppliers found.</td></tr>
  @endforelse
 </table>
 {{ $suppliers->links() }}
</div>
@endsection

@extends('layouts.app')
@section('title','Suppliers')
@section('heading', 'Suppliers')
@section('page-actions')<button type="button" onclick="document.getElementById('add-supplier').showModal()">+ Add supplier</button>@endsection
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
    <div class="actions">
     <button type="button" onclick="document.getElementById('edit-supplier-{{ $supplier->supplier_id }}').showModal()">Edit</button>
     @if(auth()->user()->hasPermission('suppliers.delete'))
      <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Are you sure you want to delete this record? This action cannot be undone.')">
       @csrf @method('DELETE')
       <button class="danger">Delete</button>
      </form>
     @endif
    </div>
   </td>
  </tr>
  @empty
  <tr><td colspan="5">No suppliers found.</td></tr>
  @endforelse
 </table>
 {{ $suppliers->links() }}
</div>

{{-- Add pop-up (three fields) --}}
@php($reopened = old('_modal') === 'add-supplier')
<x-modal id="add-supplier" title="Add supplier">
 <form method="POST" action="{{ route('suppliers.store') }}">@csrf
  <input type="hidden" name="_modal" value="add-supplier">
  @include('suppliers._modal-fields', ['prefix' => 'add-supplier', 'supplier' => null, 'reopened' => $reopened, 'withName' => true])
  <div class="actions">
   <button>Add supplier</button>
   <button type="button" class="btn-alt" onclick="this.closest('dialog').close()">Cancel</button>
  </div>
 </form>
</x-modal>

{{-- Edit pop-ups (two fields; the name stays fixed because purchase orders show it) --}}
@foreach($suppliers as $supplier)
 @php($modal = 'edit-supplier-'.$supplier->supplier_id)
 <x-modal :id="$modal" :title="$supplier->supplier_name">
  <form method="POST" action="{{ route('suppliers.update', $supplier) }}">@csrf @method('PUT')
   <input type="hidden" name="_modal" value="{{ $modal }}">
   @include('suppliers._modal-fields', ['prefix' => $modal, 'supplier' => $supplier, 'reopened' => old('_modal') === $modal, 'withName' => false])
   <div class="actions">
    <button>Save changes</button>
    <button type="button" class="btn-alt" onclick="this.closest('dialog').close()">Cancel</button>
   </div>
  </form>
 </x-modal>
@endforeach
@endsection

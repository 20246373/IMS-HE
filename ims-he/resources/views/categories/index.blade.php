@extends('layouts.app')
@section('title','Categories')
@section('heading', 'Categories')
@section('content')
<div class="card"><h3>Add category</h3>
 <form method="POST" action="{{ route('categories.store') }}" class="report-filter">@csrf
  <label class="required">Name <input name="category_name" placeholder="Category name" value="{{ old('_modal') ? '' : old('category_name') }}" maxlength="50" required></label>
  <button>Add</button>
 </form>
</div>
<div class="card">
 <table>
  <tr><th>Name</th><th>Products</th><th>Actions</th></tr>
  @foreach($categories as $c)
  <tr>
   <td>{{ $c->category_name }}</td><td>{{ $c->products_count }}</td>
   <td>
    <div class="actions">
     <button type="button" onclick="document.getElementById('edit-category-{{ $c->category_id }}').showModal()">Edit</button>
     <form method="POST" action="{{ route('categories.destroy', $c) }}" onsubmit="return confirm('Are you sure you want to delete this record? This action cannot be undone.')">@csrf @method('DELETE')<button class="danger">Delete</button></form>
    </div>
   </td>
  </tr>
  @endforeach
 </table>
</div>

{{-- Edit pop-ups (one field each). --}}
@foreach($categories as $c)
 @php($modal = 'edit-category-'.$c->category_id)
 @php($reopened = old('_modal') === $modal)
 <x-modal :id="$modal" title="Edit category">
  <form method="POST" action="{{ route('categories.update', $c) }}">@csrf @method('PUT')
   <input type="hidden" name="_modal" value="{{ $modal }}">
   <div class="field">
    <label for="{{ $modal }}-name" class="required">Name</label>
    <input id="{{ $modal }}-name" name="category_name" value="{{ $reopened ? old('category_name') : $c->category_name }}" maxlength="50" required>
    @if($reopened)@error('category_name')<div class="field-error">{{ $message }}</div>@enderror @endif
   </div>
   <div class="actions">
    <button>Save</button>
    <button type="button" class="btn-alt" onclick="this.closest('dialog').close()">Cancel</button>
   </div>
  </form>
 </x-modal>
@endforeach
@endsection

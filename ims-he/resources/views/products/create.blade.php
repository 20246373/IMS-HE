@extends('layouts.app')
@section('title','Add product')
@section('heading', 'Add Product')
@section('content')
<div class="card">
 <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">@csrf
  <x-required-note />
  @include('products._fields', ['product' => null])
  <p><small>Opening stock is recorded in the inventory log as an Adjustment.</small></p>
  <p><button>Add product</button> <a class="btn" href="{{ route('products.index') }}">Cancel</a></p>
 </form>
</div>
@endsection

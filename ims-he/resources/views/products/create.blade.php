@extends('layouts.app')
@section('title','Add product')
@section('content')
<div class="card" style="max-width:520px"><h3>Add product</h3>
 <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">@csrf
  @include('products._fields', ['product' => null])
  <p><small>Opening stock is recorded in the inventory log as an Adjustment.</small></p>
  <p><button>Add product</button> <a class="btn" href="{{ route('products.index') }}">Cancel</a></p>
 </form>
</div>
@endsection

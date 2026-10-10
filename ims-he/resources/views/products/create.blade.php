@extends('layouts.app')
@section('title','Add product')
@section('content')
<div class="card" style="max-width:520px"><h3>Add product</h3>
 <form method="POST" action="{{ route('products.store') }}">@csrf
  @include('products._fields', ['product' => null])
  <p><button>Add product</button> <a class="btn muted" href="{{ route('products.index') }}">Cancel</a></p>
 </form>
</div>
@endsection

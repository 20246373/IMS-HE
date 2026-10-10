@extends('layouts.app')
@section('title','Edit supplier')
@section('content')
<div class="card" style="max-width:520px"><h3>Edit supplier</h3>
 <form method="POST" action="{{ route('suppliers.update',$supplier) }}">@csrf @method('PUT')
  @include('suppliers._fields')
  <p><button>Save</button> <a class="btn muted" href="{{ route('suppliers.index') }}">Cancel</a></p>
 </form>
</div>
@endsection

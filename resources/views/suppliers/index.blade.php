@extends('layouts.app')
@section('title','Suppliers')
@section('content')
<div class="card" style="max-width:520px"><h3>Add supplier</h3>
 <form method="POST" action="{{ route('suppliers.store') }}">@csrf
  @include('suppliers._fields', ['supplier' => null])
  <p><button>Add supplier</button></p>
 </form>
</div>
<div class="card"><h3>Suppliers</h3>
 <table>
  <tr><th>Name</th><th>Contact</th><th>Address</th><th></th></tr>
  @foreach($suppliers as $s)
  <tr><td>{{ $s->name }}</td><td>{{ $s->contact_number }}</td><td>{{ $s->address }}</td>
   <td class="row"><a class="btn" href="{{ route('suppliers.edit',$s) }}">Edit</a>
    <form method="POST" action="{{ route('suppliers.destroy',$s) }}" class="inline" onsubmit="return confirm('Delete this supplier?')">@csrf @method('DELETE')<button class="danger">Delete</button></form></td></tr>
  @endforeach
 </table>
 {{ $suppliers->links() }}
</div>
@endsection

@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="card">
 <h2>Welcome, {{ auth()->user()->employee?->full_name ?? auth()->user()->username }}</h2>
 <p>Signed in as <strong>{{ auth()->user()->role }}</strong>.</p>
</div>
@if(!is_null($accounts))
<div class="grid">
 <div class="card"><div>User accounts</div><h2>{{ $accounts }}</h2></div>
 <div class="card"><div>Locked accounts</div><h2 class="{{ $locked ? 'low' : '' }}">{{ $locked }}</h2></div>
</div>
@endif
@endsection

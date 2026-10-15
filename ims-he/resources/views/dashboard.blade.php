@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="card">
 <h2>Welcome, {{ auth()->user()->employee?->full_name ?? auth()->user()->username }}</h2>
 <p>Signed in as <strong>{{ auth()->user()->role }}</strong>.</p>
</div>
@forelse($widgets as $widget)
 @include('dashboard._'.$widget)
@empty
 <div class="card"><p>No dashboard modules are assigned to your role yet.</p></div>
@endforelse
@endsection

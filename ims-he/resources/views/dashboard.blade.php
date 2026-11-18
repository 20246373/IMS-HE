@extends('layouts.app')
@section('title','Dashboard')
@section('heading')Welcome, {{ auth()->user()->displayName() }}@endsection
@section('content')
<p class="page-sub">Signed in as <strong>{{ auth()->user()->role }}</strong>.</p>
@forelse($widgets as $widget)
 @include('dashboard._'.$widget)
@empty
 <div class="card"><p>No dashboard modules are assigned to your role yet.</p></div>
@endforelse
@endsection

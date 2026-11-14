@extends('layouts.app')
@section('title','Waiting for a role')
@section('content')
<div class="card auth">
 <h2>Waiting for a role</h2>
 <p>Thanks for signing up, <strong>{{ auth()->user()->label() }}</strong>.</p>
 <p>Your account is created, but the administrator has not given you a role yet. Once they do, log in again and you will see the parts of the system that your role can use.</p>
 <form method="POST" action="{{ route('logout') }}">@csrf<button>Log out</button></form>
</div>
@endsection

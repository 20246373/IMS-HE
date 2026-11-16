<div class="grid">
 <div class="card"><div>User accounts</div><h2>{{ $accounts }}</h2></div>
 <div class="card"><div>Waiting for a role</div><h2 class="{{ $pendingAccounts ? 'low' : '' }}">{{ $pendingAccounts }}</h2>@if($pendingAccounts)<a href="{{ route('users.index') }}">Review</a>@endif</div>
 <div class="card"><div>Locked accounts</div><h2 class="{{ $locked ? 'low' : '' }}">{{ $locked }}</h2></div>
</div>

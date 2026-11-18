<div class="grid">
 <a class="card card-link" href="{{ route('users.index') }}"><div>User accounts</div><h2>{{ $accounts }}</h2></a>
 <a class="card card-link" href="{{ route('users.index') }}#unassigned"><div>Unassigned accounts</div><h2 class="{{ $pendingAccounts ? 'low' : '' }}">{{ $pendingAccounts }}</h2></a>
 <a class="card card-link" href="{{ route('users.index') }}"><div>Locked accounts</div><h2 class="{{ $locked ? 'low' : '' }}">{{ $locked }}</h2></a>
</div>

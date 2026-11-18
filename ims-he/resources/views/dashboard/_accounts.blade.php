<div class="dash-grid">
 <a class="stat-card" href="{{ route('users.index') }}">
  <div class="stat-label">Total User Accounts</div>
  <div class="stat-value">{{ $accounts }}</div>
 </a>
 
 <a class="stat-card" href="{{ route('users.index') }}#unassigned">
  <div class="stat-label">Unassigned Accounts</div>
  <div class="stat-value {{ $pendingAccounts ? 'alert-val' : 'ok-val' }}">{{ $pendingAccounts }}</div>
 </a>

 <a class="stat-card" href="{{ route('users.index') }}">
  <div class="stat-label">Locked Accounts</div>
  <div class="stat-value {{ $locked ? 'alert-val' : '' }}">{{ $locked }}</div>
 </a>
</div>
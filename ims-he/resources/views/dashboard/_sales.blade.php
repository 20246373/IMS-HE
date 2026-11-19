<div class="dash-grid">
 <a class="stat-card" href="{{ route('reports.sales') }}">
  <div class="stat-label">Revenue This Month</div>
  <div class="stat-value" style="color: #1f3a5f;">{{ money($monthRevenue) }}</div>
 </a>
 
 <a class="stat-card" href="{{ route('reports.sales') }}">
  <div class="stat-label">Completed Sales</div>
  <div class="stat-value">{{ number_format($monthTransactions) }}</div>
 </a>
</div>
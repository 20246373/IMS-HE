<div class="grid">
 <div class="card"><div>Revenue this month</div><h2>&#8369;{{ number_format((float) $monthRevenue, 2) }}</h2></div>
 <div class="card"><div>Completed sales this month</div><h2>{{ number_format($monthTransactions) }}</h2></div>
</div>
<div class="card">
 <a class="btn" href="{{ route('reports.sales') }}">View sales report</a>
</div>

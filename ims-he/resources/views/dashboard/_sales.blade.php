<div class="grid">
 <a class="card card-link" href="{{ route('reports.sales') }}"><div>Revenue this month</div><h2>{{ money($monthRevenue) }}</h2></a>
 <a class="card card-link" href="{{ route('reports.sales') }}"><div>Completed sales this month</div><h2>{{ number_format($monthTransactions) }}</h2></a>
</div>

<!doctype html><html lang="en"><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:10px;color:#222}h1{font-size:18px}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid #ddd;padding:7px;text-align:left}.amount{text-align:right}
</style></head><body>
<h1>Financial report</h1><p>{{ \Illuminate\Support\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($endDate)->format('M d, Y') }}</p>
<p><strong>Income:</strong> {{ money($report['totalIncome']) }} &nbsp; <strong>Expense:</strong> {{ money($report['totalExpense']) }} &nbsp; <strong>Net:</strong> {{ money($report['totalNet']) }}</p>
<table><thead><tr><th>Date</th><th class="amount">Income</th><th class="amount">Expense</th><th class="amount">Net</th></tr></thead><tbody>
@foreach($report['days'] as $day)<tr><td>{{ \Illuminate\Support\Carbon::parse($day['date'])->format('M d, Y') }}</td><td class="amount">{{ money($day['income']) }}</td><td class="amount">{{ money($day['expense']) }}</td><td class="amount">{{ money($day['net']) }}</td></tr>@endforeach
</tbody></table></body></html>

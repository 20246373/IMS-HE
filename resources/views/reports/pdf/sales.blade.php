<!doctype html>
<html lang="en">
<head>
 <meta charset="utf-8">
 <style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
  h1 { font-size: 18px; margin-bottom: 4px; }
  p { color: #555; margin-top: 0; }
  .summary { margin: 18px 0; }
  .summary span { display: inline-block; margin-right: 28px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border-bottom: 1px solid #ddd; padding: 7px; text-align: left; }
  th { background: #f0f2f5; }
  .amount { text-align: right; }
 </style>
</head>
<body>
 <h1>Sales report</h1>
 <p>{{ \Illuminate\Support\Carbon::parse($startDate)->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($endDate)->format('M d, Y') }}</p>
 <div class="summary">
  <span><strong>Total revenue:</strong> &#8369;{{ number_format((float) $report['totalRevenue'], 2) }}</span>
  <span><strong>Total transactions:</strong> {{ number_format($report['totalTransactions']) }}</span>
 </div>
 <table>
  <thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th class="amount">Total</th></tr></thead>
  <tbody>
   @forelse($report['sales'] as $sale)
   <tr>
    <td>#{{ $sale->invoice_id }}</td>
    <td>{{ \Illuminate\Support\Carbon::parse($sale->invoice_date)->format('M d, Y') }}</td>
    <td>{{ trim(($sale->first_name ?? '') . ' ' . ($sale->last_name ?? '')) ?: 'Walk-in customer' }}</td>
    <td class="amount">&#8369;{{ number_format((float) $sale->total_amount, 2) }}</td>
   </tr>
   @empty
   <tr><td colspan="4">No completed sales for this date range.</td></tr>
   @endforelse
  </tbody>
 </table>
</body>
</html>

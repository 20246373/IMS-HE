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
 <h1>Financial report</h1>
 <p>{{ \Illuminate\Support\Carbon::parse($filters['start_date'])->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($filters['end_date'])->format('M d, Y') }}</p>
 <div class="summary">
  <span><strong>Income:</strong> &#8369;{{ number_format($cashFlow['income'], 2) }}</span>
  <span><strong>Expenses:</strong> &#8369;{{ number_format($cashFlow['expenses'], 2) }}</span>
  <span><strong>Net cash flow:</strong> &#8369;{{ number_format($cashFlow['net'], 2) }}</span>
 </div>
 <table>
  <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Reference</th><th>Recorded by</th><th class="amount">Amount</th></tr></thead>
  <tbody>
   @forelse($entries as $entry)
   <tr>
    <td>{{ $entry->entry_date->format('M d, Y') }}</td>
    <td>{{ $entry->entry_type }}</td>
    <td>{{ $entry->description }}</td>
    <td>{{ $entry->reference_type ? $entry->reference_type . ' #' . $entry->reference_id : 'Manual' }}</td>
    <td>{{ $entry->recorder?->label() ?? 'System' }}</td>
    <td class="amount">&#8369;{{ number_format((float) $entry->amount, 2) }}</td>
   </tr>
   @empty
   <tr><td colspan="6">No ledger entries for this date range.</td></tr>
   @endforelse
  </tbody>
 </table>
</body>
</html>

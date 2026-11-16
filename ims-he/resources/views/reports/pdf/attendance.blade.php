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
 <h1>Attendance report</h1>
 <p>{{ \Illuminate\Support\Carbon::parse($filters['start_date'])->format('M d, Y') }} - {{ \Illuminate\Support\Carbon::parse($filters['end_date'])->format('M d, Y') }}</p>
 <div class="summary">
  <span><strong>Total days worked:</strong> {{ number_format($report['totalDays']) }}</span>
  <span><strong>Total overtime (hrs):</strong> {{ number_format($report['totalOvertime'], 2) }}</span>
 </div>
 <table>
  <thead><tr><th>Employee</th><th>Position</th><th>Days</th><th>Clocked out</th><th>Overtime (hrs)</th></tr></thead>
  <tbody>
   @forelse($report['rows'] as $row)
   <tr>
    <td>{{ trim($row->first_name . ' ' . $row->last_name) }}</td>
    <td>{{ $row->position }}</td>
    <td>{{ number_format($row->days_worked) }}</td>
    <td>{{ number_format($row->days_clocked_out) }}</td>
    <td>{{ number_format((float) $row->overtime_hours, 2) }}</td>
   </tr>
   @empty
   <tr><td colspan="5">No attendance records for this date range.</td></tr>
   @endforelse
  </tbody>
 </table>
</body>
</html>

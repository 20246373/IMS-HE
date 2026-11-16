<!doctype html>
<html lang="en">
<head>
 <meta charset="utf-8">
 <style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
  h1 { font-size: 16px; margin-bottom: 2px; }
  p { color: #555; margin-top: 0; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th, td { border-bottom: 1px solid #ddd; padding: 6px; text-align: left; }
  th { background: #f0f2f5; }
  .amount { text-align: right; }
 </style>
</head>
<body>
 @php $emp = $payslip->employee; $payroll = $payslip->payroll; @endphp
 <h1>Payslip</h1>
 <p><strong>Ibay Enterprises Corporation</strong><br>
 Employee: {{ $emp?->full_name ?? 'Employee #' . $payslip->employee_id }} ({{ $emp?->position ?? '—' }})<br>
 Period: {{ $payroll->period_start->format('M d, Y') }} – {{ $payroll->period_end->format('M d, Y') }} &middot;
 Payroll #{{ $payroll->payroll_id }} ({{ $payroll->status }})</p>
 <table>
  <tr><th colspan="2">Earnings</th><th class="amount">Amount</th></tr>
  <tr><td>Days worked ({{ number_format((float) $payslip->days_worked, 2) }})</td><td>&times; &#8369;{{ number_format((float) ($emp?->daily_rate ?? 0), 2) }}</td><td class="amount">&#8369;{{ number_format((float) $payslip->days_worked * (float) ($emp?->daily_rate ?? 0), 2) }}</td></tr>
  <tr><td colspan="2">Overtime ({{ number_format((float) $payslip->overtime_hours, 2) }} hrs)</td><td class="amount">&#8369;{{ number_format((float) $payslip->gross_pay - (float) $payslip->days_worked * (float) ($emp?->daily_rate ?? 0), 2) }}</td></tr>
  <tr><th colspan="2">Gross pay</th><th class="amount">&#8369;{{ number_format((float) $payslip->gross_pay, 2) }}</th></tr>
  <tr><th colspan="2">Deductions</th><th class="amount">Amount</th></tr>
  <tr><td colspan="2">SSS</td><td class="amount">&#8369;{{ number_format((float) $payslip->sss_deduction, 2) }}</td></tr>
  <tr><td colspan="2">PhilHealth</td><td class="amount">&#8369;{{ number_format((float) $payslip->philhealth_deduction, 2) }}</td></tr>
  <tr><td colspan="2">Pag-IBIG</td><td class="amount">&#8369;{{ number_format((float) $payslip->pagibig_deduction, 2) }}</td></tr>
  <tr><td colspan="2">Withholding tax</td><td class="amount">&#8369;{{ number_format((float) $payslip->tax_deduction, 2) }}</td></tr>
  <tr><td colspan="2">Other deductions</td><td class="amount">&#8369;{{ number_format((float) $payslip->other_deductions, 2) }}</td></tr>
  <tr><th colspan="2">Total deductions</th><th class="amount">&#8369;{{ number_format((float) $payslip->total_deductions, 2) }}</th></tr>
  <tr><th colspan="2">Net pay</th><th class="amount">&#8369;{{ number_format((float) $payslip->net_pay, 2) }}</th></tr>
 </table>
</body>
</html>

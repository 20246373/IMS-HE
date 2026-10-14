<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Payslip #{{ $payslip->payslip_id }}</title>
<style>
 body{font-family:Arial,sans-serif;margin:0;padding:20px;max-width:600px;margin:auto}
 table{width:100%;border-collapse:collapse;margin:10px 0}
 th,td{padding:8px;border-bottom:1px solid #ddd;text-align:left}
 th{background:#f5f5f5}
 .right{text-align:right}
 .total{font-weight:bold}
 @media print{body{padding:0}}
</style>
</head>
<body onload="window.print()">
 <h2>Payslip</h2>
 <p><strong>Employee:</strong> {{ $payslip->employee->last_name }}, {{ $payslip->employee->first_name }}</p>
 <p><strong>Position:</strong> {{ $payslip->employee->position }}</p>
 <p><strong>Pay Period:</strong> {{ $payslip->payroll->period_start }} to {{ $payslip->payroll->period_end }}</p>
 <hr>
 <h3>Earnings</h3>
 <table>
  <tr><th>Description</th><th class="right">Amount</th></tr>
  <tr><td>Basic Pay ({{ $payslip->days_worked }} days × {{ number_format($payslip->employee->daily_rate, 2) }})</td><td class="right">{{ number_format($payslip->gross_pay - ($payslip->overtime_hours * $payslip->employee->daily_rate / 8), 2) }}</td></tr>
  @if($payslip->overtime_hours > 0)
  <tr><td>Overtime ({{ $payslip->overtime_hours }} hours)</td><td class="right">{{ number_format($payslip->overtime_hours * $payslip->employee->daily_rate / 8, 2) }}</td></tr>
  @endif
  <tr class="total"><td><strong>Gross Pay</strong></td><td class="right"><strong>{{ number_format($payslip->gross_pay, 2) }}</strong></td></tr>
 </table>
 <h3>Deductions</h3>
 <table>
  <tr><th>Description</th><th class="right">Amount</th></tr>
  <tr><td>SSS</td><td class="right">{{ number_format($payslip->sss_deduction, 2) }}</td></tr>
  <tr><td>PhilHealth</td><td class="right">{{ number_format($payslip->philhealth_deduction, 2) }}</td></tr>
  <tr><td>Pag-IBIG</td><td class="right">{{ number_format($payslip->pagibig_deduction, 2) }}</td></tr>
  <tr><td>Tax</td><td class="right">{{ number_format($payslip->tax_deduction, 2) }}</td></tr>
  @if($payslip->other_deductions > 0)
  <tr><td>Other</td><td class="right">{{ number_format($payslip->other_deductions, 2) }}</td></tr>
  @endif
  <tr class="total"><td><strong>Total Deductions</strong></td><td class="right"><strong>{{ number_format($payslip->total_deductions, 2) }}</strong></td></tr>
 </table>
 <hr>
 <h3>Net Pay: {{ number_format($payslip->net_pay, 2) }}</h3>
 <p style="margin-top:2rem;color:#666">Generated: {{ now()->toDateTimeString() }}</p>
</body>
</html>

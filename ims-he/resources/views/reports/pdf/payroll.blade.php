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
  th, td { border-bottom: 1px solid #ddd; padding: 6px; text-align: left; }
  th { background: #f0f2f5; }
  .amount { text-align: right; }
 </style>
</head>
<body>
 <h1>Payroll report</h1>
 <p>Payroll #{{ $payroll->payroll_id }} — {{ $payroll->status }} &middot;
    {{ $payroll->period_start->format('M d, Y') }} to {{ $payroll->period_end->format('M d, Y') }} &middot;
    Processed by {{ $payroll->processor?->label() ?? '—' }}</p>
 <div class="summary">
  <span><strong>Total gross:</strong> &#8369;{{ number_format((float) $payroll->total_gross, 2) }}</span>
  <span><strong>Total deductions:</strong> &#8369;{{ number_format((float) $payroll->total_deductions, 2) }}</span>
  <span><strong>Total net:</strong> &#8369;{{ number_format((float) $payroll->total_net, 2) }}</span>
 </div>
 <table>
  <thead><tr><th>Employee</th><th>Days</th><th>OT hrs</th><th class="amount">Gross</th><th class="amount">SSS</th><th class="amount">PhilHealth</th><th class="amount">Pag-IBIG</th><th class="amount">Tax</th><th class="amount">Other</th><th class="amount">Net pay</th></tr></thead>
  <tbody>
   @foreach($payroll->payslips as $payslip)
   <tr>
    <td>{{ $payslip->employee?->full_name ?? 'Employee #' . $payslip->employee_id }}</td>
    <td>{{ number_format((float) $payslip->days_worked, 2) }}</td>
    <td>{{ number_format((float) $payslip->overtime_hours, 2) }}</td>
    <td class="amount">&#8369;{{ number_format((float) $payslip->gross_pay, 2) }}</td>
    <td class="amount">&#8369;{{ number_format((float) $payslip->sss_deduction, 2) }}</td>
    <td class="amount">&#8369;{{ number_format((float) $payslip->philhealth_deduction, 2) }}</td>
    <td class="amount">&#8369;{{ number_format((float) $payslip->pagibig_deduction, 2) }}</td>
    <td class="amount">&#8369;{{ number_format((float) $payslip->tax_deduction, 2) }}</td>
    <td class="amount">&#8369;{{ number_format((float) $payslip->other_deductions, 2) }}</td>
    <td class="amount">&#8369;{{ number_format((float) $payslip->net_pay, 2) }}</td>
   </tr>
   @endforeach
  </tbody>
 </table>
</body>
</html>

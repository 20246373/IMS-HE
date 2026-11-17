<!doctype html><html lang="en"><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:9px;color:#222}h1{font-size:18px}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid #ddd;padding:5px;text-align:left}.amount{text-align:right}
</style></head><body>
<h1>Payroll report</h1>
<p>{{ $payroll->period_start->format('M d, Y') }} – {{ $payroll->period_end->format('M d, Y') }} · {{ $payroll->status }}</p>
<p><strong>Gross:</strong> {{ money($payroll->total_gross) }} &nbsp; <strong>Deductions:</strong> {{ money($payroll->total_deductions) }} &nbsp; <strong>Net:</strong> {{ money($payroll->total_net) }}</p>
<table><thead><tr><th>Employee</th><th>Days</th><th>OT</th><th class="amount">Gross</th><th class="amount">Deductions</th><th class="amount">Net</th></tr></thead><tbody>
@foreach($payroll->payslips as $payslip)<tr><td>{{ $payslip->employee->full_name }}</td><td>{{ number_format((float) $payslip->days_worked, 2) }}</td><td>{{ number_format((float) $payslip->overtime_hours, 2) }}</td><td class="amount">{{ money($payslip->gross_pay) }}</td><td class="amount">{{ money($payslip->total_deductions) }}</td><td class="amount">{{ money($payslip->net_pay) }}</td></tr>@endforeach
</tbody></table></body></html>

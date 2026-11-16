<div class="doc">
 <h1>{{ config('payroll.company_name') }}</h1>
 <div class="sub">PAYROLL REPORT &middot; {{ $payroll->period_start->format('M d, Y') }} &ndash; {{ $payroll->period_end->format('M d, Y') }}
  &middot; <span class="{{ $payroll->isFinal() ? 'final' : 'draft' }}">{{ $payroll->isFinal() ? 'FINAL' : 'DRAFT - not yet final' }}</span></div>
 <div class="sub">Payroll #{{ $payroll->payroll_id }} &middot; run by {{ $payroll->processor?->employee?->full_name ?? $payroll->processor?->username }}</div>

 <h2>Period totals</h2>
 <table>
  <tr><td>Employees paid</td><td class="num">{{ $totals['employees'] }}</td></tr>
  <tr><td>Total gross pay</td><td class="num">@peso($totals['gross'])</td></tr>
  <tr><td>&nbsp;&nbsp;SSS</td><td class="num">@peso($totals['sss'])</td></tr>
  <tr><td>&nbsp;&nbsp;PhilHealth</td><td class="num">@peso($totals['philhealth'])</td></tr>
  <tr><td>&nbsp;&nbsp;Pag-IBIG</td><td class="num">@peso($totals['pagibig'])</td></tr>
  <tr><td>&nbsp;&nbsp;Withholding tax</td><td class="num">@peso($totals['tax'])</td></tr>
  <tr><td>Total deductions</td><td class="num">@peso($totals['deductions'])</td></tr>
  <tr class="total"><td>Total net pay</td><td class="num">@peso($totals['net'])</td></tr>
 </table>

 <h2>Per employee</h2>
 <table>
  <tr><th>Employee</th><th class="num">Days</th><th class="num">OT hrs</th><th class="num">Gross pay</th><th class="num">Deductions</th><th class="num">Net pay</th></tr>
  @foreach($payroll->payslips as $s)
  <tr>
   <td>{{ $s->employee->full_name }}</td>
   <td class="num">{{ rtrim(rtrim(number_format($s->days_worked, 2), '0'), '.') }}</td>
   <td class="num">{{ number_format($s->overtime_hours, 2) }}</td>
   <td class="num">@peso($s->gross_pay)</td>
   <td class="num">@peso($s->total_deductions)</td>
   <td class="num">@peso($s->net_pay)</td>
  </tr>
  @endforeach
  <tr class="total"><td colspan="3">Totals</td><td class="num">@peso($totals['gross'])</td><td class="num">@peso($totals['deductions'])</td><td class="num">@peso($totals['net'])</td></tr>
 </table>
</div>

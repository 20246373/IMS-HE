@php($p = $payslip->payroll)
@php($e = $payslip->employee)
<div class="doc">
 <h1>{{ config('payroll.company_name') }}</h1>
 <div class="sub">PAYSLIP &middot; {{ $p->period_start->format('M d, Y') }} &ndash; {{ $p->period_end->format('M d, Y') }}
  &middot; <span class="{{ $p->isFinal() ? 'final' : 'draft' }}">{{ $p->isFinal() ? 'FINAL' : 'DRAFT - not yet final' }}</span></div>

 <table>
  <tr><th style="width:22%">Employee</th><td style="width:28%">{{ $e->full_name }}</td><th style="width:22%">Position</th><td>{{ $e->position }}</td></tr>
  <tr><th>SSS no.</th><td>{{ $e->sss_no ?? '-' }}</td><th>PhilHealth no.</th><td>{{ $e->philhealth_no ?? '-' }}</td></tr>
  <tr><th>Pag-IBIG no.</th><td>{{ $e->pagibig_no ?? '-' }}</td><th>TIN</th><td>{{ $e->tin_no ?? '-' }}</td></tr>
 </table>

 <h2>Attendance</h2>
 <table>
  <tr><th>Days worked</th><td class="num">{{ rtrim(rtrim(number_format($payslip->days_worked, 2), '0'), '.') }}</td></tr>
  <tr><th>Overtime hours</th><td class="num">{{ number_format($payslip->overtime_hours, 2) }}</td></tr>
 </table>

 <h2>Earnings</h2>
 <table>
  <tr class="total"><td>Gross pay (days worked and overtime)</td><td class="num">@peso($payslip->gross_pay)</td></tr>
 </table>

 <h2>Deductions</h2>
 <table>
  <tr><td>SSS</td><td class="num">@peso($payslip->sss_deduction)</td></tr>
  <tr><td>PhilHealth</td><td class="num">@peso($payslip->philhealth_deduction)</td></tr>
  <tr><td>Pag-IBIG</td><td class="num">@peso($payslip->pagibig_deduction)</td></tr>
  <tr><td>Withholding tax</td><td class="num">@peso($payslip->tax_deduction)</td></tr>
  @if($payslip->other_deductions > 0)<tr><td>Other deductions</td><td class="num">@peso($payslip->other_deductions)</td></tr>@endif
  <tr class="total"><td>Total deductions</td><td class="num">@peso($payslip->total_deductions)</td></tr>
 </table>

 <table>
  <tr class="total"><td style="font-size:14px">NET PAY</td><td class="num" style="font-size:14px">@peso($payslip->net_pay)</td></tr>
 </table>
 <div class="sign">Received by: ______________________________ &nbsp;&nbsp; Date: ________________</div>
</div>

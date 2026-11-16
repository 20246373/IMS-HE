@extends('layouts.app')
@section('title', 'Payroll #' . $payroll->payroll_id)
@section('content')
@php $isTreasurer = auth()->user()->hasRole('Treasurer'); @endphp
<div class="card">
 <div class="report-heading">
  <h2>Payroll #{{ $payroll->payroll_id }} — {{ $payroll->status }}</h2>
  <span class="no-print">
   @if($isTreasurer && $payroll->status === 'Draft')
    <form method="POST" action="{{ route('payroll.finalize', $payroll) }}" class="inline" onsubmit="return confirm('Finalize this payroll? This cannot be undone.');">
     @csrf <button type="submit">Finalize payroll</button>
    </form>
   @endif
   <a class="btn" href="{{ route('payroll.index') }}">Back</a>
  </span>
 </div>
 <p>
  Period: {{ $payroll->period_start->format('M d, Y') }} – {{ $payroll->period_end->format('M d, Y') }}
  &middot; Processed by {{ $payroll->processor?->label() ?? '—' }} on {{ $payroll->processed_date?->format('M d, Y g:i A') }}
 </p>
 <div class="report-summary">
  <div><strong>Total gross</strong><span>&#8369;{{ number_format((float) $payroll->total_gross, 2) }}</span></div>
  <div><strong>Total deductions</strong><span>&#8369;{{ number_format((float) $payroll->total_deductions, 2) }}</span></div>
  <div><strong>Total net</strong><span>&#8369;{{ number_format((float) $payroll->total_net, 2) }}</span></div>
 </div>

 <table>
  <thead><tr><th>Employee</th><th>Days</th><th>OT hrs</th><th>Gross</th><th>Deductions</th><th>Net pay</th><th></th></tr></thead>
  <tbody>
   @foreach($payroll->payslips as $payslip)
   <tr>
    <td>{{ $payslip->employee?->full_name ?? 'Employee #' . $payslip->employee_id }}</td>
    <td>{{ number_format((float) $payslip->days_worked, 2) }}</td>
    <td>{{ number_format((float) $payslip->overtime_hours, 2) }}</td>
    <td>&#8369;{{ number_format((float) $payslip->gross_pay, 2) }}</td>
    <td>&#8369;{{ number_format((float) $payslip->total_deductions, 2) }}</td>
    <td>&#8369;{{ number_format((float) $payslip->net_pay, 2) }}</td>
    <td class="no-print">
     <a class="btn" href="{{ route('payroll.payslip', [$payroll, $payslip]) }}">Payslip</a>
     @if($isTreasurer && $payroll->status === 'Draft')
      <details>
       <summary>Other deductions</summary>
       <form method="POST" action="{{ route('payroll.payslips.update', $payslip) }}" class="report-filter">
        @csrf @method('PATCH')
        <label>Amount <input type="number" name="other_deductions" step="0.01" min="0" value="{{ number_format((float) $payslip->other_deductions, 2, '.', '') }}" required></label>
        <button type="submit">Save</button>
       </form>
      </details>
     @endif
    </td>
   </tr>
   @endforeach
  </tbody>
 </table>
</div>
@endsection

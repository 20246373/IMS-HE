@extends('layouts.app')
@section('title','Payroll')
@section('content')
<div class="card">
 <h3>Payroll Processing</h3>
 <p><a href="{{ route('payrolls.create') }}" class="btn">Generate New Payroll</a></p>
 <table>
  <thead><tr><th>Period</th><th>Status</th><th>Total Gross</th><th>Total Net</th><th>Processed By</th><th>Actions</th></tr></thead>
  <tbody>
   @foreach($payrolls as $payroll)
    <tr>
     <td>{{ $payroll->period_start }} to {{ $payroll->period_end }}</td>
     <td>{{ $payroll->status }}</td>
     <td>{{ number_format($payroll->total_gross, 2) }}</td>
     <td>{{ number_format($payroll->total_net, 2) }}</td>
     <td>{{ $payroll->processor?->username }}</td>
     <td>
      <a href="{{ route('payrolls.show', $payroll) }}" class="btn">View</a>
      @if($payroll->status === 'Draft')
       <form method="POST" action="{{ route('payrolls.finalize', $payroll) }}" class="inline">@csrf<button class="btn btn-alt" onclick="return confirm('Finalize this payroll?')">Finalize</button></form>
      @endif
     </td>
    </tr>
   @endforeach
  </tbody>
 </table>
 {{ $payrolls->links() }}
</div>
@endsection

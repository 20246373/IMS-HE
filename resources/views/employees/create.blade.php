@extends('layouts.app')
@section('title','Add Employee')
@section('content')
<div class="card" style="max-width:480px">
 <h3>Add Employee</h3>
 <form method="POST" action="{{ route('employees.store') }}">@csrf
  <label>First Name <input type="text" name="first_name" required></label>
  <label>Last Name <input type="text" name="last_name" required></label>
  <label>Position <input type="text" name="position" required></label>
  <label>Daily Rate <input type="number" step="0.01" name="daily_rate" required></label>
  <label>SSS No <input type="text" name="sss_no"></label>
  <label>PhilHealth No <input type="text" name="philhealth_no"></label>
  <label>Pag-IBIG No <input type="text" name="pagibig_no"></label>
  <label>TIN No <input type="text" name="tin_no"></label>
  <label>Date Hired <input type="date" name="date_hired"></label>
  <label>Status <select name="status" required><option value="Active">Active</option><option value="Inactive">Inactive</option></select></label>
  <p><button>Save</button> <a href="{{ route('employees.index') }}" class="btn btn-alt">Cancel</a></p>
 </form>
</div>
@endsection

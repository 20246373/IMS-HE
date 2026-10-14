@extends('layouts.app')
@section('title','Financial Recording')
@section('content')
<div class="card">
 <h3>Financial Recording</h3>
 <p>Income: {{ number_format($income, 2) }} | Expenses: {{ number_format($expense, 2) }} | Balance: {{ number_format($balance, 2) }}</p>
 <form method="POST" action="{{ route('financial.entries.store') }}" style="margin:1rem 0;padding:1rem;background:#f9f9f9;border-radius:5px">@csrf
  <h4>Record Transaction</h4>
  <label>Date <input type="date" name="entry_date" value="{{ now()->toDateString() }}" required></label>
  <label>Type <select name="entry_type" required><option value="income">Income</option><option value="expense">Expense</option></select></label>
  <label>Amount <input type="number" step="0.01" name="amount" required></label>
  <label>Description <input type="text" name="description" maxlength="255"></label>
  <button type="submit">Record</button>
 </form>
 <form method="GET" action="{{ route('financial.index') }}" style="margin-bottom:1rem">
  <label>Filter by date <input type="date" name="start_date" value="{{ request('start_date') }}"> to <input type="date" name="end_date" value="{{ request('end_date') }}"></label>
  <label>Type <select name="entry_type"><option value="">All</option><option value="income" {{ request('entry_type') == 'income' ? 'selected' : '' }}>Income</option><option value="expense" {{ request('entry_type') == 'expense' ? 'selected' : '' }}>Expense</option></select></label>
  <button type="submit">Filter</button>
  <a href="{{ route('financial.index') }}" class="btn btn-alt">Clear</a>
 </form>
 <table>
  <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Description</th><th>Recorded By</th><th>Actions</th></tr></thead>
  <tbody>
   @foreach($entries as $entry)
    <tr>
     <td>{{ $entry->entry_date }}</td>
     <td>{{ $entry->entry_type }}</td>
     <td>{{ number_format($entry->amount, 2) }}</td>
     <td>{{ $entry->description }}</td>
     <td>{{ $entry->recorder?->username ?? 'System' }}</td>
     <td>
      @if($entry->recorded_by === auth()->id() || auth()->user()->hasRole('Admin'))
       <a href="#" onclick="editEntry({{ $entry->ledger_id }}, '{{ $entry->entry_date }}', '{{ $entry->entry_type }}', {{ $entry->amount }}, '{{ $entry->description }}')" class="btn btn-alt">Edit</a>
       <form method="POST" action="{{ route('financial.entries.destroy', $entry) }}" class="inline">@csrf @method('DELETE')<button class="danger" onclick="return confirm('Delete this entry?')">Delete</button></form>
      @endif
     </td>
    </tr>
   @endforeach
  </tbody>
 </table>
 {{ $entries->links() }}
</div>
<div id="editModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000">
 <div class="card" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:400px;max-width:90%">
  <h4>Edit Financial Entry</h4>
  <form method="POST" action="" id="editForm">@csrf @method('PUT')
   <input type="hidden" name="entry_date" id="editDate">
   <label>Type <select name="entry_type" id="editType"><option value="income">Income</option><option value="expense">Expense</option></select></label>
   <label>Amount <input type="number" step="0.01" name="amount" id="editAmount" required></label>
   <label>Description <input type="text" name="description" id="editDescription" maxlength="255"></label>
   <button type="submit">Update</button>
   <button type="button" onclick="closeEdit()" class="btn btn-alt">Cancel</button>
  </form>
 </div>
</div>
<script>
function editEntry(id, date, type, amount, description) {
 document.getElementById('editForm').action = '/financial/entries/' + id;
 document.getElementById('editDate').value = date;
 document.getElementById('editType').value = type;
 document.getElementById('editAmount').value = amount;
 document.getElementById('editDescription').value = description;
 document.getElementById('editModal').style.display = 'block';
}
function closeEdit() {
 document.getElementById('editModal').style.display = 'none';
}
</script>
@endsection

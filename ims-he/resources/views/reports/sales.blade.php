@extends('layouts.app')
@section('title', 'Sales report')
@section('heading', 'Sales Report')
@section('page-actions')
  <a class="btn" href="{{ route('reports.sales.download', array_filter(['start_date' => $startDate, 'end_date' => $endDate, 'category_id' => $categoryId])) }}">Save as PDF</a>
@endsection
@section('content')
<div class="card">
 <form method="GET" action="{{ route('reports.sales') }}" class="no-print report-filter">
  <label>From <input type="date" name="start_date" value="{{ $startDate }}" required></label>
  <label>To <input type="date" name="end_date" value="{{ $endDate }}" required></label>
  <label>Category
   <select name="category_id">
    <option value="">All categories</option>
    @foreach($categories as $category)
     <option value="{{ $category->category_id }}" @selected((int) $categoryId === (int) $category->category_id)>{{ $category->category_name }}</option>
    @endforeach
   </select>
  </label>
  <button type="submit">Apply filter</button>
 </form>
 <div class="report-summary">
  <div><strong>Total revenue</strong><span>{{ money($report['totalRevenue']) }}</span></div>
  <div><strong>Total transactions</strong><span>{{ number_format($report['totalTransactions']) }}</span></div>
 </div>
 <h3>Sales by category</h3>
 <table>
  <thead><tr><th>Category</th><th>Units</th><th>Revenue</th></tr></thead>
  <tbody>
   @forelse($report['categorySales'] as $categorySale)
   <tr><td>{{ $categorySale->category_name }}</td><td>{{ number_format($categorySale->units) }}</td><td>{{ money($categorySale->revenue) }}</td></tr>
   @empty
   <tr><td colspan="3">No completed sales for this date range.</td></tr>
   @endforelse
  </tbody>
 </table>
 <h3>Completed sales</h3>
 <table>
  <thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Total</th></tr></thead>
  <tbody>
   @forelse($report['sales'] as $sale)
   <tr>
    <td>#{{ $sale->invoice_id }}</td>
    <td>{{ \Illuminate\Support\Carbon::parse($sale->invoice_date)->format('M d, Y') }}</td>
    <td>{{ trim(($sale->first_name ?? '') . ' ' . ($sale->last_name ?? '')) ?: 'Walk-in customer' }}</td>
    <td>{{ money($sale->total_amount) }}</td>
   </tr>
   @empty
   <tr><td colspan="4">No completed sales for this date range.</td></tr>
   @endforelse
  </tbody>
 </table>
</div>
@endsection

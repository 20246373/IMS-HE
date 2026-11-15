@extends('layouts.app')
@section('title', 'Stock history - ' . $product->product_name)
@push('head')
<style>
 .badge{display:inline-block;padding:.12rem .5rem;border-radius:999px;background:#eef0f3;color:#333;font-size:.72rem;font-weight:700;line-height:1.2;vertical-align:middle}
 .badge.green{background:#e8f7ed;color:#176b35}
 .badge.amber{background:#fff5d8;color:#785b00}
</style>
@endpush
@section('content')
<div class="card">
 <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap">
  <div>
   <h2 style="margin:0">Stock history: {{ $product->product_name }}</h2>
   <p style="margin:.35rem 0 0">
    Current stock: <strong>{{ $product->stock_quantity }}</strong>
    &middot; Reorder level: <strong>{{ $product->reorder_level }}</strong>
   </p>
  </div>
  <a class="btn" href="{{ route('products.index') }}">Back to products</a>
 </div>

 <table style="margin-top:1rem">
  <tr>
   <th>Date</th>
   <th>Type</th>
   <th>Quantity</th>
   <th>Reference type</th>
   <th>Reference ID</th>
   <th>Resulting stock</th>
  </tr>
  @forelse($transactions as $transaction)
  <tr>
   <td>{{ $transaction->transaction_date->format('M d, Y') }}</td>
   <td><span class="badge {{ $transaction->transaction_type === 'IN' ? 'green' : 'amber' }}">{{ $transaction->transaction_type }}</span></td>
   <td>{{ $transaction->quantity }}</td>
   <td>{{ $transaction->reference_type }}</td>
   <td>{{ $transaction->reference_id }}</td>
   <td>{{ $transaction->resulting_stock ?? '—' }}</td>
  </tr>
  @empty
  <tr><td colspan="6">No stock movements recorded for this product.</td></tr>
  @endforelse
 </table>

 {{ $transactions->links() }}
</div>
@endsection

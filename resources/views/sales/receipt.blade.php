<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Receipt #{{ $invoice->invoice_id }}</title>
<style>
 body{font-family:monospace;margin:0;padding:20px;max-width:300px;margin:auto}
 .center{text-align:center}
 .right{text-align:right}
 table{width:100%;border-collapse:collapse;margin:10px 0}
 hr{border:0;border-top:1px dashed #000;margin:10px 0}
 @media print{body{padding:0}}
</style>
</head>
<body onload="window.print()">
 <div class="center">
  <h3>IMS-HE</h3>
  <p>Ibay Enterprises</p>
 </div>
 <hr>
 <p><strong>Invoice #{{ $invoice->invoice_id }}</strong></p>
 <p>Date: {{ $invoice->invoice_date }}</p>
 <p>Customer: {{ $invoice->customer?->last_name.', '.$invoice->customer?->first_name ?? 'Walk-in' }}</p>
 <hr>
 <table>
  <thead><tr><th>Item</th><th class="right">Qty</th><th class="right">Price</th><th class="right">Total</th></tr></thead>
  <tbody>
   @foreach($invoice->items as $item)
    <tr>
     <td>{{ $item->product->product_name }}</td>
     <td class="right">{{ $item->quantity }}</td>
     <td class="right">{{ number_format($item->price, 2) }}</td>
     <td class="right">{{ number_format($item->subtotal, 2) }}</td>
    </tr>
   @endforeach
  </tbody>
 </table>
 <hr>
 <p class="right"><strong>Total: {{ number_format($invoice->total_amount, 2) }}</strong></p>
 @if($invoice->payment)
 <p>Payment: {{ $invoice->payment->payment_method }}</p>
 <p>Paid: {{ number_format($invoice->payment->amount_paid, 2) }}</p>
 <p>Change: {{ number_format($invoice->payment->change_amount, 2) }}</p>
 @endif
 <hr>
 <div class="center">
  <p>Thank you for your purchase!</p>
  <p>Generated: {{ now()->toDateTimeString() }}</p>
 </div>
 @if(session('status'))
 <div style="margin-top:20px;padding:10px;background:#fff3cd;border:1px solid #ffc107">{{ session('status') }}</div>
 @endif
</body>
</html>

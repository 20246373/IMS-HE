@if($pendingPurchaseOrders->count())
<div class="card">
    <h3>Pending Purchase Orders</h3>
    <table>
        <tr>
            <th>PO #</th>
            <th>Supplier</th>
            <th>Date</th>
            <th>Total</th>
            <th></th>
        </tr>
        @foreach($pendingPurchaseOrders as $po)
        <tr>
            <td>{{ $po->id }}</td>
            <td>{{ $po->supplier->name }}</td>
            <td>{{ $po->order_date->format('M j, Y') }}</td>
            <td>&#8369;{{ number_format($po->total(), 2) }}</td>
            <td>
                <a class="btn" href="{{ route('purchase-orders.show', $po) }}">View</a>
                <a class="btn" href="{{ route('deliveries.show', $po) }}">Receive</a>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endif

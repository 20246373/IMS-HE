<x-alert type="warning" title="Low-stock products ({{ $lowStockCount }})">
 <p style="margin:0 0 .55rem">
  @if($lowStockCount === 0)
   All products are above their reorder levels.
  @else
   {{ $lowStockCount }} {{ $lowStockCount === 1 ? 'product is' : 'products are' }} at or below the reorder level.
  @endif
 </p>
 <a class="btn" href="{{ route('stock.index', ['low' => 1]) }}">View low-stock list</a>
</x-alert>

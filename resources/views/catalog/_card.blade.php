@php($sold = (int) ($p->invoice_items_sum_quantity ?? 0))
<a class="pcard" href="{{ route('catalog.show',$p) }}">
 <div class="pimg {{ $p->stock_quantity < 1 ? 'sold' : '' }}">
  @if($p->image_path)<img src="{{ asset('storage/'.$p->image_path) }}" alt="{{ $p->name }}">@else{{ mb_strtoupper(mb_substr($p->name,0,1)) }}@endif
  @if($p->stock_quantity > 0 && $p->isLowStock())<span class="tag warn">Few left</span>
  @elseif($sold >= 10)<span class="tag">Best seller</span>@endif
 </div>
 <div class="pbody">
  <div class="pname">{{ $p->name }}</div>
  <div class="pbrand">{{ $p->brand ?: ($p->category?->name) }}</div>
  <div class="pprice"><small>&#8369;</small>{{ number_format($p->unit_price,2) }}</div>
  <div class="pmeta"><span>{{ $sold > 0 ? $sold.' sold' : 'New' }}</span><span>{{ $p->stock_quantity > 0 ? 'In stock' : 'Out of stock' }}</span></div>
 </div>
</a>

{{-- One product tile; used by the landing page and the catalog grid --}}
<a class="card product" href="{{ route('catalog.show', $product) }}">
 @if($product->image_path)
  <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}">
 @else
  <div class="noimg">No image</div>
 @endif
 <small>{{ $product->category->category_name }}@if($product->brand) &middot; {{ $product->brand }}@endif</small>
 <strong>{{ $product->product_name }}</strong>
 <div>{{ money($product->unit_price) }}</div>
 @include('catalog._stock')
</a>

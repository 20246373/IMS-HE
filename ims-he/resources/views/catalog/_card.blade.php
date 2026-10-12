{{-- One product tile; used by the landing page and the catalog grid --}}
<a class="card product" href="{{ route('catalog.show', $product) }}">
 @if($product->image_path)
  <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}">
 @else
  <div class="noimg">No image</div>
 @endif
 <small>{{ $product->category->category_name }}</small>
 <strong>{{ $product->product_name }}</strong>
 <div>&#8369;{{ number_format($product->unit_price, 2) }}</div>
 @include('catalog._stock')
</a>

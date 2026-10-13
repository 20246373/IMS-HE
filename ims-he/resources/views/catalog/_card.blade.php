{{-- One product tile; used by the landing page and the catalog grid --}}
<div class="card product">
 @if($product->image_path)
  <a href="{{ route('catalog.show', $product) }}"><img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}"></a>
 @else
  <a href="{{ route('catalog.show', $product) }}"><div class="noimg">No image</div></a>
 @endif
 <small>{{ $product->category->category_name }}</small>
 <strong><a href="{{ route('catalog.show', $product) }}">{{ $product->product_name }}</a></strong>
 <div>&#8369;{{ number_format($product->unit_price, 2) }}</div>
 @include('catalog._stock')
 @if($product->isInStock())
  <form method="POST" action="{{ route('customer.cart.add') }}" class="inline">@csrf
   <input type="hidden" name="product_id" value="{{ $product->product_id }}">
   <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock_quantity }}" step="1" style="width:50px" required>
   <button>Add to cart</button>
  </form>
 @else
  <button disabled>Out of stock</button>
 @endif
</div>

{{-- One product tile; used by the landing page and the catalog grid --}}
<div class="card product">
 @if($product->image_path)
  <a class="product-media" href="{{ route('catalog.show', $product) }}">
   <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->product_name }}">
  </a>
 @else
  <a class="product-media" href="{{ route('catalog.show', $product) }}">
   <div class="noimg">No image</div>
  </a>
 @endif
 <a class="product-name" href="{{ route('catalog.show', $product) }}">
  <strong>{{ $product->product_name }}</strong>
 </a>
 <small>{{ $product->category->category_name }}@if($product->brand) &middot; {{ $product->brand }}@endif</small>
 <div class="product-foot">
  <span class="price">{{ money($product->unit_price) }}</span>
  @include('catalog._stock')
 </div>
 @if($product->isInStock())
  {{-- Guests are sent to login by auth:customer, same as the product page (UC19 includes UC01). --}}
  <form method="POST" action="{{ route('customer.cart.add', $product) }}">@csrf
   <input type="hidden" name="quantity" value="1">
   <button class="product-add" type="submit">Add to Cart</button>
  </form>
 @endif
</div>

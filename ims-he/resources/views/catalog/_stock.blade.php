{{-- Stock badge from stock_quantity and reorder_level: Out of stock (0), Low stock (at or below the reorder level), In stock. --}}
@if(! $product->isInStock())<span class="stock out">Out of stock</span>
@elseif($product->isLowStock())<span class="stock low">Low stock</span>
@else<span class="stock in">In stock</span>@endif

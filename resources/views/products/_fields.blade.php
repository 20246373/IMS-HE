<label>Product name <input name="product_name" value="{{ old('product_name', $product?->product_name) }}" maxlength="100" required></label>
<label>Category
 <select name="category_id" required>
  <option value="">-- choose --</option>
  @foreach($categories as $c)
   <option value="{{ $c->category_id }}" @selected((string) old('category_id', $product?->category_id) === (string) $c->category_id)>{{ $c->category_name }}</option>
  @endforeach
 </select>
</label>
<label>Description <textarea name="description" rows="2" maxlength="255">{{ old('description', $product?->description) }}</textarea></label>
<label>Unit price <input type="number" step="0.01" min="0.01" name="unit_price" value="{{ old('unit_price', $product?->unit_price) }}" required></label>
<label>Stock quantity <input type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity', $product?->stock_quantity ?? 0) }}" required></label>
<label>Reorder level <input type="number" min="0" name="reorder_level" value="{{ old('reorder_level', $product?->reorder_level ?? 0) }}" required></label>
<label>Photo (jpg, png or webp, max 2 MB)
 @if($product?->image_path)<img src="{{ asset('storage/' . $product->image_path) }}" alt="" style="display:block;max-height:90px;margin:.3rem 0">@endif
 <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
</label>

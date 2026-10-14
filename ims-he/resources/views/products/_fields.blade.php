<label>Name <input name="product_name" value="{{ old('product_name',$product?->product_name) }}" required></label>
<label>Category <select name="category_id"><option value="">-</option>
 @foreach($categories as $c)<option value="{{ $c->category_id }}" @selected(old('category_id',$product?->category_id)==$c->category_id)>{{ $c->category_name }}</option>@endforeach
</select></label>
<label>Description <textarea name="description" rows="2">{{ old('description',$product?->description) }}</textarea></label>
<label>Unit price <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price',$product?->unit_price ?? 0) }}" required></label>
<label>Stock quantity <input type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity',$product?->stock_quantity ?? 0) }}" required></label>
<label>Reorder level <input type="number" min="0" name="reorder_level" value="{{ old('reorder_level',$product?->reorder_level ?? 0) }}" required></label>

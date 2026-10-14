<label>Name <input name="name" value="{{ old('name',$product?->name) }}" required></label>
<label>Brand <input name="brand" value="{{ old('brand',$product?->brand) }}"></label>
<label>Category <select name="category_id"><option value="">-</option>
 @foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id',$product?->category_id)==$c->id)>{{ $c->name }}</option>@endforeach
</select></label>
<label>Description <textarea name="description" rows="2">{{ old('description',$product?->description) }}</textarea></label>
<label>Unit price <input type="number" step="0.01" min="0" name="unit_price" value="{{ old('unit_price',$product?->unit_price ?? 0) }}" required></label>
<label>Stock quantity <input type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity',$product?->stock_quantity ?? 0) }}" required></label>
<label>Reorder point <input type="number" min="0" name="reorder_point" value="{{ old('reorder_point',$product?->reorder_point ?? 0) }}" required></label>

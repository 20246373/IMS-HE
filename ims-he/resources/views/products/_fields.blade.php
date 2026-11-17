<div class="form-grid">
 @if($product?->exists)
 <div class="field">
  <label for="product_id">Product ID</label>
  <input id="product_id" value="{{ $product->product_id }}" readonly>
 </div>
 <div class="field">
  <label for="stock_quantity_display">Stock quantity</label>
  <input id="stock_quantity_display" value="{{ $product->stock_quantity }}" readonly>
 </div>
 @endif
 <x-field label="Product name" name="product_name" :value="$product?->product_name" maxlength="100" />
 <x-field label="Brand" name="brand" :value="$product?->brand" maxlength="50" />
 <x-select label="Category" name="category_id">
  <option value="">-- choose --</option>
  @foreach($categories as $c)
   <option value="{{ $c->category_id }}" @selected((string) old('category_id', $product?->category_id) === (string) $c->category_id)>{{ $c->category_name }}</option>
  @endforeach
 </x-select>
 <x-select label="Default supplier" name="supplier_id" :required="false">
  <option value="">-- none --</option>
  @foreach($suppliers as $s)
   <option value="{{ $s->supplier_id }}" @selected((string) old('supplier_id', $product?->supplier_id) === (string) $s->supplier_id)>{{ $s->supplier_name }}</option>
  @endforeach
 </x-select>
 <div class="field">
  <label class="optional" for="description">Description</label>
  <textarea id="description" name="description" rows="2" maxlength="255">{{ old('description', $product?->description) }}</textarea>
  @error('description')<div class="field-error">{{ $message }}</div>@enderror
 </div>
 <x-field label="Unit price" name="unit_price" type="number" step="0.01" min="0.01" :value="$product?->unit_price" prefix="₱" />
 @unless($product?->exists)
  <x-field label="Stock quantity" name="stock_quantity" type="number" min="0" :value="$product?->stock_quantity ?? 0" />
 @endunless
 <x-field label="Reorder level" name="reorder_level" type="number" min="0" :value="$product?->reorder_level ?? 0" />
 <div class="field">
  <label class="optional" for="image">Photo (jpg, png or webp, max 2 MB)</label>
  @if($product?->image_path)<img src="{{ asset('storage/' . $product->image_path) }}" alt="" style="display:block;max-height:90px;margin:.3rem 0">@endif
  <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp">
  @error('image')<div class="field-error">{{ $message }}</div>@enderror
 </div>
</div>

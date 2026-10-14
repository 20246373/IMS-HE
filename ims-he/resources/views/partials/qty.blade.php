{{-- Quantity box with - / + buttons. Needs: $value, $max. The buttons are plain type="button", so they never submit the form. --}}
<span class="stepper">
 <button type="button" data-step="-1" aria-label="Decrease quantity">&minus;</button>
 <input type="number" name="quantity" value="{{ $value }}" min="1" max="{{ $max }}" required>
 <button type="button" data-step="1" aria-label="Increase quantity">+</button>
</span>

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $guarded = [];

    public function cart() { return $this->belongsTo(Cart::class); }
    public function product() { return $this->belongsTo(Product::class); }

    /** Always priced from the live product price, so totals recalculate. */
    public function subtotal(): float
    {
        return round((float) $this->product->unit_price * $this->quantity, 2);
    }
}

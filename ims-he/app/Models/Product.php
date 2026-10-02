<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = ["unit_price" => "decimal:2"];
    public function category() { return $this->belongsTo(Category::class); }
    public function isLowStock(): bool { return $this->stock_quantity <= $this->reorder_point; }
}

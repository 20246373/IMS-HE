<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'product_id';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['unit_price' => 'decimal:2', 'is_active' => 'boolean'];

    public function category() { return $this->belongsTo(Category::class, 'category_id', 'category_id'); }
    public function isLowStock(): bool { return $this->stock_quantity <= $this->reorder_level; }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    protected $table = 'stock_adjustments';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['adjusted_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by', 'user_id');
    }
}

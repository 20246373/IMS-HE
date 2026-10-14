<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $table = 'inventory_transactions';
    protected $primaryKey = 'transaction_id';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['transaction_date' => 'date'];

    public function product() { return $this->belongsTo(Product::class, 'product_id', 'product_id'); }
}

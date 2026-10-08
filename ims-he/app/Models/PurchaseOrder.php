<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';
    protected $primaryKey = 'po_id';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['order_date' => 'date'];

    public function supplier() { return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id'); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class, 'po_id', 'po_id'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = ["order_date" => "date", "received_at" => "datetime"];
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function total(): float { return (float) $this->items->sum('subtotal'); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class); }
}

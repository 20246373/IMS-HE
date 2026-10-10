<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded = [];

    protected $casts = ["invoice_date" => "date"];
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function payment() { return $this->hasOne(Payment::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function user() { return $this->belongsTo(User::class); }
}

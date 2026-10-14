<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';
    protected $primaryKey = 'payment_id';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['payment_date' => 'date'];

    public function invoice() { return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id'); }
}

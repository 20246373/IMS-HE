<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoices';
    protected $primaryKey = 'invoice_id';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['invoice_date' => 'date'];

    public function customer() { return $this->belongsTo(Customer::class, 'customer_id', 'customer_id'); }
    public function employee() { return $this->belongsTo(Employee::class, 'employee_id', 'employee_id'); }
    public function items() { return $this->hasMany(InvoiceItem::class, 'invoice_id', 'invoice_id'); }
    public function payment() { return $this->hasOne(Payment::class, 'invoice_id', 'invoice_id'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $guarded = [];

    public function payroll() { return $this->belongsTo(Payroll::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
}

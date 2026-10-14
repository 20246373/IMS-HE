<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $table = 'payslips';
    protected $primaryKey = 'payslip_id';
    public $timestamps = false;
    protected $guarded = [];

    public function payroll() { return $this->belongsTo(Payroll::class, 'payroll_id', 'payroll_id'); }
    public function employee() { return $this->belongsTo(Employee::class, 'employee_id', 'employee_id'); }
}

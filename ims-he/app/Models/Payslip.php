<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    protected $table = 'payslips';
    protected $primaryKey = 'payslip_id';
    public $timestamps = false;
    protected $guarded = [];

    /** Payslips of a finalized payroll are read-only. */
    protected static function booted(): void
    {
        $lock = function (Payslip $payslip) {
            if (Payroll::whereKey($payslip->payroll_id)->value('status') === Payroll::STATUS_FINAL) {
                throw new \LogicException('Payslips of a finalized payroll cannot be changed.');
            }
        };
        static::creating($lock);
        static::updating($lock);
        static::deleting($lock);
    }

    public function payroll() { return $this->belongsTo(Payroll::class, 'payroll_id', 'payroll_id'); }
    public function employee() { return $this->belongsTo(Employee::class, 'employee_id', 'employee_id'); }
}

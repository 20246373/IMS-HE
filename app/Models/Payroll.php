<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $table = 'payrolls';
    protected $primaryKey = 'payroll_id';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['period_start' => 'date', 'period_end' => 'date', 'processed_date' => 'datetime'];

    public function processor() { return $this->belongsTo(User::class, 'processed_by', 'user_id'); }
    public function payslips() { return $this->hasMany(Payslip::class, 'payroll_id', 'payroll_id'); }
}

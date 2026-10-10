<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    protected $guarded = [];

    protected $casts = ["period_start" => "date", "period_end" => "date", "processed_at" => "datetime"];
    public function payslips() { return $this->hasMany(Payslip::class); }
}

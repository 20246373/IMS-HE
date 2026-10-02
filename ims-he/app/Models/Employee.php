<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $guarded = [];

    public function attendances() { return $this->hasMany(Attendance::class); }
    public function payslips() { return $this->hasMany(Payslip::class); }
    public function getFullNameAttribute(): string { return "{$this->first_name} {$this->last_name}"; }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'employee_id';
    public $timestamps = false;
    protected $guarded = [];

    public function user() { return $this->hasOne(User::class, 'employee_id', 'employee_id'); }
    public function attendance() { return $this->hasMany(Attendance::class, 'employee_id', 'employee_id'); }
    public function payslips() { return $this->hasMany(Payslip::class, 'employee_id', 'employee_id'); }
    public function invoices() { return $this->hasMany(Invoice::class, 'employee_id', 'employee_id'); }
    public function getFullNameAttribute(): string { return $this->first_name . ' ' . $this->last_name; }
}

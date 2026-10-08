<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';
    protected $primaryKey = 'attendance_id';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['work_date' => 'date'];

    public function employee() { return $this->belongsTo(Employee::class, 'employee_id', 'employee_id'); }
}

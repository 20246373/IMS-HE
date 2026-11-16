<?php

namespace App\Models;

use App\Services\AttendanceService;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';
    protected $primaryKey = 'attendance_id';
    public $timestamps = false;
    protected $guarded = [];

    // 'date:Y-m-d' so SQLite and MySQL both store a plain date (the unique key and date filters depend on it).
    protected $casts = ['work_date' => 'date:Y-m-d'];

    public function hoursWorked(): ?float { return AttendanceService::hoursWorked($this->time_in, $this->time_out); }

    public function employee() { return $this->belongsTo(Employee::class, 'employee_id', 'employee_id'); }
}

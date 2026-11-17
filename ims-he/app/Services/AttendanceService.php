<?php

namespace App\Services;

use Carbon\Carbon;

class AttendanceService
{
    public function calculateOvertime(?string $timeIn, ?string $timeOut): float
    {
        if ($timeIn === null || $timeOut === null) {
            return 0.0;
        }

        $minutes = Carbon::parse($timeIn)->diffInMinutes(Carbon::parse($timeOut), false);

        if ($minutes <= 8 * 60) {
            return 0.0;
        }

        return round(($minutes - 8 * 60) / 60 * 4) / 4;
    }
}

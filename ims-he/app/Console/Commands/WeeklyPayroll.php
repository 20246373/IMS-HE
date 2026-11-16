<?php

namespace App\Console\Commands;

use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Weekly payroll computation (SDD 4.2 automated process): builds a Draft
 * payroll for the previous Monday-Sunday week. Idempotent - skips when a
 * batch already exists for that period. Scheduled weekly in routes/console.php.
 */
class WeeklyPayroll extends Command
{
    protected $signature = 'payroll:compute-weekly {--week= : Monday of the week to compute (Y-m-d), defaults to last week}';

    protected $description = 'Compute a Draft payroll batch for the previous work week';

    public function handle(PayrollService $payroll): int
    {
        $monday = $this->option('week')
            ? Carbon::parse($this->option('week'))->startOfWeek()
            : now()->subWeek()->startOfWeek();
        $sunday = $monday->copy()->endOfWeek();

        $exists = Payroll::whereDate('period_start', $monday->toDateString())
            ->whereDate('period_end', $sunday->toDateString())
            ->exists();

        if ($exists) {
            $this->info("Payroll for {$monday->toDateString()} to {$sunday->toDateString()} already exists - skipped.");

            return self::SUCCESS;
        }

        // payrolls.processed_by is a required FK to users; the batch is attributed
        // to the first Treasurer account (the only role allowed to run payroll).
        $treasurer = User::where('role', User::TREASURER)->where('status', User::STATUS_ACTIVE)->first();

        if ($treasurer === null) {
            $this->warn('No active Treasurer account - payroll batch not created.');

            return self::FAILURE;
        }

        $batch = $payroll->run($monday, $sunday, $treasurer->user_id);

        $this->info("Created Draft payroll #{$batch->payroll_id} for {$monday->toDateString()} to {$sunday->toDateString()} ({$batch->payslips()->count()} payslips).");

        return self::SUCCESS;
    }
}

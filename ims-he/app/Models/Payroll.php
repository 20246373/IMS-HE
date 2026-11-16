<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    public const STATUS_DRAFT = 'Draft';
    public const STATUS_FINAL = 'Final';

    protected $table = 'payrolls';
    protected $primaryKey = 'payroll_id';
    public $timestamps = false;
    protected $guarded = [];

    // 'date:Y-m-d' keeps SQLite (dev) and MySQL (prod) storing the same plain date, so period comparisons match.
    protected $casts = ['period_start' => 'date:Y-m-d', 'period_end' => 'date:Y-m-d', 'processed_date' => 'datetime'];

    /** A finalized payroll can never be edited or deleted, by any code path. */
    protected static function booted(): void
    {
        $lock = function (Payroll $payroll) {
            if ($payroll->getOriginal('status') === self::STATUS_FINAL) {
                throw new \LogicException('A finalized payroll cannot be changed.');
            }
        };
        static::updating($lock);
        static::deleting($lock);
    }

    public function processor() { return $this->belongsTo(User::class, 'processed_by', 'user_id'); }
    public function isDraft(): bool { return $this->status === self::STATUS_DRAFT; }
    public function isFinal(): bool { return $this->status === self::STATUS_FINAL; }
    public function payslips() { return $this->hasMany(Payslip::class, 'payroll_id', 'payroll_id'); }
}

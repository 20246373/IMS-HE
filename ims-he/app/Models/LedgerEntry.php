<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    protected $table = 'ledger_entries';
    protected $primaryKey = 'ledger_id';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['entry_date' => 'date'];

    public function recorder() { return $this->belongsTo(User::class, 'recorded_by', 'user_id'); }
}

<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\LedgerEntry;
use Illuminate\Validation\ValidationException;

class LedgerService
{
    public function record(
        string $type,
        float $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): LedgerEntry {
        if (! in_array($type, ['Income', 'Expense'], true)) {
            throw ValidationException::withMessages(['entry_type' => 'The ledger type must be Income or Expense.']);
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'The amount must be greater than zero.']);
        }

        return LedgerEntry::create([
            'entry_date' => today(),
            'entry_type' => $type,
            'amount' => $amount,
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'recorded_by' => auth()->id(),
        ]);
    }

    public function recordSale(Invoice $invoice): ?LedgerEntry
    {
        if (LedgerEntry::query()
            ->where('entry_type', 'Income')
            ->where('reference_type', 'Invoice')
            ->where('reference_id', $invoice->invoice_id)
            ->exists()) {
            return null;
        }

        return $this->record(
            'Income',
            (float) $invoice->total_amount,
            "Sale - Invoice #{$invoice->invoice_id}",
            'Invoice',
            $invoice->invoice_id,
        );
    }
}

<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Financial Recording module (SDD 4.1, 7.2.18): the only place that writes
 * ledger_entries. Callers run inside their own DB::transaction so a ledger
 * write rolls back together with the business transaction that caused it.
 *
 *   app(FinancialService::class)->recordIncome('Invoice', $invoice->invoice_id, $total, 'POS sale #1', $userId);
 */
class FinancialService
{
    public const INCOME = 'Income';

    public const EXPENSE = 'Expense';

    /**
     * @param  'Income'|'Expense'  $type
     */
    public function record(string $type, ?string $referenceType, ?int $referenceId, float $amount, ?string $description = null, ?int $recordedBy = null): LedgerEntry
    {
        abort_unless(in_array($type, [self::INCOME, self::EXPENSE], true), 500, 'Invalid ledger entry type.');
        abort_if($amount <= 0, 500, 'Ledger entries must be positive.');

        return LedgerEntry::create([
            'entry_date' => today(),
            'entry_type' => $type,
            'amount' => round($amount, 2),
            'description' => $description,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'recorded_by' => $recordedBy,
        ]);
    }

    public function recordIncome(string $referenceType, int $referenceId, float $amount, ?string $description = null, ?int $recordedBy = null): LedgerEntry
    {
        return $this->record(self::INCOME, $referenceType, $referenceId, $amount, $description, $recordedBy);
    }

    public function recordExpense(string $referenceType, int $referenceId, float $amount, ?string $description = null, ?int $recordedBy = null): LedgerEntry
    {
        return $this->record(self::EXPENSE, $referenceType, $referenceId, $amount, $description, $recordedBy);
    }

    /** Manual income/expense entry; no source document, so reference fields stay null (SDD 7.2.18). */
    public function recordManual(string $type, float $amount, string $description, ?int $recordedBy = null): LedgerEntry
    {
        $entry = $this->record($type, null, null, $amount, $description, $recordedBy);

        AuditLog::create([
            'user_id' => $recordedBy,
            'action' => 'Created',
            'table_name' => 'ledger_entries',
            'record_id' => $entry->ledger_id,
        ]);

        return $entry;
    }

    /**
     * Cash flow for a date range: money in minus money out.
     *
     * @return array{income: float, expenses: float, net: float, entries: Collection}
     */
    public function cashFlow(string $startDate, string $endDate): array
    {
        $entries = LedgerEntry::whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('entry_date')->orderBy('ledger_id')->get();

        $income = (float) $entries->where('entry_type', self::INCOME)->sum('amount');
        $expenses = (float) $entries->where('entry_type', self::EXPENSE)->sum('amount');

        return [
            'income' => $income,
            'expenses' => $expenses,
            'net' => round($income - $expenses, 2),
            'entries' => $entries,
        ];
    }

    /**
     * End-of-day reconciliation (SDD 4.2): total of the day's completed sales
     * (invoices with a recorded payment) vs. the day's recorded Income entries.
     * reference fields are included in recorded income but not
     * in expected sales, so they surface as a difference for review.
     *
     * @return array{date: string, sales_total: float, recorded_income: float, difference: float, reconciled: bool, invoice_count: int}
     */
    public function reconcile(string $date): array
    {
        $salesTotal = (float) Invoice::whereDate('invoice_date', $date)
            ->whereHas('payment')
            ->sum('total_amount');

        $invoiceCount = Invoice::whereDate('invoice_date', $date)->whereHas('payment')->count();

        $recorded = (float) LedgerEntry::whereDate('entry_date', $date)
            ->where('entry_type', self::INCOME)
            ->sum('amount');

        $difference = round($recorded - $salesTotal, 2);

        return [
            'date' => $date,
            'sales_total' => round($salesTotal, 2),
            'recorded_income' => round($recorded, 2),
            'difference' => $difference,
            'reconciled' => abs($difference) < 0.005,
            'invoice_count' => $invoiceCount,
        ];
    }
}

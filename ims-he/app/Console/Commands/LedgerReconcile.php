<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\LedgerEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LedgerReconcile extends Command
{
    protected $signature = 'ledger:reconcile {date?}';

    protected $description = 'Compare completed invoices with their income ledger entries.';

    public function handle(): int
    {
        $date = $this->argument('date') ?: today()->toDateString();
        $invoices = Invoice::query()
            ->whereDate('invoice_date', $date)
            ->where('status', 'Completed')
            ->get(['invoice_id', 'total_amount']);
        $ledgerByInvoice = LedgerEntry::query()
            ->where('entry_type', 'Income')
            ->where('reference_type', 'Invoice')
            ->whereIn('reference_id', $invoices->pluck('invoice_id'))
            ->get()
            ->groupBy('reference_id');
        $invoiceTotal = round((float) $invoices->sum('total_amount'), 2);
        $ledgerTotal = round((float) $ledgerByInvoice->flatten()->sum('amount'), 2);

        foreach ($invoices as $invoice) {
            $expected = round((float) $invoice->total_amount, 2);
            $actual = round((float) $ledgerByInvoice->get($invoice->invoice_id, collect())->sum('amount'), 2);

            if ($expected !== $actual) {
                Log::warning('Ledger mismatch for completed invoice.', [
                    'invoice_id' => $invoice->invoice_id,
                    'date' => $date,
                    'invoice_total' => $expected,
                    'ledger_income' => $actual,
                ]);
            }
        }

        $this->line('Completed invoices: '.money($invoiceTotal));
        $this->line('Income ledger entries: '.money($ledgerTotal));
        $this->line($invoiceTotal === $ledgerTotal ? 'Result: Match' : 'Result: Mismatch');

        return self::SUCCESS;
    }
}

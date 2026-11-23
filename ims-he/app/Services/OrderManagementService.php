<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

// Staff side of online orders (UC20 follow-up): a Pending online order is either Completed or Cancelled.
// Cancelling puts the stock back through InventoryService (IN movements). Each change is written to the audit log.
// Ledger entries for these transitions are added by the Financial Recording module (task R4).
class OrderManagementService
{
    public function __construct(private InventoryService $inventory, private LedgerService $ledger) {}

    /** @param  string|null  $referenceNo  GCash Ref. No. from the customer's receipt (GCash orders only) */
    public function complete(Invoice $invoice, User $staff, ?string $referenceNo = null): Invoice
    {
        return DB::transaction(function () use ($invoice, $staff, $referenceNo) {
            $invoice = $this->lockPending($invoice, 'completed');

            if ($referenceNo !== null) {
                $invoice->payment()->update(['reference_no' => $referenceNo]);
            }

            $invoice->update(['status' => 'Completed']);
            $this->ledger->recordSale($invoice);
            AuditLog::record('Completed', 'invoices', $invoice->invoice_id, $staff->user_id);

            return $invoice;
        });
    }

    public function cancel(Invoice $invoice, User $staff): Invoice
    {
        return DB::transaction(function () use ($invoice, $staff) {
            $invoice = $this->lockPending($invoice, 'cancelled');

            // Restore every line's stock; each call writes an IN inventory transaction referencing the invoice.
            foreach ($invoice->items as $line) {
                $this->inventory->add($line->product_id, (int) $line->quantity, 'Invoice', $invoice->invoice_id);
            }

            $invoice->update(['status' => 'Cancelled']);
            AuditLog::record('Cancelled', 'invoices', $invoice->invoice_id, $staff->user_id);

            return $invoice;
        });
    }

    // Re-reads the invoice under a row lock so two staff members cannot complete and cancel the same order at once.
    private function lockPending(Invoice $invoice, string $verb): Invoice
    {
        $invoice = Invoice::with('items')->lockForUpdate()->findOrFail($invoice->invoice_id);

        if ($invoice->channel !== 'Online') {
            throw ValidationException::withMessages(['status' => 'Only online orders are managed here.']);
        }

        if ($invoice->status !== 'Pending') {
            throw ValidationException::withMessages([
                'status' => "Order #{$invoice->invoice_id} is already {$invoice->status} and cannot be {$verb}.",
            ]);
        }

        return $invoice;
    }
}

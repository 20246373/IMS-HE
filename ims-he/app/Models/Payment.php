<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Payment extends Model
{
    protected $table = 'payments';

    protected $primaryKey = 'payment_id';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['payment_date' => 'date'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'invoice_id');
    }

    /**
     * The GCash reference number is entered by staff from the customer's GCash receipt (the 13-digit "Ref. No."):
     * at the POS when the sale is made, and on an online order when it is marked Completed.
     * Spaces are removed first (see normalizeReference).
     *
     * @return list<mixed>
     */
    public static function referenceFormat(): array
    {
        return ['regex:/^[0-9]{13}$/', Rule::unique('payments', 'reference_no')];
    }

    /**
     * POS: required when the sale's payment method (in the same form) is GCash, refused otherwise.
     *
     * @return array<string, list<mixed>>
     */
    public static function referenceRules(): array
    {
        return [
            'reference_no' => ['nullable', 'required_if:payment_method,GCash', 'prohibited_unless:payment_method,GCash', ...self::referenceFormat()],
        ];
    }

    /** @return array<string, string> */
    public static function referenceMessages(): array
    {
        return [
            'reference_no.required' => 'Enter the GCash reference number from the GCash receipt.',
            'reference_no.prohibited' => 'A reference number is only needed for GCash.',
            'reference_no.required_if' => 'Enter the GCash reference number from the GCash receipt.',
            'reference_no.prohibited_unless' => 'A reference number is only needed for GCash.',
            'reference_no.regex' => 'The GCash reference number has 13 digits.',
            'reference_no.unique' => 'This GCash reference number was already used for another payment.',
        ];
    }

    /** "1234 567 890123" -> "1234567890123", so the number can be typed the way GCash shows it. */
    public static function normalizeReference(mixed $value): ?string
    {
        $digits = preg_replace('/\s+/', '', (string) $value);

        return $digits === '' ? null : $digits;
    }
}

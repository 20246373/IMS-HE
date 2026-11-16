<?php

namespace App\Support;

/**
 * Validates and formats Philippine government numbers so the same number is always stored the same way
 * (dashes or spaces typed by the user are ignored). Digit counts follow the agencies' number formats:
 *   SSS 10 digits (12-3456789-0), PhilHealth 12 (12-345678901-2), Pag-IBIG 12 (1234-5678-9012),
 *   TIN 9 (123-456-789) or 12 with the branch code (123-456-789-000).
 */
class GovernmentId
{
    public const SSS = 'sss';
    public const PHILHEALTH = 'philhealth';
    public const PAGIBIG = 'pagibig';
    public const TIN = 'tin';

    public const LABELS = [
        self::SSS => 'SSS number',
        self::PHILHEALTH => 'PhilHealth number',
        self::PAGIBIG => 'Pag-IBIG number',
        self::TIN => 'TIN',
    ];

    public const EXAMPLES = [
        self::SSS => '12-3456789-0',
        self::PHILHEALTH => '12-345678901-2',
        self::PAGIBIG => '1234-5678-9012',
        self::TIN => '123-456-789 or 123-456-789-000',
    ];

    /** Returns the formatted number, or null when the digit count is wrong or other characters are present. */
    public static function normalize(string $type, ?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '' || preg_match('/^[0-9 \-]+$/', $value) !== 1) {
            return null;
        }

        $d = preg_replace('/\D/', '', $value);

        return match (true) {
            $type === self::SSS && strlen($d) === 10 => substr($d, 0, 2) . '-' . substr($d, 2, 7) . '-' . substr($d, 9, 1),
            $type === self::PHILHEALTH && strlen($d) === 12 => substr($d, 0, 2) . '-' . substr($d, 2, 9) . '-' . substr($d, 11, 1),
            $type === self::PAGIBIG && strlen($d) === 12 => implode('-', str_split($d, 4)),
            $type === self::TIN && strlen($d) === 9 => implode('-', str_split($d, 3)),
            $type === self::TIN && strlen($d) === 12 => implode('-', str_split($d, 3)),
            default => null,
        };
    }
}

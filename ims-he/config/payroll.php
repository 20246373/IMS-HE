<?php

/*
 * Payroll rates and rules (SDD Section 5 dependency, SRS UC06).
 *
 * IMPORTANT: the statutory rates below are placeholders taken from the
 * published tables in effect when this file was written. They MUST be
 * verified against the current official tables before real payroll runs:
 *   - SSS:      https://www.sss.gov.ph (employee share of the MSC)
 *   - PhilHealth: https://www.philhealth.gov.ph (employee share of the premium)
 *   - Pag-IBIG:   https://www.pagibigfund.gov.ph (employee share, monthly cap)
 *   - BIR:        https://www.bir.gov.ph (withholding tax table, TRAIN law)
 * Bump 'version' and the date whenever the tables are re-checked.
 */
return [
    'version' => '2026-11-17-draft',
    'verified_against' => null, // set to e.g. 'SSS circular xxx, PhilHealth advisory xxx' once checked

    // Overtime: hours beyond 'hours_per_day' in a single work day are paid
    // at 'overtime_multiplier' times the hourly rate (hourly = daily_rate / hours_per_day).
    'hours_per_day' => 8,
    'overtime_multiplier' => 1.25,

    // Statutory deductions, applied to gross pay of the payroll period.
    // Rates are employee shares; caps are per payroll period.
    'sss_rate' => 0.05,            // SSS employee share (placeholder - verify)
    'philhealth_rate' => 0.025,    // PhilHealth employee share of the 5% premium (placeholder - verify)
    'pagibig_rate' => 0.02,        // Pag-IBIG employee share (placeholder - verify)
    'pagibig_max' => 100.00,       // Pag-IBIG monthly cap applied per period (placeholder - verify)

    // BIR withholding tax on taxable gross pay, TRAIN-law monthly brackets.
    // Format: [income_over, base_tax, rate_on_excess]. The first bracket whose
    // 'income_over' is below the gross pay wins. Applied per payroll period;
    // adjust brackets if periods are not monthly.
    'tax_brackets' => [
        ['over' => 0,        'base' => 0,        'rate' => 0.00],
        ['over' => 20833.33, 'base' => 0,        'rate' => 0.15],
        ['over' => 33333.33, 'base' => 1875.00,  'rate' => 0.20],
        ['over' => 66666.67, 'base' => 8541.80,  'rate' => 0.25],
        ['over' => 166666.67, 'base' => 33541.80, 'rate' => 0.30],
        ['over' => 666666.67, 'base' => 183541.80, 'rate' => 0.35],
    ],
];

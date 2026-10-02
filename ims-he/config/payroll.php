<?php

// PLACEHOLDER RATES - replace with the current official SSS / PhilHealth / Pag-IBIG / BIR tables
// before using for real payroll (SRS requires BIR-compliant deductions).
return [
    'overtime_multiplier' => 1.25,
    'hours_per_day'       => 8,
    'sss_rate'            => 0.05,   // share of gross
    'philhealth_rate'     => 0.025,  // share of gross
    'pagibig_fixed'       => 200.00, // flat per period
    'tax_rate'            => 0.0,    // TODO: BIR withholding tax table
];

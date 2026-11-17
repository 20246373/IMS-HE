<?php

return [
    'hours_per_day' => 8,
    'overtime_multiplier' => 1.25,

    // PLACEHOLDER: SSS contribution table, 2026. Replace with the official SSS table before real use.
    'sss' => [
        'mode' => 'percentage',
        'value' => 0.00,
    ],

    // PLACEHOLDER: PhilHealth contribution table, 2026. Replace with the official PhilHealth table before real use.
    'philhealth' => [
        'mode' => 'percentage',
        'value' => 0.00,
    ],

    // PLACEHOLDER: Pag-IBIG contribution table, 2026. Replace with the official Pag-IBIG table before real use.
    'pagibig' => [
        'mode' => 'fixed',
        'value' => 0.00,
    ],

    // PLACEHOLDER: BIR withholding tax table, 2026. Replace with the official BIR table before real use.
    'withholding_tax_brackets' => [
        [
            'up_to' => null,
            'base' => 0.00,
            'rate' => 0.00,
        ],
    ],
];

<?php

/*
|--------------------------------------------------------------------------
| Payroll configuration (SDD payroll computation, Task B4)
|--------------------------------------------------------------------------
| Every statutory rate and bracket used by App\Services\PayrollService lives
| here. When a government agency publishes a new table, change ONLY this file
| and update the "Source / checked" comment above the table you changed.
|
| All tables below were last checked on 2026-11-15. They were cross-checked
| against secondary payroll references, so confirm them against the primary
| circular named in each comment before relying on them for real remittances.
|
| Only the EMPLOYEE share is deducted from pay. Employer shares (SSS 10%, EC,
| PhilHealth 2.5%, Pag-IBIG 2%) are the business's own cost and are not part
| of the payslip.
*/

return [

    // Printed on payslips and the payroll report. Keep it the same as the brand in layouts/app.blade.php.
    'company_name' => 'Ibay Enterprises',

    // Labor rules used for gross pay.
    // Source: Labor Code of the Philippines, Art. 87 (overtime on an ordinary working day = +25%).
    // Checked: 2026-11-15.
    'hours_per_day' => 8,
    'overtime_multiplier' => 1.25,

    // How many payroll runs make one month. 2 = semi-monthly (15th / end of month).
    // Government contributions and the tax table are monthly figures, so each run
    // takes 1 / periods_per_month of them. Set to 1 if payroll is run monthly.
    'periods_per_month' => 2,

    // SSS (Social Security System): employee share of the Monthly Salary Credit (MSC).
    // Source: RA 11199 (Social Security Act of 2018) and the SSS contribution schedule effective
    //         January 2025 (15% total: 10% employer, 5% employee). Table unchanged for 2026.
    //         https://www.sss.gov.ph  (contribution schedule)
    // Checked: 2026-11-15.
    // MSC = monthly compensation rounded to the nearest `msc_step`, kept between min and max
    // (e.g. pay below 5,250 => MSC 5,000; pay of 34,750 or more => MSC 35,000).
    'sss' => [
        'employee_rate' => 0.05,
        'min_msc' => 5000,
        'max_msc' => 35000,
        'msc_step' => 500,
    ],

    // PhilHealth: premium is 5% of monthly basic salary, shared equally with the employer.
    // Source: RA 11223 (Universal Health Care Act) and PhilHealth Circular 2020-0001 (5% rate,
    //         10,000 floor, 100,000 ceiling). 5% retained for 2026.
    //         https://www.philhealth.gov.ph
    // Checked: 2026-11-15.
    'philhealth' => [
        'employee_rate' => 0.025,          // half of the 5% premium
        'income_floor' => 10000,           // salary below this is treated as 10,000
        'income_ceiling' => 100000,        // salary above this is treated as 100,000
    ],

    // Pag-IBIG (HDMF): employee 2% of monthly compensation, capped at the Maximum Fund Salary.
    // Source: HDMF Circular No. 460 (maximum fund salary 10,000, effective February 2024),
    //         so the largest employee deduction is 200 a month.
    //         https://www.pagibigfund.gov.ph
    // Checked: 2026-11-15.
    'pagibig' => [
        'employee_rate' => 0.02,
        'low_income_rate' => 0.01,         // used when monthly pay is at or below low_income_limit
        'low_income_limit' => 1500,
        'max_fund_salary' => 10000,
    ],

    // Withholding tax on compensation: TRAIN annual income tax table, 2023 onwards.
    // Source: RA 10963 (TRAIN Law) and BIR RR No. 11-2018, Annex E. Table unchanged for 2026.
    //         https://www.bir.gov.ph
    // Checked: 2026-11-15.
    // Taxable pay for a run = gross - employee SSS - PhilHealth - Pag-IBIG. It is annualized
    // (x periods_per_month x 12), taxed with this table, then divided back to one run.
    // Each row: income over `over` pays `base` + `rate` of the excess over `over`.
    'tax' => [
        'annual_brackets' => [
            ['over' => 0,       'base' => 0,          'rate' => 0.00],
            ['over' => 250000,  'base' => 0,          'rate' => 0.15],
            ['over' => 400000,  'base' => 22500,      'rate' => 0.20],
            ['over' => 800000,  'base' => 102500,     'rate' => 0.25],
            ['over' => 2000000, 'base' => 402500,     'rate' => 0.30],
            ['over' => 8000000, 'base' => 2202500,    'rate' => 0.35],
        ],
    ],
];

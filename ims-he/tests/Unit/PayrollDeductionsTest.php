<?php

namespace Tests\Unit;

use App\Services\PayrollService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Task B4: statutory deductions for ONE semi-monthly payroll run (config/payroll.php, periods_per_month = 2).
 * Expected values were worked out by hand from the tables in config/payroll.php (monthly equivalent = gross x 2):
 *   SSS = 5% of the MSC / 2,  PhilHealth = 2.5% of pay (10,000 to 100,000) / 2,  Pag-IBIG = 2% of pay (max 10,000) / 2,
 *   tax = TRAIN annual table on (gross - contributions) x 24, divided by 24.
 * Extends Tests\TestCase only so config() is available; no database is used.
 */
class PayrollDeductionsTest extends TestCase
{
    /** @return array<string, array{0: float, 1: float, 2: float, 3: float, 4: float, 5: float, 6: float}> gross, sss, philhealth, pagibig, tax, total, net */
    public static function salaryExamples(): array
    {
        return [
            // Pay below every floor: SSS uses the 5,000 MSC, PhilHealth the 10,000 floor, Pag-IBIG the 1% rate.
            'very low pay (700)' => [700.00, 125.00, 125.00, 7.00, 0.00, 257.00, 443.00],
            // Monthly 4,000: SSS and PhilHealth still at their floors, Pag-IBIG 2% of 4,000.
            'low pay (2,000)' => [2000.00, 125.00, 125.00, 40.00, 0.00, 290.00, 1710.00],
            // Monthly 5,250 sits exactly on an SSS rounding edge: MSC goes up to 5,500.
            'SSS rounding edge (2,625)' => [2625.00, 137.50, 125.00, 52.50, 0.00, 315.00, 2310.00],
            // Monthly 8,000: MSC 8,000, still no tax (annual taxable is under 250,000).
            'typical minimum-wage pay (4,000)' => [4000.00, 200.00, 125.00, 80.00, 0.00, 405.00, 3595.00],
            // Monthly 20,000: MSC 20,000, Pag-IBIG capped at 200 a month, still no tax.
            'mid pay (10,000)' => [10000.00, 500.00, 250.00, 100.00, 0.00, 850.00, 9150.00],
            // Monthly 30,000: first taxed bracket (15% over 250,000): 12,090 a year / 24 = 503.75.
            'taxed pay (15,000)' => [15000.00, 750.00, 375.00, 100.00, 503.75, 1728.75, 13271.25],
            // Monthly 50,000: SSS capped at MSC 35,000; tax in the 20% bracket: 54,820 a year / 24 = 2,284.17.
            'SSS capped (25,000)' => [25000.00, 875.00, 625.00, 100.00, 2284.17, 3884.17, 21115.83],
            // Monthly 240,000: PhilHealth capped at the 100,000 ceiling; tax in the 30% bracket.
            'PhilHealth capped (120,000)' => [120000.00, 875.00, 1250.00, 100.00, 27103.33, 29328.33, 90671.67],
        ];
    }

    #[DataProvider('salaryExamples')]
    public function test_deductions_match_the_statutory_tables(
        float $gross, float $sss, float $philhealth, float $pagibig, float $tax, float $total, float $net,
    ): void {
        $d = (new PayrollService)->computeDeductions($gross);

        $this->assertEqualsWithDelta($sss, $d['sss'], 0.001, 'SSS');
        $this->assertEqualsWithDelta($philhealth, $d['philhealth'], 0.001, 'PhilHealth');
        $this->assertEqualsWithDelta($pagibig, $d['pagibig'], 0.001, 'Pag-IBIG');
        $this->assertEqualsWithDelta($tax, $d['tax'], 0.001, 'Withholding tax');
        $this->assertEqualsWithDelta($total, $d['total'], 0.001, 'Total deductions');
        $this->assertEqualsWithDelta($net, $d['net'], 0.001, 'Net pay');
    }

    public function test_no_pay_means_no_deductions(): void
    {
        $d = (new PayrollService)->computeDeductions(0);

        $this->assertSame(0.0, $d['total']);
        $this->assertSame(0.0, $d['net']);
    }

    public function test_service_reads_rates_from_config(): void
    {
        $service = new PayrollService;
        $before = $service->computeDeductions(10000);

        config(['payroll.sss.employee_rate' => 0.10]);
        $after = $service->computeDeductions(10000);

        $this->assertEqualsWithDelta(500.00, $before['sss'], 0.001);
        $this->assertEqualsWithDelta(1000.00, $after['sss'], 0.001);    // MSC 20,000 x 10% / 2
    }

    public function test_monthly_payroll_takes_the_whole_monthly_contribution(): void
    {
        config(['payroll.periods_per_month' => 1]);

        // Monthly pay 20,000: SSS 1,000, PhilHealth 500, Pag-IBIG 200, taxable 18,300 x 12 = 219,600 (no tax).
        $d = (new PayrollService)->computeDeductions(20000);

        $this->assertEqualsWithDelta(1000.00, $d['sss'], 0.001);
        $this->assertEqualsWithDelta(500.00, $d['philhealth'], 0.001);
        $this->assertEqualsWithDelta(200.00, $d['pagibig'], 0.001);
        $this->assertEqualsWithDelta(0.00, $d['tax'], 0.001);
    }

    public function test_gross_pay_adds_overtime_at_125_percent(): void
    {
        // 10 days x 800 = 8,000; 4 overtime hours x (800 / 8) x 1.25 = 500.
        $this->assertEqualsWithDelta(8500.00, (new PayrollService)->computeGross(800, 10, 4), 0.001);
    }
}

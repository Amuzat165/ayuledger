<?php

namespace Tests\Unit;

use App\Support\CustomerInvoiceFinancialCalculator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerInvoiceFinancialCalculatorTest extends TestCase
{
    public function test_line_amount_uses_quantity_and_authoritative_rate()
    {
        $calculator = new CustomerInvoiceFinancialCalculator();

        $this->assertSame(250.0, $calculator->lineAmount(5, '50.00'));
    }

    public function test_equivalent_two_decimal_rate_formats_match()
    {
        $calculator = new CustomerInvoiceFinancialCalculator();

        $this->assertTrue($calculator->submittedRateMatches('00050', '50.00'));
    }

    public function test_different_or_unrepresentable_submitted_rates_do_not_match()
    {
        $calculator = new CustomerInvoiceFinancialCalculator();

        $this->assertFalse($calculator->submittedRateMatches('999999', '50.00'));
        $this->assertFalse($calculator->submittedRateMatches('50.001', '50.00'));
    }

    public function test_multi_line_snapshot_uses_configured_vat_and_rounds_to_currency_precision()
    {
        $snapshot = (new CustomerInvoiceFinancialCalculator())->snapshot([200.00, 50.00], 7.5);

        $this->assertSame(250.0, $snapshot['subtotal']);
        $this->assertSame(7.5, $snapshot['vat_percentage']);
        $this->assertSame(18.75, $snapshot['vat_amount']);
        $this->assertSame(268.75, $snapshot['total_due']);
        $this->assertNull($snapshot['discount_percentage']);
        $this->assertNull($snapshot['discount_amount']);
        $this->assertSame(268.75, $snapshot['balance_due']);
        $this->assertSame(2, $snapshot['financial_snapshot_version']);
    }

    public function test_approved_ten_percent_discount_is_calculated_from_total_after_vat()
    {
        $snapshot = (new CustomerInvoiceFinancialCalculator())->snapshot([100000], 7.5, true, '10');

        $this->assertSame(100000.0, $snapshot['subtotal']);
        $this->assertSame(7500.0, $snapshot['vat_amount']);
        $this->assertSame(107500.0, $snapshot['total_due']);
        $this->assertSame(10.0, $snapshot['discount_percentage']);
        $this->assertSame(10750.0, $snapshot['discount_amount']);
        $this->assertSame(96750.0, $snapshot['balance_due']);
    }

    public function test_discount_does_not_reduce_vat_and_zero_discount_keeps_balance_equal_to_total()
    {
        $calculator = new CustomerInvoiceFinancialCalculator();
        $discounted = $calculator->snapshot([100000], 7.5, true, '10');
        $zero = $calculator->snapshot([100000], 7.5, true, '0');

        $this->assertSame(7500.0, $discounted['vat_amount']);
        $this->assertSame(0.0, $zero['discount_amount']);
        $this->assertSame($zero['total_due'], $zero['balance_due']);
    }

    public function test_disabled_discount_ignores_a_browser_submitted_percentage_and_stores_no_applied_discount()
    {
        $snapshot = (new CustomerInvoiceFinancialCalculator())->snapshot([100000], 7.5, false, '10');

        $this->assertSame(107500.0, $snapshot['total_due']);
        $this->assertSame(107500.0, $snapshot['balance_due']);
        $this->assertNull($snapshot['discount_percentage']);
        $this->assertNull($snapshot['discount_amount']);
    }

    /** @dataProvider invalidDiscountProvider */
    public function test_invalid_discount_percentages_are_rejected($percentage)
    {
        $this->expectException(ValidationException::class);
        (new CustomerInvoiceFinancialCalculator())->snapshot([100], 7.5, true, $percentage);
    }

    public static function invalidDiscountProvider()
    {
        return ['negative' => ['-1'], 'over 100' => ['100.01'], 'extra decimals' => ['1.234'], 'not numeric' => ['NaN']];
    }

    public function test_snapshot_calculates_zero_values()
    {
        $snapshot = (new CustomerInvoiceFinancialCalculator())->snapshot([0.00], 7.5);

        $this->assertSame(0.0, $snapshot['subtotal']);
        $this->assertSame(0.0, $snapshot['vat_amount']);
        $this->assertSame(0.0, $snapshot['total_due']);
    }

    public function test_unrepresentable_configured_vat_precision_is_rejected_instead_of_silently_changed()
    {
        $this->expectException(ValidationException::class);

        (new CustomerInvoiceFinancialCalculator())->snapshot([100.00], 7.555);
    }
}

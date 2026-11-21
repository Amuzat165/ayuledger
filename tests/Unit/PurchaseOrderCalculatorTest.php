<?php

namespace Tests\Unit;

use App\Support\PurchaseOrderCalculator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PurchaseOrderCalculatorTest extends TestCase
{
    public function test_it_calculates_line_and_order_totals_from_decimal_strings()
    {
        $result = (new PurchaseOrderCalculator())->calculate([
            ['quantity' => '10', 'agreed_unit_price' => '5200.00'],
            ['quantity' => '2.500', 'agreed_unit_price' => '100.25'],
        ]);

        $this->assertSame('5200.00', $result['lines'][0]['agreed_unit_price']);
        $this->assertSame('52000.00', $result['lines'][0]['line_total']);
        $this->assertSame('250.63', $result['lines'][1]['line_total']);
        $this->assertSame('52250.63', $result['subtotal']);
        $this->assertSame($result['subtotal'], $result['order_total']);
    }

    public function test_it_rounds_fractional_quantity_to_minor_currency_units()
    {
        $result = (new PurchaseOrderCalculator())->calculate([
            ['quantity' => '0.333', 'agreed_unit_price' => '10.00'],
        ]);

        $this->assertSame('3.33', $result['lines'][0]['line_total']);
    }

    public function test_it_rejects_empty_lines_and_invalid_financial_values()
    {
        $calculator = new PurchaseOrderCalculator();
        try {
            $calculator->calculate([]);
            $this->fail('An empty order must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        try {
            $calculator->calculate([['quantity' => '-1', 'agreed_unit_price' => '12.345']]);
            $this->fail('Invalid quantity and price precision must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertTrue($exception->errors() !== []);
        }
    }

    public function test_it_rejects_document_totals_above_the_supported_decimal_limit()
    {
        $this->expectException(ValidationException::class);
        (new PurchaseOrderCalculator())->calculate([
            ['quantity' => '999999999', 'agreed_unit_price' => '9999999999999.99'],
        ]);
    }

    public function test_it_formats_large_persisted_amounts_without_float_conversion()
    {
        $this->assertSame('9,999,999,999,999.99', PurchaseOrderCalculator::formatAmount('9999999999999.99'));
        $this->assertSame('1,234.5', PurchaseOrderCalculator::formatQuantity('1234.500'));
        $this->assertSame('€ 1,234.56', PurchaseOrderCalculator::formatCurrency('1234.56', '€', 'Prefix'));
        $this->assertSame('1,234.56 €', PurchaseOrderCalculator::formatCurrency('1234.56', '€', 'Suffix'));
    }
}

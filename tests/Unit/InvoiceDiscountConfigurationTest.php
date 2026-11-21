<?php

namespace Tests\Unit;

use App\Support\InvoiceDiscountConfiguration;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InvoiceDiscountConfigurationTest extends TestCase
{
    /** @dataProvider validPercentageProvider */
    public function test_valid_configured_percentages_are_normalized($value, $expected)
    {
        $this->assertSame($expected, (new InvoiceDiscountConfiguration())->percentageFromValue($value));
    }

    public static function validPercentageProvider()
    {
        return [
            'zero' => ['0.00', 0.0],
            'normal rate' => ['10', 10.0],
            'maximum' => ['100.00', 100.0],
        ];
    }

    /** @dataProvider invalidPercentageProvider */
    public function test_invalid_configured_percentages_fail_closed($value)
    {
        $this->expectException(RuntimeException::class);
        (new InvoiceDiscountConfiguration())->percentageFromValue($value);
    }

    public static function invalidPercentageProvider()
    {
        return [
            'negative' => ['-0.01'],
            'over maximum' => ['100.01'],
            'too many decimals' => ['1.234'],
            'not numeric' => ['NaN'],
            'exponent notation' => ['1e1'],
        ];
    }
}
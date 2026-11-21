<?php

namespace Tests\Unit;

use App\Support\AmountInWordsFormatter;
use PHPUnit\Framework\TestCase;

class AmountInWordsFormatterTest extends TestCase
{
    /** @dataProvider amounts */
    public function test_formats_supported_currency_amounts_with_minor_units($amount, $currency, $expected)
    {
        $this->assertSame($expected, (new AmountInWordsFormatter())->format($amount, $currency));
    }

    public static function amounts()
    {
        return [
            'whole naira amount' => ['5000.00', 'NGN', 'Five Thousand Naira Only.'],
            'naira with kobo' => ['5750.50', 'NGN', 'Five Thousand Seven Hundred and Fifty Naira, Fifty Kobo Only.'],
            'receipt amount with kobo' => ['30344.10', 'NGN', 'Thirty Thousand Three Hundred and Forty Four Naira, Ten Kobo Only.'],
            'kobo only' => ['0.50', 'NGN', 'Fifty Kobo Only.'],
            'zero' => ['0.00', 'NGN', 'Zero Naira Only.'],
            'other mapped currency' => ['25.10', 'USD', 'Twenty Five US Dollars, Ten Cents Only.'],
            'large amount' => ['1000000000.00', 'NGN', 'One Billion Naira Only.'],
            'unmapped currency uses code, not guessed unit' => ['12.50', 'JPY', 'Twelve JPY, Fifty hundredths of JPY Only.'],
        ];
    }

    public function test_invalid_or_negative_values_do_not_produce_misleading_words()
    {
        $formatter = new AmountInWordsFormatter();
        $this->assertSame('', $formatter->format('-1.00', 'NGN'));
        $this->assertSame('', $formatter->format('1.234', 'NGN'));
    }
}

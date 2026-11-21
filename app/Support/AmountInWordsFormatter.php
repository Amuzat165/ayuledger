<?php

namespace App\Support;

class AmountInWordsFormatter
{
    private $currencies = [
        'NGN' => ['Naira', 'Naira', 'Kobo', 'Kobo'],
        'USD' => ['US Dollar', 'US Dollars', 'Cent', 'Cents'],
        'GBP' => ['Pound Sterling', 'Pounds Sterling', 'Penny', 'Pence'],
        'EUR' => ['Euro', 'Euros', 'Cent', 'Cents'],
        'CNY' => ['Yuan', 'Yuan', 'Fen', 'Fen'],
        'CAD' => ['Canadian Dollar', 'Canadian Dollars', 'Cent', 'Cents'],
        'AUD' => ['Australian Dollar', 'Australian Dollars', 'Cent', 'Cents'],
        'INR' => ['Indian Rupee', 'Indian Rupees', 'Paisa', 'Paise'],
        'GHS' => ['Ghana Cedi', 'Ghana Cedis', 'Pesewa', 'Pesewas'],
        'ZAR' => ['South African Rand', 'South African Rand', 'Cent', 'Cents'],
        'KES' => ['Kenyan Shilling', 'Kenyan Shillings', 'Cent', 'Cents'],
    ];

    public function format($amount, $currencyCode)
    {
        $normalized = $this->normalize($amount);
        if ($normalized === null) {
            return '';
        }

        [$whole, $fraction] = explode('.', $normalized);
        $wholeWords = $this->integerToWords($whole);
        $code = strtoupper((string) $currencyCode);

        if (!isset($this->currencies[$code])) {
            $major = $code !== '' ? $code : 'currency units';
            $result = $wholeWords . ' ' . $major;
            if ((int) $fraction > 0) {
                $result .= ', ' . $this->integerToWords((string) (int) $fraction) . ' hundredths of ' . $major;
            }
            return $result . ' Only.';
        }

        [$majorSingular, $majorPlural, $minorSingular, $minorPlural] = $this->currencies[$code];
        $result = ((int) $whole === 0 && (int) $fraction > 0)
            ? ''
            : $wholeWords . ' ' . ((int) $whole === 1 ? $majorSingular : $majorPlural);
        if ((int) $fraction > 0) {
            $minorWords = $this->integerToWords((string) (int) $fraction) . ' '
                . ((int) $fraction === 1 ? $minorSingular : $minorPlural);
            $result .= $result === '' ? $minorWords : ', ' . $minorWords;
        }

        return $result . ' Only.';
    }

    private function normalize($amount)
    {
        $value = trim((string) $amount);
        if (!preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $value, $matches)) {
            return null;
        }
        if ($matches[1] === '-') {
            return null;
        }
        $whole = ltrim($matches[2], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($matches[3] ?? '', 2, '0');
        return $whole . '.' . $fraction;
    }

    private function integerToWords($number)
    {
        $number = ltrim((string) $number, '0');
        if ($number === '') return 'Zero';
        if (strlen($number) > 18) return $number;

        $number = (int) $number;
        $ones = [
            0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven',
            12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen',
            17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
        ];
        $tens = [2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty', 6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'];
        $scales = ['', 'Thousand', 'Million', 'Billion', 'Trillion', 'Quadrillion', 'Quintillion'];
        $groups = [];
        while ($number > 0) {
            $groups[] = $number % 1000;
            $number = intdiv($number, 1000);
        }
        $parts = [];
        foreach ($groups as $index => $group) {
            if ($group === 0) continue;
            $words = [];
            $hundreds = '';
            if ($group >= 100) {
                $hundreds = $ones[intdiv($group, 100)] . ' Hundred';
                $group %= 100;
            }
            if ($group >= 20) {
                $tail = $group % 10;
                $words[] = $tens[intdiv($group, 10)] . ($tail ? ' ' . $ones[$tail] : '');
            } elseif ($group > 0) {
                $words[] = $ones[$group];
            }
            $groupWords = implode(' ', $words);
            if ($hundreds !== '') {
                $groupWords = $hundreds . ($groupWords !== '' ? ($group < 100 ? ' and ' : ' ') . $groupWords : '');
            }
            $parts[] = $groupWords . ($scales[$index] ? ' ' . $scales[$index] : '');
        }
        $parts = array_reverse($parts);
        if (count($parts) > 1) {
            $last = array_pop($parts);
            $lastGroup = $groups[0] ?? 0;
            return implode(' ', $parts) . ($lastGroup > 0 && $lastGroup < 100 ? ' and ' : ' ') . $last;
        }
        return $parts[0];
    }
}

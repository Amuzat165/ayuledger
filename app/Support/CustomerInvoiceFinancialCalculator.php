<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class CustomerInvoiceFinancialCalculator
{
    private const ITEM_DECIMAL_MAX = 99999999.99;
    private const SNAPSHOT_DECIMAL_MAX = 9999999999999.99;
    private const VAT_PERCENTAGE_MAX = 100.00;

    public function submittedRateMatches($submittedRate, $authoritativeRate)
    {
        $submitted = $this->normalizeTwoDecimalValue($submittedRate);
        $authoritative = $this->normalizeTwoDecimalValue($authoritativeRate);

        return $submitted !== null && $authoritative !== null && $submitted === $authoritative;
    }

    public function lineAmount($quantity, $authoritativeRate)
    {
        $rate = (float) $authoritativeRate;
        $amount = round($rate * (int) $quantity, 2);

        if (abs($rate) > self::ITEM_DECIMAL_MAX || abs($amount) > self::ITEM_DECIMAL_MAX) {
            throw ValidationException::withMessages([
                'items' => ['The selected item rate or line amount exceeds the supported invoice-item amount range.'],
            ]);
        }

        return $amount;
    }

    public function snapshot(array $lineAmounts, $vatPercentage, bool $discountEnabled = false, $discountPercentage = null)
    {
        if (!is_numeric($vatPercentage) || !is_finite((float) $vatPercentage)) {
            throw ValidationException::withMessages([
                'items' => ['The configured VAT percentage cannot be used for this invoice.'],
            ]);
        }

        $vatPercentage = (float) $vatPercentage;
        if (abs($vatPercentage) > self::VAT_PERCENTAGE_MAX || abs($vatPercentage - round($vatPercentage, 2)) > 0.0000001) {
            throw ValidationException::withMessages([
                'items' => ['The configured VAT percentage cannot be stored at the supported precision.'],
            ]);
        }

        $subtotal = round(array_sum($lineAmounts), 2);
        $vatAmount = round($subtotal * $vatPercentage / 100, 2);
        $totalDue = round($subtotal + $vatAmount, 2);

        $storedDiscountPercentage = null;
        $discountAmount = null;
        $balanceDue = $totalDue;
        if ($discountEnabled) {
            $discountPercentage = $discountPercentage === null || $discountPercentage === '' ? 0 : $discountPercentage;
            $discountText = (string) $discountPercentage;
            if (!is_numeric($discountPercentage) || !is_finite((float) $discountPercentage)
                || !preg_match('/^\d+(?:\.\d{1,2})?$/D', $discountText)
                || (float) $discountPercentage < 0 || (float) $discountPercentage > 100) {
                throw ValidationException::withMessages([
                    'discount_percentage' => ['Enter a discount percentage from 0 to 100 with no more than two decimal places.'],
                ]);
            }

            $storedDiscountPercentage = (float) $discountPercentage;
            $discountAmount = round($totalDue * $storedDiscountPercentage / 100, 2);
            $balanceDue = round($totalDue - $discountAmount, 2);
        }

        foreach ([$subtotal, $vatAmount, $totalDue, $discountAmount ?? 0, $balanceDue] as $amount) {
            if (!is_finite($amount) || abs($amount) > self::SNAPSHOT_DECIMAL_MAX) {
                throw ValidationException::withMessages([
                    'items' => ['The calculated invoice total exceeds the supported snapshot amount range.'],
                ]);
            }
        }

        return [
            'subtotal' => $subtotal,
            'vat_percentage' => $vatPercentage,
            'vat_amount' => $vatAmount,
            'total_due' => $totalDue,
            'discount_percentage' => $storedDiscountPercentage,
            'discount_amount' => $discountAmount,
            'balance_due' => $balanceDue,
            'financial_snapshot_version' => 2,
        ];
    }

    private function normalizeTwoDecimalValue($value)
    {
        $value = trim((string) $value);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $value)) {
            return null;
        }

        $parts = explode('.', $value, 2);
        $whole = ltrim($parts[0], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = isset($parts[1]) ? str_pad($parts[1], 2, '0') : '00';

        return $whole . '.' . $fraction;
    }
}

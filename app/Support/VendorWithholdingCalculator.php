<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class VendorWithholdingCalculator
{
    private const MAX_AMOUNT_CENTS = 9999999999; // Existing vendor invoice/payment monetary columns are DECIMAL(10,2).
    private const CALCULATION_VERSION = 1;

    public function calculate(array $lines, array $configuration): array
    {
        $wht = app(WhtConfiguration::class)->withDefaults($configuration);
        if ($wht['wht_enabled'] && $wht['wht_mode'] === 'rule_based') {
            throw ValidationException::withMessages([
                'wht_mode' => ['Rule-Based WHT is selected but no approved rules are configured. This vendor invoice requires review and cannot be created yet.'],
            ]);
        }

        if (!$lines) {
            throw ValidationException::withMessages(['items' => ['Add at least one invoice item.']]);
        }

        $calculatedLines = [];
        $vendorTotalCents = 0;
        foreach ($lines as $index => $line) {
            $quantityText = (string) ($line['quantity'] ?? '');
            if (!preg_match('/^\d+$/D', $quantityText) || (int) $quantityText < 1) {
                throw ValidationException::withMessages(["items.$index.quantity" => ['Quantity must be a positive whole number.']]);
            }

            $quantity = (int) $quantityText;
            $rateCents = $this->toCents($line['rate'] ?? null, "items.$index.rate");
            if ($rateCents > 0 && $quantity > intdiv(self::MAX_AMOUNT_CENTS, $rateCents)) {
                throw ValidationException::withMessages(["items.$index.quantity" => ['The calculated line amount exceeds the supported invoice-item range.']]);
            }

            $lineCents = $rateCents * $quantity;
            if ($lineCents > self::MAX_AMOUNT_CENTS || $vendorTotalCents > self::MAX_AMOUNT_CENTS - $lineCents) {
                throw ValidationException::withMessages(['items' => ['The vendor invoice total exceeds the supported monetary range.']]);
            }

            $vendorTotalCents += $lineCents;
            $calculatedLines[] = [
                'item_id' => (int) $line['item_id'],
                'description' => $line['description'] ?? null,
                'quantity' => $quantity,
                'rate' => $this->fromCents($rateCents),
                'amount' => $this->fromCents($lineCents),
            ];
        }

        $manualApplies = $wht['wht_enabled'] && $wht['wht_mode'] === 'manual';
        $rateBasisPoints = $manualApplies ? $this->percentageToBasisPoints($wht['wht_manual_rate']) : 0;
        $whtBaseCents = $manualApplies ? $vendorTotalCents : 0;

        // Half-up rounding to cents: (base cents * rate basis points / 10,000).
        $whtNumerator = $whtBaseCents * $rateBasisPoints;
        $whtAmountCents = intdiv($whtNumerator + 5000, 10000);
        $balanceDueCents = $vendorTotalCents - $whtAmountCents;

        return [
            'lines' => $calculatedLines,
            'vendor_total' => $this->fromCents($vendorTotalCents),
            'wht_enabled_snapshot' => $wht['wht_enabled'],
            'wht_mode_snapshot' => $wht['wht_mode'],
            'wht_rate_snapshot' => $manualApplies ? $wht['wht_manual_rate'] : '0.00',
            'wht_base_snapshot' => $this->fromCents($whtBaseCents),
            'withholding_tax_amount' => $this->fromCents($whtAmountCents),
            'wht_balance_due_snapshot' => $this->fromCents($balanceDueCents),
            'wht_calculation_version' => self::CALCULATION_VERSION,
        ];
    }

    private function toCents($amount, string $field): int
    {
        $amount = (string) $amount;
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $amount)) {
            throw ValidationException::withMessages([$field => ['The accepted item rate must be a non-negative amount with at most two decimal places.']]);
        }

        $parts = explode('.', $amount, 2);
        $whole = ltrim($parts[0], '0');
        $whole = $whole === '' ? '0' : $whole;
        if (strlen($whole) > 8) {
            throw ValidationException::withMessages([$field => ['The accepted item rate exceeds the supported invoice-item range.']]);
        }

        $cents = ((int) $whole * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($cents > self::MAX_AMOUNT_CENTS) {
            throw ValidationException::withMessages([$field => ['The accepted item rate exceeds the supported invoice-item range.']]);
        }
        return $cents;
    }

    private function percentageToBasisPoints($percentage): int
    {
        $parts = explode('.', (string) $percentage, 2);
        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

/** Calculates Purchase Order amounts using decimal strings and integer minor units. */
class PurchaseOrderCalculator
{
    // DECIMAL(15,2): 13 whole-number digits and 2 decimal digits.
    private const MAX_CENTS = 999999999999999;

    /** Format stored decimal strings without converting financial values to binary floats. */
    public static function formatAmount($value): string
    {
        $parts = explode('.', (string) $value, 2);
        $whole = ltrim($parts[0], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($parts[1] ?? '', 2, '0');
        return number_format((int) $whole, 0, '.', ',') . '.' . substr($fraction, 0, 2);
    }

    public static function formatQuantity($value): string
    {
        $parts = explode('.', (string) $value, 2);
        $whole = ltrim($parts[0], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = rtrim(str_pad($parts[1] ?? '', 3, '0'), '0');
        return number_format((int) $whole, 0, '.', ',') . ($fraction === '' ? '' : '.' . substr($fraction, 0, 3));
    }

    public static function formatCurrency($value, string $symbol, string $position): string
    {
        $amount = self::formatAmount($value);
        return strtolower($position) === 'suffix' ? $amount . ' ' . $symbol : $symbol . ' ' . $amount;
    }

    public function calculate(array $lines): array
    {
        if (!$lines) {
            throw ValidationException::withMessages(['items' => [__('purchase_orders.validation.items_required')]]);
        }

        $calculated = [];
        $subtotalCents = 0;

        foreach ($lines as $index => $line) {
            $quantityMilli = $this->quantityToMilli($line['quantity'] ?? null, "items.$index.quantity");
            $priceCents = $this->amountToCents($line['agreed_unit_price'] ?? null, "items.$index.agreed_unit_price");

            if ($priceCents > 0 && $priceCents > intdiv(self::MAX_CENTS * 1000, $quantityMilli)) {
                throw ValidationException::withMessages(["items.$index.agreed_unit_price" => [__('purchase_orders.validation.amount_too_large')]]);
            }

            $lineCents = intdiv(($quantityMilli * $priceCents) + 500, 1000);
            if ($lineCents > self::MAX_CENTS || $subtotalCents > self::MAX_CENTS - $lineCents) {
                throw ValidationException::withMessages(['items' => [__('purchase_orders.validation.amount_too_large')]]);
            }

            $subtotalCents += $lineCents;
            $calculated[] = [
                'quantity' => $this->fromMilli($quantityMilli),
                'agreed_unit_price' => $this->fromCents($priceCents),
                'line_total' => $this->fromCents($lineCents),
            ];
        }

        $subtotal = $this->fromCents($subtotalCents);
        return ['lines' => $calculated, 'subtotal' => $subtotal, 'order_total' => $subtotal];
    }

    private function quantityToMilli($value, string $field): int
    {
        $value = (string) $value;
        if (!preg_match('/^\d+(?:\.\d{1,3})?$/D', $value)) {
            throw ValidationException::withMessages([$field => [__('purchase_orders.validation.invalid_quantity')]]);
        }

        $parts = explode('.', $value, 2);
        $whole = ltrim($parts[0], '0');
        $whole = $whole === '' ? '0' : $whole;
        if (strlen($whole) > 9) {
            throw ValidationException::withMessages([$field => [__('purchase_orders.validation.invalid_quantity')]]);
        }

        $quantity = ((int) $whole * 1000) + (int) str_pad($parts[1] ?? '', 3, '0');
        if ($quantity <= 0) {
            throw ValidationException::withMessages([$field => [__('purchase_orders.validation.invalid_quantity')]]);
        }

        return $quantity;
    }

    private function amountToCents($value, string $field): int
    {
        $value = (string) $value;
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $value)) {
            throw ValidationException::withMessages([$field => [__('purchase_orders.validation.invalid_price')]]);
        }

        $parts = explode('.', $value, 2);
        $whole = ltrim($parts[0], '0');
        $whole = $whole === '' ? '0' : $whole;
        if (strlen($whole) > 13) {
            throw ValidationException::withMessages([$field => [__('purchase_orders.validation.invalid_price')]]);
        }

        $cents = ((int) $whole * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($cents > self::MAX_CENTS) {
            throw ValidationException::withMessages([$field => [__('purchase_orders.validation.invalid_price')]]);
        }

        return $cents;
    }

    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function fromMilli(int $milli): string
    {
        return intdiv($milli, 1000) . '.' . str_pad((string) ($milli % 1000), 3, '0', STR_PAD_LEFT);
    }
}

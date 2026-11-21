<?php

namespace App\Support;

use App\Support\WhtConfiguration;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/** Calculates Supplier Invoice amounts without trusting browser totals. */
class SupplierInvoiceCalculator
{
    private const MAX_CENTS = 999999999999999;

    public function calculate(array $lines, array $whtSettings): array
    {
        if (!$lines) {
            throw ValidationException::withMessages(['items' => [__('supplier_invoices.errors.add_line')]]);
        }

        $calculated = [];
        $subtotalCents = 0;
        $variance = false;
        foreach ($lines as $index => $line) {
            $quantityMilli = $this->quantityToMilli($line['quantity'] ?? null, "items.$index.quantity");
            $poPriceCents = $this->amountToCents($line['purchase_order_unit_price'] ?? null, "items.$index.supplier_unit_price");
            $supplierPriceCents = $this->amountToCents($line['supplier_unit_price'] ?? null, "items.$index.supplier_unit_price");
            if ($supplierPriceCents > 0 && $supplierPriceCents > intdiv(self::MAX_CENTS * 1000, $quantityMilli)) {
                throw ValidationException::withMessages(["items.$index.quantity" => [__('supplier_invoices.errors.amount_too_large')]]);
            }
            $lineCents = intdiv(($quantityMilli * $supplierPriceCents) + 500, 1000);
            if ($lineCents > self::MAX_CENTS || $subtotalCents > self::MAX_CENTS - $lineCents) {
                throw ValidationException::withMessages(['items' => [__('supplier_invoices.errors.amount_too_large')]]);
            }
            $subtotalCents += $lineCents;
            $variance = $variance || $poPriceCents !== $supplierPriceCents;
            $calculated[] = array_merge($line, [
                'quantity' => $this->fromMilli($quantityMilli),
                'purchase_order_unit_price' => $this->fromCents($poPriceCents),
                'supplier_unit_price' => $this->fromCents($supplierPriceCents),
                'line_total' => $this->fromCents($lineCents),
            ]);
        }

        $subtotal = $this->fromCents($subtotalCents);
        try {
            $configuration = app(WhtConfiguration::class)->normalize($whtSettings);
            if ($configuration['wht_enabled'] && $configuration['wht_mode'] === 'rule_based') {
                throw new RuntimeException('No approved rule-based WHT calculation is configured.');
            }
            $rateBasisPoints = $configuration['wht_enabled'] ? $this->rateToBasisPoints($configuration['wht_manual_rate']) : 0;
            $whtCents = $this->percentageOfCents($subtotalCents, $rateBasisPoints);
            $payableCents = $subtotalCents - $whtCents;
            return [
                'lines' => $calculated, 'subtotal' => $subtotal,
                'wht_amount' => $this->fromCents($whtCents),
                'total_amount' => $this->fromCents($payableCents),
                'wht_enabled_snapshot' => $configuration['wht_enabled'],
                'wht_mode_snapshot' => $configuration['wht_mode'],
                'wht_rate_snapshot' => $configuration['wht_enabled'] ? $configuration['wht_manual_rate'] : '0.00',
                'wht_base_snapshot' => $this->fromCents($configuration['wht_enabled'] ? $subtotalCents : 0),
                'wht_calculation_version' => 1, 'wht_error' => null,
                'price_variance' => $variance,
            ];
        } catch (\Throwable $exception) {
            return [
                'lines' => $calculated, 'subtotal' => $subtotal, 'wht_amount' => null,
                'total_amount' => null, 'wht_enabled_snapshot' => null,
                'wht_mode_snapshot' => isset($whtSettings['wht_mode']) ? (string) $whtSettings['wht_mode'] : null,
                'wht_rate_snapshot' => null, 'wht_base_snapshot' => null,
                'wht_calculation_version' => null, 'wht_error' => __('supplier_invoices.wht_review_reason'),
                'price_variance' => $variance,
            ];
        }
    }

    private function quantityToMilli($value, string $field): int
    {
        $value = (string) $value;
        if (!preg_match('/^\d+(?:\.\d{1,3})?$/D', $value)) {
            throw ValidationException::withMessages([$field => [__('supplier_invoices.errors.invalid_quantity')]]);
        }
        $parts = explode('.', $value, 2);
        $whole = ltrim($parts[0], '0') ?: '0';
        if (strlen($whole) > 9) {
            throw ValidationException::withMessages([$field => [__('supplier_invoices.errors.invalid_quantity')]]);
        }
        $milli = ((int) $whole * 1000) + (int) str_pad($parts[1] ?? '', 3, '0');
        if ($milli <= 0) {
            throw ValidationException::withMessages([$field => [__('supplier_invoices.errors.invalid_quantity')]]);
        }
        return $milli;
    }

    private function amountToCents($value, string $field): int
    {
        $value = (string) $value;
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $value)) {
            throw ValidationException::withMessages([$field => [__('supplier_invoices.errors.invalid_price')]]);
        }
        $parts = explode('.', $value, 2);
        $whole = ltrim($parts[0], '0') ?: '0';
        if (strlen($whole) > 13) {
            throw ValidationException::withMessages([$field => [__('supplier_invoices.errors.invalid_price')]]);
        }
        $cents = ((int) $whole * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($cents > self::MAX_CENTS) {
            throw ValidationException::withMessages([$field => [__('supplier_invoices.errors.amount_too_large')]]);
        }
        return $cents;
    }

    private function rateToBasisPoints(string $rate): int
    {
        $parts = explode('.', $rate, 2);
        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
    }

    private function percentageOfCents(int $baseCents, int $rateBasisPoints): int
    {
        $whole = intdiv($baseCents, 10000) * $rateBasisPoints;
        $fraction = intdiv((($baseCents % 10000) * $rateBasisPoints) + 5000, 10000);
        return $whole + $fraction;
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

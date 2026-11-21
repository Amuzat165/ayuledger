<?php

namespace App\Support;

use RuntimeException;

class InvoiceDiscountConfiguration
{
    public function enabledFromValue($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1' || $value === 'true') {
            return true;
        }

        if ($value === 0 || $value === '0' || $value === 'false' || $value === null) {
            return false;
        }

        throw new RuntimeException('The configured invoice discount setting is invalid.');
    }

    public function percentageFromValue($value): float
    {
        $text = is_scalar($value) ? trim((string) $value) : '';
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $text)
            || !is_numeric($text) || !is_finite((float) $text)
            || (float) $text < 0 || (float) $text > 100) {
            throw new RuntimeException('The configured invoice discount percentage is invalid.');
        }

        return (float) $text;
    }
}
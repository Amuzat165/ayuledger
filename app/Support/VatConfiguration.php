<?php

namespace App\Support;

use RuntimeException;

class VatConfiguration
{
    public function fromGeneralSettingsContent($content): float
    {
        $settings = json_decode($content, true);

        if (!is_array($settings) || !array_key_exists('vat_percentage', $settings)) {
            throw new RuntimeException('General Settings must contain a valid VAT percentage.');
        }

        return $this->validatePercentage($settings['vat_percentage']);
    }

    public function validatePercentage($percentage): float
    {
        if (!is_numeric($percentage) || !is_finite((float) $percentage)) {
            throw new RuntimeException('The configured VAT percentage is invalid.');
        }

        $percentage = (float) $percentage;
        if ($percentage < 0 || $percentage > 100 || abs($percentage - round($percentage, 2)) > 0.0000001) {
            throw new RuntimeException('The configured VAT percentage must be between 0 and 100 with at most two decimal places.');
        }

        return $percentage;
    }
}

<?php

namespace App\Support;

use RuntimeException;

class WhtConfiguration
{
    public function defaults(): array
    {
        return [
            'wht_enabled' => false,
            'wht_mode' => 'manual',
            'wht_manual_rate' => '0.00',
        ];
    }

    public function withDefaults(array $settings): array
    {
        return array_merge($settings, $this->defaults(), $this->normalize($settings));
    }

    public function normalize(array $settings): array
    {
        $defaults = $this->defaults();
        $enabled = $this->enabledFromValue($settings['wht_enabled'] ?? $defaults['wht_enabled']);
        $mode = $settings['wht_mode'] ?? $defaults['wht_mode'];
        if (!in_array($mode, ['manual', 'rule_based'], true)) {
            throw new RuntimeException('The configured WHT mode is invalid.');
        }

        $rate = $this->validateRate($settings['wht_manual_rate'] ?? $defaults['wht_manual_rate']);
        if (!$enabled) {
            $rate = 0.0;
        }

        return [
            'wht_enabled' => $enabled,
            'wht_mode' => $mode,
            'wht_manual_rate' => number_format($rate, 2, '.', ''),
        ];
    }

    public function activeManualRate(array $settings): ?float
    {
        $normalized = $this->normalize($settings);
        if (!$normalized['wht_enabled'] || $normalized['wht_mode'] !== 'manual') {
            return null;
        }

        return (float) $normalized['wht_manual_rate'];
    }

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

        throw new RuntimeException('The configured WHT enabled setting is invalid.');
    }

    public function validateRate($rate): float
    {
        if (!is_numeric($rate) || !is_finite((float) $rate)
            || !preg_match('/^\d+(?:\.\d{1,2})?$/', (string) $rate)) {
            throw new RuntimeException('The configured WHT manual rate is invalid.');
        }

        $rate = (float) $rate;
        if ($rate < 0 || $rate > 100 || abs($rate - round($rate, 2)) > 0.0000001) {
            throw new RuntimeException('The configured WHT manual rate must be between 0 and 100 with at most two decimal places.');
        }

        return $rate;
    }
}

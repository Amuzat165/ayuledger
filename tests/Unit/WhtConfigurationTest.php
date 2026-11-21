<?php

namespace Tests\Unit;

use App\Support\WhtConfiguration;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class WhtConfigurationTest extends TestCase
{
    public function test_defaults_are_disabled_manual_and_zero_rate()
    {
        $configuration = new WhtConfiguration();

        $this->assertSame([
            'wht_enabled' => false,
            'wht_mode' => 'manual',
            'wht_manual_rate' => '0.00',
        ], $configuration->defaults());
    }

    public function test_enabled_manual_mode_exposes_only_its_configured_rate()
    {
        $configuration = new WhtConfiguration();
        $settings = $configuration->normalize([
            'wht_enabled' => '1',
            'wht_mode' => 'manual',
            'wht_manual_rate' => '10.25',
        ]);

        $this->assertTrue($settings['wht_enabled']);
        $this->assertSame('10.25', $settings['wht_manual_rate']);
        $this->assertSame(10.25, $configuration->activeManualRate($settings));
    }

    public function test_disabled_mode_cannot_activate_a_submitted_manual_rate()
    {
        $configuration = new WhtConfiguration();
        $settings = $configuration->normalize([
            'wht_enabled' => '0',
            'wht_mode' => 'manual',
            'wht_manual_rate' => '10',
        ]);

        $this->assertFalse($settings['wht_enabled']);
        $this->assertSame('0.00', $settings['wht_manual_rate']);
        $this->assertNull($configuration->activeManualRate($settings));
    }

    public function test_rule_based_mode_never_falls_back_to_the_manual_rate()
    {
        $configuration = new WhtConfiguration();
        $settings = $configuration->normalize([
            'wht_enabled' => '1',
            'wht_mode' => 'rule_based',
            'wht_manual_rate' => '10',
        ]);

        $this->assertSame('rule_based', $settings['wht_mode']);
        $this->assertNull($configuration->activeManualRate($settings));
    }

    /** @dataProvider invalidRateProvider */
    public function test_invalid_manual_rates_are_rejected($rate)
    {
        $this->expectException(RuntimeException::class);
        (new WhtConfiguration())->validateRate($rate);
    }

    public static function invalidRateProvider()
    {
        return [
            'negative' => ['-0.01'],
            'over one hundred' => ['100.01'],
            'more than two decimal places' => ['7.555'],
            'not numeric' => ['invalid'],
            'not finite' => ['INF'],
        ];
    }

    /** @dataProvider invalidSettingsProvider */
    public function test_invalid_enabled_or_mode_values_are_rejected($settings)
    {
        $this->expectException(RuntimeException::class);
        (new WhtConfiguration())->normalize($settings);
    }

    public static function invalidSettingsProvider()
    {
        return [
            'unsupported status' => [['wht_enabled' => 'yes', 'wht_mode' => 'manual', 'wht_manual_rate' => '0']],
            'unsupported mode' => [['wht_enabled' => '1', 'wht_mode' => 'statutory', 'wht_manual_rate' => '0']],
        ];
    }

    public function test_missing_legacy_wht_keys_resolve_to_safe_defaults()
    {
        $configuration = new WhtConfiguration();

        $this->assertSame(array_merge(
            ['vat_percentage' => '7.50'],
            $configuration->defaults()
        ), $configuration->withDefaults(['vat_percentage' => '7.50']));
    }
}

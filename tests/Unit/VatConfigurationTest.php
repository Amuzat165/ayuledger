<?php

namespace Tests\Unit;

use App\Support\VatConfiguration;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class VatConfigurationTest extends TestCase
{
    public function test_general_settings_json_is_the_effective_vat_source_even_when_legacy_scalar_differs()
    {
        $content = json_encode(['vat_percentage' => '7.50', 'legacy_scalar_vat_percentage' => '0.00']);

        $this->assertSame(7.5, (new VatConfiguration())->fromGeneralSettingsContent($content));
    }

    public function test_changed_authoritative_setting_is_returned_for_new_invoices()
    {
        $this->assertSame(10.0, (new VatConfiguration())->fromGeneralSettingsContent('{"vat_percentage":10}'));
    }

    /** @dataProvider invalidSettingsProvider */
    public function test_missing_or_invalid_general_settings_fail_closed($content)
    {
        $this->expectException(RuntimeException::class);
        (new VatConfiguration())->fromGeneralSettingsContent($content);
    }

    public static function invalidSettingsProvider()
    {
        return [
            'invalid JSON' => ['{'],
            'missing VAT key' => ['{"company_name":"AYUGLOBAL"}'],
            'null VAT' => ['{"vat_percentage":null}'],
            'outside approved range' => ['{"vat_percentage":100.01}'],
            'excess precision' => ['{"vat_percentage":7.555}'],
        ];
    }
}

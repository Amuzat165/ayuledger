<?php

namespace Tests\Unit;

use App\Support\VendorWithholdingCalculator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VendorWithholdingCalculatorTest extends TestCase
{
    private function line($rate = '10000.00', $quantity = 10, array $extra = [])
    {
        return array_merge([
            'item_id' => 4,
            'description' => 'Controlled calculator test',
            'rate' => $rate,
            'quantity' => $quantity,
        ], $extra);
    }

    public function test_disabled_wht_has_zero_deduction_and_full_balance_due()
    {
        $result = app(VendorWithholdingCalculator::class)->calculate([$this->line()], ['wht_enabled'=>false,'wht_mode'=>'manual','wht_manual_rate'=>'0.00']);
        $this->assertSame('100000.00', $result['vendor_total']);
        $this->assertSame('0.00', $result['withholding_tax_amount']);
        $this->assertSame('0.00', $result['wht_base_snapshot']);
        $this->assertSame('100000.00', $result['wht_balance_due_snapshot']);
    }

    public function test_manual_product_rate_calculates_wht_and_balance()
    {
        $result = app(VendorWithholdingCalculator::class)->calculate([$this->line()], ['wht_enabled'=>true,'wht_mode'=>'manual','wht_manual_rate'=>'10.00']);
        $this->assertSame('100000.00', $result['vendor_total']);
        $this->assertTrue($result['wht_enabled_snapshot']);
        $this->assertSame('manual', $result['wht_mode_snapshot']);
        $this->assertSame('10.00', $result['wht_rate_snapshot']);
        $this->assertSame('100000.00', $result['wht_base_snapshot']);
        $this->assertSame('10000.00', $result['withholding_tax_amount']);
        $this->assertSame('90000.00', $result['wht_balance_due_snapshot']);
        $this->assertSame(1, $result['wht_calculation_version']);
    }

    public function test_browser_amount_wht_total_and_balance_fields_do_not_override_calculation()
    {
        $line=$this->line('10000.00',10,['amount'=>'1.00','total'=>'1.00','balance_due'=>'1.00','withholding_tax_amount'=>'1.00']);
        $result=app(VendorWithholdingCalculator::class)->calculate([$line],['wht_enabled'=>true,'wht_mode'=>'manual','wht_manual_rate'=>'10.00','vat_percentage'=>'7.50']);
        $this->assertSame('100000.00',$result['vendor_total']);
        $this->assertSame('10000.00',$result['withholding_tax_amount']);
        $this->assertSame('90000.00',$result['wht_balance_due_snapshot']);
    }

    public function test_rule_based_mode_fails_closed_without_approved_rules()
    {
        $this->expectException(ValidationException::class);
        app(VendorWithholdingCalculator::class)->calculate([$this->line()],['wht_enabled'=>true,'wht_mode'=>'rule_based','wht_manual_rate'=>'10.00']);
    }

    public function test_rounding_is_half_up_to_two_decimal_places()
    {
        $result=app(VendorWithholdingCalculator::class)->calculate([$this->line('100.20',1)],['wht_enabled'=>true,'wht_mode'=>'manual','wht_manual_rate'=>'7.50']);
        $this->assertSame('100.20',$result['vendor_total']);
        $this->assertSame('7.52',$result['withholding_tax_amount']);
        $this->assertSame('92.68',$result['wht_balance_due_snapshot']);
    }

    public function test_line_amount_is_exact_quantity_times_authoritative_rate()
    {
        $result=app(VendorWithholdingCalculator::class)->calculate([$this->line('12.34',3)],['wht_enabled'=>false,'wht_mode'=>'manual','wht_manual_rate'=>'0.00']);
        $this->assertSame('37.02',$result['lines'][0]['amount']);
    }

    public function test_disabled_mode_never_uses_a_nonzero_manual_rate()
    {
        $result=app(VendorWithholdingCalculator::class)->calculate([$this->line()],['wht_enabled'=>false,'wht_mode'=>'manual','wht_manual_rate'=>'10.00']);
        $this->assertSame('0.00',$result['withholding_tax_amount']);
        $this->assertSame('100000.00',$result['wht_balance_due_snapshot']);
    }
}

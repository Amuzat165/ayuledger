<?php

namespace Tests\Unit;

use App\Support\VendorPaymentSettlementCalculator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VendorPaymentSettlementCalculatorTest extends TestCase
{
    private function calculator(): VendorPaymentSettlementCalculator
    {
        return new VendorPaymentSettlementCalculator();
    }

    private function state($paid = '0.00', $paymentTotal = null, $paymentBalance = null): array
    {
        return $this->calculator()->state(
            '100000.00',
            '10000.00',
            true,
            'manual',
            '90000.00',
            $paid,
            $paymentTotal,
            $paymentBalance
        );
    }

    public function test_full_payment_settles_net_payable_without_counting_wht_as_cash()
    {
        $result = $this->calculator()->applyPayment($this->state(), '90000.00');

        $this->assertSame('100000.00', $result['invoice_total']);
        $this->assertSame('10000.00', $result['wht_amount']);
        $this->assertSame('90000.00', $result['net_payable']);
        $this->assertSame('90000.00', $result['amount_paid']);
        $this->assertSame('0.00', $result['balance_due']);
    }

    public function test_partial_and_second_partial_payments_use_remaining_net_balance()
    {
        $first = $this->calculator()->applyPayment($this->state(), '50000.00');
        $this->assertSame('50000.00', $first['amount_paid']);
        $this->assertSame('40000.00', $first['balance_due']);

        $secondState = $this->state('50000.00', '90000.00', '40000.00');
        $second = $this->calculator()->applyPayment($secondState, '40000.00');
        $this->assertSame('90000.00', $second['amount_paid']);
        $this->assertSame('0.00', $second['balance_due']);
    }

    public function test_overpayment_is_rejected()
    {
        $this->expectException(ValidationException::class);
        $this->calculator()->applyPayment($this->state('50000.00', '90000.00', '40000.00'), '40000.01');
    }

    /** @dataProvider invalidPaymentAmounts */
    public function test_zero_negative_malformed_and_excess_precision_payments_are_rejected($amount)
    {
        $this->expectException(ValidationException::class);
        $this->calculator()->validatePaymentAmount($amount);
    }

    public static function invalidPaymentAmounts(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-1.00'],
            'malformed' => ['NaN'],
            'too many decimals' => ['1.001'],
        ];
    }

    public function test_historical_invoice_without_wht_snapshot_fails_closed()
    {
        $this->expectException(ValidationException::class);
        $this->calculator()->state('100000.00', '2250.00', null, null, null);
    }

    public function test_existing_payment_summary_must_match_snapshot_backed_net_payable()
    {
        $this->expectException(ValidationException::class);
        $this->state('50000.00', '100000.00', '50000.00');
    }

    public function test_settings_are_not_an_input_to_historical_settlement_calculation()
    {
        config(['settings.wht_enabled' => false, 'settings.wht_mode' => 'rule_based', 'settings.wht_manual_rate' => '87.50']);

        $result = $this->calculator()->applyPayment($this->state(), '50000.00');

        $this->assertSame('10000.00', $result['wht_amount']);
        $this->assertSame('90000.00', $result['net_payable']);
        $this->assertSame('40000.00', $result['balance_due']);
    }
}

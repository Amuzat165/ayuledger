<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerInvoiceDiscountViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $compiledViews = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ayu-invoice-discount-views';
        if (!is_dir($compiledViews)) {
            mkdir($compiledViews, 0777, true);
        }
        Config::set('view.compiled', $compiledViews);
        $compiler = $this->app->make('blade.compiler');
        $cachePath = new \ReflectionProperty(\Illuminate\View\Compilers\Compiler::class, 'cachePath');
        $cachePath->setAccessible(true);
        $cachePath->setValue($compiler, $compiledViews);
        URL::shouldReceive('route')->andReturn('/test-customer-invoice-store');
    }
    private function invoiceForm(bool $enabled, $percentage = 0): string
    {
        $params = (object) [
            'customer_id_options' => [],
            'customers' => [],
            'next_invoice_number' => '0001',
            'vat_percentage' => 7.5,
            'discount_enabled' => $enabled,
            'discount_percentage' => $percentage,
            'inventory_items' => collect(),
        ];

        return view('pages.customers.invoices.ui.create-invoice', compact('params'))->render();
    }

    public function test_enabled_discount_is_shown_as_configured_read_only_value()
    {
        $html = $this->invoiceForm(true, 10);

        $this->assertStringContainsString('id="discount_percentage"', $html);
        $this->assertStringContainsString('value="10.00" readonly', $html);
        $this->assertStringNotContainsString('name="discount_percentage"', $html);
    }

    public function test_disabled_discount_controls_are_absent_from_invoice_form()
    {
        $html = $this->invoiceForm(false, 10);

        $this->assertStringNotContainsString('id="discount_percentage"', $html);
        $this->assertStringNotContainsString('id="discount_amount"', $html);
    }
}
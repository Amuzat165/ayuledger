<?php

namespace Tests\Feature;

use App\Models\CustomerInvoice;
use App\Models\CustomerPaymentReceipt;
use App\Models\CustomerDeposit;
use App\Http\Controllers\CustomerInvoiceController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\CustomerDepositController;
use App\Services\CustomerPaymentService;
use App\Services\CustomerDepositApplicationService;
use App\Services\CustomerDepositBalanceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class CustomerPaymentAllocationTest extends TestCase
{
    protected $testDb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDb = getenv('AYU_STEP6D2_TEST_DATABASE');
        if (!$this->testDb || strpos($this->testDb, 'ayuglobal_step6d2_test_') !== 0) {
            $this->markTestSkipped('Set AYU_STEP6D2_TEST_DATABASE to a dedicated ayuglobal_step6d2_test_* MySQL database.');
        }
        if ($this->testDb === config('database.connections.mysql.database')) {
            $this->fail('The isolated test database must differ from the configured application database.');
        }
        $compiledViews = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ayu-step6d2-blade';
        if (!is_dir($compiledViews)) mkdir($compiledViews, 0777, true);
        Config::set('view.compiled', $compiledViews);
        $compiler = $this->app->make('blade.compiler');
        $cachePath = new \ReflectionProperty(\Illuminate\View\Compilers\Compiler::class, 'cachePath');
        $cachePath->setAccessible(true);
        $cachePath->setValue($compiler, $compiledViews);
        Config::set('database.connections.mysql.database', $this->testDb);
        DB::purge('mysql');
        $this->createBaseSchema();
        if (!Schema::hasTable('branches')) Schema::create('branches', function (Blueprint $t) { $t->increments('id'); $t->string('name')->nullable(); $t->timestamps(); $t->softDeletes(); });
        if (!Schema::hasTable('customer_invoice_items')) Schema::create('customer_invoice_items', function (Blueprint $t) { $t->bigIncrements('id'); $t->unsignedBigInteger('invoice_id'); $t->unsignedInteger('item_id')->nullable(); $t->string('description')->nullable(); $t->decimal('rate', 15, 2)->default(0); $t->integer('quantity')->default(1); $t->decimal('amount', 15, 2)->default(0); $t->timestamps(); $t->softDeletes(); });
        if (Schema::hasTable('bank_cashes') && !Schema::hasColumn('bank_cashes', 'deleted_at')) Schema::table('bank_cashes', function (Blueprint $t) { $t->softDeletes(); });
        if (!Schema::hasColumn('customers', 'deleted_at')) Schema::table('customers', function (Blueprint $t) { $t->softDeletes(); });
        if (!Schema::hasColumn('customers', 'branch_id')) Schema::table('customers', function (Blueprint $t) { $t->unsignedInteger('branch_id')->nullable(); });
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'deleted_at')) Schema::table('users', function (Blueprint $t) { $t->softDeletes(); });
        if (!Schema::hasTable('customer_payment_receipts')) {
            if (!class_exists('CreateCustomerPaymentReceiptsTable', false)) {
                require_once base_path('database/migrations/2026_09_24_000004_create_customer_payment_receipts_table.php');
            }
            (new \CreateCustomerPaymentReceiptsTable())->up();
        }
        if (!Schema::hasTable('customer_deposits')) {
            if (!class_exists('CreateCustomerDepositsAndApplicationsTables', false)) require_once base_path('database/migrations/2026_09_24_000005_create_customer_deposits_and_applications_tables.php');
            (new \CreateCustomerDepositsAndApplicationsTables())->up();
        }
        DB::beginTransaction();
        DB::table('customer_deposit_applications')->delete();
        DB::table('customer_deposits')->delete();
        DB::table('customer_payment_allocations')->delete();
        DB::table('customer_payment_receipts')->delete();
        if (Schema::hasTable('customer_invoice_items')) DB::table('customer_invoice_items')->delete();
        DB::table('customer_invoices')->delete();
        DB::table('customer_payments')->delete();
        DB::table('customers')->delete();
        DB::table('bank_cashes')->delete();
        DB::table('customer_payment_receipt_sequences')->where('id', 1)->update(['last_number' => 0]);
        $this->customerId = DB::table('customers')->insertGetId(['name' => 'Step 6D2 test customer', 'created_at' => now(), 'updated_at' => now()]);
        $this->modeId = DB::table('bank_cashes')->insertGetId(['name' => 'Test cash', 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function tearDown(): void
    {
        if (DB::connection('mysql')->transactionLevel() > 0) DB::rollBack();
        parent::tearDown();
    }

    public function test_partial_and_exact_final_payment_use_snapshot_balance()
    {
        $invoice = $this->invoice('100.00');
        $first = $this->pay($invoice, '40.00', 'a54f1369-7c43-4aa7-8f6d-4a4f2ff98101');
        $this->assertSame('60.00', $first->allocations->first()->balance_after);
        $this->assertSame('60.00', app(CustomerPaymentService::class)->balanceFor($invoice));
        $last = $this->pay($invoice, '60.00', 'a54f1369-7c43-4aa7-8f6d-4a4f2ff98102');
        $this->assertSame('0.00', $last->allocations->first()->balance_after);
        $this->assertSame('0.00', app(CustomerPaymentService::class)->balanceFor($invoice));
        $this->assertSame(2, CustomerPaymentReceipt::count());
    }

    public function test_overpayment_creates_deposit_and_zero_or_negative_are_rejected()
    {
        $invoice = $this->invoice('10.00');
        $receipt = $this->pay($invoice, '15.00', 'a64f1369-7c43-4aa7-8f6d-4a4f2ff98101');
        $this->assertSame('15.00', $receipt->amount_received);
        $this->assertSame('10.00', $receipt->allocations->first()->allocated_amount);
        $this->assertSame('0.00', $receipt->allocations->first()->balance_after);
        $this->assertSame('5.00', $receipt->deposit->original_amount);
        $this->assertSame('5.00', $receipt->customer_deposit_balance_after);
        $this->assertSame('0.00', app(CustomerPaymentService::class)->balanceFor($invoice));
        $retry = $this->pay($invoice, '15.00', 'a64f1369-7c43-4aa7-8f6d-4a4f2ff98101');
        $this->assertSame($receipt->id, $retry->id);
        $this->assertSame(1, CustomerDeposit::count());
        foreach (['0.00', '-1.00'] as $amount) {
            try { $this->pay($invoice, $amount, (string) \Illuminate\Support\Str::uuid()); $this->fail('Expected invalid payment to be rejected.'); }
            catch (\Illuminate\Validation\ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        }
        $this->assertSame(1, CustomerPaymentReceipt::count());
        $this->assertSame(1, CustomerDeposit::count());
    }

    public function test_deposit_can_be_applied_in_multiple_events_and_is_idempotent()
    {
        $source = $this->invoice('10.00'); $target = $this->invoice('20.00');
        $receipt = $this->pay($source, '20.00', 'b64f1369-7c43-4aa7-8f6d-4a4f2ff98102');
        $deposit = $receipt->deposit;
        $service = app(CustomerDepositApplicationService::class);
        $key = 'c64f1369-7c43-4aa7-8f6d-4a4f2ff98103';
        $first = $service->apply($deposit->id, $target->id, '4.00', $key);
        $again = $service->apply($deposit->id, $target->id, '4.00', $key);
        $this->assertSame($first->id, $again->id);
        $second = $service->apply($deposit->id, $target->id, '6.00', 'd64f1369-7c43-4aa7-8f6d-4a4f2ff98104');
        $this->assertSame('16.00', $first->invoice_balance_after);
        $this->assertSame('6.00', $first->deposit_balance_after);
        $this->assertSame('0.00', $second->deposit_balance_after);
        $this->assertSame('0.00', app(CustomerDepositBalanceService::class)->balanceFor($deposit));
        $this->assertSame('10.00', app(CustomerPaymentService::class)->balanceFor($target));
        $this->assertSame(2, DB::table('customer_deposit_applications')->count());
    }

    public function test_deposit_history_eager_loads_application_operator_as_app_user()
    {
        $operatorId = $this->testUser();
        $source = $this->invoice('10.00');
        $deposit = $this->pay($source, '15.00', '774f1369-7c43-4aa7-8f6d-4a4f2ff98101')->deposit;
        $target = $this->invoice('20.00');
        app(CustomerDepositApplicationService::class)->apply(
            $deposit->id,
            $target->id,
            '1.00',
            '874f1369-7c43-4aa7-8f6d-4a4f2ff98101',
            $operatorId
        );

        $history = CustomerDeposit::with('applications.operator')->findOrFail($deposit->id);
        $application = $history->applications->sole();
        $this->assertSame($operatorId, (int) $application->operator->id);
        $this->assertInstanceOf(\App\User::class, $application->operator);

        $controllerView = app(CustomerDepositController::class)->index();
        $controllerDeposit = $controllerView->getData()['deposits']->sole();
        $this->assertSame($operatorId, (int) $controllerDeposit->applications->sole()->operator->id);
    }

    public function test_invoice_pdf_uses_saved_snapshot_without_payment_history_or_current_vat()
    {
        $invoice = $this->invoice('11180.00');
        DB::table('customer_invoices')->where('id', $invoice->id)->update([
            'subtotal' => '10400.00', 'vat_percentage' => '7.50', 'vat_amount' => '780.00', 'total_due' => '11180.00',
        ]);
        $invoice = CustomerInvoice::findOrFail($invoice->id);
        Config::set('settings.vat_percentage', 99);
        Config::set('settings.currency_symbol', 'USD');

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('A4')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->with('invoice_' . $invoice->id . '.pdf')->andReturn(response('PDF'));
        Pdf::shouldReceive('loadView')->once()->with('pages.customers.invoices.receipt', \Mockery::on(function ($data) {
            $this->assertSame('10400.00', (string) $data['invoice']->subtotal);
            $this->assertSame('7.50', (string) $data['invoice']->vat_percentage);
            $this->assertSame('780.00', (string) $data['invoice']->vat_amount);
            $this->assertSame('11180.00', (string) $data['invoice']->total_due);
            $this->assertSame('USD', $data['currency']);
            $this->assertSame(99, config('settings.vat_percentage'));
            return true;
        }))->andReturn($pdf);

        $response = app(CustomerInvoiceController::class)->pdf($invoice->id);
        $this->assertSame('PDF', $response->getContent());
        $this->assertSame(0, CustomerPaymentReceipt::where('customer_id', $this->customerId)->count());
        $this->assertSame(0, DB::table('customer_payments')->count());
    }

    public function test_unpaid_snapshot_invoice_can_generate_a_real_pdf()
    {
        $invoice = $this->invoice('10.00');
        Config::set('settings.currency_symbol', 'USD');

        $response = app(CustomerInvoiceController::class)->pdf($invoice->id);

        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertSame(0, CustomerPaymentReceipt::where('customer_id', $this->customerId)->count());
        $this->assertSame(0, DB::table('customer_payments')->count());
    }

    public function test_payment_receipt_pdf_uses_an_existing_payment_event()
    {
        $invoice = $this->discountedInvoice();
        $receipt = $this->pay($invoice, '5.00', '974f1369-7c43-4aa7-8f6d-4a4f2ff98101');
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->once()->with('A4')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->with($receipt->receipt_number . '.pdf')->andReturn(response('PDF'));
        Pdf::shouldReceive('loadView')->once()->with('pages.customers.receipts.event-pdf', \Mockery::on(function ($data) use ($receipt) {
            $this->assertSame($receipt->id, $data['receipt']->id);
            $this->assertSame($receipt->allocations->first()->id, $data['allocation']->id);
            $this->assertSame('10.00', (string) $data['allocation']->invoice->discount_percentage);
            $this->assertSame('10750.00', (string) $data['allocation']->invoice->discount_amount);
            $this->assertSame('96750.00', (string) $data['allocation']->invoice->balance_due);
            return true;
        }))->andReturn($pdf);

        $response = app(CustomerPaymentController::class)->receiptPdf($receipt->id);
        $this->assertSame('PDF', $response->getContent());
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(CustomerPaymentController::class)->receiptPdf(999999);
    }

    public function test_discounted_snapshot_balance_drives_partial_and_full_payments_even_after_setting_changes()
    {
        $invoice = $this->discountedInvoice();
        Config::set('settings.discount_enabled', false);

        $this->assertSame('96750.00', app(\App\Services\CustomerInvoiceBalanceService::class)->balanceFor($invoice));
        $partial = $this->pay($invoice, '50000.00', 'a8c0f0c6-5182-4d29-a97d-0f2e1eb09901');
        $this->assertSame('50000.00', $partial->allocations->first()->allocated_amount);
        $this->assertSame('46750.00', $partial->allocations->first()->balance_after);

        $remainder = $this->pay($invoice, '46750.00', 'afc32df4-3353-47e6-b328-0b029b33ca02');
        $this->assertSame('46750.00', $remainder->allocations->first()->allocated_amount);
        $this->assertSame('0.00', $remainder->allocations->first()->balance_after);
        $this->assertSame('0.00', app(\App\Services\CustomerInvoiceBalanceService::class)->balanceFor($invoice));
    }

    public function test_overpayment_against_discounted_balance_becomes_customer_deposit()
    {
        $invoice = $this->discountedInvoice();
        $receipt = $this->pay($invoice, '100000.00', 'b7d3dfd6-480a-4df1-9909-3d69e017aa01');

        $this->assertSame('96750.00', $receipt->allocations->first()->allocated_amount);
        $this->assertSame('0.00', $receipt->allocations->first()->balance_after);
        $this->assertSame('3250.00', $receipt->deposit_created_amount);
        $this->assertSame('0.00', app(\App\Services\CustomerInvoiceBalanceService::class)->balanceFor($invoice));
    }

    public function test_invoice_list_and_print_view_use_configured_currency()
    {
        Config::set('settings.currency_symbol', 'USD');
        $this->invoice('25.00');

        if (!app('router')->getRoutes()->getByName('customers.invoices.create') || !app('router')->getRoutes()->getByName('administration.hub')) {
            \Illuminate\Support\Facades\Route::middleware('web')->namespace('App\\Http\\Controllers')->group(function () {
                require base_path('routes/web.php');
                if (!app('router')->getRoutes()->getByName('customers.invoices.create') || !app('router')->getRoutes()->getByName('administration.hub')) {
                    require base_path('routes/web/customers-route.php');
                }
            });
        }
        app('url')->setRoutes(app('router')->getRoutes());
        app('view')->share('is_rtl', \App\Support\LocaleManager::direction(app()->getLocale()) === 'rtl' ? 1 : 0);
        $list = app(CustomerInvoiceController::class)->index();
        $this->assertInstanceOf(\Illuminate\View\View::class, $list);
        $this->assertSame('USD', $list->getData()['currency']);
        $listView = file_get_contents(resource_path('views/pages/customers/invoices/invoice-view.blade.php'));
        $this->assertStringContainsString('{{ $currency }}{{ $total_amount }}', $listView);

        $invoice = (object) [
            'invoice_number' => 'INV-USD', 'invoice_date' => '2026-09-24',
            'subtotal' => '25.00', 'vat_percentage' => '0.00', 'vat_amount' => '0.00', 'total_due' => '25.00',
            'discount_percentage' => null, 'discount_amount' => null, 'balance_due' => null,
        ];
        $customer = (object) ['name' => 'Test Customer', 'address' => 'Test address', 'branch' => null];
        $item = (object) [
            'inventory' => (object) ['type' => (object) ['name' => 'Service']],
            'description' => 'Test item', 'quantity' => 1, 'rate' => '25.00', 'amount' => '25.00',
        ];
        $viewData = [
            'invoice' => $invoice, 'customer' => $customer, 'invoiceItems' => collect([$item]), 'totalAmount' => '25.00',
            'header_image' => null, 'company_logo' => '', 'company_name' => 'AYU GLOBAL', 'company_color' => '#00897B',
            'address_1' => '', 'address_2' => '', 'city' => '', 'phone' => '', 'email' => '',
            'vat_percentage' => '0.00', 'currency' => 'USD',
        ];
        $blade = new \Illuminate\View\Compilers\BladeCompiler(new \Illuminate\Filesystem\Filesystem(), sys_get_temp_dir());
        $compiled = $blade->compileString(file_get_contents(resource_path('views/pages/customers/invoices/receipt.blade.php')));
        extract($viewData);
        $__env = app('view');
        ob_start();
        eval('?>' . $compiled);
        $html = ob_get_clean();
        $this->assertStringContainsString('USD25.00', $html);
        $this->assertStringNotContainsString('₦', $html);
        $this->assertStringNotContainsString('Discount', $html);

        $invoice->discount_percentage = '10.00';
        $invoice->discount_amount = '2.50';
        $invoice->balance_due = '22.50';
        extract($viewData);
        ob_start();
        eval('?>' . $compiled);
        $discountedHtml = ob_get_clean();
        $this->assertStringContainsString('Discount (10.00%)', $discountedHtml);
        $this->assertStringContainsString('USD2.50', $discountedHtml);
        $this->assertStringContainsString('USD22.50', $discountedHtml);
    }

    public function test_invoice_pdf_and_payment_receipt_pdf_routes_are_distinct_and_authenticated()
    {
        $invoiceRoute = app('router')->getRoutes()->getByName('customers.invoices.pdf');
        $receiptRoute = app('router')->getRoutes()->getByName('customer.payment_receipts.pdf');
        $this->assertNotNull($invoiceRoute);
        $this->assertSame('App\\Http\\Controllers\\CustomerInvoiceController@pdf', $invoiceRoute->getActionName());
        $this->assertContains('auth', $invoiceRoute->gatherMiddleware());
        $this->assertNotNull($receiptRoute);
        $this->assertSame('App\\Http\\Controllers\\CustomerPaymentController@receiptPdf', $receiptRoute->getActionName());
        $this->assertContains('auth', $receiptRoute->gatherMiddleware());
        $this->assertNull(app('router')->getRoutes()->getByName('invoices.receipt.pdf'));
    }

    public function test_application_rejects_other_customer_excess_amounts_and_snapshotless_invoice()
    {
        $source = $this->invoice('10.00'); $receipt = $this->pay($source, '15.00', 'e64f1369-7c43-4aa7-8f6d-4a4f2ff98105');
        $deposit = $receipt->deposit; $service = app(CustomerDepositApplicationService::class);
        $otherCustomer = DB::table('customers')->insertGetId(['name' => 'Other test customer', 'created_at' => now(), 'updated_at' => now()]);
        $other = $this->invoiceFor($otherCustomer, '20.00');
        $smallInvoice = $this->invoice('3.00');
        foreach ([[$other->id, '1.00'], [$this->invoice('20.00')->id, '6.00'], [$smallInvoice->id, '4.00'], [$this->invoice(null)->id, '1.00'], [$this->invoice('20.00')->id, '0.00'], [$this->invoice('20.00')->id, '-1.00']] as $index => $case) {
            try { $service->apply($deposit->id, $case[0], $case[1], 'f64f1369-7c43-4aa7-8f6d-' . str_pad((string) (98106 + $index), 12, '0', STR_PAD_LEFT)); $this->fail('Invalid application should be rejected.'); }
            catch (\Illuminate\Validation\ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        }
        $this->assertSame(0, DB::table('customer_deposit_applications')->count());
    }

    public function test_application_idempotency_key_conflict_is_rejected()
    {
        $source = $this->invoice('10.00'); $target = $this->invoice('20.00');
        $deposit = $this->pay($source, '15.00', '164f1369-7c43-4aa7-8f6d-4a4f2ff98106')->deposit;
        $service = app(CustomerDepositApplicationService::class); $key = '264f1369-7c43-4aa7-8f6d-4a4f2ff98107';
        $service->apply($deposit->id, $target->id, '2.00', $key);
        try { $service->apply($deposit->id, $target->id, '3.00', $key); $this->fail('Changed application payload must conflict.'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        $this->assertSame(1, DB::table('customer_deposit_applications')->count());
    }

    public function test_competing_application_waits_on_customer_lock_before_spending_deposit()
    {
        $source = $this->invoice('10.00'); $target = $this->invoice('20.00');
        $deposit = $this->pay($source, '20.00', '364f1369-7c43-4aa7-8f6d-4a4f09810801')->deposit;
        DB::connection('mysql')->commit();
        DB::connection('mysql')->beginTransaction();
        DB::connection('mysql')->table('customers')->where('id', $this->customerId)->lockForUpdate()->first();
        Config::set('database.connections.step6d3_lock', Config::get('database.connections.mysql'));
        DB::connection('step6d3_lock')->statement('SET SESSION innodb_lock_wait_timeout = 1');
        Config::set('database.default', 'step6d3_lock');
        try {
            try {
                app(CustomerDepositApplicationService::class)->apply($deposit->id, $target->id, '6.00', '464f1369-7c43-4aa7-8f6d-4a4f09810802');
                $this->fail('Competing application should wait for the customer row lock.');
            } catch (\Illuminate\Database\QueryException $e) {
                $this->assertStringContainsString('lock wait timeout', strtolower($e->getMessage()));
            }
        } finally { Config::set('database.default', 'mysql'); }
        $this->assertSame(0, DB::connection('mysql')->table('customer_deposit_applications')->count());
        DB::connection('mysql')->rollBack();
        $applied = app(CustomerDepositApplicationService::class)->apply($deposit->id, $target->id, '6.00', '564f1369-7c43-4aa7-8f6d-4a4f09810803');
        $this->assertSame('4.00', $applied->deposit_balance_after);
    }

    public function test_snapshotless_historical_invoice_is_rejected()
    {
        $invoice = $this->invoice(null);
        try { $this->pay($invoice, '1.00', 'b54f1369-7c43-4aa7-8f6d-4a4f2ff98101'); $this->fail('Historical invoice should be blocked.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertStringContainsString('Historical invoice requires review', implode(' ', $e->errors()['invoice'])); }
        $this->assertSame(0, CustomerPaymentReceipt::count());
    }

    public function test_inconsistent_version_one_invoice_snapshot_is_rejected_for_review()
    {
        $invoice = $this->invoice('10.00');
        DB::table('customer_invoices')->where('id', $invoice->id)->update(['total_due' => '11.00']);
        $invoice = CustomerInvoice::findOrFail($invoice->id);
        try { $this->pay($invoice, '1.00', 'f54f1369-7c43-4aa7-8f6d-4a4f2ff98101'); $this->fail('Inconsistent snapshot should be blocked.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertStringContainsString('Historical invoice requires review', implode(' ', $e->errors()['invoice'])); }
        $this->assertSame(0, CustomerPaymentReceipt::count());
    }

    public function test_same_idempotency_payload_returns_existing_and_conflict_is_rejected()
    {
        $invoice = $this->invoice('20.00');
        $key = 'c54f1369-7c43-4aa7-8f6d-4a4f2ff98101';
        $first = $this->pay($invoice, '5.00', $key);
        $again = $this->pay($invoice, '5.00', $key);
        $this->assertSame($first->id, $again->id);
        $this->assertSame(1, CustomerPaymentReceipt::count());
        try { $this->pay($invoice, '6.00', $key); $this->fail('Changed payload must conflict.'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
    }

    public function test_receipt_numbers_are_sequential_and_legacy_aggregate_table_is_not_written()
    {
        $invoice = $this->invoice('30.00');
        $legacyId = DB::table('customer_payments')->insertGetId([
            'customer_id' => $this->customerId, 'invoice_id' => $invoice->id,
            'total_amount' => '30.00', 'amount_paid' => '2.00', 'balance_due' => '28.00',
            'payment_mode_id' => $this->modeId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $a = $this->pay($invoice, '5.00', 'd54f1369-7c43-4aa7-8f6d-4a4f2ff98101');
        $b = $this->pay($invoice, '5.00', 'e54f1369-7c43-4aa7-8f6d-4a4f2ff98101');
        $this->assertSame('AYU-RCP-000001', $a->receipt_number);
        $this->assertSame('AYU-RCP-000002', $b->receipt_number);
        $this->assertSame('2.00', DB::table('customer_payments')->where('id', $legacyId)->value('amount_paid'));
        $this->assertSame('28.00', DB::table('customer_payments')->where('id', $legacyId)->value('balance_due'));
    }

    public function test_receipt_pdf_renders_the_saved_event_and_configured_currency_without_hardcoded_naira()
    {
        $receipt = (object) [
            'receipt_number' => 'AYU-RCP-000007', 'customer' => (object) ['name' => 'Acme'],
            'amount_received' => '12.50', 'deposit_created_amount' => '2.50', 'customer_deposit_balance_after' => '2.50', 'deposit' => (object) ['id' => 8], 'payment_mode_name' => 'Test cash',
            'received_at' => now(),
        ];
        $allocation = (object) [
            'invoice' => (object) ['invoice_number' => 'INV-7'],
            'allocated_amount' => '12.50', 'balance_after' => '2.50',
        ];
        $data = [
            'receipt' => $receipt,
            'allocation' => $allocation,
            'currency' => 'USD',
            'currency_code' => 'USD',
            'amount_in_words' => app(\App\Support\AmountInWordsFormatter::class)->format($receipt->amount_received, 'USD'),
            'total_paid_to_date' => '12.50',
            'company_name' => 'AYU GLOBAL',
            'contact_person' => '',
            'company_logo' => '',
            'header_image' => null,
            'company_color' => '#00897B',
            'address_1' => '',
            'address_2' => '',
            'city' => '',
            'state' => '',
            'zip_code' => '',
            'phone' => '',
            'email' => '',
        ];
        $html = view('pages.customers.receipts.event-pdf', $data)->render();
        foreach (['AYU-RCP-000007', 'Acme', 'INV-7', 'Applied to Invoice', 'USD12.50', 'Test cash', 'Customer Credit', 'USD2.50'] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('₦', $html);
    }

    public function test_unsupported_receipt_store_route_is_removed_and_event_pdf_route_exists()
    {
        $this->assertNull(app('router')->getRoutes()->getByName('customers.receipts.store'));
        $route = app('router')->getRoutes()->getByName('customer.payment_receipts.pdf');
        $this->assertNotNull($route);
        $this->assertSame(['GET', 'HEAD'], $route->methods());
    }

    public function test_receipt_schema_has_unique_numbers_idempotency_and_restrictive_history_foreign_keys()
    {
        $columns = DB::select('SELECT TABLE_NAME, COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (?, ?)', ['customer_payment_receipts', 'customer_payment_allocations']);
        $this->assertContains('receipt_number', array_column($columns, 'COLUMN_NAME'));
        $this->assertContains('idempotency_key', array_column($columns, 'COLUMN_NAME'));
        $uniqueIndexes = DB::select('SELECT COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND NON_UNIQUE = 0', ['customer_payment_receipts']);
        $this->assertContains('receipt_number', array_column($uniqueIndexes, 'COLUMN_NAME'));
        $this->assertContains('idempotency_key', array_column($uniqueIndexes, 'COLUMN_NAME'));
        $constraints = DB::select('SELECT k.TABLE_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r USING (CONSTRAINT_SCHEMA, CONSTRAINT_NAME) WHERE k.CONSTRAINT_SCHEMA = DATABASE() AND k.TABLE_NAME IN (?, ?)', ['customer_payment_receipts', 'customer_payment_allocations']);
        foreach ([['customer_payment_receipts', 'customer_id', 'customers', 'RESTRICT'], ['customer_payment_allocations', 'receipt_id', 'customer_payment_receipts', 'RESTRICT'], ['customer_payment_allocations', 'invoice_id', 'customer_invoices', 'RESTRICT'], ['customer_payment_receipts', 'payment_mode_id', 'bank_cashes', 'SET NULL'], ['customer_payment_receipts', 'created_by', 'users', 'SET NULL'], ['customer_payment_allocations', 'user_id', 'users', 'SET NULL']] as $expected) {
            $this->assertTrue(collect($constraints)->contains(function ($row) use ($expected) {
                return [$row->TABLE_NAME, $row->COLUMN_NAME, $row->REFERENCED_TABLE_NAME, strtoupper($row->DELETE_RULE)] === $expected;
            }), implode('.', array_slice($expected, 0, 2)));
        }
    }

    public function test_deposit_routes_are_authenticated_csrf_protected_post_for_mutation_and_schema_is_restrictive()
    {
        foreach (['customer-deposits.index', 'customer-deposits.apply.form', 'customer-deposits.apply'] as $name) {
            $route = app('router')->getRoutes()->getByName($name); $this->assertNotNull($route);
            $this->assertContains('auth', $route->gatherMiddleware());
        }
        $post = app('router')->getRoutes()->getByName('customer-deposits.apply');
        $this->assertSame(['POST'], $post->methods());
        $this->assertContains('web', $post->gatherMiddleware());
        $get = app('router')->getRoutes()->getByName('customer-deposits.apply.form');
        $this->assertSame(['GET', 'HEAD'], $get->methods());
        $columns = DB::select("SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('customer_deposits','customer_deposit_applications')");
        $this->assertContains('original_amount', array_column($columns, 'COLUMN_NAME'));
        $this->assertContains('amount_applied', array_column($columns, 'COLUMN_NAME'));
        $keys = DB::select("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME IN ('customer_deposits','customer_deposit_applications') AND REFERENCED_TABLE_NAME IS NOT NULL");
        foreach ([['customer_deposits','customer_id','customers'], ['customer_deposits','source_payment_receipt_id','customer_payment_receipts'], ['customer_deposits','source_invoice_id','customer_invoices'], ['customer_deposit_applications','customer_deposit_id','customer_deposits'], ['customer_deposit_applications','invoice_id','customer_invoices']] as $expected) {
            $this->assertTrue(collect($keys)->contains(fn($r) => [$r->TABLE_NAME,$r->COLUMN_NAME,$r->REFERENCED_TABLE_NAME] === $expected));
        }
    }

    public function test_guest_cannot_open_deposit_pages_or_post_an_application()
    {
        $this->get(route('customer-deposits.index'))->assertRedirect(route('login'));
        $this->get(route('customer-deposits.apply.form', 999))->assertRedirect(route('login'));
        $this->post(route('customer-deposits.apply', 999), [])->assertRedirect(route('login'));
    }

    public function test_invoice_row_lock_blocks_a_competing_payment_until_the_first_transaction_finishes()
    {
        $invoice = $this->invoice('15.00');
        DB::connection('mysql')->commit(); // Commit isolated fixtures, then hold only the invoice row lock.
        DB::connection('mysql')->beginTransaction();
        DB::connection('mysql')->table('customer_invoices')->where('id', $invoice->id)->lockForUpdate()->first();

        Config::set('database.connections.step6d2_lock', Config::get('database.connections.mysql'));
        $secondConnection = DB::connection('step6d2_lock');
        $secondConnection->statement('SET SESSION innodb_lock_wait_timeout = 1');
        Config::set('database.default', 'step6d2_lock');
        try {
            try {
                app(CustomerPaymentService::class)->pay($invoice->id, $invoice->customer_id, '10.00', $this->modeId, '12541369-7c43-4aa7-8f6d-4a4f2ff98101');
                $this->fail('Competing payment should wait for the invoice row lock.');
            } catch (\Illuminate\Database\QueryException $e) {
                $this->assertStringContainsString('lock wait timeout', strtolower($e->getMessage()));
            }
        } finally {
            Config::set('database.default', 'mysql');
        }
        $this->assertSame(0, DB::connection('mysql')->table('customer_payment_receipts')->count());
        DB::connection('mysql')->rollBack();
        $accepted = $this->pay($invoice, '10.00', '22541369-7c43-4aa7-8f6d-4a4f2ff98101');
        $this->assertSame('5.00', $accepted->allocations->first()->balance_after);
    }

    private function pay($invoice, $amount, $key)
    {
        return app(CustomerPaymentService::class)->pay($invoice->id, $invoice->customer_id, $amount, $this->modeId, $key);
    }

    private function invoice($total)
    {
        return $this->invoiceFor($this->customerId, $total);
    }

    private function invoiceFor($customerId, $total)
    {
        $id = DB::table('customer_invoices')->insertGetId([
            'customer_id' => $customerId, 'invoice_number' => 'T-' . \Illuminate\Support\Str::random(8),
            'invoice_date' => now()->toDateString(), 'exchange_rate' => '1.00',
            'subtotal' => $total, 'vat_percentage' => $total === null ? null : '0.00',
            'vat_amount' => $total === null ? null : '0.00', 'total_due' => $total,
            'financial_snapshot_version' => $total === null ? null : 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return CustomerInvoice::findOrFail($id);
    }

    private function discountedInvoice()
    {
        $id = DB::table('customer_invoices')->insertGetId([
            'customer_id' => $this->customerId,
            'invoice_number' => 'DISC-' . \Illuminate\Support\Str::random(8),
            'invoice_date' => now()->toDateString(),
            'exchange_rate' => '1.00',
            'subtotal' => '100000.00', 'vat_percentage' => '7.50', 'vat_amount' => '7500.00',
            'total_due' => '107500.00', 'discount_percentage' => '10.00', 'discount_amount' => '10750.00',
            'balance_due' => '96750.00', 'financial_snapshot_version' => 2,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return CustomerInvoice::findOrFail($id);
    }

    private function testUser()
    {
        $columns = Schema::getColumnListing('users');
        $attributes = [];
        foreach ([
            'name' => 'Step 6D5 test operator',
            'email' => 'step6d5-' . \Illuminate\Support\Str::uuid() . '@example.test',
            'password' => bcrypt('isolated-test-password'),
            'role_manage_id' => null,
            'language_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ] as $column => $value) {
            if (in_array($column, $columns, true)) $attributes[$column] = $value;
        }
        return DB::table('users')->insertGetId($attributes);
    }

    private function createBaseSchema()
    {
        if (!Schema::hasTable('customers')) Schema::create('customers', function (Blueprint $t) { $t->increments('id'); $t->string('name'); $t->unsignedInteger('branch_id')->nullable(); $t->timestamps(); $t->softDeletes(); });
        if (!Schema::hasTable('bank_cashes')) Schema::create('bank_cashes', function (Blueprint $t) { $t->increments('id'); $t->string('name'); $t->timestamps(); });
        if (!Schema::hasTable('users')) Schema::create('users', function (Blueprint $t) { $t->increments('id'); $t->string('name')->nullable(); $t->string('email')->nullable(); $t->string('password')->nullable(); $t->timestamps(); $t->softDeletes(); });
        if (!Schema::hasTable('customer_invoices')) Schema::create('customer_invoices', function (Blueprint $t) {
            $t->bigIncrements('id'); $t->unsignedInteger('customer_id'); $t->string('invoice_number')->unique(); $t->date('invoice_date'); $t->decimal('exchange_rate', 8, 2);
            $t->decimal('subtotal', 15, 2)->nullable(); $t->decimal('vat_percentage', 5, 2)->nullable(); $t->decimal('vat_amount', 15, 2)->nullable(); $t->decimal('total_due', 15, 2)->nullable(); $t->decimal('discount_percentage', 5, 2)->nullable(); $t->decimal('discount_amount', 15, 2)->nullable(); $t->decimal('balance_due', 15, 2)->nullable(); $t->unsignedTinyInteger('financial_snapshot_version')->nullable(); $t->timestamps(); $t->softDeletes();
            $t->foreign('customer_id')->references('id')->on('customers');
        });
        foreach (['discount_percentage' => function ($t) { $t->decimal('discount_percentage', 5, 2)->nullable(); }, 'discount_amount' => function ($t) { $t->decimal('discount_amount', 15, 2)->nullable(); }, 'balance_due' => function ($t) { $t->decimal('balance_due', 15, 2)->nullable(); }] as $column => $definition) {
            if (!Schema::hasColumn('customer_invoices', $column)) Schema::table('customer_invoices', function (Blueprint $t) use ($definition) { $definition($t); });
        }
        if (!Schema::hasTable('customer_payments')) Schema::create('customer_payments', function (Blueprint $t) { $t->bigIncrements('id'); $t->unsignedInteger('customer_id'); $t->unsignedBigInteger('invoice_id'); $t->decimal('total_amount', 10, 2); $t->decimal('amount_paid', 10, 2); $t->decimal('balance_due', 10, 2); $t->unsignedInteger('payment_mode_id')->nullable(); $t->timestamps(); });
        foreach (['customer_id' => function ($t) { $t->unsignedInteger('customer_id')->default(0); }, 'invoice_id' => function ($t) { $t->unsignedBigInteger('invoice_id')->default(0); }, 'total_amount' => function ($t) { $t->decimal('total_amount', 10, 2)->default(0); }, 'amount_paid' => function ($t) { $t->decimal('amount_paid', 10, 2)->default(0); }, 'balance_due' => function ($t) { $t->decimal('balance_due', 10, 2)->default(0); }, 'payment_mode_id' => function ($t) { $t->unsignedInteger('payment_mode_id')->nullable(); }] as $column => $definition) {
            if (!Schema::hasColumn('customer_payments', $column)) Schema::table('customer_payments', function (Blueprint $t) use ($definition) { $definition($t); });
        }
    }
}

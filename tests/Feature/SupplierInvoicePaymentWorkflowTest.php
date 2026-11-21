<?php

namespace Tests\Feature;

use App\Branch;
use App\BankCash;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoicePayment;
use App\Models\Vendor;
use App\RoleManage;
use App\Services\SupplierInvoicePaymentService;
use App\Services\SupplierInvoiceService;
use App\Support\AmountInWordsFormatter;
use App\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SupplierInvoicePaymentWorkflowTest extends TestCase
{
    private const DATABASE = 'ayu_po_stage2_test';
    private const DB_USER = 'ayu_po_stage2_test_user';
    private $user;
    private $vendor;
    private $branch;
    private $paymentMode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame(self::DATABASE, config('database.connections.mysql.database'));
        $this->assertSame(self::DB_USER, config('database.connections.mysql.username'));
        $this->assertSame(self::DATABASE, DB::connection('mysql')->selectOne('SELECT DATABASE() AS database_name')->database_name);
        foreach (['supplier_invoice_payments', 'supplier_invoice_payment_sequences', 'supplier_invoices', 'supplier_invoice_items', 'purchase_orders', 'purchase_order_items', 'bank_cashes'] as $table) {
            $this->assertTrue(Schema::connection('mysql')->hasTable($table), 'Required isolated test table missing: ' . $table);
        }
        $this->assertSame(0, DB::connection('mysql')->table('supplier_invoice_payments')->count(), 'Payment test records must not persist between isolated tests.');
        DB::connection('mysql')->beginTransaction();
        $this->user = User::where('email', 'po-test-user@example.invalid')->firstOrFail();
        $role = RoleManage::findOrFail($this->user->role_manage_id);
        $role->content = json_encode(['SupplierInvoice' => ['Supplier Invoice', 1, 1, 1, 1, 0, 0, 0, 0, 0]]);
        $role->save();
        $this->actingAs($this->user);
        config([
            'role_manage.SupplierInvoice.Show' => 1,
            'role_manage.SupplierInvoice.Create' => 1,
            'role_manage.SupplierPayment.Create' => 1,
            'role_manage.SupplierPayment.Pdf' => 1,
            'settings.wht_enabled' => 0,
        ]);
        $this->vendor = Vendor::where('name', 'TEST VENDOR')->firstOrFail();
        $this->branch = Branch::where('name', 'TEST BRANCH A')->firstOrFail();
        $this->paymentMode = BankCash::query()->first();
        if (!$this->paymentMode) $this->paymentMode = BankCash::create(['name' => 'Stage 5 Test Account ' . Str::random(6)]);
    }

    protected function tearDown(): void
    {
        if (DB::connection('mysql')->transactionLevel() > 0) DB::connection('mysql')->rollBack();
        parent::tearDown();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_full_payment_updates_balances_and_generates_branded_valid_receipt_pdf(): void
    {
        $invoice = $this->approvedInvoice('980.00');
        $beforeRemaining = $invoice->amount_remaining;
        $response = $this->get(route('supplier-invoice-payments.create', $invoice->id));
        $response->assertOk()->assertSee('Make Payment')->assertSee('Payment Amount')->assertSee('Amount Remaining');

        $token = (string) Str::uuid();
        $response = $this->post(route('supplier-invoice-payments.store', $invoice->id), [
            'payment_amount' => '980.00', 'payment_date' => now()->toDateString(),
            'payment_mode_id' => $this->paymentMode->id, 'submission_token' => $token,
            'vendor_id' => $invoice->vendor_id + 999, 'currency_code' => 'USD',
        ]);
        $payment = SupplierInvoicePayment::where('submission_token', $token)->firstOrFail();
        $response->assertRedirect(route('supplier-invoice-payments.receipt', $payment->id));
        $this->assertMatchesRegularExpression('/^SIP-\d{8}$/', $payment->payment_reference);
        $this->assertSame($beforeRemaining, $payment->previous_amount_remaining);
        $this->assertSame('0.00', $payment->new_amount_remaining);
        $this->assertSame('NGN', $payment->currency_code);
        $this->assertSame($invoice->vendor_id, (int) $payment->supplierInvoice->vendor_id);
        $this->assertSame('980.00', $invoice->fresh()->amount_paid);
        $this->assertSame('0.00', $invoice->fresh()->amount_remaining);
        $this->assertSame('paid', $invoice->fresh()->paymentStatus());
        $pdf = $this->get(route('supplier-invoice-payments.receipt', $payment->id))->assertOk();
        $this->assertStringStartsWith('%PDF-', substr($pdf->getContent(), 0, 5));
        $amountInWords = app(AmountInWordsFormatter::class)->format('980.00', 'NGN');
        $this->assertSame('Nine Hundred and Eighty Naira Only.', $amountInWords);
        $invoice = $payment->supplierInvoice;
        $receiptHtml = view('admin.supplier-invoices.payment-receipt-pdf', [
            'payment' => $payment,
            'invoice' => $invoice,
            'vendor' => $invoice->vendor,
            'purchaseOrder' => $invoice->purchaseOrder,
            'amountInWords' => $amountInWords,
            'company_name' => 'Payment Receipt Test Company', 'contact_person' => 'Test Contact',
            'company_logo' => null, 'header_image' => null, 'company_color' => '#176b63',
            'address_1' => 'Test Address', 'address_2' => '', 'city' => 'Test City',
            'state' => '', 'zip_code' => '', 'phone' => '000', 'email' => 'test@example.invalid',
        ])->render();
        foreach (['Payment Receipt Test Company', $this->vendor->name, $invoice->supplier_invoice_number,
            $invoice->internal_invoice_number, $invoice->purchaseOrder->purchase_order_number,
            $payment->payment_reference, $payment->payment_mode_name, '980.00', '0.00', $amountInWords] as $receiptValue) {
            $this->assertStringContainsString((string) $receiptValue, $receiptHtml);
        }
        $this->assertStringContainsString("@include('pages.customers.documents.partials.company-header')", file_get_contents(resource_path('views/admin/supplier-invoices/payment-receipt-pdf.blade.php')));
    }

    public function test_partial_and_multiple_payments_preserve_each_event_and_final_payment_marks_paid(): void
    {
        $invoice = $this->approvedInvoice('980.00');
        $first = app(SupplierInvoicePaymentService::class)->pay($invoice->id, '300.00', now()->toDateString(), $this->paymentMode->id, (string) Str::uuid(), $this->user->id);
        $this->assertSame('980.00', $first->previous_amount_remaining);
        $this->assertSame('680.00', $first->new_amount_remaining);
        $this->assertSame('300.00', $invoice->fresh()->amount_paid);
        $this->assertSame('680.00', $invoice->fresh()->amount_remaining);
        $this->assertSame('part_paid', $invoice->fresh()->paymentStatus());

        $second = app(SupplierInvoicePaymentService::class)->pay($invoice->id, '200.00', now()->toDateString(), $this->paymentMode->id, (string) Str::uuid(), $this->user->id);
        $third = app(SupplierInvoicePaymentService::class)->pay($invoice->id, '480.00', now()->toDateString(), $this->paymentMode->id, (string) Str::uuid(), $this->user->id);
        $this->assertSame('680.00', $second->previous_amount_remaining);
        $this->assertSame('480.00', $second->new_amount_remaining);
        $this->assertSame('480.00', $third->previous_amount_remaining);
        $this->assertSame('0.00', $third->new_amount_remaining);
        $this->assertSame('3', (string) SupplierInvoicePayment::where('supplier_invoice_id', $invoice->id)->count());
        $this->assertSame('980.00', $invoice->fresh()->amount_paid);
        $this->assertSame('0.00', $invoice->fresh()->amount_remaining);
        $this->assertSame('paid', $invoice->fresh()->paymentStatus());
        $this->assertSame('300.00', $first->fresh()->payment_amount, 'An earlier payment remains an individual history record.');
    }

    public function test_zero_negative_and_over_balance_payments_are_rejected_without_writes(): void
    {
        $invoice = $this->approvedInvoice('100.00');
        foreach (['0', '-1.00', '100.01'] as $amount) {
            try {
                app(SupplierInvoicePaymentService::class)->pay($invoice->id, $amount, now()->toDateString(), $this->paymentMode->id, (string) Str::uuid(), $this->user->id);
                $this->fail('Expected payment validation to reject ' . $amount);
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('payment_amount', $exception->errors());
            }
        }
        $this->assertSame(0, SupplierInvoicePayment::where('supplier_invoice_id', $invoice->id)->count());
        $this->assertSame('100.00', $invoice->fresh()->amount_remaining);
    }

    public function test_unapproved_cancelled_and_already_paid_invoices_cannot_receive_payment(): void
    {
        $draft = $this->draftInvoice('100.00');
        $this->assertPaymentRejected($draft);
        $approved = app(SupplierInvoiceService::class)->approve($draft->id, $this->user);
        $cancelled = app(SupplierInvoiceService::class)->cancel($approved->id, $this->user, 'Test cancellation');
        $this->assertPaymentRejected($cancelled);
        $paid = $this->approvedInvoice('100.00');
        app(SupplierInvoicePaymentService::class)->pay($paid->id, '100.00', now()->toDateString(), $this->paymentMode->id, (string) Str::uuid(), $this->user->id);
        $this->assertPaymentRejected($paid->fresh());
    }

    public function test_same_token_is_safe_to_retry_and_different_payload_is_rejected(): void
    {
        $invoice = $this->approvedInvoice('100.00');
        $token = (string) Str::uuid();
        $service = app(SupplierInvoicePaymentService::class);
        $first = $service->pay($invoice->id, '25.00', '2026-10-03', $this->paymentMode->id, $token, $this->user->id);
        $retry = $service->pay($invoice->id, '25.00', '2026-10-03', $this->paymentMode->id, $token, $this->user->id);
        $this->assertSame((int) $first->id, (int) $retry->id);
        $this->assertSame(1, SupplierInvoicePayment::where('submission_token', $token)->count());
        try {
            $service->pay($invoice->id, '26.00', '2026-10-03', $this->paymentMode->id, $token, $this->user->id);
            $this->fail('A token reused with a different amount must be rejected.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        $this->assertSame('75.00', $invoice->fresh()->amount_remaining);
    }

    public function test_supplier_and_currency_are_taken_from_invoice_and_financial_side_effects_are_absent(): void
    {
        config(['settings.wht_enabled' => 1, 'settings.wht_mode' => 'manual', 'settings.wht_manual_rate' => '2.00']);
        $invoice = $this->approvedInvoice('100.00');
        $inventoryCount = DB::table('inventories')->count();
        $inventoryQuantity = DB::table('inventories')->sum('quantity_at_hand');
        $protected = ['vendor_invoices', 'vendor_invoice_items', 'vendor_payments', 'transactions'];
        $counts = [];
        foreach ($protected as $table) if (Schema::hasTable($table)) $counts[$table] = DB::table($table)->count();
        $vat = [$invoice->subtotal, $invoice->wht_amount, $invoice->total_amount, $invoice->wht_enabled_snapshot, $invoice->wht_rate_snapshot];
        $writes = [];
        DB::listen(function (QueryExecuted $query) use (&$writes): void {
            if (preg_match('/^\s*(insert(?:\s+ignore)?\s+into|update|delete\s+from)\s+[`"]?([A-Za-z0-9_]+)/i', $query->sql, $matches)) $writes[] = strtolower($matches[2]);
        });
        $payment = app(SupplierInvoicePaymentService::class)->pay($invoice->id, '40.00', now()->toDateString(), $this->paymentMode->id, (string) Str::uuid(), $this->user->id);
        $this->assertSame($invoice->vendor_id, (int) $payment->supplierInvoice->vendor_id);
        $this->assertSame($invoice->currency_code, $payment->currency_code);
        $this->assertSame($vat, [$invoice->fresh()->subtotal, $invoice->fresh()->wht_amount, $invoice->fresh()->total_amount, $invoice->fresh()->wht_enabled_snapshot, $invoice->fresh()->wht_rate_snapshot]);
        $this->assertSame($inventoryCount, DB::table('inventories')->count());
        $this->assertSame($inventoryQuantity, DB::table('inventories')->sum('quantity_at_hand'));
        foreach ($counts as $table => $count) $this->assertSame($count, DB::table($table)->count(), $table . ' must not be written.');
        foreach (['vendor_invoices', 'vendor_invoice_items', 'vendor_payments', 'transactions', 'bank_cashes', 'inventories'] as $forbidden) $this->assertNotContains($forbidden, $writes);
        $this->assertContains('supplier_invoice_payments', $writes);
        $this->assertContains('supplier_invoices', $writes);
    }

    public function test_payment_transaction_locks_invoice_and_invoice_page_exposes_payment_history_action(): void
    {
        $invoice = $this->approvedInvoice('100.00');
        $locks = [];
        DB::listen(function (QueryExecuted $query) use (&$locks): void {
            if (stripos($query->sql, 'supplier_invoices') !== false && stripos($query->sql, 'for update') !== false) $locks[] = $query->sql;
        });
        app(SupplierInvoicePaymentService::class)->pay($invoice->id, '10.00', now()->toDateString(), $this->paymentMode->id, (string) Str::uuid(), $this->user->id);
        $this->assertNotEmpty($locks, 'The Supplier Invoice row must be locked during payment.');
        $showView = file_get_contents(resource_path('views/admin/supplier-invoices/show.blade.php'));
        $this->assertStringContainsString("__('supplier_payments.payment_history')", $showView);
        $this->assertStringContainsString("route('supplier-invoice-payments.create'", $showView);
        $this->assertStringContainsString('$invoice->payments as $payment', $showView);
        $this->assertStringContainsString("(float) \$invoice->amount_remaining > 0", $showView);
    }

    public function test_payment_reference_and_submission_token_have_database_unique_indexes(): void
    {
        $indexes = DB::connection('mysql')->select('SHOW INDEX FROM supplier_invoice_payments');
        $uniqueColumns = [];
        foreach ($indexes as $index) {
            if ((int) $index->Non_unique === 0) {
                $uniqueColumns[$index->Key_name][] = $index->Column_name;
            }
        }

        $this->assertContains(['payment_reference'], array_values($uniqueColumns));
        $this->assertContains(['submission_token'], array_values($uniqueColumns));
    }

    public function test_payment_translations_cover_six_locales_and_keep_arabic_rtl(): void
    {
        $flatten = function (array $values, string $prefix = '') use (&$flatten): array {
            $flat = [];
            foreach ($values as $key => $value) {
                $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                if (is_array($value)) $flat += $flatten($value, $path);
                else $flat[$path] = $value;
            }
            return $flat;
        };
        $source = $flatten(require resource_path('lang/main/supplier_payments.php'));
        foreach (['fr', 'ar', 'pt', 'es', 'zh'] as $locale) {
            $translated = $flatten(require resource_path("lang/{$locale}/supplier_payments.php"));
            $this->assertSame(array_keys($source), array_keys($translated), $locale . ' Supplier Payment translation keys');
            foreach ($translated as $key => $value) $this->assertNotEmpty($value, $locale . '.' . $key);
        }
        $this->assertSame('rtl', \App\Support\LocaleManager::direction('ar'));
    }

    private function assertPaymentRejected(SupplierInvoice $invoice): void
    {
        try {
            app(SupplierInvoicePaymentService::class)->pay($invoice->id, '1.00', now()->toDateString(), $this->paymentMode->id, (string) Str::uuid(), $this->user->id);
            $this->fail('Expected this invoice state to reject payment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('invoice', $exception->errors());
        }
    }

    private function approvedInvoice(string $amount): SupplierInvoice
    {
        return app(SupplierInvoiceService::class)->approve($this->draftInvoice($amount)->id, $this->user);
    }

    private function draftInvoice(string $amount): SupplierInvoice
    {
        $order = PurchaseOrder::create([
            'purchase_order_number' => 'PO-PAY-' . strtoupper(Str::random(10)), 'submission_token' => (string) Str::uuid(),
            'vendor_id' => $this->vendor->id, 'vendor_name_snapshot' => $this->vendor->name,
            'branch_id' => $this->branch->id, 'branch_name_snapshot' => $this->branch->name,
            'currency_code' => 'NGN', 'currency_symbol' => '₦', 'currency_position' => 'Prefix',
            'order_date' => now()->toDateString(), 'status' => PurchaseOrder::STATUS_APPROVED,
            'subtotal' => $amount, 'order_total' => $amount, 'approved_by' => $this->user->id,
            'approved_at' => now(), 'created_by' => $this->user->id, 'updated_by' => $this->user->id,
        ]);
        $line = PurchaseOrderItem::create([
            'purchase_order_id' => $order->id, 'inventory_item_id' => null,
            'inventory_classification' => 'service', 'inventory_item_name_snapshot' => 'Payment test service',
            'description' => 'Payment test service', 'unit' => 'Service', 'quantity' => '1.000',
            'agreed_unit_price' => $amount, 'line_total' => $amount, 'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
        $order->load('items');
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, [
            'purchase_order_id' => $order->id, 'supplier_invoice_number' => 'PAY-' . strtoupper(Str::random(8)),
            'invoice_date' => now()->toDateString(), 'due_date' => null, 'submission_token' => (string) Str::uuid(),
            'items' => [$line->id => ['quantity' => '1', 'supplier_unit_price' => $amount]],
        ]);
        return $invoice;
    }
}

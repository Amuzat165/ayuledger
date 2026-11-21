<?php

namespace Tests\Feature;

use App\BankCash;
use App\Branch;
use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoicePayment;
use App\Models\Vendor;
use App\Support\PurchaseOrderCalculator;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SupplierPaymentHistoryTest extends TestCase
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

        $compiledViews = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ayu-supplier-history-views-' . getmypid();
        if (!is_dir($compiledViews)) {
            mkdir($compiledViews, 0777, true);
        }
        config(['view.compiled' => $compiledViews]);
        app()->setLocale('main');

        $this->assertSame('mysql', config('database.default'));
        $this->assertSame(self::DATABASE, config('database.connections.mysql.database'));
        $this->assertSame(self::DB_USER, config('database.connections.mysql.username'));
        $this->assertSame(self::DATABASE, DB::connection('mysql')->selectOne('SELECT DATABASE() AS database_name')->database_name);
        foreach (['supplier_invoice_payments', 'supplier_invoices', 'purchase_orders', 'vendors', 'branches', 'users'] as $table) {
            $this->assertTrue(Schema::connection('mysql')->hasTable($table), 'Required isolated test table missing: ' . $table);
        }
        $this->assertSame(0, DB::connection('mysql')->table('supplier_invoice_payments')->count(), 'History test rows must not persist between tests.');

        DB::connection('mysql')->beginTransaction();
        $this->user = User::where('email', 'po-test-user@example.invalid')->firstOrFail();
        $this->vendor = Vendor::where('name', 'TEST VENDOR')->firstOrFail();
        $this->branch = Branch::where('name', 'TEST BRANCH A')->firstOrFail();
        $this->paymentMode = BankCash::query()->first();
        if (!$this->paymentMode) {
            $this->paymentMode = BankCash::create(['name' => 'History Test Account ' . Str::random(6)]);
        }

        $this->actingAs($this->user);
        config([
            'role_manage.SupplierPayment.Show' => 1,
            'role_manage.SupplierPayment.Pdf' => 1,
            'role_manage.SupplierInvoice.Show' => 1,
            'settings.date_format' => 'd/m/Y',
        ]);
    }

    protected function tearDown(): void
    {
        if (DB::connection('mysql')->transactionLevel() > 0) {
            DB::connection('mysql')->rollBack();
        }
        parent::tearDown();
    }

    public function test_authenticated_history_requires_show_permission_and_route_exists(): void
    {
        $this->assertTrue(Route::has('supplier-invoice-payments.index'));
        $this->assertTrue(Route::has('supplier-invoice-payments.receipt'));
        $this->assertTrue(Route::has('supplier-invoices.show'));

        config(['role_manage.SupplierPayment.Show' => 0]);
        $this->get(route('supplier-invoice-payments.index'))->assertForbidden();

        config(['role_manage.SupplierPayment.Show' => 1]);
        auth()->logout();
        $this->get(route('supplier-invoice-payments.index'))->assertRedirect(route('login'));
    }

    public function test_history_displays_payment_snapshots_and_permission_controlled_actions_without_writes(): void
    {
        $invoice = $this->invoice('HISTORY-' . strtoupper(Str::random(6)), '100.00', '30.00', '70.00');
        $payment = $this->payment($invoice, 'HISTORY-REF-' . strtoupper(Str::random(6)), '30.00', '100.00', '70.00', '2026-10-04');
        $payment->currency_code = 'USD';
        $payment->currency_symbol = '$';
        $payment->currency_position = 'Suffix';
        $payment->save();

        $invoiceBefore = [$invoice->fresh()->amount_paid, $invoice->fresh()->amount_remaining];
        $paymentCountBefore = DB::table('supplier_invoice_payments')->count();
        $inventoryBefore = Schema::hasTable('inventories') ? [DB::table('inventories')->count(), DB::table('inventories')->sum('quantity_at_hand')] : null;
        $cashCountBefore = Schema::hasTable('bank_cashes') ? DB::table('bank_cashes')->count() : null;
        $legacyCountBefore = Schema::hasTable('vendor_payments') ? DB::table('vendor_payments')->count() : null;
        $transactionsBefore = Schema::hasTable('transactions') ? DB::table('transactions')->count() : null;

        $connection = DB::connection('mysql');
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        $response = $this->get(route('supplier-invoice-payments.index'));
        $queries = $connection->getQueryLog();
        $connection->disableQueryLog();

        $response->assertOk()
            ->assertSee($payment->payment_reference)
            ->assertSee($this->vendor->name)
            ->assertSee($invoice->supplier_invoice_number)
            ->assertSee($invoice->internal_invoice_number)
            ->assertSee($invoice->purchaseOrder->purchase_order_number)
            ->assertSee($this->paymentMode->name)
            ->assertSee($this->user->name)
            ->assertSee(PurchaseOrderCalculator::formatCurrency('30.00', '$', 'Suffix'))
            ->assertSee(route('supplier-invoice-payments.receipt', $payment->id), false)
            ->assertSee('target="_blank" rel="noopener noreferrer"', false)
            ->assertSee(route('supplier-invoices.show', $invoice->id), false);

        $sql = implode("\n", array_column($queries, 'query'));
        $this->assertStringContainsString('supplier_invoice_payments', strtolower($sql));
        $this->assertStringNotContainsString('vendor_payments', strtolower($sql), 'History must not query legacy Vendor Payments.');

        config(['role_manage.SupplierPayment.Pdf' => 0, 'role_manage.SupplierInvoice.Show' => 0]);
        $restrictedActions = $this->get(route('supplier-invoice-payments.index'))->assertOk();
        $restrictedActions->assertDontSee(route('supplier-invoice-payments.receipt', $payment->id), false);
        $restrictedActions->assertDontSee(route('supplier-invoices.show', $invoice->id), false);

        $this->assertSame($invoiceBefore, [$invoice->fresh()->amount_paid, $invoice->fresh()->amount_remaining]);
        $this->assertSame($paymentCountBefore, DB::table('supplier_invoice_payments')->count());
        if ($inventoryBefore !== null) {
            $this->assertSame($inventoryBefore, [DB::table('inventories')->count(), DB::table('inventories')->sum('quantity_at_hand')]);
        }
        if ($cashCountBefore !== null) $this->assertSame($cashCountBefore, DB::table('bank_cashes')->count());
        if ($legacyCountBefore !== null) $this->assertSame($legacyCountBefore, DB::table('vendor_payments')->count());
        if ($transactionsBefore !== null) $this->assertSame($transactionsBefore, DB::table('transactions')->count());
    }

    public function test_filters_pagination_and_newest_first_ordering_work(): void
    {
        $invoice = $this->invoice('FILTER-' . strtoupper(Str::random(6)), '27.00', '27.00', '0.00');
        $firstReference = null;
        for ($index = 1; $index <= 27; $index++) {
            $reference = 'SH-' . str_pad((string) $index, 3, '0', STR_PAD_LEFT);
            if ($index === 1) $firstReference = $reference;
            $date = now()->startOfMonth()->addDays($index - 1)->toDateString();
            $this->payment($invoice, $reference, '1.00', (string) (28 - $index) . '.00', (string) (27 - $index) . '.00', $date);
        }

        $firstPage = $this->get(route('supplier-invoice-payments.index'));
        $firstPage->assertOk()->assertSee('SH-027')->assertDontSee('SH-001');
        $secondPage = $this->get(route('supplier-invoice-payments.index', ['page' => 2]));
        $secondPage->assertOk()->assertSee('SH-001');

        $filtered = $this->get(route('supplier-invoice-payments.index', [
            'supplier' => $this->vendor->name,
            'supplier_invoice_number' => $invoice->supplier_invoice_number,
            'system_invoice_number' => $invoice->internal_invoice_number,
            'payment_reference' => 'SH-014',
            'date_from' => now()->startOfMonth()->addDays(13)->toDateString(),
            'date_to' => now()->startOfMonth()->addDays(13)->toDateString(),
            'payment_method' => $this->paymentMode->name,
            'branch' => $this->branch->name,
        ]));
        $filtered->assertOk()->assertSee('SH-014')->assertDontSee('SH-013')->assertDontSee('SH-015');
    }

    public function test_empty_history_state_is_shown_when_no_supplier_payments_exist(): void
    {
        $this->get(route('supplier-invoice-payments.index'))
            ->assertOk()
            ->assertSee(__('supplier_payments.no_history'));
    }

    private function invoice(string $suffix, string $total, string $paid, string $remaining): SupplierInvoice
    {
        $order = PurchaseOrder::create([
            'purchase_order_number' => 'PO-HIST-' . strtoupper(Str::random(8)),
            'submission_token' => (string) Str::uuid(),
            'vendor_id' => $this->vendor->id,
            'vendor_name_snapshot' => $this->vendor->name,
            'branch_id' => $this->branch->id,
            'branch_name_snapshot' => $this->branch->name,
            'currency_code' => 'NGN',
            'currency_symbol' => '₦',
            'currency_position' => 'Prefix',
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_APPROVED,
            'subtotal' => $total,
            'order_total' => $total,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        return SupplierInvoice::create([
            'internal_invoice_number' => 'SI-HIST-' . strtoupper(Str::random(8)),
            'supplier_invoice_number' => 'SUP-' . $suffix,
            'vendor_id' => $this->vendor->id,
            'purchase_order_id' => $order->id,
            'branch_id' => $this->branch->id,
            'vendor_name_snapshot' => $this->vendor->name,
            'branch_name_snapshot' => $this->branch->name,
            'currency_code' => 'NGN',
            'currency_symbol' => '₦',
            'currency_position' => 'Prefix',
            'invoice_date' => now()->toDateString(),
            'status' => SupplierInvoice::STATUS_APPROVED,
            'subtotal' => $total,
            'wht_amount' => '0.00',
            'total_amount' => $total,
            'amount_paid' => $paid,
            'amount_remaining' => $remaining,
            'submission_token' => (string) Str::uuid(),
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);
    }

    private function payment(SupplierInvoice $invoice, string $reference, string $amount, string $previous, string $next, string $date): SupplierInvoicePayment
    {
        return SupplierInvoicePayment::create([
            'supplier_invoice_id' => $invoice->id,
            'payment_reference' => $reference,
            'payment_date' => $date,
            'payment_amount' => $amount,
            'payment_mode_id' => $this->paymentMode->id,
            'payment_mode_name' => $this->paymentMode->name,
            'currency_code' => $invoice->currency_code,
            'currency_symbol' => $invoice->currency_symbol,
            'currency_position' => $invoice->currency_position,
            'previous_amount_remaining' => $previous,
            'new_amount_remaining' => $next,
            'submission_token' => (string) Str::uuid(),
            'payload_fingerprint' => hash('sha256', $reference),
            'created_by' => $this->user->id,
        ]);
    }
}
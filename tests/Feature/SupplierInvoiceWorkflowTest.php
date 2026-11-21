<?php

namespace Tests\Feature;

use App\Branch;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceivedItem;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use App\Models\SupplierInvoiceReceiptAllocation;
use App\Models\Vendor;
use App\RoleManage;
use App\Services\SupplierInvoiceService;
use App\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SupplierInvoiceWorkflowTest extends TestCase
{
    private const DATABASE = 'ayu_po_stage2_test';
    private const DB_USER = 'ayu_po_stage2_test_user';
    private $user;
    private $vendor;
    private $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $compiledViews = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ayu-po-stage4-views-' . getmypid();
        if (!is_dir($compiledViews)) {
            mkdir($compiledViews, 0777, true);
        }
        config(['view.compiled' => $compiledViews]);
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame(self::DATABASE, config('database.connections.mysql.database'));
        $this->assertSame(self::DB_USER, config('database.connections.mysql.username'));
        $this->assertSame(self::DATABASE, DB::connection('mysql')->selectOne('SELECT DATABASE() AS database_name')->database_name);
        foreach (['purchase_orders', 'purchase_order_items', 'goods_receipts', 'goods_received_items', 'supplier_invoices', 'supplier_invoice_items', 'supplier_invoice_receipt_allocations'] as $table) {
            $this->assertTrue(Schema::connection('mysql')->hasTable($table), 'Required table is missing: ' . $table);
        }
        DB::connection('mysql')->beginTransaction();
        $this->user = User::where('email', 'po-test-user@example.invalid')->firstOrFail();
        $role = RoleManage::findOrFail($this->user->role_manage_id);
        $role->content = json_encode([
            'PurchaseOrder' => ['Purchase Order', 1, 1, 1, 1, 0, 0, 0, 0, 0],
            'SupplierInvoice' => ['Supplier Invoice', 1, 1, 1, 1, 1, 0, 0, 0, 0],
        ]);
        $role->save();
        $this->actingAs($this->user);
        config([
            'role_manage.SupplierInvoice.All' => 1,
            'role_manage.SupplierInvoice.Show' => 1,
            'role_manage.SupplierInvoice.Create' => 1,
            'role_manage.SupplierInvoice.Edit' => 1,
            'role_manage.SupplierInvoice.Delete' => 1,
            'settings.wht_enabled' => 0,
            'settings.wht_mode' => 'manual',
            'settings.wht_manual_rate' => '0.00',
        ]);
        $this->vendor = Vendor::where('name', 'TEST VENDOR')->firstOrFail();
        $this->branch = Branch::where('name', 'TEST BRANCH A')->firstOrFail();
    }

    protected function tearDown(): void
    {
        if (DB::connection('mysql')->transactionLevel() > 0) {
            DB::connection('mysql')->rollBack();
        }
        parent::tearDown();
    }

    public function test_draft_invoice_persists_order_supplier_branch_price_and_system_number(): void
    {
        $inventory = $this->inventory(1);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '5.000', 'price' => '100.00']]);
        $this->receive($order, $order->items->first(), '5.000');
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '2', 'supplier_unit_price' => '100.00']]));

        $this->assertSame(SupplierInvoice::STATUS_DRAFT, $invoice->status);
        $this->assertSame($this->vendor->id, (int) $invoice->vendor_id);
        $this->assertSame($order->id, (int) $invoice->purchase_order_id);
        $this->assertSame($this->branch->id, (int) $invoice->branch_id);
        $this->assertSame('NGN', $invoice->currency_code);
        $this->assertSame('SI-', substr($invoice->internal_invoice_number, 0, 3));
        $this->assertSame('200.00', $invoice->subtotal);
        $this->assertSame('200.00', $invoice->items->first()->line_total);
        $this->assertSame('100.00', $invoice->items->first()->purchase_order_unit_price);
        $this->assertSame('100.00', $invoice->items->first()->supplier_unit_price);
        $this->assertNull($invoice->amount_remaining, 'A draft is not yet a payable.');
    }

    public function test_server_calculates_line_and_subtotal_ignoring_submitted_totals(): void
    {
        $inventory = $this->inventory(0);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '4.000', 'price' => '10.00'], ['inventory' => $inventory, 'quantity' => '3.000', 'price' => '20.00']]);
        $payload = $this->payload($order, [
            $order->items[0]->id => ['quantity' => '2', 'supplier_unit_price' => '11.25'],
            $order->items[1]->id => ['quantity' => '1.5', 'supplier_unit_price' => '20.00'],
        ]);
        $payload['subtotal'] = '0.01';
        $payload['total_amount'] = '0.01';
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $payload);
        $this->assertSame('52.50', $invoice->subtotal);
        $this->assertSame('22.50', $invoice->items[0]->line_total);
        $this->assertSame('30.00', $invoice->items[1]->line_total);
    }

    public function test_supplier_invoice_number_is_required_by_the_form_endpoint(): void
    {
        $order = $this->order([['quantity' => '1.000', 'price' => '10.00']]);
        $payload = $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]);
        unset($payload['supplier_invoice_number']);
        $this->post(route('supplier-invoices.store'), $payload)->assertSessionHasErrors('supplier_invoice_number');
    }

    public function test_supplier_document_number_is_unique_per_supplier(): void
    {
        $order = $this->order([['quantity' => '2.000', 'price' => '10.00']]);
        $data = $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]);
        $first = app(SupplierInvoiceService::class)->saveDraft($this->user, $data);
        $secondData = $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]);
        $secondData['supplier_invoice_number'] = $first->supplier_invoice_number;
        $this->expectValidationError(function () use ($secondData) { app(SupplierInvoiceService::class)->saveDraft($this->user, $secondData); }, 'supplier_invoice_number');
        $otherVendor = $this->vendor->replicate();
        $otherVendor->name = 'SECOND TEST VENDOR ' . Str::random(6);
        $otherVendor->reg_number = 'SI-' . Str::random(10);
        $otherVendor->branch_id = $this->branch->id;
        $otherVendor->save();
        $otherOrder = $this->order([['quantity' => '1.000', 'price' => '10.00']], $otherVendor);
        $otherData = $this->payload($otherOrder, [$otherOrder->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]);
        $otherData['supplier_invoice_number'] = $first->supplier_invoice_number;
        $this->assertSame($otherVendor->id, (int) app(SupplierInvoiceService::class)->saveDraft($this->user, $otherData)->vendor_id);
    }

    public function test_invoice_inherits_supplier_branch_and_currency_and_rejects_manipulated_values(): void
    {
        $order = $this->order([['quantity' => '1.000', 'price' => '10.00']]);
        $data = $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]);
        $data['vendor_id'] = $order->vendor_id + 1000;
        $this->expectValidationError(function () use ($data) { app(SupplierInvoiceService::class)->saveDraft($this->user, $data); }, 'vendor_id');
        $data['vendor_id'] = $order->vendor_id;
        $data['branch_id'] = $order->branch_id + 1000;
        $this->expectValidationError(function () use ($data) { app(SupplierInvoiceService::class)->saveDraft($this->user, $data); }, 'branch_id');
        $data['branch_id'] = $order->branch_id;
        $data['currency_code'] = 'USD';
        $this->expectValidationError(function () use ($data) { app(SupplierInvoiceService::class)->saveDraft($this->user, $data); }, 'currency_code');
        $this->assertSame(0, SupplierInvoice::count());
    }

    public function test_stock_item_requires_confirmed_goods_received(): void
    {
        $inventory = $this->inventory(1);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '5.000']]);
        $this->expectValidationError(function () use ($order) {
            app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]));
        }, 'items.' . $order->items->first()->id . '.quantity');
        $this->assertSame(0, SupplierInvoice::count());
    }

    public function test_unclassified_inventory_cannot_be_invoiced_as_stock_or_service(): void
    {
        $inventory = $this->inventory(null, 12);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '2.000']]);
        $line = $order->items->first();
        $data = $this->payload($order, [$line->id => [
            'quantity' => '1', 'supplier_unit_price' => '10.00',
            // These forged values must not override the persisted PO classification.
            'inventory_classification' => 'service', 'track_stock' => '0',
        ]]);

        $this->expectValidationError(function () use ($data) {
            app(SupplierInvoiceService::class)->saveDraft($this->user, $data);
        }, 'items.' . $line->id . '.quantity');

        $this->assertSame('unclassified', $line->fresh()->inventory_classification);
        $this->assertSame(12, (int) $inventory->fresh()->quantity_at_hand);
        $this->assertSame(0, SupplierInvoice::count());
    }

    public function test_deleted_inventory_relationship_does_not_turn_stock_po_line_into_service(): void
    {
        $inventory = $this->inventory(1, 9);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '1.000']]);
        $line = $order->items->first();

        DB::table('inventories')->where('id', $inventory->id)->update(['track_stock' => 0]);
        DB::table('inventories')->where('id', $inventory->id)->delete();
        $line->refresh();
        $this->assertNull($line->inventory_item_id);
        $this->assertSame('stock', $line->inventory_classification);

        $data = $this->payload($order, [$line->id => [
            'quantity' => '1', 'supplier_unit_price' => '10.00',
            'inventory_classification' => 'service',
        ]]);
        $this->expectValidationError(function () use ($data) {
            app(SupplierInvoiceService::class)->saveDraft($this->user, $data);
        }, 'items.' . $line->id . '.quantity');
        $this->assertSame(0, SupplierInvoice::count());
    }

    public function test_draft_goods_received_does_not_count_as_billable(): void
    {
        $inventory = $this->inventory(1);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '5.000']]);
        $this->receive($order, $order->items->first(), '5.000', GoodsReceipt::STATUS_DRAFT);
        $this->expectValidationError(function () use ($order) {
            app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]));
        }, 'items.' . $order->items->first()->id . '.quantity');
    }

    public function test_invoice_quantity_cannot_exceed_confirmed_received_quantity(): void
    {
        $inventory = $this->inventory(1);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '10.000']]);
        $this->receive($order, $order->items->first(), '6.000');
        $this->expectValidationError(function () use ($order) {
            app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '7', 'supplier_unit_price' => '10.00']]));
        }, 'items.' . $order->items->first()->id . '.quantity');
    }

    public function test_partial_invoices_allocate_distinct_confirmed_receipt_quantities(): void
    {
        $inventory = $this->inventory(1);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '10.000']]);
        $this->receive($order, $order->items->first(), '6.000');
        $this->receive($order, $order->items->first(), '4.000');
        $lineId = $order->items->first()->id;
        $first = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$lineId => ['quantity' => '6', 'supplier_unit_price' => '10.00']]));
        $second = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$lineId => ['quantity' => '4', 'supplier_unit_price' => '10.00']]));
        $this->assertSame('10.000', number_format((float) $first->items->sum('quantity') + (float) $second->items->sum('quantity'), 3, '.', ''));
        $this->assertSame(2, SupplierInvoiceReceiptAllocation::whereIn('supplier_invoice_item_id', [$first->items->first()->id, $second->items->first()->id])->count());
        $this->expectValidationError(function () use ($order, $lineId) {
            app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$lineId => ['quantity' => '1', 'supplier_unit_price' => '10.00']]));
        }, 'items.' . $lineId . '.quantity');
    }

    public function test_duplicate_billing_of_the_same_confirmed_receipt_quantity_is_blocked(): void
    {
        $inventory = $this->inventory(1);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '2.000']]);
        $this->receive($order, $order->items->first(), '2.000');
        $lineId = $order->items->first()->id;
        app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$lineId => ['quantity' => '2', 'supplier_unit_price' => '10.00']]));
        $this->expectValidationError(function () use ($order, $lineId) {
            app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$lineId => ['quantity' => '0.001', 'supplier_unit_price' => '10.00']]));
        }, 'items.' . $lineId . '.quantity');
    }

    public function test_price_variance_creates_review_required_and_retains_both_prices(): void
    {
        $inventory = $this->inventory(0);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '3.000', 'price' => '10.00']]);
        $line = $order->items->first();
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$line->id => ['quantity' => '2', 'supplier_unit_price' => '10.01']]));
        $this->assertSame(SupplierInvoice::STATUS_REVIEW_REQUIRED, $invoice->status);
        $this->assertSame('10.00', $invoice->items->first()->purchase_order_unit_price);
        $this->assertSame('10.01', $invoice->items->first()->supplier_unit_price);
        $this->assertStringContainsString('differs', $invoice->review_reason);
    }

    public function test_nonstock_service_can_be_invoiced_directly_without_goods_received(): void
    {
        $inventory = $this->inventory(0);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '3.500', 'price' => '20.00']]);
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '3.5', 'supplier_unit_price' => '20.00']]));
        $this->assertSame(SupplierInvoice::STATUS_DRAFT, $invoice->status);
        $this->assertSame(0, SupplierInvoiceReceiptAllocation::count());
        $this->assertSame('3.500', $invoice->items->first()->quantity);
    }

    public function test_service_quantity_cannot_exceed_ordered_quantity_across_invoices(): void
    {
        $order = $this->order([['quantity' => '5.000', 'price' => '10.00']]);
        $lineId = $order->items->first()->id;
        app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$lineId => ['quantity' => '4', 'supplier_unit_price' => '10.00']]));
        $this->expectValidationError(function () use ($order, $lineId) {
            app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$lineId => ['quantity' => '2', 'supplier_unit_price' => '10.00']]));
        }, 'items.' . $lineId . '.quantity');
    }

    public function test_wht_uses_existing_configuration_and_approved_invoice_creates_payable_balance(): void
    {
        config(['settings.wht_enabled' => 1, 'settings.wht_mode' => 'manual', 'settings.wht_manual_rate' => '5.00']);
        $order = $this->order([['quantity' => '2.000', 'price' => '100.00']]);
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '2', 'supplier_unit_price' => '100.00']]));
        $this->assertSame('10.00', $invoice->wht_amount);
        $this->assertSame('190.00', $invoice->total_amount);
        $invoice = app(SupplierInvoiceService::class)->approve($invoice->id, $this->user);
        $this->assertSame(SupplierInvoice::STATUS_APPROVED, $invoice->status);
        $this->assertSame('190.00', $invoice->amount_remaining);
        $this->assertSame('0.00', $invoice->amount_paid);
        if (Schema::connection('mysql')->hasTable('vendor_payments')) {
            $this->assertSame(0, DB::table('vendor_payments')->where('invoice_id', $invoice->id)->count());
        }
    }

    public function test_supplier_invoice_pdf_is_authorized_and_renders_stored_values_without_modifying_financial_records(): void
    {
        config([
            'role_manage.SupplierInvoice.Pdf' => 1,
            'settings.wht_enabled' => 1,
            'settings.wht_mode' => 'manual',
            'settings.wht_manual_rate' => '5.00',
        ]);

        $inventory = $this->inventory(1);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '2.000', 'price' => '100.00']]);
        $line = $order->items->first();
        $this->receive($order, $line, '2.000');
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [
            $line->id => ['quantity' => '2', 'supplier_unit_price' => '100.00'],
        ]));
        $invoice = app(SupplierInvoiceService::class)->approve($invoice->id, $this->user);

        $this->assertSame('200.00', $invoice->subtotal);
        $this->assertSame('10.00', $invoice->wht_amount);
        $this->assertSame('190.00', $invoice->total_amount);
        $this->assertSame('0.00', $invoice->amount_paid);
        $this->assertSame('190.00', $invoice->amount_remaining);

        $before = [
            'invoice_count' => SupplierInvoice::count(),
            'item_count' => SupplierInvoiceItem::count(),
            'invoice_values' => $invoice->only(['subtotal', 'wht_amount', 'total_amount', 'amount_paid', 'amount_remaining', 'status']),
            'inventory_quantity' => Inventory::findOrFail($inventory->id)->quantity_at_hand,
        ];

        $listView = file_get_contents(resource_path('views/admin/supplier-invoices/index.blade.php'));
        $viewActionPosition = strpos($listView, "__('supplier_invoices.view')");
        $pdfActionPosition = strpos($listView, "route('supplier-invoices.pdf'");
        $this->assertNotFalse($viewActionPosition);
        $this->assertNotFalse($pdfActionPosition);
        $this->assertGreaterThan($viewActionPosition, $pdfActionPosition);
        $this->assertStringContainsString('target="_blank" rel="noopener noreferrer"', $listView);



        $role = RoleManage::findOrFail($this->user->role_manage_id);
        $permissionData = json_decode($role->content, true);
        $permissionData['SupplierInvoice'][6] = 1;
        $role->content = json_encode($permissionData);
        $role->save();

        $pdf = $this->get(route('supplier-invoices.pdf', $invoice->id))->assertOk();
        $pdf->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', substr($pdf->getContent(), 0, 5));

        $pdfHtml = view('admin.supplier-invoices.pdf', [
            'invoice' => $invoice->fresh(['items', 'purchaseOrder', 'vendor', 'branch']),
            'vendor' => $invoice->vendor,
            'purchaseOrder' => $order,
            'amountInWords' => app(\App\Support\AmountInWordsFormatter::class)->format($invoice->total_amount, $invoice->currency_code),
            'company_name' => 'Supplier Invoice PDF Test Company',
            'contact_person' => 'Test Contact',
            'company_logo' => null,
            'header_image' => null,
            'company_color' => '#176b63',
            'address_1' => 'Test Address',
            'address_2' => '',
            'city' => 'Test City',
            'state' => '',
            'zip_code' => '',
            'phone' => '000',
            'email' => 'test@example.invalid',
        ])->render();

        foreach ([
            'Supplier Invoice PDF Test Company',
            $invoice->internal_invoice_number,
            $invoice->supplier_invoice_number,
            $invoice->vendor_name_snapshot,
            $order->purchase_order_number,
            $invoice->branch_name_snapshot,
            'NGN',
            '200.00',
            '10.00',
            '190.00',
            '0.00',
            'One Hundred and Ninety Naira Only.',
        ] as $expectedValue) {
            $this->assertStringContainsString((string) $expectedValue, $pdfHtml);
        }

        $permissionData['SupplierInvoice'][6] = 0;
        $role->content = json_encode($permissionData);
        $role->save();
        config(['role_manage.SupplierInvoice.Pdf' => 0]);
        $this->get(route('supplier-invoices.pdf', $invoice->id))->assertForbidden();

        $invoice->refresh();
        $this->assertSame($before['invoice_count'], SupplierInvoice::count());
        $this->assertSame($before['item_count'], SupplierInvoiceItem::count());
        $this->assertSame($before['invoice_values'], $invoice->only(['subtotal', 'wht_amount', 'total_amount', 'amount_paid', 'amount_remaining', 'status']));
        $this->assertSame($before['inventory_quantity'], Inventory::findOrFail($inventory->id)->quantity_at_hand);
    }
    public function test_fractional_quantity_manual_wht_calculates_and_approves_correctly(): void
    {
        config(['settings.wht_enabled' => 1, 'settings.wht_mode' => 'manual', 'settings.wht_manual_rate' => '5.00']);
        $order = $this->order([['quantity' => '1.500', 'price' => '100.00']]);
        $line = $order->items->first();
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [
            $line->id => ['quantity' => '1.250', 'supplier_unit_price' => '100.00'],
        ]));

        $this->assertSame('1.250', $invoice->items->first()->quantity);
        $this->assertSame('125.00', $invoice->items->first()->line_total);
        $this->assertSame('125.00', $invoice->subtotal);
        $this->assertSame('6.25', $invoice->wht_amount);
        $this->assertSame('118.75', $invoice->total_amount);

        $approved = app(SupplierInvoiceService::class)->approve($invoice->id, $this->user);
        $this->assertSame(SupplierInvoice::STATUS_APPROVED, $approved->status);
        $this->assertSame('118.75', $approved->amount_remaining);
    }

    public function test_unavailable_rule_based_wht_keeps_invoice_in_review_and_blocks_payable_approval(): void
    {
        config(['settings.wht_enabled' => 1, 'settings.wht_mode' => 'rule_based', 'settings.wht_manual_rate' => '0.00']);
        $order = $this->order([['quantity' => '1.000', 'price' => '100.00']]);
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '100.00']]));
        $this->assertSame(SupplierInvoice::STATUS_REVIEW_REQUIRED, $invoice->status);
        $this->assertNull($invoice->wht_amount);
        $this->assertStringContainsString('WHT', $invoice->review_reason);
        $approved = app(SupplierInvoiceService::class)->approve($invoice->id, $this->user);
        $this->assertSame(SupplierInvoice::STATUS_REVIEW_REQUIRED, $approved->status);
        $this->assertNull($approved->amount_remaining);
    }

    public function test_supplier_invoice_has_no_vat_fields_or_calculation(): void
    {
        $this->assertFalse(Schema::connection('mysql')->hasColumn('supplier_invoices', 'vat_amount'));
        $this->assertFalse(Schema::connection('mysql')->hasColumn('supplier_invoices', 'vat_rate'));
        $order = $this->order([['quantity' => '2.000', 'price' => '50.00']]);
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '2', 'supplier_unit_price' => '50.00']]));
        $this->assertSame('100.00', $invoice->subtotal);
        $this->assertSame('100.00', $invoice->total_amount);
    }

    public function test_wht_is_not_calculated_or_recorded_when_creating_a_purchase_order(): void
    {
        config(['settings.wht_enabled' => 1, 'settings.wht_mode' => 'manual', 'settings.wht_manual_rate' => '5.00']);
        $sequenceBefore = (int) DB::table('invoice_sequences')->where('document_type', 'supplier_invoice')->value('current_number');
        $order = $this->order([['quantity' => '2.000', 'price' => '100.00']]);

        $this->assertSame(0, SupplierInvoice::where('purchase_order_id', $order->id)->count());
        $this->assertFalse(Schema::connection('mysql')->hasColumn('purchase_orders', 'wht_amount'));
        $this->assertSame($sequenceBefore, (int) DB::table('invoice_sequences')->where('document_type', 'supplier_invoice')->value('current_number'));
    }

    public function test_supplier_invoice_creation_does_not_change_inventory_or_create_payment_accounting_or_legacy_records(): void
    {
        $inventory = $this->inventory(1, 27);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '2.000']]);
        $this->receive($order, $order->items->first(), '2.000');
        $beforeInventory = (int) $inventory->quantity_at_hand;
        $protectedTables = ['vendor_invoices', 'vendor_invoice_items', 'vendor_payments', 'transactions', 'bank_cashes'];
        $beforeCounts = [];
        foreach ($protectedTables as $table) {
            if (Schema::connection('mysql')->hasTable($table)) { $beforeCounts[$table] = DB::table($table)->count(); }
        }
        $writes = [];
        DB::listen(function (QueryExecuted $query) use (&$writes): void {
            if (preg_match('/^\s*(insert(?:\s+ignore)?\s+into|update|delete\s+from)\s+[`"]?([A-Za-z0-9_]+)/i', $query->sql, $m)) { $writes[] = strtolower($m[2]); }
        });
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '2', 'supplier_unit_price' => '10.00']]));
        app(SupplierInvoiceService::class)->approve($invoice->id, $this->user);
        $this->assertSame($beforeInventory, (int) $inventory->fresh()->quantity_at_hand);
        foreach ($beforeCounts as $table => $count) { $this->assertSame($count, DB::table($table)->count(), $table . ' must remain unchanged.'); }
        foreach (['supplier_invoices', 'supplier_invoice_items', 'supplier_invoice_receipt_allocations', 'invoice_sequences'] as $allowed) { if (in_array($allowed, $writes, true)) { $this->assertContains($allowed, $writes); } }
        foreach ($protectedTables as $forbidden) { $this->assertNotContains($forbidden, $writes); }
        $this->assertSame(0, (int) $invoice->fresh()->amount_paid);
    }

    public function test_approval_rejects_currency_mismatch_and_receipt_branch_mismatch(): void
    {
        $inventory = $this->inventory(1);
        $order = $this->order([['inventory' => $inventory, 'quantity' => '2.000']]);
        $otherBranch = Branch::where('id', '<>', $this->branch->id)->firstOrFail();
        $this->receive($order, $order->items->first(), '2.000', GoodsReceipt::STATUS_CONFIRMED, $otherBranch->id);
        $data = $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]);
        $data['currency_code'] = 'USD';
        $this->expectValidationError(function () use ($data) { app(SupplierInvoiceService::class)->saveDraft($this->user, $data); }, 'currency_code');
        unset($data['currency_code']);
        $this->expectValidationError(function () use ($order, $data) { app(SupplierInvoiceService::class)->saveDraft($this->user, $data); }, 'items');
    }

    public function test_approval_and_cancellation_require_their_role_permissions(): void
    {
        $order = $this->order([['quantity' => '1.000', 'price' => '10.00']]);
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]));
        $role = RoleManage::findOrFail($this->user->role_manage_id);
        $role->content = json_encode(['SupplierInvoice' => ['Supplier Invoice', 0, 1, 1, 0, 0, 0, 0, 0, 0]]);
        $role->save();
        $this->assertHttpExceptionStatus(function () use ($invoice) {
            $this->post(route('supplier-invoices.approve', $invoice));
        }, 403);
        $this->assertHttpExceptionStatus(function () use ($invoice) {
            $this->post(route('supplier-invoices.cancel', $invoice), ['cancellation_reason' => 'Test cancel']);
        }, 403);
    }

    public function test_price_variance_requires_supplier_invoice_edit_permission_to_approve(): void
    {
        $order = $this->order([['quantity' => '1.000', 'price' => '10.00']]);
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.01']]));
        $this->assertSame(SupplierInvoice::STATUS_REVIEW_REQUIRED, $invoice->status);

        $role = RoleManage::findOrFail($this->user->role_manage_id);
        $role->content = json_encode(['SupplierInvoice' => ['Supplier Invoice', 1, 1, 1, 0, 0, 0, 0, 0, 0]]);
        $role->save();
        $this->assertHttpExceptionStatus(function () use ($invoice) {
            $this->post(route('supplier-invoices.approve', $invoice));
        }, 403);

        $role->content = json_encode(['SupplierInvoice' => ['Supplier Invoice', 1, 1, 1, 1, 0, 0, 0, 0, 0]]);
        $role->save();
        $this->post(route('supplier-invoices.approve', $invoice))->assertRedirect(route('supplier-invoices.show', $invoice));
        $this->assertSame(SupplierInvoice::STATUS_APPROVED, $invoice->fresh()->status);
    }

    public function test_authorized_approval_and_controlled_unpaid_cancellation(): void
    {
        $order = $this->order([['quantity' => '1.000', 'price' => '10.00']]);
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]));
        config(['role_manage.SupplierInvoice.Edit' => 1, 'role_manage.SupplierInvoice.Delete' => 1]);
        $approved = app(SupplierInvoiceService::class)->approve($invoice->id, $this->user);
        $this->assertNotNull($approved->approved_at);
        $cancelled = app(SupplierInvoiceService::class)->cancel($approved->id, $this->user, 'Supplier document was withdrawn');
        $this->assertSame(SupplierInvoice::STATUS_CANCELLED, $cancelled->status);
        $this->assertSame('Supplier document was withdrawn', $cancelled->cancellation_reason);
        $this->assertSame((int) $this->user->id, (int) $cancelled->cancelled_by);
        $this->expectValidationError(function () use ($cancelled) {
            app(SupplierInvoiceService::class)->saveDraft($this->user, [], $cancelled->id);
        }, 'invoice');
    }

    public function test_draft_and_review_required_invoices_can_be_cancelled_with_reason(): void
    {
        $order = $this->order([['quantity' => '2.000', 'price' => '10.00']]);
        $line = $order->items->first();
        $draft = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [
            $line->id => ['quantity' => '1', 'supplier_unit_price' => '10.00'],
        ]));
        $this->post(route('supplier-invoices.cancel', $draft), ['cancellation_reason' => ''])
            ->assertSessionHasErrors('cancellation_reason');
        $this->assertSame(SupplierInvoice::STATUS_DRAFT, $draft->fresh()->status);
        $cancelledDraft = app(SupplierInvoiceService::class)->cancel($draft->id, $this->user, 'Draft document withdrawn');
        $this->assertSame(SupplierInvoice::STATUS_CANCELLED, $cancelledDraft->status);
        $this->assertSame('Draft document withdrawn', $cancelledDraft->cancellation_reason);

        $review = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [
            $line->id => ['quantity' => '1', 'supplier_unit_price' => '10.01'],
        ]));
        $this->assertSame(SupplierInvoice::STATUS_REVIEW_REQUIRED, $review->status);
        $cancelledReview = app(SupplierInvoiceService::class)->cancel($review->id, $this->user, 'Variance not accepted');
        $this->assertSame(SupplierInvoice::STATUS_CANCELLED, $cancelledReview->status);
        $this->assertSame('Variance not accepted', $cancelledReview->cancellation_reason);
    }

    public function test_paid_approved_invoice_cannot_be_cancelled(): void
    {
        $order = $this->order([['quantity' => '1.000', 'price' => '10.00']]);
        $invoice = app(SupplierInvoiceService::class)->saveDraft($this->user, $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]));
        $invoice = app(SupplierInvoiceService::class)->approve($invoice->id, $this->user);
        $invoice->amount_paid = '1.00';
        $invoice->amount_remaining = '9.00';
        $invoice->save();
        $this->expectValidationError(function () use ($invoice) { app(SupplierInvoiceService::class)->cancel($invoice->id, $this->user, 'Not allowed'); }, 'invoice');
    }

    public function test_duplicate_submission_token_returns_same_invoice_without_second_record(): void
    {
        $order = $this->order([['quantity' => '2.000', 'price' => '10.00']]);
        $data = $this->payload($order, [$order->items->first()->id => ['quantity' => '1', 'supplier_unit_price' => '10.00']]);
        $sequenceBefore = (int) DB::table('invoice_sequences')->where('document_type', 'supplier_invoice')->value('current_number');
        $first = app(SupplierInvoiceService::class)->saveDraft($this->user, $data);
        $sequenceAfterFirst = (int) DB::table('invoice_sequences')->where('document_type', 'supplier_invoice')->value('current_number');
        $second = app(SupplierInvoiceService::class)->saveDraft($this->user, $data);
        $this->assertSame((int) $first->id, (int) $second->id);
        $this->assertSame(1, SupplierInvoice::where('submission_token', $data['submission_token'])->count());
        $this->assertSame($sequenceBefore + 1, $sequenceAfterFirst);
        $this->assertSame($sequenceAfterFirst, (int) DB::table('invoice_sequences')->where('document_type', 'supplier_invoice')->value('current_number'));
    }

    public function test_missing_role_module_is_off_and_supplier_invoice_uses_current_role_configuration(): void
    {
        $config = require base_path('config/role_manage.php');
        $this->assertSame(0, $config['SupplierInvoice']['Create']);
        $this->assertSame(0, $config['SupplierInvoice']['Edit']);
        $this->assertTrue(isset($config['SupplierInvoice']['Delete']));
    }

    private function order(array $items, ?Vendor $vendor = null): PurchaseOrder
    {
        $vendor = $vendor ?: $this->vendor;
        $order = PurchaseOrder::create([
            'purchase_order_number' => 'PO-SI-' . strtoupper(Str::random(10)), 'submission_token' => (string) Str::uuid(),
            'vendor_id' => $vendor->id, 'vendor_name_snapshot' => $vendor->name, 'branch_id' => $this->branch->id,
            'branch_name_snapshot' => $this->branch->name, 'currency_code' => 'NGN', 'currency_symbol' => '₦',
            'currency_position' => 'Prefix', 'order_date' => now()->toDateString(), 'status' => PurchaseOrder::STATUS_APPROVED,
            'subtotal' => '0.00', 'order_total' => '0.00', 'approved_by' => $this->user->id, 'approved_at' => now(),
            'created_by' => $this->user->id, 'updated_by' => $this->user->id,
        ]);
        foreach ($items as $row) {
            $inventory = $row['inventory'] ?? null;
            $price = $row['price'] ?? '10.00';
            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id, 'inventory_item_id' => $inventory ? $inventory->id : null,
                'inventory_classification' => !$inventory ? 'service' : ($inventory->track_stock === null ? 'unclassified' : ($inventory->tracksStock() ? 'stock' : 'service')),
                'inventory_item_name_snapshot' => $inventory ? $inventory->description : ($row['description'] ?? 'Test service'),
                'description' => $row['description'] ?? ($inventory ? $inventory->description : 'Test service'),
                'unit' => 'Unit', 'quantity' => $row['quantity'], 'reference_unit_price' => $inventory ? $inventory->unit_cost : null,
                'agreed_unit_price' => $price, 'line_total' => number_format((float) $row['quantity'] * (float) $price, 2, '.', ''),
                'created_by' => $this->user->id, 'updated_by' => $this->user->id,
            ]);
        }
        return $order->load('items');
    }

    private function inventory(?int $trackStock, int $quantity = 0): Inventory
    {
        return Inventory::create([
            'branch_id' => $this->branch->id, 'description' => 'SI TEST ITEM ' . Str::random(8),
            'unit_of_measurement' => 'Unit', 'unit_cost' => '10.00', 'sale_price' => '20.00',
            'quantity_at_hand' => $quantity, 'reorder_level' => 0, 'track_stock' => $trackStock, 'account' => 'Test',
        ]);
    }

    private function receive(PurchaseOrder $order, PurchaseOrderItem $line, string $quantity, string $status = GoodsReceipt::STATUS_CONFIRMED, ?int $branchId = null): GoodsReceipt
    {
        $receipt = GoodsReceipt::create([
            'receipt_number' => 'GR-SI-' . strtoupper(Str::random(8)), 'submission_token' => (string) Str::uuid(),
            'purchase_order_id' => $order->id, 'vendor_id' => $order->vendor_id, 'branch_id' => $branchId ?: $order->branch_id,
            'received_date' => now()->toDateString(), 'status' => $status, 'created_by' => $this->user->id,
            'received_by' => $status === GoodsReceipt::STATUS_CONFIRMED ? $this->user->id : null,
        ]);
        GoodsReceivedItem::create([
            'goods_receipt_id' => $receipt->id, 'purchase_order_item_id' => $line->id,
            'inventory_item_id' => $line->inventory_item_id, 'item_description_snapshot' => $line->description,
            'unit_snapshot' => $line->unit, 'received_quantity' => $quantity,
        ]);
        return $receipt;
    }

    private function payload(PurchaseOrder $order, array $items): array
    {
        return [
            'purchase_order_id' => $order->id, 'supplier_invoice_number' => 'BILL-' . strtoupper(Str::random(8)),
            'invoice_date' => now()->toDateString(), 'due_date' => now()->addDays(30)->toDateString(),
            'submission_token' => (string) Str::uuid(), 'items' => $items,
        ];
    }

    private function expectValidationError(callable $callback, string $key): void
    {
        try {
            $callback();
            $this->fail('Expected a validation error for ' . $key . '.');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            $this->assertArrayHasKey($key, $errors, implode('; ', array_keys($errors)));
        }
    }

    private function assertHttpExceptionStatus(callable $callback, int $status): void
    {
        $this->withoutExceptionHandling();
        try {
            $callback();
            $this->fail('Expected HTTP ' . $status . ' exception.');
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }
}

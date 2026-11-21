<?php

namespace Tests\Feature;

use App\Branch;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceivedItem;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\RoleManage;
use App\Services\GoodsReceiptService;
use App\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;
use Tests\TestCase;

class GoodsReceivedWorkflowTest extends TestCase
{
    private const DATABASE = 'ayuglobal_saas_t01_test';
    private const DB_HOST = '127.0.0.1';
    private const DB_PORT = '3306';
    private const DB_USER = 'ayu_saas_t01_test_user';
    private const AUTHENTICATED_ACCOUNT = 'ayu_saas_t01_test_user@127.0.0.1';
    private const SERVER_HOSTNAME = 'DESKTOP-36CITOG';
    private const SERVER_PORT = '3306';
    private $user;
    private $vendor;
    private $branch;
    private $transactionStarted = false;

    protected function setUp(): void
    {
        try {
            parent::setUp();
            $this->assertApprovedDatabaseTarget();

            $identity = DB::connection('mysql')->selectOne(
                'SELECT DATABASE() AS database_name, CURRENT_USER() AS authenticated_account, @@hostname AS server_hostname, @@port AS server_port'
            );
            $this->assertNotNull($identity, 'Could not verify the approved MySQL identity.');
            $this->assertSame(self::DATABASE, $identity->database_name);
            $this->assertSame(self::AUTHENTICATED_ACCOUNT, $identity->authenticated_account);
            $this->assertSame(self::SERVER_HOSTNAME, $identity->server_hostname);
            $this->assertSame(self::SERVER_PORT, (string) $identity->server_port);

            foreach (['purchase_orders', 'purchase_order_items', 'inventories', 'goods_receipts', 'goods_received_items'] as $table) {
                $this->assertTrue(Schema::connection('mysql')->hasTable($table), 'Required table is missing: ' . $table);
            }
            $this->assertTrue(Schema::connection('mysql')->hasColumn('inventories', 'track_stock'));

            DB::connection('mysql')->beginTransaction();
            $this->transactionStarted = true;

            $role = RoleManage::create([
                'name' => 'GR TEST ROLE ' . Str::uuid(),
                'content' => json_encode([
                    'PurchaseOrder' => ['Purchase Order', 1, 1, 1, 1, 0, 0, 0, 0, 0],
                ]),
            ]);

            $this->user = User::create([
                'name' => 'GR TEST USER ' . Str::random(8),
                'email' => 'gr-test-' . Str::uuid() . '@example.invalid',
                'password' => Hash::make(Str::random(64)),
                'role_manage_id' => $role->id,
            ]);

            $this->actingAs($this->user);
            config(['role_manage.PurchaseOrder.Create' => 1, 'role_manage.PurchaseOrder.Show' => 1, 'role_manage.PurchaseOrder.Edit' => 1]);

            $this->branch = Branch::create([
                'name' => 'GR TEST BRANCH ' . Str::uuid(),
                'location' => 'Test',
            ]);
            $this->vendor = Vendor::create([
                'branch_id' => $this->branch->id,
                'name' => 'GR TEST VENDOR ' . Str::uuid(),
                'address' => 'Test address',
                'phone_number' => '0000000000',
                'account' => 'Test Account',
                'terms_of_payment' => '30 days',
            ]);
        } catch (Throwable $exception) {
            $rollbackFailure = $this->rollbackTestTransaction();
            if ($rollbackFailure !== null) {
                $this->reportRollbackFailure($rollbackFailure);
            }

            throw $exception;
        }
    }

    private function rollbackTestTransaction(): ?Throwable
    {
        try {
            if (!$this->transactionStarted) {
                return null;
            }

            $connection = DB::connection('mysql');
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }

            return null;
        } catch (Throwable $rollbackFailure) {
            return $rollbackFailure;
        } finally {
            $this->transactionStarted = false;
        }
    }

    private function reportRollbackFailure(Throwable $rollbackFailure): void
    {
        try {
            $diagnostic = sprintf(
                'GoodsReceivedWorkflowTest transaction rollback failed (%s, code %s).',
                get_class($rollbackFailure),
                (string) $rollbackFailure->getCode()
            );

            if (!error_log($diagnostic) && defined('STDERR')) {
                @fwrite(STDERR, $diagnostic . PHP_EOL);
            }
        } catch (Throwable $diagnosticFailure) {
            // Preserve the original setup or test failure if diagnostics are unavailable.
        }
    }

    private function assertApprovedDatabaseTarget(): void
    {
        $connection = config('database.connections.mysql', []);
        $environmentOverrides = [
            'DB_URL' => [getenv('DB_URL'), env('DB_URL')],
            'DATABASE_URL' => [getenv('DATABASE_URL'), env('DATABASE_URL')],
            'DB_SOCKET' => [getenv('DB_SOCKET'), env('DB_SOCKET')],
        ];

        $this->assertFalse(app()->configurationIsCached(), 'Cached Laravel configuration is not allowed for this test.');
        foreach ($environmentOverrides as $name => $values) {
            foreach ($values as $value) {
                $this->assertSame('', trim((string) ($value === false || $value === null ? '' : $value)), $name . ' overrides are not allowed for this test.');
            }
        }
        $this->assertSame('', trim((string) ($connection['url'] ?? '')), 'A configured database URL is not allowed for this test.');
        $this->assertSame('', trim((string) ($connection['unix_socket'] ?? '')), 'A configured database socket is not allowed for this test.');

        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('mysql', $connection['driver'] ?? null);
        $this->assertSame(self::DATABASE, $connection['database'] ?? null);
        $this->assertSame(self::DB_HOST, $connection['host'] ?? null);
        $this->assertSame(self::DB_PORT, (string) ($connection['port'] ?? ''));
        $this->assertSame(self::DB_USER, $connection['username'] ?? null);
    }

    protected function tearDown(): void
    {
        $rollbackFailure = $this->rollbackTestTransaction();
        if ($rollbackFailure !== null) {
            $this->reportRollbackFailure($rollbackFailure);
        }
        parent::tearDown();
    }

    public function test_draft_purchase_order_cannot_start_goods_received(): void
    {
        $order = $this->order(PurchaseOrder::STATUS_DRAFT, [['quantity' => '4.000']]);
        $this->get(route('purchase-orders.goods-received.create', $order))->assertStatus(422);
    }

    public function test_approved_purchase_order_can_start_goods_received(): void
    {
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['quantity' => '4.000']]);
        $view = app(\App\Http\Controllers\GoodsReceivedController::class)->create($order->id);
        $this->assertSame('admin.goods-received.create', $view->name());
        $this->assertSame((int) $order->id, (int) $view->getData()['order']->id);
    }

    public function test_save_draft_persists_receipt_lines_without_stock_or_order_status_change(): void
    {
        $inventory = $this->inventory(1, 8);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '4.000']]);
        $before = (int) $inventory->quantity_at_hand;
        $receipt = $this->saveDraft($order, [$order->items->first()->id => '2']);
        $this->assertSame(GoodsReceipt::STATUS_DRAFT, $receipt->status);
        $this->assertCount(1, $receipt->items);
        $this->assertSame('approved', $order->fresh()->status);
        $this->assertSame($before, (int) $inventory->fresh()->quantity_at_hand);
    }

    public function test_confirmed_stock_tracked_receipt_increases_inventory(): void
    {
        $inventory = $this->inventory(1, 3);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '5.000']]);
        $receipt = $this->saveDraft($order, [$order->items->first()->id => '2']);
        app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
        $this->assertSame(5, (int) $inventory->fresh()->quantity_at_hand);
    }

    public function test_service_classified_inventory_does_not_increase_quantity(): void
    {
        $inventory = $this->inventory(0, 3);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '2.500']]);
        $receipt = $this->saveDraft($order, [$order->items->first()->id => '1.5']);
        app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
        $this->assertSame(3, (int) $inventory->fresh()->quantity_at_hand);
        $this->assertSame('partially_received', $order->fresh()->status);
    }

    public function test_unclassified_inventory_blocks_confirmation_without_stock_change(): void
    {
        $inventory = $this->inventory(null, 6);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '3.000']]);
        $receipt = $this->saveDraft($order, [$order->items->first()->id => '1']);
        try {
            app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
            $this->fail('Unclassified inventory must not be confirmed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('received_quantities', $exception->errors());
        }
        $this->assertSame(6, (int) $inventory->fresh()->quantity_at_hand);
        $this->assertSame('draft', $receipt->fresh()->status);
    }

    public function test_partial_receipt_page_shows_server_calculated_remaining_quantity(): void
    {
        $inventory = $this->inventory(1, 2);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '5.000']]);
        $receipt = $this->saveDraft($order, [$order->items->first()->id => '2']);
        app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
        $view = app(\App\Http\Controllers\GoodsReceivedController::class)->create($order->id);
        $this->assertSame('3.000', $view->getData()['lines']->first()->remaining_quantity);
    }

    public function test_multiple_partial_receipts_can_complete_one_order(): void
    {
        $inventory = $this->inventory(1, 0);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '5.000']]);
        $line = $order->items->first();
        app(GoodsReceiptService::class)->confirm($this->saveDraft($order, [$line->id => '2'])->id, $this->user);
        $this->assertSame('partially_received', $order->fresh()->status);
        app(GoodsReceiptService::class)->confirm($this->saveDraft($order, [$line->id => '3'])->id, $this->user);
        $this->assertSame('received', $order->fresh()->status);
        $this->assertSame(5, (int) $inventory->fresh()->quantity_at_hand);
    }

    public function test_over_receipt_is_rejected_when_a_draft_becomes_stale(): void
    {
        $inventory = $this->inventory(1, 1);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '10.000']]);
        $line = $order->items->first();
        $first = $this->saveDraft($order, [$line->id => '6']);
        $second = $this->saveDraft($order, [$line->id => '5']);
        app(GoodsReceiptService::class)->confirm($first->id, $this->user);
        try {
            app(GoodsReceiptService::class)->confirm($second->id, $this->user);
            $this->fail('Stale receipt must be rejected when it exceeds the remaining quantity.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('received_quantities', $exception->errors());
        }
        $this->assertSame(7, (int) $inventory->fresh()->quantity_at_hand);
        $this->assertSame(GoodsReceipt::STATUS_DRAFT, $second->fresh()->status);
    }

    public function test_purchase_order_becomes_partially_received_after_a_partial_receipt(): void
    {
        $inventory = $this->inventory(1, 0);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '4.000']]);
        $this->confirm($order, '1');
        $this->assertSame(PurchaseOrder::STATUS_PARTIALLY_RECEIVED, $order->fresh()->status);
    }

    public function test_purchase_order_becomes_received_after_all_quantities_are_received(): void
    {
        $inventory = $this->inventory(1, 0);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '4.000']]);
        $this->confirm($order, '4');
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->fresh()->status);
    }

    public function test_repeated_confirmation_does_not_duplicate_stock_or_numbering(): void
    {
        $inventory = $this->inventory(1, 1);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '4.000']]);
        $receipt = $this->saveDraft($order, [$order->items->first()->id => '2']);
        $beforeSequence = (int) DB::table('invoice_sequences')->where('document_type', 'goods_receipt')->value('current_number');
        $confirmed = app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
        $number = $confirmed->receipt_number;
        app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
        $this->assertSame(3, (int) $inventory->fresh()->quantity_at_hand);
        $this->assertSame($number, $receipt->fresh()->receipt_number);
        $this->assertSame($beforeSequence + 1, (int) DB::table('invoice_sequences')->where('document_type', 'goods_receipt')->value('current_number'));
    }

    public function test_only_draft_receipts_can_be_confirmed(): void
    {
        $inventory = $this->inventory(1, 3);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '2.000']]);
        $receipt = $this->saveDraft($order, [$order->items->first()->id => '1']);
        $receipt->status = 'cancelled';
        $receipt->save();

        try {
            app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
            $this->fail('A non-draft Goods Received record must not be confirmed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('receipt', $exception->errors());
        }

        $this->assertSame(3, (int) $inventory->fresh()->quantity_at_hand);
        $this->assertSame(PurchaseOrder::STATUS_APPROVED, $order->fresh()->status);
        $this->assertSame('cancelled', $receipt->fresh()->status);
    }

    public function test_failed_multi_line_confirmation_rolls_back_receipt_stock_and_order_status(): void
    {
        $tracked = $this->inventory(1, 4);
        $unclassified = $this->inventory(null, 9);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [
            ['inventory' => $tracked, 'quantity' => '2.000'], ['inventory' => $unclassified, 'quantity' => '2.000'],
        ]);
        $lines = $order->items;
        $receipt = $this->saveDraft($order, [$lines[0]->id => '1', $lines[1]->id => '1']);
        try {
            app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
            $this->fail('The unclassified second line should abort the transaction.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('received_quantities', $exception->errors());
        }
        $this->assertSame(4, (int) $tracked->fresh()->quantity_at_hand);
        $this->assertSame(9, (int) $unclassified->fresh()->quantity_at_hand);
        $this->assertSame('draft', $receipt->fresh()->status);
        $this->assertSame('approved', $order->fresh()->status);
        $this->assertNull($receipt->fresh()->receipt_number);
    }

    public function test_po_agreed_price_is_displayed_instead_of_changed_inventory_cost(): void
    {
        $inventory = $this->inventory(1, 0);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '2.000', 'agreed_price' => '123.45']]);
        $inventory->unit_cost = '999.99';
        $inventory->save();
        $view = app(\App\Http\Controllers\GoodsReceivedController::class)->create($order->id);
        $this->assertSame('123.45', $view->getData()['lines']->first()->agreed_unit_price);
        $this->assertSame('123.45', $order->items->first()->fresh()->agreed_unit_price);
    }

    public function test_receipt_inherits_purchase_order_branch_and_mismatched_stock_branch_is_blocked(): void
    {
        $otherBranch = Branch::create(['name' => 'GR TEST OTHER ' . Str::uuid(), 'location' => 'Test']);
        $inventory = $this->inventory(1, 2, $otherBranch->id);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '2.000']]);
        $receipt = $this->saveDraft($order, [$order->items->first()->id => '1']);
        $this->assertSame((int) $order->branch_id, (int) $receipt->branch_id);
        try {
            app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
            $this->fail('Cross-branch stock receipt should be blocked.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('received_quantities', $exception->errors());
        }
        $this->assertSame(2, (int) $inventory->fresh()->quantity_at_hand);
    }

    public function test_approval_is_a_single_draft_to_approved_transition(): void
    {
        $order = $this->order(PurchaseOrder::STATUS_DRAFT, [['quantity' => '1.000']]);
        $this->post(route('purchase-orders.approve', ['id' => $order->id]))->assertRedirect(route('purchase-orders.show', ['id' => $order->id]));
        $order->refresh();
        $this->assertSame(PurchaseOrder::STATUS_APPROVED, $order->status);
        $this->assertSame((int) $this->user->id, (int) $order->approved_by);
        $this->assertNotNull($order->approved_at);
        $this->post(route('purchase-orders.approve', ['id' => $order->id]))->assertStatus(422);
    }

    public function test_goods_received_routes_require_the_purchase_order_role_permission(): void
    {
        $role = RoleManage::findOrFail($this->user->role_manage_id);
        $role->content = json_encode(['PurchaseOrder' => ['Purchase Order', 0, 0, 0, 0, 0, 0, 0, 0, 0]]);
        $role->save();
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['quantity' => '1.000']]);
        $this->withoutExceptionHandling();
        $this->expectException(HttpException::class);
        $this->get(route('purchase-orders.goods-received.create', $order));
    }

    public function test_inventory_track_stock_choices_are_respected_without_changing_customer_price_source(): void
    {
        $this->assertTrue($this->inventory(1, 1)->tracksStock());
        $this->assertTrue($this->inventory(0, 1)->isNonStock());
        $this->assertFalse($this->inventory(null, 1)->hasStockTrackingChoice());
        $source = file_get_contents(app_path('Http/Controllers/CustomerInvoiceController.php'));
        $this->assertStringContainsString("'sale_price'", $source);
    }

    public function test_stock_tracked_fractional_receipt_is_rejected(): void
    {
        $inventory = $this->inventory(1, 5);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '2.000']]);
        $receipt = $this->saveDraft($order, [$order->items->first()->id => '1.5']);
        try {
            app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
            $this->fail('Physical inventory receipt must use whole-number quantities.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('received_quantities', $exception->errors());
        }
        $this->assertSame(5, (int) $inventory->fresh()->quantity_at_hand);
    }

    public function test_custom_service_receipt_can_be_confirmed_without_inventory_movement(): void
    {
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['quantity' => '1.500', 'description' => 'Custom installation service']]);
        $receipt = $this->saveDraft($order, [$order->items->first()->id => '1.5']);
        app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
        $this->assertSame(GoodsReceipt::STATUS_CONFIRMED, $receipt->fresh()->status);
        $this->assertNull($receipt->items->first()->inventory_item_id);
        $this->assertSame(PurchaseOrder::STATUS_RECEIVED, $order->fresh()->status);
    }

    public function test_duplicate_submission_token_does_not_create_another_draft(): void
    {
        $inventory = $this->inventory(0, 0);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '2.000']]);
        $line = $order->items->first();
        $data = ['submission_token' => (string) Str::uuid(), 'received_date' => now()->toDateString(), 'received_quantities' => [$line->id => '1']];
        $first = app(GoodsReceiptService::class)->saveDraft($order, $this->user, $data);
        $second = app(GoodsReceiptService::class)->saveDraft($order, $this->user, $data);
        $this->assertSame((int) $first->id, (int) $second->id);
        $this->assertSame(1, GoodsReceipt::where('submission_token', $data['submission_token'])->count());
    }

    public function test_confirmation_does_not_create_vendor_invoice_or_payment_records(): void
    {
        $inventory = $this->inventory(1, 0);
        $order = $this->order(PurchaseOrder::STATUS_APPROVED, [['inventory' => $inventory, 'quantity' => '1.000']]);
        $counts = [];
        foreach (['vendor_invoices', 'vendor_payments', 'transactions', 'bank_cash'] as $table) {
            $counts[$table] = Schema::connection('mysql')->hasTable($table) ? DB::table($table)->count() : null;
        }
        $this->confirm($order, '1');
        foreach ($counts as $table => $count) {
            if ($count !== null) {
                $this->assertSame($count, DB::table($table)->count(), $table . ' must remain untouched by Goods Received.');
            }
        }
    }

    private function confirm(PurchaseOrder $order, string $quantity): GoodsReceipt
    {
        $line = $order->items->first();
        $receipt = $this->saveDraft($order, [$line->id => $quantity]);
        return app(GoodsReceiptService::class)->confirm($receipt->id, $this->user);
    }

    private function saveDraft(PurchaseOrder $order, array $quantities): GoodsReceipt
    {
        return app(GoodsReceiptService::class)->saveDraft($order, $this->user, [
            'submission_token' => (string) Str::uuid(),
            'received_date' => now()->toDateString(),
            'notes' => 'Automated test only',
            'received_quantities' => $quantities,
        ]);
    }

    private function order(string $status, array $lines): PurchaseOrder
    {
        $order = PurchaseOrder::create([
            'purchase_order_number' => 'PO-GR-' . strtoupper(Str::random(10)), 'submission_token' => (string) Str::uuid(),
            'vendor_id' => $this->vendor->id, 'vendor_name_snapshot' => $this->vendor->name,
            'branch_id' => $this->branch->id, 'branch_name_snapshot' => $this->branch->name,
            'currency_code' => 'NGN', 'currency_symbol' => '₦', 'currency_position' => 'Prefix',
            'order_date' => now()->toDateString(), 'status' => $status, 'subtotal' => '0.00', 'order_total' => '0.00',
            'created_by' => $this->user->id, 'updated_by' => $this->user->id,
            'approved_by' => $status === PurchaseOrder::STATUS_DRAFT ? null : $this->user->id,
            'approved_at' => $status === PurchaseOrder::STATUS_DRAFT ? null : now(),
        ]);
        foreach ($lines as $row) {
            $inventory = $row['inventory'] ?? null;
            $quantity = $row['quantity'];
            $price = $row['agreed_price'] ?? '100.00';
            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id, 'inventory_item_id' => $inventory ? $inventory->id : null,
                'inventory_classification' => !$inventory ? 'service' : ($inventory->track_stock === null ? 'unclassified' : ($inventory->tracksStock() ? 'stock' : 'service')),
                'inventory_item_name_snapshot' => $inventory ? $inventory->description : ($row['description'] ?? 'Service'),
                'description' => $row['description'] ?? ($inventory ? $inventory->description : 'Service'),
                'unit' => 'Unit', 'quantity' => $quantity, 'reference_unit_price' => $inventory ? $inventory->unit_cost : null,
                'agreed_unit_price' => $price, 'line_total' => number_format((float) $quantity * (float) $price, 2, '.', ''),
                'created_by' => $this->user->id, 'updated_by' => $this->user->id,
            ]);
        }
        return $order->load('items');
    }

    private function inventory(?int $trackStock, int $quantity, ?int $branchId = null): Inventory
    {
        return Inventory::create([
            'branch_id' => $branchId ?: $this->branch->id, 'description' => 'GR TEST ITEM ' . Str::random(8), 'sub_item' => null,
            'unit_of_measurement' => 'Unit', 'unit_cost' => '10.00', 'sale_price' => '20.00', 'quantity_at_hand' => $quantity,
            'reorder_level' => 0, 'track_stock' => $trackStock, 'account' => 'Test Account',
        ]);
    }

}

<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Vendor;
use App\Branch;
use App\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PurchaseOrderStage2DatabaseTest extends TestCase
{
    private const DATABASE = 'ayu_po_stage2_test';
    private const DB_USER = 'ayu_po_stage2_test_user';
    private const SUBMISSION_TOKEN = '00000000-0000-4000-8000-000000000003';

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('mysql', config('database.default'), 'Tests must use the dedicated MySQL test connection.');
        $this->assertSame(self::DATABASE, config('database.connections.mysql.database'), 'Refusing any database other than the isolated PO test database.');
        $this->assertSame(self::DB_USER, config('database.connections.mysql.username'), 'Refusing any database account other than the restricted PO test account.');

        $actualDatabase = DB::connection('mysql')->selectOne('SELECT DATABASE() AS database_name');
        $this->assertSame(self::DATABASE, $actualDatabase->database_name, 'The connected database does not match the isolated PO test database.');
        DB::connection('mysql')->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (DB::connection('mysql')->transactionLevel() > 0) {
            DB::connection('mysql')->rollBack();
        }

        parent::tearDown();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_authenticated_draft_purchase_order_flow_is_persisted_and_isolated(): void
    {
        $this->get('/purchase-orders/create')->assertRedirect('/login');

        $user = User::where('email', 'po-test-user@example.invalid')->firstOrFail();
        $this->post('/login', [
            'email' => 'po-test-user@example.invalid',
            'password' => 'PO-TEST-ONLY-2026!',
        ])->assertRedirect('/purchase-orders/create');
        $this->assertAuthenticatedAs($user);

        $vendor = Vendor::where('name', 'TEST VENDOR')->firstOrFail();
        $branchA = Branch::where('name', 'TEST BRANCH A')->firstOrFail();
        $branchB = Branch::where('name', 'TEST BRANCH B')->firstOrFail();
        $inventory = Inventory::where('description', 'TEST INVENTORY ITEM')->firstOrFail();
        $this->assertSame((int) $branchA->id, (int) $vendor->branch_id);
        $this->assertSame('1250.50', number_format((float) $inventory->unit_cost, 2, '.', ''));

        $response = $this->get('/purchase-orders/create')
            ->assertOk()
            ->assertSee('TEST VENDOR')
            ->assertSee('TEST BRANCH A')
            ->assertSee('TEST BRANCH B');

        $hasInventorySelect = preg_match('/<select\b[^>]*class="[^"]*\bpo-item-select\b[^"]*"[^>]*>(.*?)<\/select>/s', $response->getContent(), $inventorySelect);
        $this->assertSame(1, $hasInventorySelect, 'The Purchase Order response must render its Inventory selector.');
        if ($hasInventorySelect === 1) {
            $this->assertStringContainsString('class="form-control po-item-select ms"', $inventorySelect[0]);
            foreach (Inventory::orderBy('description')->get() as $inventoryOption) {
                $this->assertStringContainsString('value="' . $inventoryOption->id . '"', $inventorySelect[1]);
                $expectedLabel = trim($inventoryOption->description . ' ' . ($inventoryOption->sub_item ?: ''));
                $this->assertStringContainsString(e($expectedLabel), $inventorySelect[1]);
            }
        }
        $this->get(route('purchase-orders.vendors.show', $vendor->id))
            ->assertOk()->assertJsonPath('name', 'TEST VENDOR')->assertJsonPath('address', 'TEST ADDRESS')
            ->assertJsonPath('phone_number', 'TEST ONLY')->assertJsonPath('account', 'TEST ACCOUNT')
            ->assertJsonPath('terms_of_payment', 'TEST TERMS')->assertJsonPath('branch', 'TEST BRANCH A');
        $this->get(route('purchase-orders.inventory-items.show', $inventory->id))
            ->assertOk()->assertJsonPath('reference_unit_price', '1250.50');

        $payload = [
            'vendor_id' => $vendor->id,
            'branch_id' => $branchB->id,
            'submission_token' => self::SUBMISSION_TOKEN,
            'order_date' => '2026-09-30',
            'expected_delivery_date' => '2026-10-15',
            'delivery_instructions' => 'Synthetic test delivery only',
            'internal_notes' => 'Synthetic test draft only',
            'subtotal' => '999999.99',
            'order_total' => '0.01',
            'items' => [
                [
                    'inventory_item_id' => $inventory->id,
                    'inventory_classification' => 'service',
                    'description' => 'Synthetic test line one',
                    'unit' => 'box',
                    'quantity' => '2',
                    'agreed_unit_price' => '1000.00',
                ],
                [
                    'inventory_item_id' => $inventory->id,
                    'inventory_classification' => 'service',
                    'description' => 'Synthetic test line two',
                    'unit' => 'box',
                    'quantity' => '1.5',
                    'agreed_unit_price' => '900.00',
                ],
            ],
        ];

        $quantityBefore = $inventory->quantity_at_hand;
        $existing = PurchaseOrder::where('submission_token', self::SUBMISSION_TOKEN)->first();
        $existingClassification = $existing
            ? PurchaseOrderItem::where('purchase_order_id', $existing->id)->orderBy('id')->value('inventory_classification')
            : null;
        $sequenceBefore = (int) DB::table('invoice_sequences')->where('document_type', 'purchase_order')->value('current_number');
        $orderCountBefore = PurchaseOrder::count();
        $itemCountBefore = PurchaseOrderItem::count();
        $writes = [];
        DB::listen(function (QueryExecuted $query) use (&$writes): void {
            if (preg_match('/^\s*(insert(?:\s+ignore)?\s+into|update|delete\s+from)\s+[`"]?([A-Za-z0-9_]+)/i', $query->sql, $matches)) {
                $writes[] = strtolower($matches[2]);
            }
        });

        $response = $this->post(route('purchase-orders.store'), $payload);
        $response->assertRedirect();

        $order = PurchaseOrder::where('submission_token', self::SUBMISSION_TOKEN)->with('items')->firstOrFail();
        $this->assertSame('draft', $order->status);
        $this->assertSame('3350.00', $order->subtotal);
        $this->assertSame('3350.00', $order->order_total);
        $this->assertSame((int) $vendor->id, (int) $order->vendor_id);
        $this->assertSame('TEST VENDOR', $order->vendor_name_snapshot);
        $this->assertSame('TEST ADDRESS', $order->vendor_address_snapshot);
        $this->assertSame('TEST ONLY', $order->vendor_phone_snapshot);
        $this->assertSame('TEST ACCOUNT', $order->vendor_account_snapshot);
        $this->assertSame('TEST TERMS', $order->vendor_terms_snapshot);
        $this->assertSame('TEST BRANCH A', $order->vendor_branch_name_snapshot);
        $this->assertSame((int) $branchA->id, (int) $order->vendor->branch_id);
        $this->assertSame((int) $branchB->id, (int) $order->branch_id);
        $this->assertSame('TEST BRANCH B', $order->branch_name_snapshot);
        $this->assertSame('NGN', $order->currency_code);
        $this->assertSame("\u{20A6}", $order->currency_symbol);
        $this->assertCount(2, $order->items);
        $expectedClassification = $inventory->track_stock === null
            ? 'unclassified'
            : ($inventory->tracksStock() ? 'stock' : 'service');
        $this->assertSame('2000.00', $order->items[0]->line_total);
        $this->assertSame('1350.00', $order->items[1]->line_total);
        $this->assertSame('1250.50', $order->items[0]->reference_unit_price);
        $this->assertSame('1000.00', $order->items[0]->agreed_unit_price);
        if ($existing) {
            $this->assertSame($existingClassification, $order->items[0]->inventory_classification, 'Idempotent submission must preserve existing PO classification.');
        } else {
            $this->assertSame($expectedClassification, $order->items[0]->inventory_classification, 'PO classification must come from the Inventory master, not the request.');
            $this->assertSame($expectedClassification, $order->items[1]->inventory_classification);
        }
        $this->assertSame('PO-' . str_pad((string) ($sequenceBefore + ($existing ? 0 : 1)), 6, '0', STR_PAD_LEFT), $order->purchase_order_number);
        $this->assertSame((int) $quantityBefore, (int) Inventory::findOrFail($inventory->id)->quantity_at_hand);

        $allowedWrites = ['invoice_sequences', 'purchase_orders', 'purchase_order_items'];
        if ($existing) {
            $this->assertSame([], $writes, 'A repeated submission token must not write a duplicate Purchase Order.');
        } else {
            $this->assertNotEmpty($writes, 'A new submission must persist the draft and advance its sequence.');
        }
        foreach (array_unique($writes) as $table) {
            $this->assertContains($table, $allowedWrites, 'Unexpected database write during Purchase Order creation: ' . $table);
        }
        foreach (['vendor_invoices', 'vendor_payments', 'transactions', 'bank_cash', 'goods_received', 'goods_received_items'] as $forbiddenTable) {
            $this->assertNotContains($forbiddenTable, $writes);
        }

        $this->get(route('purchase-orders.show', $order->id))->assertOk()->assertSee($order->purchase_order_number);
        $this->get(route('purchase-orders.edit', $order->id))->assertOk();

        $beforeDuplicateSequence = (int) DB::table('invoice_sequences')->where('document_type', 'purchase_order')->value('current_number');
        $this->post(route('purchase-orders.store'), $payload)->assertRedirect();
        $this->assertSame((int) $order->id, (int) PurchaseOrder::where('submission_token', self::SUBMISSION_TOKEN)->value('id'));
        $this->assertSame($orderCountBefore + ($existing ? 0 : 1), PurchaseOrder::count());
        $this->assertSame($itemCountBefore + ($existing ? 0 : 2), PurchaseOrderItem::count());
        $this->assertSame($beforeDuplicateSequence, (int) DB::table('invoice_sequences')->where('document_type', 'purchase_order')->value('current_number'));

        $itemPriceSnapshot = $order->items[0]->reference_unit_price;
        try {
            DB::table('inventories')->where('id', $inventory->id)->update(['unit_cost' => '1777.77']);
            $this->assertSame($itemPriceSnapshot, PurchaseOrderItem::where('purchase_order_id', $order->id)->where('inventory_item_id', $inventory->id)->orderBy('id')->value('reference_unit_price'));
        } finally {
            DB::table('inventories')->where('id', $inventory->id)->update(['unit_cost' => '1250.50']);
        }

        $classificationsBeforeUpdate = $order->items->pluck('inventory_classification')->all();
        $updatePayload = $payload;
        unset($updatePayload['submission_token'], $updatePayload['subtotal'], $updatePayload['order_total']);
        $updatePayload['internal_notes'] = 'Synthetic draft edit verified';
        $this->put(route('purchase-orders.update', $order->id), $updatePayload)->assertRedirect();
        $updatedOrder = PurchaseOrder::with('items')->findOrFail($order->id);
        $this->assertSame('draft', $updatedOrder->status);
        $this->assertSame('3350.00', $updatedOrder->order_total);
        $this->assertSame('Synthetic draft edit verified', $updatedOrder->internal_notes);
        $this->assertCount(2, $updatedOrder->items);
        $this->assertSame($classificationsBeforeUpdate, $updatedOrder->items->pluck('inventory_classification')->all(), 'Editing a draft must preserve each matching line\'s classification snapshot.');
        $this->assertSame('2000.00', $updatedOrder->items[0]->line_total);
        $this->assertSame('1350.00', $updatedOrder->items[1]->line_total);
        $this->assertSame('1250.50', number_format((float) Inventory::findOrFail($inventory->id)->unit_cost, 2, '.', ''));

        // Exercise a genuinely new PO create request within a rollback-only transaction.
        $payload['submission_token'] = (string) \Illuminate\Support\Str::uuid();
        DB::beginTransaction();
        try {
            $this->post(route('purchase-orders.store'), $payload)->assertRedirect();
            $newOrder = PurchaseOrder::where('submission_token', $payload['submission_token'])->with('items')->firstOrFail();
            $this->assertSame($expectedClassification, $newOrder->items[0]->inventory_classification, 'A submitted service classification must not override Inventory stock tracking.');
            $this->assertSame($expectedClassification, $newOrder->items[1]->inventory_classification);
        } finally {
            DB::rollBack();
        }
    }
}

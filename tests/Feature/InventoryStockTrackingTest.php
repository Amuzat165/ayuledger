<?php

namespace Tests\Feature;

use App\Branch;
use App\Models\Inventory;
use App\Models\InventoryType;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryStockTrackingTest extends TestCase
{
    private const DATABASE = 'ayu_po_stage2_test';
    private const DB_USER = 'ayu_po_stage2_test_user';

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('mysql', config('database.default'));
        $this->assertSame(self::DATABASE, config('database.connections.mysql.database'));
        $this->assertSame(self::DB_USER, config('database.connections.mysql.username'));
        $this->assertSame(self::DATABASE, DB::connection('mysql')->selectOne('SELECT DATABASE() AS database_name')->database_name);
        $this->assertTrue(Schema::connection('mysql')->hasColumn('inventories', 'track_stock'));
        DB::connection('mysql')->beginTransaction();

        $user = User::where('email', 'po-test-user@example.invalid')->firstOrFail();
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        if (DB::connection('mysql')->transactionLevel() > 0) {
            DB::connection('mysql')->rollBack();
        }
        parent::tearDown();
    }
    public function test_inventory_forms_compile_with_the_three_explicit_track_stock_choices(): void
    {
        foreach (['create', 'edit'] as $form) {
            $source = file_get_contents(resource_path("views/pages/inventories/{$form}.blade.php"));
            $this->assertIsString(\Illuminate\Support\Facades\Blade::compileString($source));
            $this->assertStringContainsString("__('financial.screens.track_stock')", $source);
            $this->assertStringContainsString("__('financial.screens.track_stock_yes')", $source);
            $this->assertStringContainsString("__('financial.screens.track_stock_no')", $source);
            $this->assertStringContainsString("__('financial.screens.track_stock_unclassified')", $source);
        }
    }
    public function test_existing_inventory_records_remain_unclassified_after_additive_column_installation(): void
    {
        $this->assertSame(0, Inventory::whereNotNull('track_stock')->count());
    }

    public function test_new_inventory_can_be_saved_as_stock_tracked_non_stock_or_unclassified(): void
    {
        foreach ([['1', 1], ['0', 0], ['', null]] as [$submitted, $expected]) {
            $payload = $this->inventoryPayload($submitted);
            $this->post(route('inventories.store'), $payload)->assertRedirect(route('inventories.index'));
            $item = Inventory::where('description', $payload['description'])->firstOrFail();
            $this->assertSame($expected, $item->track_stock === null ? null : (int) $item->track_stock);
            $this->assertSame(1 === $expected, $item->tracksStock());
            $this->assertSame(0 === $expected, $item->isNonStock());
            $this->assertSame($expected !== null, $item->hasStockTrackingChoice());
        }
    }

    public function test_classification_update_preserves_quantity_and_both_prices(): void
    {
        $payload = $this->inventoryPayload('');
        $this->post(route('inventories.store'), $payload)->assertRedirect(route('inventories.index'));
        $item = Inventory::where('description', $payload['description'])->firstOrFail();
        $before = [$item->quantity_at_hand, $item->unit_cost, $item->sale_price];
        $payload['track_stock'] = '1';
        $this->put(route('inventories.update', $item->id), $payload)->assertRedirect(route('inventories.index'));

        $item->refresh();
        $this->assertSame(1, (int) $item->track_stock);
        $this->assertSame((int) $before[0], (int) $item->quantity_at_hand);
        $this->assertSame((string) $before[1], (string) $item->unit_cost);
        $this->assertSame((string) $before[2], (string) $item->sale_price);
    }

    public function test_customer_invoice_selling_price_and_purchase_order_reference_price_sources_remain_unchanged(): void
    {
        $customerInvoice = file_get_contents(app_path('Http/Controllers/CustomerInvoiceController.php'));
        $purchaseOrder = file_get_contents(app_path('Http/Controllers/PurchaseOrderController.php'));

        $this->assertStringContainsString("'sale_price'", $customerInvoice);
        $this->assertStringContainsString('->unit_cost', $purchaseOrder);
    }
    public function test_financial_localization_catalogs_have_the_stock_tracking_labels(): void
    {
        foreach (['main', 'fr', 'ar', 'pt', 'es', 'zh'] as $locale) {
            app()->setLocale($locale);
            foreach (['track_stock', 'track_stock_yes', 'track_stock_no', 'track_stock_unclassified'] as $key) {
                $this->assertNotSame('financial.screens.' . $key, __('financial.screens.' . $key));
            }
        }
        app()->setLocale('main');
    }

    private function inventoryPayload(string $trackStock): array
    {
        $branch = Branch::query()->firstOrFail();
        $type = InventoryType::query()->first();
        if (!$type) {
            $type = InventoryType::create(['name' => 'STOCK TRACKING TEST ' . uniqid()]);
        }

        return [
            'type_id' => $type->id,
            'branch_id' => $branch->id,
            'new_type' => '',
            'description' => 'STOCK TRACKING TEST ITEM ' . uniqid(),
            'sub_item' => 'Test only',
            'unit_of_measurement' => 'Pieces',
            'unit_cost' => '123.45',
            'sale_price' => '234.56',
            'quantity_at_hand' => '7',
            'reorder_level' => '1',
            'account' => 'Test Account',
            'track_stock' => $trackStock,
        ];
    }
}
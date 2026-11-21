<?php

namespace Tests\Feature;

use App\Http\Controllers\CustomerInvoiceController;
use App\Http\Controllers\SettingsController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerInvoiceFinancialSnapshotTest extends TestCase
{
    private $testDatabase;
    private $transactionStarted = false;

    protected function setUp(): void
    {
        if (!defined('AYU_CUSTOMER_INVOICE_STOCK_SAFETY_GATE_PASSED')
            || AYU_CUSTOMER_INVOICE_STOCK_SAFETY_GATE_PASSED !== true
            || getenv('APP_ENV') !== 'testing'
            || getenv('APP_RUNNING_IN_CONSOLE') !== 'true'
            || getenv('APP_CONFIG_CACHE') !== 'bootstrap/cache/config.php'
            || is_file(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap/cache/config.php')) {
            throw new \RuntimeException('Run this test only through its dedicated pre-bootstrap safety gate.');
        }

        parent::setUp();

        $this->testDatabase = getenv('AYU_TEST_DATABASE');
        $approvedDatabase = getenv('AYU_CUSTOMER_INVOICE_STOCK_APPROVED_DATABASE');
        $configuredDatabase = config('database.connections.mysql.database');
        if (!$this->testDatabase
            || $this->testDatabase !== $approvedDatabase
            || $this->testDatabase !== $configuredDatabase
            || !preg_match('/^ayuglobal_step6d1_test_[a-z0-9_]+$/i', $this->testDatabase)) {
            throw new \RuntimeException('AYU_TEST_DATABASE must match the independently approved dedicated database target.');
        }

        $this->assertApprovedLaravelDatabaseIdentity();
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');

        $this->createMinimalSchema();
        $this->prepareGeneralSettings();
        $this->runSnapshotMigrationWhenNeeded();
        $this->runDiscountMigrationWhenNeeded();
        DB::beginTransaction();
        $this->transactionStarted = true;

        DB::table('customers')->insert(['id' => 1, 'name' => 'Test Customer', 'deleted_at' => null]);
        DB::table('inventories')->insert([
            'id' => 1,
            'description' => 'Test item one',
            'sale_price' => '100.00',
            'quantity_at_hand' => 20,
            'deleted_at' => null,
        ]);
        DB::table('inventories')->insert([
            'id' => 2,
            'description' => 'Test item two',
            'sale_price' => '50.00',
            'quantity_at_hand' => 20,
            'deleted_at' => null,
        ]);
        DB::table('customer_payments')->insert([
            'id' => 1,
            'invoice_id' => 9001,
            'amount' => '12.34',
        ]);
        Config::set('settings.vat_percentage', 7.5);
        Config::set('settings.discount_enabled', false);
        Config::set('settings.discount_percentage', '0.00');
    }

    private function assertApprovedLaravelDatabaseIdentity(): void
    {
        $approved = [
            'database' => getenv('AYU_CUSTOMER_INVOICE_STOCK_APPROVED_DATABASE'),
            'host' => getenv('AYU_CUSTOMER_INVOICE_STOCK_APPROVED_HOST'),
            'port' => getenv('AYU_CUSTOMER_INVOICE_STOCK_APPROVED_PORT'),
            'username' => getenv('AYU_CUSTOMER_INVOICE_STOCK_APPROVED_USERNAME'),
            'server_hostname' => getenv('AYU_CUSTOMER_INVOICE_STOCK_APPROVED_SERVER_HOSTNAME'),
            'server_port' => getenv('AYU_CUSTOMER_INVOICE_STOCK_APPROVED_SERVER_PORT'),
            'authenticated_account' => getenv('AYU_CUSTOMER_INVOICE_STOCK_APPROVED_AUTHENTICATED_ACCOUNT'),
        ];
        $connection = config('database.connections.mysql');

        if (config('database.default') !== 'mysql'
            || !is_array($connection)
            || ($connection['driver'] ?? null) !== 'mysql'
            || (string) ($connection['host'] ?? '') !== (string) $approved['host']
            || (string) ($connection['port'] ?? '') !== (string) $approved['port']
            || (string) ($connection['database'] ?? '') !== (string) $approved['database']
            || (string) ($connection['username'] ?? '') !== (string) $approved['username']
            || trim((string) ($connection['unix_socket'] ?? '')) !== ''
            || (isset($connection['url']) && trim((string) $connection['url']) !== '')
            || (isset($connection['read']) && !empty($connection['read']))
            || (isset($connection['write']) && !empty($connection['write']))) {
            throw new \RuntimeException('Laravel effective database configuration does not match the approved MySQL target.');
        }

        DB::purge('mysql');
        $identity = DB::connection('mysql')->selectOne(
            'SELECT DATABASE() AS database_name, CURRENT_USER() AS authenticated_account, @@hostname AS server_hostname, @@port AS server_port'
        );

        if (!$identity
            || (string) ($identity->database_name ?? '') !== (string) $approved['database']
            || (string) ($identity->authenticated_account ?? '') !== (string) $approved['authenticated_account']
            || (string) ($identity->server_hostname ?? '') !== (string) $approved['server_hostname']
            || (string) ($identity->server_port ?? '') !== (string) $approved['server_port']) {
            throw new \RuntimeException('Laravel MySQL identity does not match the independently approved target.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_snapshot_migration_adds_nullable_fields_with_approved_types()
    {
        $columns = DB::select(
            "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_invoices' AND COLUMN_NAME IN ('subtotal','vat_percentage','vat_amount','total_due','discount_percentage','discount_amount','balance_due','financial_snapshot_version')"
        );
        $columns = collect($columns)->keyBy('COLUMN_NAME');

        $this->assertCount(8, $columns);
        $this->assertSame('decimal(15,2)', strtolower($columns['subtotal']->COLUMN_TYPE));
        $this->assertSame('decimal(5,2)', strtolower($columns['vat_percentage']->COLUMN_TYPE));
        $this->assertSame('decimal(15,2)', strtolower($columns['vat_amount']->COLUMN_TYPE));
        $this->assertSame('decimal(15,2)', strtolower($columns['total_due']->COLUMN_TYPE));
        $this->assertSame('decimal(5,2)', strtolower($columns['discount_percentage']->COLUMN_TYPE));
        $this->assertSame('decimal(15,2)', strtolower($columns['discount_amount']->COLUMN_TYPE));
        $this->assertSame('decimal(15,2)', strtolower($columns['balance_due']->COLUMN_TYPE));
        $this->assertSame('tinyint(3) unsigned', strtolower($columns['financial_snapshot_version']->COLUMN_TYPE));
        foreach ($columns as $column) {
            $this->assertSame('YES', $column->IS_NULLABLE);
        }
    }

    public function test_store_uses_inventory_price_and_persists_invoice_snapshot_without_changing_payments_or_exchange_rate()
    {
        $this->storeInvoice([
            ['item_id' => 1, 'description' => 'line one', 'rate' => '100.00', 'quantity' => 2, 'amount' => '999999.99'],
        ], '2.50');

        $invoice = DB::table('customer_invoices')->where('customer_id', 1)->first();
        $line = DB::table('customer_invoice_items')->where('invoice_id', $invoice->id)->first();

        $this->assertSame('100.00', $line->rate);
        $this->assertSame('200.00', $line->amount);
        $this->assertSame('200.00', $invoice->subtotal);
        $this->assertSame('7.50', $invoice->vat_percentage);
        $this->assertSame('15.00', $invoice->vat_amount);
        $this->assertSame('215.00', $invoice->total_due);
        $this->assertSame(2, (int) $invoice->financial_snapshot_version);
        $this->assertSame('200.00', $invoice->balance_due);
        $this->assertNull($invoice->discount_percentage);
        $this->assertNull($invoice->discount_amount);
        $this->assertSame('2.50', $invoice->exchange_rate);
        $this->assertSame(18, (int) DB::table('inventories')->where('id', 1)->value('quantity_at_hand'));
        $this->assertSame(1, DB::table('customer_payments')->count());
        $this->assertSame('12.34', DB::table('customer_payments')->where('id', 1)->value('amount'));
    }

    public function test_submitted_rate_mismatch_is_rejected_before_invoice_or_stock_changes()
    {
        try {
            $this->storeInvoice([
                ['item_id' => 1, 'description' => 'line one', 'rate' => '999999.00', 'quantity' => 1, 'amount' => '1.00'],
            ]);
            $this->fail('A client-submitted rate that differs from Inventory.sale_price must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertSame(0, DB::table('customer_invoices')->count());
        $this->assertSame(20, (int) DB::table('inventories')->where('id', 1)->value('quantity_at_hand'));
    }

    public function test_multiple_lines_calculate_subtotal_vat_and_total_due_from_server_prices()
    {
        $this->storeInvoice([
            ['item_id' => 1, 'description' => 'line one', 'rate' => '100.00', 'quantity' => 2, 'amount' => '0.00'],
            ['item_id' => 2, 'description' => 'line two', 'rate' => '50.00', 'quantity' => 1, 'amount' => '50000.00'],
        ]);

        $invoice = DB::table('customer_invoices')->first();
        $this->assertSame('250.00', $invoice->subtotal);
        $this->assertSame('18.75', $invoice->vat_amount);
        $this->assertSame('268.75', $invoice->total_due);
        $this->assertSame(2, DB::table('customer_invoice_items')->count());
    }

    public function test_duplicate_inventory_lines_cannot_oversell_combined_available_stock()
    {
        DB::table('inventories')->where('id', 1)->update(['quantity_at_hand' => 5]);

        try {
            $this->storeInvoice([
                ['item_id' => 1, 'description' => 'line one', 'rate' => '100.00', 'quantity' => 4, 'amount' => '400.00'],
                ['item_id' => 1, 'description' => 'line two', 'rate' => '100.00', 'quantity' => 4, 'amount' => '400.00'],
            ]);
            $this->fail('Combined duplicate-item quantity above available stock must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertSame(0, DB::table('customer_invoices')->count());
        $this->assertSame(5, (int) DB::table('inventories')->where('id', 1)->value('quantity_at_hand'));
    }

    public function test_duplicate_inventory_lines_within_stock_are_preserved_and_deducted_by_combined_quantity()
    {
        DB::table('inventories')->where('id', 1)->update(['quantity_at_hand' => 5]);

        $this->storeInvoice([
            ['item_id' => 1, 'description' => 'line one', 'rate' => '100.00', 'quantity' => 2, 'amount' => '0.00'],
            ['item_id' => 1, 'description' => 'line two', 'rate' => '100.00', 'quantity' => 3, 'amount' => '999999.99'],
        ]);

        $invoice = DB::table('customer_invoices')->first();
        $this->assertSame(2, DB::table('customer_invoice_items')->where('invoice_id', $invoice->id)->count());
        $this->assertSame(0, (int) DB::table('inventories')->where('id', 1)->value('quantity_at_hand'));
    }

    public function test_discounted_invoice_uses_total_after_vat_and_ignores_browser_calculated_values()
    {
        Config::set('settings.discount_enabled', true);
        Config::set('settings.discount_percentage', '10.00');
        DB::table('inventories')->where('id', 1)->update(['sale_price' => '100000.00']);
        $this->storeInvoice([
            ['item_id' => 1, 'description' => 'line one', 'rate' => '100000.00', 'quantity' => 1, 'amount' => '1.00'],
        ], '1.00', [
            'discount_percentage' => '90', 'discount_amount' => '1.00', 'subtotal' => '1.00',
            'vat_amount' => '0.00', 'total_due' => '1.00', 'balance_due' => '1.00',
        ]);

        $invoice = DB::table('customer_invoices')->first();
        $this->assertSame('100000.00', $invoice->subtotal);
        $this->assertSame('7500.00', $invoice->vat_amount);
        $this->assertSame('107500.00', $invoice->total_due);
        $this->assertSame('10.00', $invoice->discount_percentage);
        $this->assertSame('10750.00', $invoice->discount_amount);
        $this->assertSame('96750.00', $invoice->balance_due);
        $this->assertSame(2, (int) $invoice->financial_snapshot_version);
    }

    public function test_zero_configured_percentage_is_persisted_when_discounts_are_enabled()
    {
        Config::set('settings.discount_enabled', true);
        Config::set('settings.discount_percentage', '0.00');
        $this->storeInvoice([
            ['item_id' => 1, 'description' => 'line one', 'rate' => '100.00', 'quantity' => 1, 'amount' => '0.00'],
        ], '1.00', ['discount_percentage' => '99']);

        $invoice = DB::table('customer_invoices')->first();
        $this->assertSame('107.50', $invoice->total_due);
        $this->assertSame('0.00', $invoice->discount_percentage);
        $this->assertSame('0.00', $invoice->discount_amount);
        $this->assertSame('107.50', $invoice->balance_due);
    }

    public function test_general_settings_rejects_invalid_enabled_discount_percentage()
    {
        $before = DB::table('settings')->where('settings_name', 'General Settings')->value('content');
        $payload = [
            'company_name' => 'AYUGLOBAL', 'vat_percentage' => '7.50',
            'wht_enabled' => '0', 'wht_mode' => 'manual', 'wht_manual_rate' => '0.00',
            'discount_enabled' => '1', 'discount_percentage' => '100.01',
        ];

        try {
            app(SettingsController::class)->general_update(Request::create('/', 'POST', $payload));
            $this->fail('An excessive discount percentage must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('discount_percentage', $exception->errors());
        }

        $this->assertSame($before, DB::table('settings')->where('settings_name', 'General Settings')->value('content'));
    }

    public function test_general_settings_persists_discount_rate_and_preserves_it_when_disabled()
    {
        $payload = [
            'company_name' => 'AYUGLOBAL', 'vat_percentage' => '7.50',
            'wht_enabled' => '0', 'wht_mode' => 'manual', 'wht_manual_rate' => '0.00',
            'discount_enabled' => '1', 'discount_percentage' => '10',
        ];
        app(SettingsController::class)->general_update(Request::create('/', 'POST', $payload));
        $saved = json_decode(DB::table('settings')->where('settings_name', 'General Settings')->value('content'), true);
        $this->assertTrue($saved['discount_enabled']);
        $this->assertSame('10.00', $saved['discount_percentage']);

        $payload['discount_enabled'] = '0';
        unset($payload['discount_percentage']);
        app(SettingsController::class)->general_update(Request::create('/', 'POST', $payload));
        $saved = json_decode(DB::table('settings')->where('settings_name', 'General Settings')->value('content'), true);
        $this->assertFalse($saved['discount_enabled']);
        $this->assertSame('10.00', $saved['discount_percentage']);
    }

    public function test_disabled_discount_attack_is_ignored_and_not_stored()
    {
        Config::set('settings.discount_enabled', false);
        Config::set('settings.discount_percentage', '0.00');
        $this->storeInvoice([
            ['item_id' => 1, 'description' => 'line one', 'rate' => '100.00', 'quantity' => 1, 'amount' => '0.00'],
        ], '1.00', [
            'discount_percentage' => '10', 'discount_amount' => '1.00', 'total_due' => '1.00', 'balance_due' => '1.00',
        ]);

        $invoice = DB::table('customer_invoices')->first();
        $this->assertSame('100.00', $invoice->total_due);
        $this->assertSame('100.00', $invoice->balance_due);
        $this->assertNull($invoice->discount_percentage);
        $this->assertNull($invoice->discount_amount);
    }

    public function test_later_vat_setting_change_does_not_change_saved_snapshot()
    {
        $this->storeInvoice([
            ['item_id' => 1, 'description' => 'line one', 'rate' => '100.00', 'quantity' => 1, 'amount' => '100.00'],
        ]);
        $invoice = DB::table('customer_invoices')->first();

        Config::set('settings.vat_percentage', 20);
        Config::set('settings.discount_enabled', true);
        $reloaded = DB::table('customer_invoices')->where('id', $invoice->id)->first();

        $this->assertSame('7.50', $reloaded->vat_percentage);
        $this->assertSame('7.50', $reloaded->vat_amount);
        $this->assertSame('107.50', $reloaded->total_due);
        $this->assertSame('107.50', $reloaded->balance_due);
        $this->assertNull($reloaded->discount_percentage);
        $this->assertNull($reloaded->discount_amount);
    }

    public function test_legacy_invoice_with_null_snapshot_fields_remains_valid_and_unchanged()
    {
        $id = DB::table('customer_invoices')->insertGetId([
            'customer_id' => 1,
            'invoice_number' => 'LEGACY-1',
            'invoice_date' => '2025-01-01',
            'exchange_rate' => '1.00',
            'subtotal' => null,
            'vat_percentage' => null,
            'vat_amount' => null,
            'total_due' => null,
            'financial_snapshot_version' => null,
        ]);

        $legacyInvoice = DB::table('customer_invoices')->where('id', $id)->first();
        $this->assertNull($legacyInvoice->subtotal);
        $this->assertNull($legacyInvoice->vat_percentage);
        $this->assertNull($legacyInvoice->vat_amount);
        $this->assertNull($legacyInvoice->total_due);
        $this->assertNull($legacyInvoice->financial_snapshot_version);
    }

    public function test_migration_rollback_refuses_to_remove_existing_snapshot_data()
    {
        DB::table('customer_invoices')->insert([
            'customer_id' => 1,
            'invoice_number' => 'SNAPSHOT-1',
            'invoice_date' => '2026-09-24',
            'exchange_rate' => '1.00',
            'subtotal' => '100.00',
            'vat_percentage' => '7.50',
            'vat_amount' => '7.50',
            'total_due' => '107.50',
            'financial_snapshot_version' => 1,
        ]);

        try {
            $migration = require base_path('database/migrations/2026_09_24_000003_add_financial_snapshot_fields_to_customer_invoices.php');
            $migration->down();
            $this->fail('Rollback must not discard issued financial snapshots.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('snapshot data exists', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('customer_invoices', 'total_due'));
    }

    private function storeInvoice(array $items, $exchangeRate = '1.00', array $extra = [])
    {
        $request = Request::create('/', 'POST', array_merge([
            'customer_id' => 1,
            'invoice_date' => '2026-09-24',
            'exchange_rate' => $exchangeRate,
            'items' => $items,
        ], $extra));

        $result = app(CustomerInvoiceController::class)->store($request);
        if ($result instanceof \Illuminate\Http\RedirectResponse) {
            throw new \RuntimeException($result->getSession()->get('error', 'Invoice creation returned an unexpected redirect.'));
        }

        return $result;
    }

    private function createMinimalSchema()
    {
        if (!Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('inventories')) {
            Schema::create('inventories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->text('description')->nullable();
                $table->decimal('sale_price', 15, 2);
                $table->integer('quantity_at_hand');
                $table->timestamps();
                $table->softDeletes();
            });
        } elseif (!Schema::hasColumn('inventories', 'updated_at')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('customer_invoices')) {
            Schema::create('customer_invoices', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('customer_id');
                $table->string('invoice_number')->unique();
                $table->date('invoice_date');
                $table->decimal('exchange_rate', 8, 2);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('customer_invoice_items')) {
            Schema::create('customer_invoice_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('item_id');
                $table->string('description')->nullable();
                $table->decimal('rate', 10, 2);
                $table->integer('quantity');
                $table->decimal('amount', 10, 2);
                $table->timestamps();
                $table->softDeletes();
            });
        }
        if (!Schema::hasTable('customer_payments')) {
            Schema::create('customer_payments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('invoice_id');
                $table->decimal('amount', 15, 2);
            });
        }
        if (!Schema::hasTable('invoice_sequences')) {
            Schema::create('invoice_sequences', function (Blueprint $table) {
                $table->string('document_type', 32)->primary();
                $table->unsignedInteger('current_number')->default(0);
                $table->timestamps();
            });
        }
    }

    private function runSnapshotMigrationWhenNeeded()
    {
        if (Schema::hasColumn('customer_invoices', 'subtotal')) {
            return;
        }

        $migration = require base_path('database/migrations/2026_09_24_000003_add_financial_snapshot_fields_to_customer_invoices.php');
        $migration->up();
    }

    private function runDiscountMigrationWhenNeeded()
    {
        if (Schema::hasColumn('customer_invoices', 'discount_percentage')
            && Schema::hasColumn('customer_invoices', 'discount_amount')
            && Schema::hasColumn('customer_invoices', 'balance_due')) {
            return;
        }

        $migration = require base_path('database/migrations/2026_09_25_000002_add_customer_invoice_discount_snapshot.php');
        $migration->up();
    }

    private function prepareGeneralSettings()
    {
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('settings_name')->nullable();
                $table->longText('content');
                $table->decimal('vat_percentage', 5, 2)->default(0);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('settings', 'vat_percentage')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->decimal('vat_percentage', 5, 2)->default(0);
            });
        }

        DB::table('settings')->where('settings_name', 'General Settings')->delete();
        DB::table('settings')->insert([
            'settings_name' => 'General Settings',
            'content' => json_encode(['vat_percentage' => '7.50', 'discount_enabled' => false, 'discount_percentage' => '0.00']),
            'vat_percentage' => '7.50',
        ]);
    }
}

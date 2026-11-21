<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BranchInventoryTenantOwnershipMigrationTest extends TestCase
{
    private const DATABASE = 'ayuglobal_saas_t01_test';
    private const MYSQL_USER = 'ayu_saas_t01_test_user@127.0.0.1';
    private const MIGRATION = 'AddTenantOwnershipToBranchesInventoryTypesAndInventories';

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame(self::DATABASE, config('database.connections.mysql.database'));
        $this->assertSame(self::DATABASE, DB::connection('mysql')->getDatabaseName());
        $this->assertSame(self::MYSQL_USER, DB::connection('mysql')->selectOne('SELECT CURRENT_USER() AS user')->user);
    }

    public function test_authorized_database_has_nullable_tenant_ownership_and_preserved_baseline_data(): void
    {
        $db = DB::connection('mysql');
        $this->assertSame(self::DATABASE, $db->selectOne('SELECT DATABASE() AS db')->db);
        $tenant = $db->table('tenants')->where('id', 1)->first();
        $this->assertNotNull($tenant);
        $this->assertSame('AYUGLOBAL', $tenant->name);
        $this->assertSame('ayuglobal', $tenant->slug);
        $this->assertSame('active', $tenant->status);

        $tables = ['branches', 'inventory_types', 'inventories'];
        foreach ($tables as $table) {
            $column = $db->table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', self::DATABASE)
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', 'tenant_id')
                ->first();
            $tenantColumn = $db->table('information_schema.COLUMNS')
                ->where('TABLE_SCHEMA', self::DATABASE)
                ->where('TABLE_NAME', 'tenants')
                ->where('COLUMN_NAME', 'id')
                ->first();

            $this->assertNotNull($column, $table . '.tenant_id is missing');
            $this->assertSame($tenantColumn->COLUMN_TYPE, $column->COLUMN_TYPE);
            $this->assertSame('YES', $column->IS_NULLABLE);
            $this->assertSame($db->table($table)->count(), $db->table($table)->where('tenant_id', 1)->count());
            $this->assertSame(0, $db->table($table)->whereNull('tenant_id')->count());

            $this->assertTrue($db->table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', self::DATABASE)
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', $table . '_tenant_id_index')
                ->exists());

            $foreignKey = $db->table('information_schema.KEY_COLUMN_USAGE as k')
                ->join('information_schema.REFERENTIAL_CONSTRAINTS as r', function ($join) {
                    $join->on('r.CONSTRAINT_SCHEMA', '=', 'k.CONSTRAINT_SCHEMA')
                        ->on('r.CONSTRAINT_NAME', '=', 'k.CONSTRAINT_NAME');
                })
                ->where('k.CONSTRAINT_SCHEMA', self::DATABASE)
                ->where('k.TABLE_NAME', $table)
                ->where('k.COLUMN_NAME', 'tenant_id')
                ->where('k.REFERENCED_TABLE_NAME', 'tenants')
                ->first(['k.CONSTRAINT_NAME', 'k.REFERENCED_COLUMN_NAME', 'r.DELETE_RULE', 'r.UPDATE_RULE']);

            $this->assertNotNull($foreignKey);
            $this->assertSame($table . '_tenant_id_foreign', $foreignKey->CONSTRAINT_NAME);
            $this->assertSame('id', $foreignKey->REFERENCED_COLUMN_NAME);
            $this->assertSame('RESTRICT', strtoupper($foreignKey->DELETE_RULE));
            $this->assertSame('CASCADE', strtoupper($foreignKey->UPDATE_RULE));
        }

        $this->assertSame([1, 2, 3, 4], $db->table('branches')->orderBy('id')->pluck('id')->map(function ($id) { return (int) $id; })->all());
        $this->assertSame([1, 2, 3, 4, 5, 6, 7], $db->table('inventory_types')->orderBy('id')->pluck('id')->map(function ($id) { return (int) $id; })->all());
        $this->assertSame([1, 2, 3, 4, 5, 6, 7, 8, 9], $db->table('inventories')->orderBy('id')->pluck('id')->map(function ($id) { return (int) $id; })->all());
        $this->assertSame(4, $db->table('branches')->count());
        $this->assertSame(7, $db->table('inventory_types')->count());
        $this->assertSame(9, $db->table('inventories')->count());

        $this->assertSame(['Abuja Head Office', 'Lagos Branch', 'Kano Branch', 'Ibadan'],
            $db->table('branches')->orderBy('id')->pluck('name')->all());
        $this->assertSame([
            'Building Materials', 'Office Supplies', 'Computers', 'Bags', 'Phones', 'Consulting',
            'Goods Received Test Item - Ibadan',
        ], $db->table('inventory_types')->orderBy('id')->pluck('name')->all());

        $inventoryBaseline = [
            [1, 1, 1, 'Premium cement', 105, '6500.00', '7500.00'],
            [2, 1, 1, 'Reinforcement steel', 69, '4500.00', '5200.00'],
            [3, 2, 1, 'A4 paper', 34, '7000.00', '8500.00'],
            [4, 3, 1, 'laptops', 7, '15000.00', '17000.00'],
            [5, 3, 4, 'Hard drive', 15, '8000.00', '10000.00'],
            [6, 4, 4, 'Training Bags', 30, '6500.00', '7800.00'],
            [7, 5, 1, 'iPhone', 20, '3000.00', '4000.00'],
            [8, 6, 1, 'Training Services Local', 35, '7000.00', '8500.00'],
            [9, 7, 4, 'Goods Received Test Item - Ibadan', 1, '1000.00', '1000.00'],
        ];
        $actualInventory = $db->table('inventories')->orderBy('id')->get([
            'id', 'type_id', 'branch_id', 'description', 'quantity_at_hand', 'unit_cost', 'sale_price',
        ])->map(function ($row) {
            return [(int) $row->id, (int) $row->type_id, (int) $row->branch_id, $row->description,
                (int) $row->quantity_at_hand, (string) $row->unit_cost, (string) $row->sale_price];
        })->all();
        $this->assertSame($inventoryBaseline, $actualInventory);

        $baselineHashes = [
            'branches' => '9b8b205ec89926993e3fba2bf787b6dacd1b88f7bbd53d8f1fe9f13b21f3256d',
            'inventory_types' => '634a50634a6d2210725017a5f1e933a98e3b7ec7dd3b8a0da7c3415e5eda1232',
            'inventories' => '62c5d60c1410ec1f1790939df0cacc127646b05ab402f172da36b1579364d44a',
        ];
        foreach ($baselineHashes as $table => $expectedHash) {
            $rows = $db->table($table)->orderBy('id')->get()->map(function ($row) {
                $record = (array) $row;
                unset($record['tenant_id']);
                return $record;
            })->all();
            $this->assertSame($expectedHash, hash('sha256', json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)), $table . ' business fields changed');
        }

        foreach ([
            ['branches', 'branches_name_unique'],
            ['inventory_types', 'inventory_types_name_unique'],
        ] as [$table, $index]) {
            $this->assertTrue($db->table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', self::DATABASE)->where('TABLE_NAME', $table)->where('INDEX_NAME', $index)
                ->where('NON_UNIQUE', 0)->exists());
        }

        foreach ([
            ['inventories_type_id_foreign', 'type_id', 'inventory_types', 'SET NULL', 'RESTRICT'],
            ['inventories_branch_id_foreign', 'branch_id', 'branches', 'SET NULL', 'RESTRICT'],
        ] as [$name, $column, $parent, $delete, $update]) {
            $fk = $db->table('information_schema.KEY_COLUMN_USAGE as k')
                ->join('information_schema.REFERENTIAL_CONSTRAINTS as r', function ($join) {
                    $join->on('r.CONSTRAINT_SCHEMA', '=', 'k.CONSTRAINT_SCHEMA')
                        ->on('r.CONSTRAINT_NAME', '=', 'k.CONSTRAINT_NAME');
                })
                ->where('k.CONSTRAINT_SCHEMA', self::DATABASE)->where('k.TABLE_NAME', 'inventories')
                ->where('k.CONSTRAINT_NAME', $name)->where('k.COLUMN_NAME', $column)
                ->first(['k.REFERENCED_TABLE_NAME', 'r.DELETE_RULE', 'r.UPDATE_RULE']);
            $this->assertNotNull($fk);
            $this->assertSame($parent, $fk->REFERENCED_TABLE_NAME);
            $this->assertSame($delete, strtoupper($fk->DELETE_RULE));
            $this->assertSame($update, strtoupper($fk->UPDATE_RULE));
        }

        foreach (['customer_invoice_items', 'goods_received_items', 'purchase_order_items', 'supplier_invoice_items', 'vendor_invoice_items'] as $child) {
            $this->assertFalse(Schema::connection('mysql')->hasColumn($child, 'tenant_id'), $child . ' must remain parent-inherited');
        }

        $this->assertSame(0, $db->table('inventories as i')->leftJoin('branches as b', 'b.id', '=', 'i.branch_id')
            ->whereNotNull('i.branch_id')->whereNull('b.id')->count());
        $this->assertSame(0, $db->table('inventories as i')->leftJoin('inventory_types as t', 't.id', '=', 'i.type_id')
            ->whereNotNull('i.type_id')->whereNull('t.id')->count());
        $this->assertSame(0, $db->table('inventories as i')->join('branches as b', 'b.id', '=', 'i.branch_id')
            ->whereNotNull('i.branch_id')->whereColumn('i.tenant_id', '<>', 'b.tenant_id')->count());
        $this->assertSame(0, $db->table('inventories as i')->join('inventory_types as t', 't.id', '=', 'i.type_id')
            ->whereNotNull('i.type_id')->whereColumn('i.tenant_id', '<>', 't.tenant_id')->count());
    }

    public function test_migration_definition_updates_all_rows_and_has_a_guarded_rollback(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_10_07_000003_add_tenant_ownership_to_branches_inventory_types_and_inventories.php'));
        $this->assertStringContainsString('private $tables = [\'branches\', \'inventory_types\', \'inventories\']', $migration);
        $this->assertStringContainsString("unsignedBigInteger('tenant_id')->nullable()", $migration);
        $this->assertStringContainsString('$table . \'_tenant_id_index\'', $migration);
        $this->assertStringContainsString('$table . \'_tenant_id_foreign\'', $migration);
        $this->assertStringContainsString("->onDelete('restrict')", $migration);
        $this->assertStringContainsString("->onUpdate('cascade')", $migration);
        $this->assertStringContainsString('DB::table($table)->whereNull(\'tenant_id\')->update([\'tenant_id\' => $tenant->id])', $migration);
        $this->assertStringNotContainsString("whereNull('deleted_at')", $migration);
        $this->assertStringContainsString('array_reverse($this->tables)', $migration);
        $this->assertStringContainsString('$blueprint->dropColumn(\'tenant_id\')', $migration);
    }
}

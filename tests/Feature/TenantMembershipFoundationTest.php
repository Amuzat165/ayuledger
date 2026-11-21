<?php

namespace Tests\Feature;

use App\Tenant;
use App\TenantMembership;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantMembershipFoundationTest extends TestCase
{
    private const DATABASE = 'ayuglobal_saas_t01_test';
    private const MYSQL_USER = 'ayu_saas_t01_test_user@127.0.0.1';

    private const BUSINESS_COUNTS = [
        'branches' => 4,
        'customers' => 5,
        'vendors' => 4,
        'inventories' => 9,
        'customer_invoices' => 12,
        'customer_payments' => 7,
        'customer_payment_receipts' => 5,
        'purchase_orders' => 2,
        'goods_receipts' => 2,
        'supplier_invoices' => 1,
        'supplier_invoice_payments' => 1,
        'transactions' => 4,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame(self::DATABASE, getenv('DB_DATABASE'));
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame(self::DATABASE, config('database.connections.mysql.database'));
        $this->assertSame(self::DATABASE, DB::connection('mysql')->getDatabaseName());
        $this->assertSame(self::MYSQL_USER, DB::connection('mysql')->selectOne('SELECT CURRENT_USER() AS user')->user);
        $this->assertTrue(Schema::connection('mysql')->hasTable('tenants'));
        $this->assertTrue(Schema::connection('mysql')->hasTable('tenant_memberships'));

        DB::connection('mysql')->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (DB::connection('mysql')->transactionLevel() > 0) {
            DB::connection('mysql')->rollBack();
        }

        parent::tearDown();
    }

    public function test_bootstrap_relationships_and_business_counts_are_intact(): void
    {
        $tenant = Tenant::on('mysql')->where('slug', 'ayuglobal')->firstOrFail();
        $this->assertSame(1, (int) $tenant->id);
        $this->assertSame('AYUGLOBAL', $tenant->name);
        $this->assertSame('ayuglobal', $tenant->slug);
        $this->assertSame('active', $tenant->status);
        $this->assertSame(1, Tenant::on('mysql')->where('slug', 'ayuglobal')->count());
        $this->assertSame(1, $tenant->memberships()->count());
        $this->assertSame(1, DB::connection('mysql')->table('tenant_memberships')->count());

        $membership = TenantMembership::on('mysql')->where('tenant_id', $tenant->id)->firstOrFail();
        $user = DB::connection('mysql')->table('users')->where('deleted_at', null)->first();
        $this->assertNotNull($user);
        $this->assertSame(1, (int) $user->id);
        $this->assertSame(1, (int) $user->role_manage_id);
        $this->assertSame((int) $user->id, (int) $membership->user_id);
        $this->assertSame((int) $user->role_manage_id, (int) $membership->role_manage_id);
        $this->assertSame('active', $membership->status);
        $this->assertSame(1, $tenant->users()->count());
        $this->assertSame((int) $tenant->id, (int) $membership->tenant->id);
        $this->assertSame((int) $user->id, (int) $membership->user->id);
        $this->assertSame((int) $user->role_manage_id, (int) $membership->roleManage->id);

        $this->assertSame(1, DB::connection('mysql')->table('users')->count());
        $this->assertSame(1, DB::connection('mysql')->table('role_manages')->count());
        foreach (self::BUSINESS_COUNTS as $table => $expectedCount) {
            $this->assertSame($expectedCount, DB::connection('mysql')->table($table)->count(), $table . ' count changed');
        }
    }

    public function test_unique_slug_and_membership_constraints_reject_duplicates(): void
    {
        $tenant = DB::connection('mysql')->table('tenants')->where('slug', 'ayuglobal')->first();
        $membership = DB::connection('mysql')->table('tenant_memberships')->where('tenant_id', $tenant->id)->first();

        try {
            DB::connection('mysql')->table('tenants')->insert([
                'name' => 'Duplicate slug', 'slug' => 'ayuglobal', 'status' => 'active',
            ]);
            $this->fail('Duplicate tenant slug was accepted.');
        } catch (QueryException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }

        try {
            DB::connection('mysql')->table('tenant_memberships')->insert([
                'tenant_id' => $membership->tenant_id,
                'user_id' => $membership->user_id,
                'role_manage_id' => $membership->role_manage_id,
                'status' => 'active',
            ]);
            $this->fail('Duplicate tenant membership was accepted.');
        } catch (QueryException $exception) {
            $this->assertNotSame('', $exception->getMessage());
        }
    }

    public function test_foreign_keys_reject_orphan_rows_and_enforce_restrict_and_cascade(): void
    {
        $foreignKeys = DB::connection('mysql')->table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', self::DATABASE)
            ->where('TABLE_NAME', 'tenant_memberships')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->get(['COLUMN_NAME', 'REFERENCED_TABLE_NAME', 'REFERENCED_COLUMN_NAME'])
            ->mapWithKeys(function ($foreignKey) {
                return [$foreignKey->COLUMN_NAME => [
                    $foreignKey->REFERENCED_TABLE_NAME,
                    $foreignKey->REFERENCED_COLUMN_NAME,
                ]];
            })
            ->all();
        ksort($foreignKeys);
        $this->assertSame([
            'role_manage_id' => ['role_manages', 'id'],
            'tenant_id' => ['tenants', 'id'],
            'user_id' => ['users', 'id'],
        ], $foreignKeys);

        foreach ([
            ['tenant_id' => 999999, 'user_id' => 1, 'role_manage_id' => 1],
            ['tenant_id' => 1, 'user_id' => 999999, 'role_manage_id' => 1],
            ['tenant_id' => 1, 'user_id' => 1, 'role_manage_id' => 999999],
        ] as $attributes) {
            try {
                DB::connection('mysql')->table('tenant_memberships')->insert($attributes + ['status' => 'active']);
                $this->fail('An orphan tenant membership was accepted.');
            } catch (QueryException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }

        foreach ([
            ['tenants', 1],
            ['users', 1],
            ['role_manages', 1],
        ] as [$table, $id]) {
            try {
                DB::connection('mysql')->table($table)->where('id', $id)->delete();
                $this->fail('ON DELETE RESTRICT was not enforced for ' . $table . '.');
            } catch (QueryException $exception) {
                $this->assertNotSame('', $exception->getMessage());
            }
        }

        $constraints = DB::connection('mysql')->table('information_schema.REFERENTIAL_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', self::DATABASE)
            ->where('TABLE_NAME', 'tenant_memberships')
            ->get(['CONSTRAINT_NAME', 'DELETE_RULE', 'UPDATE_RULE']);
        $this->assertCount(3, $constraints);
        foreach ($constraints as $constraint) {
            $this->assertSame('RESTRICT', strtoupper($constraint->DELETE_RULE));
            $this->assertSame('CASCADE', strtoupper($constraint->UPDATE_RULE));
        }

        DB::connection('mysql')->table('tenants')->where('id', 1)->update(['id' => 1001]);
        $membership = DB::connection('mysql')->table('tenant_memberships')->first();
        $this->assertSame(1001, (int) $membership->tenant_id);
        $this->assertSame(1, (int) $membership->user_id);
        $this->assertSame(1, (int) $membership->role_manage_id);
    }
}

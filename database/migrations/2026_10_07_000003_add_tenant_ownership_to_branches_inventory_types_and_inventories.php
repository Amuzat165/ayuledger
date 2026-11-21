<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTenantOwnershipToBranchesInventoryTypesAndInventories extends Migration
{
    private $tables = ['branches', 'inventory_types', 'inventories'];

    public function up()
    {
        foreach (array_merge(['tenants'], $this->tables) as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException('Tenant ownership migration prerequisite table is missing: ' . $table);
            }
        }

        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'tenant_id')) {
                throw new RuntimeException('Tenant ownership migration stopped because tenant_id already exists on ' . $table . '.');
            }
        }

        $tenant = DB::table('tenants')
            ->where('name', 'AYUGLOBAL')
            ->where('slug', 'ayuglobal')
            ->where('status', 'active')
            ->first();

        if (!$tenant || DB::table('tenants')->where('slug', 'ayuglobal')->count() !== 1) {
            throw new RuntimeException('Tenant ownership migration requires one active AYUGLOBAL tenant.');
        }

        $orphanBranch = DB::table('inventories as i')
            ->leftJoin('branches as b', 'b.id', '=', 'i.branch_id')
            ->whereNotNull('i.branch_id')
            ->whereNull('b.id')
            ->exists();
        $orphanType = DB::table('inventories as i')
            ->leftJoin('inventory_types as t', 't.id', '=', 'i.type_id')
            ->whereNotNull('i.type_id')
            ->whereNull('t.id')
            ->exists();

        if ($orphanBranch || $orphanType) {
            throw new RuntimeException('Tenant ownership migration stopped because an inventory branch or type reference is orphaned.');
        }

        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedBigInteger('tenant_id')->nullable();
                $blueprint->index('tenant_id', $table . '_tenant_id_index');
                $blueprint->foreign('tenant_id', $table . '_tenant_id_foreign')
                    ->references('id')->on('tenants')
                    ->onDelete('restrict')
                    ->onUpdate('cascade');
            });
        }

        foreach ($this->tables as $table) {
            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);

            if (DB::table($table)->whereNull('tenant_id')->exists()
                || DB::table($table)->where('tenant_id', '<>', $tenant->id)->exists()) {
                throw new RuntimeException('Tenant ownership backfill did not assign every ' . $table . ' row to Tenant #1.');
            }
        }
    }

    public function down()
    {
        $tenant = DB::table('tenants')
            ->where('name', 'AYUGLOBAL')
            ->where('slug', 'ayuglobal')
            ->first();

        if (!$tenant) {
            throw new RuntimeException('Tenant ownership rollback stopped because AYUGLOBAL cannot be identified.');
        }

        foreach ($this->tables as $table) {
            if (!Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            if (DB::table($table)->whereNull('tenant_id')->exists()
                || DB::table($table)->where('tenant_id', '<>', $tenant->id)->exists()) {
                throw new RuntimeException('Tenant ownership rollback stopped because ' . $table . ' contains ownership outside Tenant #1 or NULL.');
            }
        }

        foreach (array_reverse($this->tables) as $table) {
            if (!Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->dropForeign($table . '_tenant_id_foreign');
                $blueprint->dropIndex($table . '_tenant_id_index');
                $blueprint->dropColumn('tenant_id');
            });
        }
    }
}

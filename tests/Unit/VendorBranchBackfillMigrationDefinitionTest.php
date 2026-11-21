<?php

namespace Tests\Unit;

use Tests\TestCase;

class VendorBranchBackfillMigrationDefinitionTest extends TestCase
{
    public function test_backfill_is_transactional_guarded_and_limited_to_verified_vendor_ids(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_09_24_000002_assign_existing_vendors_to_abuja_head_office.php'));

        $this->assertStringContainsString('DB::transaction(function ()', $migration);
        $this->assertStringContainsString("->where('name', 'Abuja Head Office')", $migration);
        $this->assertStringContainsString('$branches->count() !== 1', $migration);
        $this->assertStringContainsString('(int) $branches->first()->id !== 1', $migration);
        $this->assertStringContainsString('$vendors->count() !== 2', $migration);
        $this->assertStringContainsString("1 => 'BuildRight Materials'", $migration);
        $this->assertStringContainsString("2 => 'Office World Nigeria'", $migration);
        $this->assertStringContainsString('$vendor->branch_id !== null', $migration);
        $this->assertStringContainsString("->whereIn('id', [1, 2])", $migration);
        $this->assertStringContainsString('->update([\'branch_id\' => $branchId])', $migration);
        $this->assertStringContainsString('$updated !== 2', $migration);
        $this->assertStringContainsString('throw new LogicException(', $migration);
        $this->assertStringNotContainsString("->update(['name'", $migration);
        $this->assertStringNotContainsString("->update(['branch_id' => null])", $migration);
    }
}

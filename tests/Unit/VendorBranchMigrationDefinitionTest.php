<?php

namespace Tests\Unit;

use Tests\TestCase;

class VendorBranchMigrationDefinitionTest extends TestCase
{
    public function test_migration_defines_nullable_unsigned_integer_and_set_null_branch_foreign_key(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_09_24_000001_add_branch_id_to_vendors_table.php'));

        $this->assertStringContainsString('$table->unsignedInteger(\'branch_id\')->nullable()', $migration);
        $this->assertStringContainsString('$table->foreign(\'branch_id\')', $migration);
        $this->assertStringContainsString('->references(\'id\')', $migration);
        $this->assertStringContainsString('->on(\'branches\')', $migration);
        $this->assertStringContainsString('->onDelete(\'set null\')', $migration);
        $this->assertStringContainsString('$table->dropForeign([\'branch_id\'])', $migration);
        $this->assertStringContainsString('$table->dropColumn(\'branch_id\')', $migration);
        $this->assertStringNotContainsString('UPDATE vendors', strtoupper($migration));
        $this->assertStringNotContainsString('DELETE FROM vendors', strtoupper($migration));
    }
}

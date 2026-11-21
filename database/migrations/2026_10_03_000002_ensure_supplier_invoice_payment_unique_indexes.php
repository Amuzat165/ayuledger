<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureSupplierInvoicePaymentUniqueIndexes extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('supplier_invoice_payments')) {
            throw new RuntimeException('Supplier Invoice Payment table must exist before its unique indexes can be ensured.');
        }

        $this->ensureUniqueIndex('payment_reference', 'sip_payment_reference_uq');
        $this->ensureUniqueIndex('submission_token', 'sip_submission_token_uq');
    }

    private function ensureUniqueIndex(string $column, string $name): void
    {
        $database = DB::connection()->getDatabaseName();
        $exists = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', 'supplier_invoice_payments')
            ->where('INDEX_NAME', $name)
            ->where('NON_UNIQUE', 0)
            ->exists();
        if (!$exists) {
            Schema::table('supplier_invoice_payments', function (Blueprint $table) use ($column, $name) {
                $table->unique($column, $name);
            });
        }
    }

    public function down()
    {
        // Preserve uniqueness if the index was created by the initial table migration.
    }
}

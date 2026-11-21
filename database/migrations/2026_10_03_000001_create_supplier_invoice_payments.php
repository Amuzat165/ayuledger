<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSupplierInvoicePayments extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('supplier_invoice_payments')) {
            Schema::create('supplier_invoice_payments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('supplier_invoice_id');
                $table->string('payment_reference', 32);
                $table->date('payment_date');
                $table->decimal('payment_amount', 15, 2);
                $table->unsignedInteger('payment_mode_id')->nullable();
                $table->string('payment_mode_name', 191)->nullable();
                $table->string('currency_code', 3);
                $table->string('currency_symbol', 8);
                $table->string('currency_position', 16)->default('Prefix');
                $table->decimal('previous_amount_remaining', 15, 2);
                $table->decimal('new_amount_remaining', 15, 2);
                $table->uuid('submission_token');
                $table->char('payload_fingerprint', 64);
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['supplier_invoice_id', 'payment_date'], 'sip_invoice_date_idx');
                $table->unique('payment_reference', 'sip_payment_reference_uq');
                $table->unique('submission_token', 'sip_submission_token_uq');
            });
        }

        $this->ensureUniqueIndex('payment_reference', 'sip_payment_reference_uq');
        $this->ensureUniqueIndex('submission_token', 'sip_submission_token_uq');
        $this->ensureForeignKey('supplier_invoice_id', 'supplier_invoices', 'id', 'sip_invoice_fk', 'restrict');
        $this->ensureForeignKey('payment_mode_id', 'bank_cashes', 'id', 'sip_payment_mode_fk', 'set null');
        $this->ensureForeignKey('created_by', 'users', 'id', 'sip_created_by_fk', 'set null');

        if (!Schema::hasTable('supplier_invoice_payment_sequences')) {
            Schema::create('supplier_invoice_payment_sequences', function (Blueprint $table) {
                $table->unsignedTinyInteger('id')->primary();
                $table->unsignedBigInteger('last_number')->default(0);
            });
        }
        DB::table('supplier_invoice_payment_sequences')->insertOrIgnore(['id' => 1, 'last_number' => 0]);
    }

    private function ensureForeignKey(string $column, string $parentTable, string $parentColumn, string $name, string $deleteAction): void
    {
        $database = DB::connection()->getDatabaseName();
        $exists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', 'supplier_invoice_payments')
            ->where('COLUMN_NAME', $column)
            ->where('REFERENCED_TABLE_NAME', $parentTable)
            ->where('REFERENCED_COLUMN_NAME', $parentColumn)
            ->exists();
        if (!$exists) {
            Schema::table('supplier_invoice_payments', function (Blueprint $table) use ($column, $parentTable, $parentColumn, $name, $deleteAction) {
                $table->foreign($column, $name)->references($parentColumn)->on($parentTable)->onDelete($deleteAction);
            });
        }
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
        if (Schema::hasTable('supplier_invoice_payments') && DB::table('supplier_invoice_payments')->exists()) {
            throw new RuntimeException('Supplier payment history exists; reconcile it before rolling back this migration.');
        }
        if (Schema::hasTable('supplier_invoice_payment_sequences')
            && (int) DB::table('supplier_invoice_payment_sequences')->where('id', 1)->value('last_number') > 0) {
            throw new RuntimeException('Supplier payment references have been issued; preserve the sequence before rolling back this migration.');
        }
        Schema::dropIfExists('supplier_invoice_payments');
        Schema::dropIfExists('supplier_invoice_payment_sequences');
    }
}

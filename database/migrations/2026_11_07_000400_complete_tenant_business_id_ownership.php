<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompleteTenantBusinessIdOwnership extends Migration
{
    private $tenantTables = [
        'bank_cashes', 'budgets', 'customers', 'customer_deposits',
        'customer_deposit_applications', 'customer_invoices', 'customer_invoice_items',
        'customer_payments', 'customer_payment_allocations', 'customer_payment_receipts',
        'customer_payment_receipt_sequences', 'expenses', 'expenses_types',
        'general_journals', 'goods_receipts', 'goods_received_items',
        'income_expense_groups', 'income_expense_heads', 'income_expense_types',
        'invoice_sequences', 'payrolls', 'personal_access_tokens', 'profiles',
        'purchase_orders', 'purchase_order_items', 'role_manages', 'settings',
        'site_settings', 'site_slides', 'supplier_invoices', 'supplier_invoice_items',
        'supplier_invoice_payments', 'supplier_invoice_payment_sequences',
        'supplier_invoice_receipt_allocations', 'transactions', 'vendors',
        'vendor_invoices', 'vendor_invoice_items', 'vendor_payments',
    ];

    public function up()
    {
        foreach ($this->tenantTables as $table) {
            if (!Schema::hasTable($table)) continue;

            $hasColumn = DB::select(
                "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = 'business_id'",
                [$table]
            );
            if (!$hasColumn) {
                DB::statement("ALTER TABLE `{$table}` ADD COLUMN `business_id` BIGINT UNSIGNED NULL");
            }

            $indexed = DB::select(
                "SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
                [$table, $table . '_business_id_index']
            );
            if (!$indexed) {
                DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$table}_business_id_index` (`business_id`)");
            }
        }

        // Existing records belong to the original tenant until tenant-specific records are created.
        foreach ($this->tenantTables as $table) {
            if (!Schema::hasTable($table)) continue;
            $globalTables = ['site_settings', 'site_slides'];
            if (!in_array($table, $globalTables, true)) {
                DB::table($table)->whereNull('business_id')->update(['business_id' => 1]);
            } elseif (!DB::table($table)->exists()) {
                continue;
            }
        }
    }

    public function down()
    {
        foreach ($this->tenantTables as $table) {
            if (!Schema::hasTable($table)) continue;
            DB::statement("ALTER TABLE `{$table}` DROP COLUMN IF EXISTS `business_id`");
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSupplierInvoiceTables extends Migration
{
    public function up()
    {
        // MySQL DDL is not transactional. If a prior attempt created the tables
        // but stopped while adding a foreign key, complete that same schema
        // safely instead of trying to recreate the existing tables.
        if (Schema::hasTable('supplier_invoices')
            && Schema::hasTable('supplier_invoice_items')
            && Schema::hasTable('supplier_invoice_receipt_allocations')) {
            $foreignKeys = [
                ['supplier_invoices', 'vendor_id', 'vendors', 'id', 'si_vendor_fk', 'restrict'],
                ['supplier_invoices', 'purchase_order_id', 'purchase_orders', 'id', 'si_purchase_order_fk', 'restrict'],
                ['supplier_invoices', 'branch_id', 'branches', 'id', 'si_branch_fk', 'set null'],
                ['supplier_invoices', 'created_by', 'users', 'id', 'si_created_by_fk', 'set null'],
                ['supplier_invoices', 'updated_by', 'users', 'id', 'si_updated_by_fk', 'set null'],
                ['supplier_invoices', 'approved_by', 'users', 'id', 'si_approved_by_fk', 'set null'],
                ['supplier_invoices', 'cancelled_by', 'users', 'id', 'si_cancelled_by_fk', 'set null'],
                ['supplier_invoice_items', 'supplier_invoice_id', 'supplier_invoices', 'id', 'sii_invoice_fk', 'restrict'],
                ['supplier_invoice_items', 'purchase_order_item_id', 'purchase_order_items', 'id', 'sii_po_item_fk', 'restrict'],
                ['supplier_invoice_items', 'inventory_id', 'inventories', 'id', 'sii_inventory_fk', 'set null'],
                ['supplier_invoice_receipt_allocations', 'supplier_invoice_item_id', 'supplier_invoice_items', 'id', 'si_alloc_invoice_item_fk', 'restrict'],
                ['supplier_invoice_receipt_allocations', 'goods_received_item_id', 'goods_received_items', 'id', 'si_alloc_received_item_fk', 'restrict'],
            ];
            foreach ($foreignKeys as $foreignKey) {
                $this->ensureForeignKey(...$foreignKey);
            }
            $this->ensureSupplierInvoiceSequence();
            return;
        }

        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('internal_invoice_number', 32)->unique();
            $table->string('supplier_invoice_number', 120);
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedInteger('branch_id')->nullable();
            $table->string('vendor_name_snapshot', 191);
            $table->string('branch_name_snapshot', 191)->nullable();
            $table->string('currency_code', 3);
            $table->string('currency_symbol', 8);
            $table->string('currency_position', 16)->default('Prefix');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('status', 24)->default('draft');
            $table->text('review_reason')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('wht_amount', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('amount_remaining', 15, 2)->nullable();
            $table->boolean('wht_enabled_snapshot')->nullable();
            $table->string('wht_mode_snapshot', 24)->nullable();
            $table->decimal('wht_rate_snapshot', 5, 2)->nullable();
            $table->decimal('wht_base_snapshot', 15, 2)->nullable();
            $table->unsignedSmallInteger('wht_calculation_version')->nullable();
            $table->uuid('submission_token')->unique();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'supplier_invoice_number'], 'supplier_invoice_vendor_document_unique');
            $table->index(['status', 'invoice_date'], 'supplier_invoice_status_date_idx');
            $table->index(['purchase_order_id', 'status'], 'supplier_invoice_po_status_idx');
            $table->index(['vendor_id', 'invoice_date'], 'supplier_invoice_vendor_date_idx');
            $table->foreign('vendor_id', 'si_vendor_fk')->references('id')->on('vendors')->onDelete('restrict');
            $table->foreign('purchase_order_id', 'si_purchase_order_fk')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('branch_id', 'si_branch_fk')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('created_by', 'si_created_by_fk')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by', 'si_updated_by_fk')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by', 'si_approved_by_fk')->references('id')->on('users')->onDelete('set null');
            $table->foreign('cancelled_by', 'si_cancelled_by_fk')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('supplier_invoice_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_invoice_id');
            $table->unsignedBigInteger('purchase_order_item_id');
            $table->unsignedBigInteger('inventory_id')->nullable();
            $table->string('description_snapshot', 255);
            $table->string('unit_snapshot', 64);
            $table->boolean('track_stock_snapshot')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('purchase_order_unit_price', 15, 2);
            $table->decimal('supplier_unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();

            $table->index(['purchase_order_item_id', 'supplier_invoice_id'], 'supplier_invoice_po_item_idx');
            $table->foreign('supplier_invoice_id', 'sii_invoice_fk')->references('id')->on('supplier_invoices')->onDelete('restrict');
            $table->foreign('purchase_order_item_id', 'sii_po_item_fk')->references('id')->on('purchase_order_items')->onDelete('restrict');
            $table->foreign('inventory_id', 'sii_inventory_fk')->references('id')->on('inventories')->onDelete('set null');
        });

        Schema::create('supplier_invoice_receipt_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('supplier_invoice_item_id');
            $table->unsignedBigInteger('goods_received_item_id');
            $table->decimal('allocated_quantity', 12, 3);
            $table->timestamps();

            $table->unique(['supplier_invoice_item_id', 'goods_received_item_id'], 'supplier_invoice_receipt_pair_unique');
            $table->index('goods_received_item_id', 'supplier_invoice_receipt_item_idx');
            $table->foreign('supplier_invoice_item_id', 'si_alloc_invoice_item_fk')->references('id')->on('supplier_invoice_items')->onDelete('restrict');
            $table->foreign('goods_received_item_id', 'si_alloc_received_item_fk')->references('id')->on('goods_received_items')->onDelete('restrict');
        });

        $this->ensureSupplierInvoiceSequence();
    }

    private function ensureForeignKey($tableName, $column, $referencedTable, $referencedColumn, $constraintName, $deleteAction)
    {
        $database = DB::connection()->getDatabaseName();
        $exists = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $tableName)
            ->where('COLUMN_NAME', $column)
            ->where('REFERENCED_TABLE_NAME', $referencedTable)
            ->where('REFERENCED_COLUMN_NAME', $referencedColumn)
            ->exists();

        if (!$exists) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $referencedTable, $referencedColumn, $constraintName, $deleteAction) {
                $table->foreign($column, $constraintName)
                    ->references($referencedColumn)->on($referencedTable)->onDelete($deleteAction);
            });
        }
    }

    private function ensureSupplierInvoiceSequence()
    {
        DB::table('invoice_sequences')->insertOrIgnore([
            'document_type' => 'supplier_invoice',
            'current_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        if ((Schema::hasTable('supplier_invoice_receipt_allocations') && DB::table('supplier_invoice_receipt_allocations')->exists())
            || (Schema::hasTable('supplier_invoice_items') && DB::table('supplier_invoice_items')->exists())
            || (Schema::hasTable('supplier_invoices') && DB::table('supplier_invoices')->exists())) {
            throw new RuntimeException('Supplier Invoices exist; cancel or reconcile them before rolling back this migration.');
        }

        Schema::dropIfExists('supplier_invoice_receipt_allocations');
        Schema::dropIfExists('supplier_invoice_items');
        Schema::dropIfExists('supplier_invoices');
        // Preserve the invoice sequence row because it may contain shared numbering history.
    }
}

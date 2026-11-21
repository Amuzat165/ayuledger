<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGoodsReceivedTables extends Migration
{
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedInteger('approved_by')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('receipt_number', 32)->nullable()->unique();
            $table->uuid('submission_token')->unique();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedInteger('branch_id');
            $table->date('received_date');
            $table->string('status', 16)->default('draft');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('received_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['purchase_order_id', 'status']);
            $table->index(['vendor_id', 'received_date']);
            $table->index(['branch_id', 'received_date']);
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('restrict');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('goods_received_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('goods_receipt_id');
            $table->unsignedBigInteger('purchase_order_item_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->string('item_description_snapshot', 255);
            $table->string('unit_snapshot', 64);
            $table->decimal('received_quantity', 12, 3);
            $table->timestamps();
            $table->unique(['goods_receipt_id', 'purchase_order_item_id'], 'goods_receipt_item_unique');
            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->onDelete('restrict');
            $table->foreign('purchase_order_item_id')->references('id')->on('purchase_order_items')->onDelete('restrict');
            $table->foreign('inventory_item_id')->references('id')->on('inventories')->onDelete('restrict');
        });

        DB::table('invoice_sequences')->insertOrIgnore([
            'document_type' => 'goods_receipt',
            'current_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        if ((Schema::hasTable('goods_receipts') && DB::table('goods_receipts')->exists())
            || (Schema::hasTable('goods_received_items') && DB::table('goods_received_items')->exists())
            || (Schema::hasTable('purchase_orders') && DB::table('purchase_orders')->where(function ($query) {
                $query->whereNotNull('approved_by')->orWhereNotNull('approved_at');
            })->exists())) {
            throw new RuntimeException('Goods Received or approved Purchase Order records exist; reconcile them before rollback.');
        }

        Schema::dropIfExists('goods_received_items');
        Schema::dropIfExists('goods_receipts');
        if (Schema::hasTable('purchase_orders')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn(['approved_by', 'approved_at']);
            });
        }
        // Keep the sequence row: it may predate this migration or hold shared numbering history.
    }
}

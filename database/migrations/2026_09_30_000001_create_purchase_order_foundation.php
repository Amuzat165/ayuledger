<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrderFoundation extends Migration
{
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('purchase_order_number', 32)->unique();
            $table->uuid('submission_token')->unique();
            $table->unsignedBigInteger('vendor_id');
            $table->string('vendor_name_snapshot');
            $table->text('vendor_address_snapshot')->nullable();
            $table->string('vendor_phone_snapshot')->nullable();
            $table->string('vendor_account_snapshot')->nullable();
            $table->string('vendor_terms_snapshot')->nullable();
            $table->string('vendor_branch_name_snapshot')->nullable();
            $table->unsignedInteger('branch_id')->nullable();
            $table->string('branch_name_snapshot');
            $table->string('currency_code', 3);
            $table->string('currency_symbol', 8);
            $table->string('currency_position', 16)->default('Prefix');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('status', 24)->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('order_total', 15, 2)->default(0);
            $table->text('delivery_instructions')->nullable();
            $table->text('internal_notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'order_date']);
            $table->index(['vendor_id', 'order_date']);
            $table->index(['branch_id', 'order_date']);
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('restrict');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->string('inventory_item_name_snapshot');
            $table->text('description');
            $table->string('unit', 64);
            $table->decimal('quantity', 12, 3);
            $table->decimal('reference_unit_price', 15, 2)->nullable();
            $table->decimal('agreed_unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('inventory_item_id')->references('id')->on('inventories')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        DB::table('invoice_sequences')->insertOrIgnore([
            'document_type' => 'purchase_order',
            'current_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        if (Schema::hasTable('purchase_orders') && DB::table('purchase_orders')->exists()) {
            throw new RuntimeException('Purchase Orders exist; archive or reconcile them before rolling back this migration.');
        }

        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        if (Schema::hasTable('invoice_sequences')) {
            DB::table('invoice_sequences')->where('document_type', 'purchase_order')->delete();
        }
    }
}

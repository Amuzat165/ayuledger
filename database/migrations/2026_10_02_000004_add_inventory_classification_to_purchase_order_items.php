<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddInventoryClassificationToPurchaseOrderItems extends Migration
{
    public function up()
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            // Existing PO lines remain NULL (unclassified) until explicitly reviewed.
            $table->string('inventory_classification', 16)->nullable()->after('inventory_item_id');
        });
    }

    public function down()
    {
        if (Schema::hasColumn('purchase_order_items', 'inventory_classification')
            && DB::table('purchase_order_items')->whereNotNull('inventory_classification')->exists()) {
            throw new RuntimeException('Purchase Order inventory classifications exist; reconcile them before rolling back this migration.');
        }

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('inventory_classification');
        });
    }
}

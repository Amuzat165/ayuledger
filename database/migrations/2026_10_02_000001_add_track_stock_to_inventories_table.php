<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTrackStockToInventoriesTable extends Migration
{
    public function up()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->boolean('track_stock')->nullable()->after('quantity_at_hand');
        });
    }

    public function down()
    {
        if (Schema::hasColumn('inventories', 'track_stock') && DB::table('inventories')->whereNotNull('track_stock')->exists()) {
            throw new \RuntimeException('Inventory stock-tracking choices exist; preserve them before rolling back this migration.');
        }

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('track_stock');
        });
    }
}
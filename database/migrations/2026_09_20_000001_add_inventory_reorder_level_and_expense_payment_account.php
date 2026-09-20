<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInventoryReorderLevelAndExpensePaymentAccount extends Migration
{
    public function up()
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->unsignedInteger('reorder_level')->default(0)->after('quantity_at_hand');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedInteger('bank_cash_id')->nullable()->after('branch_id');
        });
    }

    public function down()
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('bank_cash_id');
        });

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn('reorder_level');
        });
    }
}

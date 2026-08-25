<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToInventoriesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('inventories', 'branch_id')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->unsignedInteger('branch_id')->nullable()->after('type_id');
                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('inventories', 'branch_id')) {
            Schema::table('inventories', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
}

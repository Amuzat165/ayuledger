<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToVendorsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('vendors', 'branch_id')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->unsignedInteger('branch_id')->nullable()->after('id');
                $table->foreign('branch_id')
                    ->references('id')
                    ->on('branches')
                    ->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('vendors', 'branch_id')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
}

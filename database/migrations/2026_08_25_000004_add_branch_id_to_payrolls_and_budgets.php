<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToPayrollsAndBudgets extends Migration
{
    public function up()
    {
        foreach (['payrolls', 'budgets'] as $tableName) {
            if (!Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedInteger('branch_id')->nullable()->after('id');
                    $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                });
            }
        }
    }

    public function down()
    {
        foreach (['payrolls', 'budgets'] as $tableName) {
            if (Schema::hasColumn($tableName, 'branch_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['branch_id']);
                    $table->dropColumn('branch_id');
                });
            }
        }
    }
}

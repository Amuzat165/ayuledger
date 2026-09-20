<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RestoreBranchIdOnTransactionsTable extends Migration
{
    /**
     * Restore the column expected by the transaction and banking voucher flows.
     *
     * Some existing installations recorded the original transactions migration
     * as complete even though this column was not present in the live table.
     */
    public function up()
    {
        if (!Schema::hasColumn('transactions', 'branch_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedInteger('branch_id')->nullable()->after('voucher_no');
                $table->foreign('branch_id')->references('id')->on('branches');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('transactions', 'branch_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
}

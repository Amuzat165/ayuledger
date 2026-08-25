<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToPersonalAccessTokensTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('personal_access_tokens') && !Schema::hasColumn('personal_access_tokens', 'deleted_at')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('personal_access_tokens') && Schema::hasColumn('personal_access_tokens', 'deleted_at')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
}

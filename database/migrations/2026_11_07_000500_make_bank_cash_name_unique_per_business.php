<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeBankCashNameUniquePerBusiness extends Migration
{
    public function up()
    {
        Schema::table('bank_cashes', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['business_id', 'name'], 'bank_cashes_business_name_unique');
        });
    }

    public function down()
    {
        Schema::table('bank_cashes', function (Blueprint $table) {
            $table->dropUnique('bank_cashes_business_name_unique');
            $table->unique('name');
        });
    }
}

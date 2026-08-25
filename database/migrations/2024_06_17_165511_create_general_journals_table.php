<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGeneralJournalsTable extends Migration
{
    public function up()
    {
        Schema::create('general_journals', function (Blueprint $table) {
            $table->id();
            $table->string('account');
            $table->decimal('debit', 15, 2);
            $table->decimal('credit', 15, 2);
            $table->text('memo');
            $table->string('customer_vendor');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('general_journals');
    }
}

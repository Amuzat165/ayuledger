<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->string('employee');
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('housing_allowance', 15, 2);
            $table->decimal('transport_allowance', 15, 2);
            $table->decimal('medical_allowance', 15, 2);
            $table->decimal('other_allowance', 15, 2);
            $table->decimal('gross_salary', 15, 2);
            $table->decimal('deduction_payee', 15, 2);
            $table->decimal('pension', 15, 2);
            $table->decimal('net_pay', 15, 2);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payrolls');
    }
}

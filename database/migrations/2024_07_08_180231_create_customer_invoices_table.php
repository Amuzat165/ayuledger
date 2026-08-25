<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerInvoicesTable extends Migration
{
    private $table;

    public function __construct()
    {
        $this->table = 'customer_invoices';
    }

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->decimal('exchange_rate', 8, 2);
            // Add other fields as needed

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('customer_invoices')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventories')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->decimal('rate', 10, 2);
            $table->integer('quantity');
            $table->decimal('amount', 10, 2);
            // Add other fields as needed

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_invoice_items');
        Schema::dropIfExists($this->table);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorInvoicesTables extends Migration
{
    public function up()
    {
        Schema::create('vendor_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->decimal('exchange_rate', 8, 2);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vendor_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('vendor_invoices')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventories')->onDelete('cascade');
            $table->string('description')->nullable();
            $table->decimal('rate', 10, 2);
            $table->integer('quantity');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_invoice_items');
        Schema::dropIfExists('vendor_invoices');
    }
}

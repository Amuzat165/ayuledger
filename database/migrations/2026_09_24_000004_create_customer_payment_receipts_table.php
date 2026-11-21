<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerPaymentReceiptsTable extends Migration
{
    public function up()
    {
        Schema::create('customer_payment_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('receipt_number', 32)->unique();
            $table->uuid('idempotency_key')->unique();
            $table->char('payload_fingerprint', 64);
            $table->unsignedInteger('customer_id');
            $table->decimal('amount_received', 15, 2);
            $table->unsignedInteger('payment_mode_id')->nullable();
            $table->string('payment_mode_name', 191)->nullable();
            $table->dateTime('received_at');
            $table->string('status', 16)->default('received');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('payment_mode_id')->references('id')->on('bank_cashes')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['customer_id', 'received_at']);
        });

        Schema::create('customer_payment_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('receipt_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('allocated_amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->dateTime('allocated_at');
            $table->string('status', 16)->default('allocated');
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
            $table->foreign('receipt_id')->references('id')->on('customer_payment_receipts')->onDelete('restrict');
            $table->foreign('invoice_id')->references('id')->on('customer_invoices')->onDelete('restrict');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['invoice_id', 'status']);
        });

        Schema::create('customer_payment_receipt_sequences', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('last_number')->default(0);
        });
        \Illuminate\Support\Facades\DB::table('customer_payment_receipt_sequences')->insert(['id' => 1, 'last_number' => 0]);
    }

    public function down()
    {
        if (\Illuminate\Support\Facades\DB::table('customer_payment_allocations')->exists()
            || \Illuminate\Support\Facades\DB::table('customer_payment_receipts')->exists()) {
            throw new RuntimeException('Cannot roll back customer payment receipt tables while payment history exists.');
        }
        Schema::dropIfExists('customer_payment_allocations');
        Schema::dropIfExists('customer_payment_receipts');
        Schema::dropIfExists('customer_payment_receipt_sequences');
    }
}

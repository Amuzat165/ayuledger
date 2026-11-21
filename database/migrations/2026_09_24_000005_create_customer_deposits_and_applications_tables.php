<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCustomerDepositsAndApplicationsTables extends Migration
{
    public function up()
    {
        Schema::create('customer_deposits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('customer_id');
            $table->unsignedBigInteger('source_payment_receipt_id')->unique();
            $table->unsignedBigInteger('source_invoice_id')->nullable();
            $table->decimal('original_amount', 15, 2, true);
            $table->string('status', 16)->default('active');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
            $table->foreign('source_payment_receipt_id')->references('id')->on('customer_payment_receipts')->onDelete('restrict');
            $table->foreign('source_invoice_id')->references('id')->on('customer_invoices')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['customer_id', 'status']);
            $table->index('source_invoice_id');
        });

        Schema::create('customer_deposit_applications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('customer_deposit_id');
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount_applied', 15, 2, true);
            $table->decimal('invoice_balance_after', 15, 2);
            $table->decimal('deposit_balance_after', 15, 2);
            $table->dateTime('applied_at');
            $table->string('status', 16)->default('applied');
            $table->uuid('idempotency_key')->unique();
            $table->char('payload_fingerprint', 64);
            $table->unsignedInteger('applied_by')->nullable();
            $table->timestamps();
            $table->foreign('customer_deposit_id')->references('id')->on('customer_deposits')->onDelete('restrict');
            $table->foreign('invoice_id')->references('id')->on('customer_invoices')->onDelete('restrict');
            $table->foreign('applied_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['customer_deposit_id', 'status']);
            $table->index(['invoice_id', 'status']);
        });

        Schema::table('customer_payment_receipts', function (Blueprint $table) {
            $table->decimal('deposit_created_amount', 15, 2)->default(0)->after('amount_received');
            $table->decimal('customer_deposit_balance_after', 15, 2)->nullable()->after('deposit_created_amount');
        });
    }

    public function down()
    {
        if (DB::table('customer_deposit_applications')->exists()
            || DB::table('customer_deposits')->exists()
            || DB::table('customer_payment_receipts')->exists()) {
            throw new RuntimeException('Cannot roll back customer deposit support while deposit applications, deposits, or payment receipt history exist.');
        }
        Schema::table('customer_payment_receipts', function (Blueprint $table) {
            $table->dropColumn(['deposit_created_amount', 'customer_deposit_balance_after']);
        });
        Schema::dropIfExists('customer_deposit_applications');
        Schema::dropIfExists('customer_deposits');
    }
}

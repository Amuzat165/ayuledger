<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlignPaymentColumns extends Migration
{
    public function up()
    {
        if (Schema::hasTable('customer_payments')) {
            if (Schema::hasColumn('customer_payments', 'amount') && !Schema::hasColumn('customer_payments', 'amount_paid')) {
                Schema::table('customer_payments', function (Blueprint $table) {
                    $table->decimal('total_amount', 10, 2)->default(0)->after('invoice_id');
                    $table->decimal('amount_paid', 10, 2)->default(0)->after('total_amount');
                    $table->unsignedInteger('payment_mode_id')->nullable()->after('balance_due');
                });

                DB::statement('UPDATE customer_payments SET amount_paid = amount, total_amount = amount + balance_due');
                Schema::table('customer_payments', function (Blueprint $table) {
                    $table->dropColumn('amount');
                    $table->foreign('payment_mode_id')->references('id')->on('bank_cashes')->onDelete('set null');
                });
            }

            if (Schema::hasColumn('customer_payments', 'payment_mode_id')) {
                DB::statement('ALTER TABLE customer_payments MODIFY payment_mode_id INT UNSIGNED NULL');
                if (!collect(DB::select('SHOW CREATE TABLE customer_payments'))->contains(function ($row) {
                    return strpos($row->{'Create Table'}, 'customer_payments_payment_mode_id_foreign') !== false;
                })) {
                    Schema::table('customer_payments', function (Blueprint $table) {
                        $table->foreign('payment_mode_id')->references('id')->on('bank_cashes')->onDelete('set null');
                    });
                }
            }
        }

        if (Schema::hasTable('vendor_payments') && !Schema::hasColumn('vendor_payments', 'amount_paid')) {
            Schema::table('vendor_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('id');
                $table->unsignedBigInteger('invoice_id')->nullable()->after('vendor_id');
                $table->decimal('total_amount', 10, 2)->default(0)->after('invoice_id');
                $table->decimal('amount_paid', 10, 2)->default(0)->after('total_amount');
                $table->decimal('balance_due', 10, 2)->default(0)->after('amount_paid');
                $table->unsignedInteger('payment_mode_id')->nullable()->after('balance_due');
                $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
                $table->foreign('invoice_id')->references('id')->on('vendor_invoices')->onDelete('cascade');
                $table->foreign('payment_mode_id')->references('id')->on('bank_cashes')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        // Payment data is intentionally kept during rollback.
    }
}

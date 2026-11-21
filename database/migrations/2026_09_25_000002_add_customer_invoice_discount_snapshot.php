<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCustomerInvoiceDiscountSnapshot extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('customer_invoices')) {
            throw new RuntimeException('The customer_invoices table is missing.');
        }

        $generalRows = DB::table('settings')->where('settings_name', 'General Settings')->get();
        if ($generalRows->count() !== 1) {
            throw new RuntimeException('Expected exactly one General Settings record before enabling invoice discounts.');
        }

        $general = $generalRows->first();
        $settings = json_decode($general->content, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($settings)) {
            throw new RuntimeException('General Settings JSON is invalid; discount configuration was not changed.');
        }
        if (array_key_exists('discount_enabled', $settings)
            && !in_array($settings['discount_enabled'], [true, false, 0, 1, '0', '1'], true)) {
            throw new RuntimeException('Existing discount setting is invalid; it was not overwritten.');
        }

        if (!Schema::hasColumn('customer_invoices', 'discount_percentage')
            || !Schema::hasColumn('customer_invoices', 'discount_amount')
            || !Schema::hasColumn('customer_invoices', 'balance_due')) {
            Schema::table('customer_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('customer_invoices', 'discount_percentage')) {
                    $table->decimal('discount_percentage', 5, 2)->nullable();
                }
                if (!Schema::hasColumn('customer_invoices', 'discount_amount')) {
                    $table->decimal('discount_amount', 15, 2)->nullable();
                }
                if (!Schema::hasColumn('customer_invoices', 'balance_due')) {
                    $table->decimal('balance_due', 15, 2)->nullable();
                }
            });
        }

        if (!array_key_exists('discount_enabled', $settings)) {
            $settings['discount_enabled'] = false;
            DB::table('settings')->where('id', $general->id)->update(['content' => json_encode($settings, JSON_THROW_ON_ERROR)]);
        }
    }

    public function down()
    {
        $hasDiscountData = DB::table('customer_invoices')
            ->where(function ($query) {
                $query->whereNotNull('discount_percentage')
                    ->orWhereNotNull('discount_amount')
                    ->orWhereNotNull('balance_due');
            })
            ->exists();

        if ($hasDiscountData) {
            throw new RuntimeException('Cannot remove customer invoice discount snapshot columns while snapshot data exists.');
        }

        Schema::table('customer_invoices', function (Blueprint $table) {
            $table->dropColumn(['discount_percentage', 'discount_amount', 'balance_due']);
        });
        // Preserve the Administrator's discount setting during rollback.
    }
}

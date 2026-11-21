<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVendorInvoiceWhtSnapshots extends Migration
{
    public function up()
    {
        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->boolean('wht_enabled_snapshot')->nullable()->after('withholding_tax_amount');
            $table->string('wht_mode_snapshot', 20)->nullable()->after('wht_enabled_snapshot');
            $table->decimal('wht_rate_snapshot', 5, 2)->nullable()->after('wht_mode_snapshot');
            $table->decimal('wht_base_snapshot', 10, 2)->nullable()->after('wht_rate_snapshot');
            $table->decimal('wht_balance_due_snapshot', 10, 2)->nullable()->after('wht_base_snapshot');
            $table->unsignedSmallInteger('wht_calculation_version')->nullable()->after('wht_balance_due_snapshot');
            $table->timestamp('wht_calculated_at')->nullable()->after('wht_calculation_version');
        });
    }

    public function down()
    {
        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'wht_enabled_snapshot',
                'wht_mode_snapshot',
                'wht_rate_snapshot',
                'wht_base_snapshot',
                'wht_balance_due_snapshot',
                'wht_calculation_version',
                'wht_calculated_at',
            ]);
        });
    }
}

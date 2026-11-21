<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWithholdingTaxToVendorInvoices extends Migration
{
    public function up()
    {
        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->decimal('withholding_tax_amount', 10, 2)->default(0)->after('exchange_rate');
        });
    }

    public function down()
    {
        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->dropColumn('withholding_tax_amount');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customer_invoices', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->nullable();
            $table->decimal('vat_percentage', 5, 2)->nullable();
            $table->decimal('vat_amount', 15, 2)->nullable();
            $table->decimal('total_due', 15, 2)->nullable();
            $table->unsignedTinyInteger('financial_snapshot_version')->nullable();
        });
    }

    public function down()
    {
        $hasSnapshots = DB::table('customer_invoices')
            ->where(function ($query) {
                $query->whereNotNull('subtotal')
                    ->orWhereNotNull('vat_percentage')
                    ->orWhereNotNull('vat_amount')
                    ->orWhereNotNull('total_due')
                    ->orWhereNotNull('financial_snapshot_version');
            })
            ->exists();

        if ($hasSnapshots) {
            throw new RuntimeException('Cannot remove invoice financial snapshot columns while snapshot data exists.');
        }

        Schema::table('customer_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'vat_percentage',
                'vat_amount',
                'total_due',
                'financial_snapshot_version',
            ]);
        });
    }
};

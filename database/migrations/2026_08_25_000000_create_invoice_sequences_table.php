<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateInvoiceSequencesTable extends Migration
{
    public function up()
    {
        foreach (['password_resets', 'profiles', 'settings', 'customer_invoice_items', 'customer_payments', 'vendor_payments'] as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'deleted_at')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }

        foreach (['customer_invoices', 'vendor_invoices'] as $tableName) {
            if (Schema::hasTable($tableName)) {
                $indexes = DB::select("SHOW INDEX FROM {$tableName} WHERE Column_name = 'invoice_number'");
                $hasUniqueIndex = collect($indexes)->contains(function ($index) {
                    return (int) $index->Non_unique === 0;
                });

                if (!$hasUniqueIndex) {
                    foreach ($indexes as $index) {
                        Schema::table($tableName, function (Blueprint $table) use ($index) {
                            $table->dropIndex($index->Key_name);
                        });
                    }

                    Schema::table($tableName, function (Blueprint $table) {
                        $table->unique('invoice_number');
                    });
                }
            }
        }

        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->string('document_type', 32)->primary();
            $table->unsignedInteger('current_number')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        foreach (['customer' => 'customer_invoices', 'vendor' => 'vendor_invoices'] as $type => $tableName) {
            if (Schema::hasTable($tableName)) {
                $latest = DB::table($tableName)
                    ->selectRaw('MAX(CAST(invoice_number AS UNSIGNED)) AS current_number')
                    ->value('current_number');

                DB::table('invoice_sequences')->insert([
                    'document_type' => $type,
                    'current_number' => (int) $latest,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('invoice_sequences');
    }
}

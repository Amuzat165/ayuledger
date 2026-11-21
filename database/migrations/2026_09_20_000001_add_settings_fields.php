<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSettingsFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('header_image')->nullable()->after('content');
            $table->decimal('vat_percentage', 5, 2)->default(0)->after('header_image');
            $table->string('company_color')->default('#00897B')->after('vat_percentage');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('header_image');
            $table->dropColumn('vat_percentage');
            $table->dropColumn('company_color');
        });
    }
}

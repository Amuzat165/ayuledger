<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageToTestimonialsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('testimonials', 'image')) {
            Schema::table('testimonials', function (Blueprint $table) {
                $table->string('image')->nullable()->after('quote');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('testimonials', 'image')) {
            Schema::table('testimonials', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }
    }
}

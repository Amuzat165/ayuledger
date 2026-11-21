<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakePublicFeedbackBusinessOptional extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE business_feedback MODIFY business_id BIGINT UNSIGNED NULL');
    }

    public function down()
    {
    }
}

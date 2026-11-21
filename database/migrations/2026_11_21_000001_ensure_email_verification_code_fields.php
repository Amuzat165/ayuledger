<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureEmailVerificationCodeFields extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('users')) {
            throw new \RuntimeException('The users table must exist before email verification fields can be added.');
        }

        if (!Schema::hasColumn('users', 'email_verification_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email_verification_code')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'email_verification_expires_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('email_verification_expires_at')->nullable();
            });
        }
    }

    public function down()
    {
        // Keep these columns because earlier migrations may also own them.
    }
}

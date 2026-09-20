<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReorderCustomersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        if (Schema::hasColumn('customers', 'new_column')) {
            Schema::table('customers', function ($table) {
                $table->dropColumn('new_column');
            });
        }

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(<<<'SQL'
ALTER TABLE `customers`
    MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT FIRST,
    MODIFY `name` VARCHAR(191) NOT NULL AFTER `id`,
    MODIFY `email` VARCHAR(191) NOT NULL AFTER `name`,
    MODIFY `phone` VARCHAR(191) NOT NULL AFTER `email`,
    MODIFY `address` VARCHAR(191) NOT NULL AFTER `phone`,
    MODIFY `income_expense_type_id` BIGINT UNSIGNED NOT NULL AFTER `address`,
    MODIFY `terms_of_payment` VARCHAR(191) NOT NULL AFTER `income_expense_type_id`,
    MODIFY `terms_of_payment_in_days` TINYINT NULL AFTER `terms_of_payment`,
    MODIFY `branch_id` INT UNSIGNED NULL AFTER `terms_of_payment_in_days`,
    MODIFY `status` TINYINT NOT NULL DEFAULT 0 AFTER `branch_id`,
    MODIFY `created_by` INT UNSIGNED NULL AFTER `status`,
    MODIFY `updated_by` INT UNSIGNED NULL AFTER `created_by`,
    MODIFY `deleted_by` INT UNSIGNED NULL AFTER `updated_by`,
    MODIFY `created_at` TIMESTAMP NULL AFTER `deleted_by`,
    MODIFY `updated_at` TIMESTAMP NULL AFTER `created_at`,
    MODIFY `deleted_at` TIMESTAMP NULL AFTER `updated_at`
SQL
        );
    }

    public function down()
    {
        // Column order and the removed placeholder column are not restored.
    }
}

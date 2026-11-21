<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncScalarVatWithGeneralSettings extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('settings', 'vat_percentage')) {
            throw new RuntimeException('The settings.vat_percentage compatibility column is missing.');
        }

        DB::transaction(function () {
            $rows = DB::table('settings')->where('settings_name', 'General Settings')->lockForUpdate()->get();
            if ($rows->count() !== 1) {
                throw new RuntimeException('Expected exactly one General Settings record.');
            }

            $row = $rows->first();
            $settings = json_decode($row->content, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($settings) || !array_key_exists('vat_percentage', $settings)) {
                throw new RuntimeException('General Settings JSON does not contain a valid VAT percentage.');
            }

            $vat = $settings['vat_percentage'];
            if (!is_numeric($vat) || !is_finite((float) $vat) || abs((float) $vat - 7.5) > 0.0000001) {
                throw new RuntimeException('The effective General Settings VAT is not the verified 7.5 percent value.');
            }

            $scalar = (float) $row->vat_percentage;
            if (abs($scalar - 7.5) <= 0.0000001) {
                return;
            }
            if (abs($scalar) > 0.0000001) {
                throw new RuntimeException('The scalar VAT value differs from the verified pre-migration value.');
            }

            $updated = DB::table('settings')->where('id', $row->id)->update(['vat_percentage' => '7.50']);
            if ($updated !== 1) {
                throw new RuntimeException('The scalar VAT compatibility value was not synchronized.');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('settings', 'vat_percentage')) {
            return;
        }

        DB::transaction(function () {
            $rows = DB::table('settings')->where('settings_name', 'General Settings')->lockForUpdate()->get();
            if ($rows->count() !== 1) {
                throw new RuntimeException('Expected exactly one General Settings record while restoring VAT synchronization.');
            }

            $row = $rows->first();
            $settings = json_decode($row->content, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($settings) || !isset($settings['vat_percentage']) || !is_numeric($settings['vat_percentage'])) {
                throw new RuntimeException('Cannot synchronize the scalar VAT value from invalid General Settings JSON.');
            }

            $vat = (float) $settings['vat_percentage'];
            if (!is_finite($vat) || $vat < 0 || $vat > 100 || abs($vat - round($vat, 2)) > 0.0000001) {
                throw new RuntimeException('Cannot synchronize an invalid General Settings VAT percentage.');
            }

            DB::table('settings')->where('id', $row->id)->update(['vat_percentage' => number_format($vat, 2, '.', '')]);
        });
    }
}

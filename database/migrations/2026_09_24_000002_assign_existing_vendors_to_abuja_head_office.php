<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AssignExistingVendorsToAbujaHeadOffice extends Migration
{
    /**
     * Assign the two verified legacy vendors without changing other vendor fields.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $branches = DB::table('branches')
                ->where('name', 'Abuja Head Office')
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->get(['id', 'name']);

            if ($branches->count() !== 1 || (int) $branches->first()->id !== 1) {
                throw new RuntimeException('Expected exactly one active Abuja Head Office branch with ID 1.');
            }

            $vendors = DB::table('vendors')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'name', 'branch_id']);

            $expectedVendors = [
                1 => 'BuildRight Materials',
                2 => 'Office World Nigeria',
            ];

            if ($vendors->count() !== 2) {
                throw new RuntimeException('Expected exactly two active vendors before assigning their branch.');
            }

            foreach ($vendors as $vendor) {
                $vendorId = (int) $vendor->id;

                if (!array_key_exists($vendorId, $expectedVendors)
                    || $vendor->name !== $expectedVendors[$vendorId]
                    || $vendor->branch_id !== null) {
                    throw new RuntimeException('Active vendor records do not match the verified unassigned vendors.');
                }

                unset($expectedVendors[$vendorId]);
            }

            if ($expectedVendors !== []) {
                throw new RuntimeException('One or more verified active vendors are missing.');
            }

            $branchId = (int) $branches->first()->id;
            $updated = DB::table('vendors')
                ->whereIn('id', [1, 2])
                ->whereNull('deleted_at')
                ->whereNull('branch_id')
                ->update(['branch_id' => $branchId]);

            if ($updated !== 2) {
                throw new RuntimeException('Expected to assign exactly two verified vendors to Abuja Head Office.');
            }
        });
    }

    /**
     * This is a business-data assignment, not a reversible schema change.
     * Refuse rollback rather than clearing assignments that may have been
     * changed or confirmed since this migration ran.
     *
     * @return void
     */
    public function down()
    {
        throw new LogicException(
            'This vendor branch assignment is business data and cannot be rolled back automatically.'
        );
    }
}

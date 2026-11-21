<?php

namespace App\Support;

/** Resolves bulk voucher actions against the application's existing permissions. */
class VoucherBulkActionAuthorization
{
    private const MODULES = [
        'dr_voucher' => 'DrVoucher',
        'cr_voucher' => 'CrVoucher',
        'jnl_voucher' => 'JnlVoucher',
        'contra_voucher' => 'ContraVoucher',
    ];

    public static function permissionFor(string $location, int $action): ?string
    {
        $permissions = [
            'active' => [3 => 'Delete', 2 => 'PermanentlyDelete'],
            'trashed' => [1 => 'Restore', 2 => 'PermanentlyDelete'],
        ];

        return $permissions[$location][$action] ?? null;
    }

    public static function authorize(string $module, string $location, int $action): void
    {
        $permission = self::permissionFor($location, $action);
        abort_unless(
            isset(self::MODULES[$module])
                && $permission !== null
                && config('role_manage.' . self::MODULES[$module] . '.' . $permission),
            403
        );
    }
}

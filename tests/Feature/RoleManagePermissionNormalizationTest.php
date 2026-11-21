<?php

namespace Tests\Feature;

use App\Http\Controllers\RoleManageController;
use App\RoleManage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleManagePermissionNormalizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('ayu_po_stage2_test', config('database.connections.mysql.database'));
        $this->assertSame('ayu_po_stage2_test_user', config('database.connections.mysql.username'));
        $this->assertSame('ayu_po_stage2_test', DB::connection()->selectOne('SELECT DATABASE() AS database_name')->database_name);

        DB::beginTransaction();

        Auth::shouldReceive('user')->andReturn((object) ['email' => 'role-test@example.invalid']);
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_edit_normalizes_missing_configured_modules_without_changing_existing_values(): void
    {
        $stored = [
            'User' => ['User', 1, 1, 0, 1, 0, 0, 0, 0, 0],
            'PurchaseOrder' => ['Purchase Order', 0, 0, 0, 1, 0, 0, 0, 0, 0],
        ];
        $role = $this->createRole($stored);

        $view = (new RoleManageController())->edit($role->id);
        $normalized = $view->getData()['item']->content;

        $this->assertSame($stored['User'], $normalized['User']);
        $this->assertSame($stored['PurchaseOrder'], $normalized['PurchaseOrder']);
        $this->assertArrayHasKey('Language', $normalized);
        $this->assertSame(['Language', 0, 0, 0, 0, 0, 0, 0, 0, 0], array_values($normalized['Language']));
        $this->assertArrayHasKey('StatementOfChangesInEquity', $normalized);
        $this->assertSame(0, $normalized['StatementOfChangesInEquity'][4]);
    }

    public function test_update_succeeds_when_language_and_other_configured_modules_are_missing(): void
    {
        $role = $this->createRole([
            'User' => ['User', 1, 1, 0, 1, 0, 0, 0, 0, 0],
        ]);

        $response = $this->updateRole($role->id, [
            'User' => [0 => 'User', 1 => 'on', 2 => 'on', 4 => 'on'],
            'PurchaseOrder' => [0 => 'Purchase Order', 4 => 'on'],
        ]);

        $this->assertSame(route('role-manage'), $response->getTargetUrl());
        $saved = json_decode(RoleManage::findOrFail($role->id)->content, true);
        $this->assertSame(['Language', 0, 0, 0, 0, 0, 0, 0, 0, 0], array_values($saved['Language']));
        $this->assertSame(1, $saved['User'][4]);
        $this->assertSame(1, $saved['PurchaseOrder'][4]);
        $this->assertSame(0, $saved['PurchaseOrder'][3]);
    }

    public function test_unsubmitted_purchase_order_permissions_default_to_off(): void
    {
        $role = $this->createRole(['User' => ['User', 1, 1, 0, 1, 0, 0, 0, 0, 0]]);

        $this->updateRole($role->id, [
            'User' => [0 => 'User', 1 => 'on', 2 => 'on', 4 => 'on'],
        ]);

        $saved = json_decode(RoleManage::findOrFail($role->id)->content, true);
        $this->assertSame(['Purchase Order', 0, 0, 0, 0, 0, 0, 0, 0, 0], array_values($saved['PurchaseOrder']));
    }

    public function test_edit_adds_missing_supplier_payment_module_with_permissions_off_and_preserves_existing_modules(): void
    {
        $stored = [
            'User' => ['User', 1, 1, 0, 1, 0, 0, 0, 0, 0],
            'PurchaseOrder' => ['Purchase Order', 0, 0, 0, 1, 0, 0, 0, 0, 0],
            'SupplierInvoice' => ['Supplier Invoice', 1, 1, 0, 1, 0, 0, 0, 0, 0],
        ];
        $role = $this->createRole($stored);

        $view = (new RoleManageController())->edit($role->id);
        $normalized = $view->getData()['item']->content;

        foreach ($stored as $module => $permissions) {
            $this->assertSame($permissions, $normalized[$module], $module . ' permissions must remain unchanged.');
        }
        $this->assertSame(['Supplier Payment', 0, 0, 0, 0, 0, 0, 0, 0, 0], array_values($normalized['SupplierPayment']));
    }

    public function test_super_admin_can_receive_supplier_payment_access_without_changing_other_permissions(): void
    {
        $stored = [
            'User' => ['User', 1, 1, 0, 1, 0, 0, 0, 0, 0],
            'PurchaseOrder' => ['Purchase Order', 1, 1, 0, 1, 0, 0, 0, 0, 0],
            'SupplierInvoice' => ['Supplier Invoice', 1, 1, 0, 1, 0, 0, 0, 0, 0],
        ];
        $role = RoleManage::create([
            'name' => 'Super Admin',
            'content' => json_encode($stored),
            'create_by' => 'role-test@example.invalid',
        ]);

        $response = $this->updateRole($role->id, [
            'User' => [0 => 'User', 1 => 'on', 2 => 'on', 4 => 'on'],
            'PurchaseOrder' => [0 => 'Purchase Order', 1 => 'on', 2 => 'on', 4 => 'on'],
            'SupplierInvoice' => [0 => 'Supplier Invoice', 1 => 'on', 2 => 'on', 4 => 'on'],
            'SupplierPayment' => [0 => 'Supplier Payment', 1 => 'on', 2 => 'on', 3 => 'on', 6 => 'on'],
        ]);

        $this->assertSame(route('role-manage'), $response->getTargetUrl());
        $saved = json_decode(RoleManage::findOrFail($role->id)->content, true);
        foreach ($stored as $module => $permissions) {
            $this->assertSame($permissions, $saved[$module], $module . ' permissions must remain unchanged.');
        }
        $this->assertSame(['Supplier Payment', 1, 1, 1, 0, 0, 1, 0, 0, 0], array_values($saved['SupplierPayment']));
    }

    private function createRole(array $content): RoleManage
    {
        return RoleManage::create([
            'name' => 'Test Role',
            'content' => json_encode($content),
            'create_by' => 'test@example.invalid',
        ]);
    }

    private function updateRole(int $id, array $details)
    {
        $request = Request::create('/role-manage/update/' . $id, 'POST', [
            'name' => 'Test Role',
            'details' => $details,
        ]);

        return (new RoleManageController())->update($request, $id);
    }
}

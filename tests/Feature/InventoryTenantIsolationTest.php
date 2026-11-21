<?php

namespace Tests\Feature;

use App\Branch;
use App\Http\Middleware\RequireTenantContext;
use App\Models\Inventory;
use App\Models\InventoryType;
use App\RoleManage;
use App\Services\TenantContext;
use App\Tenant;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FixedInventoryTenantContext extends TenantContext
{
    private $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function currentTenant(Request $request, ?User $user = null): ?Tenant
    {
        return $this->tenant;
    }
}

class InventoryTenantIsolationTest extends TestCase
{
    private $user;
    private $role;
    private $tenant;
    private $foreignTenant;
    private $branch;
    private $foreignBranch;
    private $type;
    private $foreignType;
    private $transactionStarted = false;

    protected function setUp(): void
    {
        $approval = self::requiredTargetApproval();
        parent::setUp();

        if (config('database.default') !== 'mysql'
            || config('database.connections.mysql.driver') !== 'mysql'
            || (string) config('database.connections.mysql.database') !== $approval['database']
            || (string) config('database.connections.mysql.username') !== $approval['username']
            || (string) config('database.connections.mysql.host') !== $approval['host']
            || (string) config('database.connections.mysql.port') !== $approval['port']) {
            throw new \RuntimeException('Inventory isolation test refused: configured connection does not match its approved target.');
        }

        $identity = DB::connection('mysql')->selectOne(
            'SELECT DATABASE() AS database_name, CURRENT_USER() AS authenticated_account, @@hostname AS server_hostname, @@port AS server_port'
        );
        if ((string) $identity->database_name !== $approval['database']
            || (string) $identity->authenticated_account !== $approval['authenticated_account']
            || (string) $identity->server_hostname !== $approval['server_hostname']
            || (string) $identity->server_port !== $approval['server_port']) {
            throw new \RuntimeException('Inventory isolation test refused: authenticated database identity does not match its approved target.');
        }

        try {
            DB::connection('mysql')->beginTransaction();
            $this->transactionStarted = true;

            $suffix = bin2hex(random_bytes(6));
            $this->role = RoleManage::create([
                'name' => 'Inventory isolation test role ' . $suffix,
                'content' => json_encode([]),
            ]);
            $this->user = User::create([
                'name' => 'Inventory isolation test user',
                'email' => 'inventory-isolation-' . $suffix . '@example.invalid',
                'password' => bcrypt(bin2hex(random_bytes(16))),
                'role_manage_id' => $this->role->id,
            ]);
            $this->grantInventoryPermissions(['All', 'Create', 'Edit', 'Delete']);
            $this->actingAs($this->user);

            $this->tenant = Tenant::create([
                'name' => 'Inventory isolation ' . $suffix,
                'slug' => 'inventory-isolation-' . $suffix,
                'status' => 'active',
            ]);
            $this->foreignTenant = Tenant::create([
                'name' => 'Inventory foreign ' . $suffix,
                'slug' => 'inventory-foreign-' . $suffix,
                'status' => 'active',
            ]);

            $this->branch = $this->createBranch($this->tenant, 'Inventory branch ' . $suffix);
            $this->foreignBranch = $this->createBranch($this->foreignTenant, 'Foreign branch ' . $suffix);
            $this->type = $this->createType($this->tenant, 'Inventory type ' . $suffix);
            $this->foreignType = $this->createType($this->foreignTenant, 'Foreign type ' . $suffix);

            $this->app->instance(TenantContext::class, new FixedInventoryTenantContext($this->tenant));
            $this->withoutMiddleware(RequireTenantContext::class);
        } catch (\Throwable $exception) {
            if ($this->transactionStarted && DB::connection('mysql')->transactionLevel() > 0) {
                DB::connection('mysql')->rollBack();
                $this->transactionStarted = false;
            }

            throw $exception;
        }
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted && DB::connection('mysql')->transactionLevel() > 0) {
            DB::connection('mysql')->rollBack();
        }
        $this->transactionStarted = false;

        parent::tearDown();
    }

    private static function requiredTargetApproval(): array
    {
        $names = [
            'approved' => 'AYU_INVENTORY_TEST_APPROVED',
            'database' => 'AYU_INVENTORY_TEST_DATABASE',
            'username' => 'AYU_INVENTORY_TEST_USERNAME',
            'host' => 'AYU_INVENTORY_TEST_HOST',
            'port' => 'AYU_INVENTORY_TEST_PORT',
            'server_hostname' => 'AYU_INVENTORY_TEST_SERVER_HOSTNAME',
            'server_port' => 'AYU_INVENTORY_TEST_SERVER_PORT',
            'authenticated_account' => 'AYU_INVENTORY_TEST_AUTHENTICATED_ACCOUNT',
        ];
        $values = [];

        foreach ($names as $key => $name) {
            $value = getenv($name);
            if ($value === false && isset($_SERVER[$name])) {
                $value = $_SERVER[$name];
            }
            if ($value === false && isset($_ENV[$name])) {
                $value = $_ENV[$name];
            }
            if (!is_string($value) || trim($value) === '') {
                throw new \RuntimeException('Inventory isolation test refused: explicit target approval is incomplete.');
            }
            $values[$key] = trim($value);
        }

        if ($values['approved'] !== '1') {
            throw new \RuntimeException('Inventory isolation test refused: explicit target approval is required.');
        }

        return $values;
    }

    public function test_store_assigns_active_tenant_and_ignores_submitted_tenant_id(): void
    {
        $payload = $this->payload($this->branch, $this->type);
        $payload['tenant_id'] = $this->foreignTenant->id;

        $this->post(route('inventories.store'), $payload)->assertRedirect(route('inventories.index'));

        $item = Inventory::where('description', $payload['description'])->firstOrFail();
        $this->assertSame((int) $this->tenant->id, (int) $item->tenant_id);
    }

    public function test_index_lists_only_active_tenant_inventory(): void
    {
        $owned = $this->createInventory($this->tenant, $this->branch, $this->type, 'Owned inventory');
        $this->createInventory($this->foreignTenant, $this->foreignBranch, $this->foreignType, 'Foreign inventory');

        $this->get(route('inventories.index'))
            ->assertOk()
            ->assertViewHas('inventories', function ($items) use ($owned) {
                return $items->count() === 1 && (int) $items->first()->id === (int) $owned->id;
            });
    }

    public function test_foreign_inventory_ids_cannot_be_edited_updated_or_soft_deleted(): void
    {
        $foreign = $this->createInventory($this->foreignTenant, $this->foreignBranch, $this->foreignType, 'Foreign target');
        $payload = $this->payload($this->branch, $this->type);

        $this->get(route('inventories.edit', $foreign->id))->assertNotFound();
        $this->put(route('inventories.update', $foreign->id), $payload)->assertNotFound();
        $this->delete(route('inventories.destroy', $foreign->id))->assertNotFound();

        $this->assertNull($foreign->fresh()->deleted_at);
        $this->assertSame('Foreign target', $foreign->fresh()->description);
    }

    public function test_store_rejects_foreign_branch_and_type_without_creating_inventory(): void
    {
        $foreignBranchPayload = $this->payload($this->foreignBranch, $this->type);
        $this->post(route('inventories.store'), $foreignBranchPayload)->assertSessionHasErrors('branch_id');
        $this->assertSame(0, Inventory::where('description', $foreignBranchPayload['description'])->count());

        $foreignTypePayload = $this->payload($this->branch, $this->foreignType);
        $this->post(route('inventories.store'), $foreignTypePayload)->assertSessionHasErrors('type_id');
        $this->assertSame(0, Inventory::where('description', $foreignTypePayload['description'])->count());
    }

    public function test_rejected_new_type_request_does_not_create_a_type_or_inventory(): void
    {
        $name = 'Rejected inventory type ' . bin2hex(random_bytes(6));
        $payload = $this->payload($this->foreignBranch, $this->type);
        $payload['type_id'] = '';
        $payload['new_type'] = $name;

        $this->post(route('inventories.store'), $payload)->assertSessionHasErrors('branch_id');

        $this->assertSame(0, InventoryType::withTrashed()->where('name', $name)->count());
        $this->assertSame(0, Inventory::where('description', $payload['description'])->count());
    }

    public function test_inventory_write_failure_rolls_back_new_type_and_inventory(): void
    {
        $name = 'Rolled back inventory type ' . bin2hex(random_bytes(6));
        $payload = $this->payload($this->branch, $this->type);
        $payload['type_id'] = '';
        $payload['new_type'] = $name;

        Inventory::creating(function (Inventory $inventory) {
            throw new \RuntimeException('Forced inventory persistence failure.');
        });

        $this->withoutExceptionHandling();
        try {
            $this->post(route('inventories.store'), $payload);
            $this->fail('Expected the forced inventory persistence failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Forced inventory persistence failure.', $exception->getMessage());
        }

        $this->assertSame(0, InventoryType::withTrashed()->where('name', $name)->count());
        $this->assertSame(0, Inventory::withTrashed()->where('description', $payload['description'])->count());
    }

    public function test_inventory_update_failure_rolls_back_new_type_and_preserves_inventory(): void
    {
        $item = $this->createInventory($this->tenant, $this->branch, $this->type, 'Update rollback target');
        $originalTypeId = (int) $item->type_id;
        $name = 'Rolled back update type ' . bin2hex(random_bytes(6));
        $payload = $this->payload($this->branch, $this->type, 'Changed description');
        $payload['type_id'] = '';
        $payload['new_type'] = $name;

        Inventory::updating(function (Inventory $inventory) {
            throw new \RuntimeException('Forced inventory update failure.');
        });

        $this->withoutExceptionHandling();
        try {
            $this->put(route('inventories.update', $item->id), $payload);
            $this->fail('Expected the forced inventory update failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Forced inventory update failure.', $exception->getMessage());
        }

        $this->assertSame(0, InventoryType::withTrashed()->where('name', $name)->count());
        $this->assertSame('Update rollback target', $item->fresh()->description);
        $this->assertSame($originalTypeId, (int) $item->fresh()->type_id);
    }

    public function test_controller_created_type_uses_active_tenant_and_global_name_uniqueness(): void
    {
        $name = 'Controller-owned type ' . bin2hex(random_bytes(6));
        $payload = $this->payload($this->branch, $this->type);
        $payload['type_id'] = '';
        $payload['new_type'] = $name;

        $this->post(route('inventories.store'), $payload)->assertRedirect(route('inventories.index'));

        $createdType = InventoryType::where('name', $name)->firstOrFail();
        $createdInventory = Inventory::where('description', $payload['description'])->firstOrFail();
        $this->assertSame((int) $this->tenant->id, (int) $createdType->tenant_id);
        $this->assertSame((int) $createdType->id, (int) $createdInventory->type_id);

        $duplicate = $this->payload($this->branch, $this->type);
        $duplicate['type_id'] = '';
        $duplicate['new_type'] = $this->foreignType->name;
        $this->post(route('inventories.store'), $duplicate)->assertSessionHasErrors('new_type');
        $this->assertSame(0, Inventory::where('description', $duplicate['description'])->count());
    }

    public function test_list_does_not_expose_foreign_branch_or_type_relationships(): void
    {
        $item = $this->createInventory($this->tenant, $this->foreignBranch, $this->foreignType, 'Mismatched relations');

        $this->get(route('inventories.index'))
            ->assertOk()
            ->assertViewHas('inventories', function ($items) use ($item) {
                $listed = $items->firstWhere('id', $item->id);
                return $listed !== null
                    && $listed->relationLoaded('branch')
                    && $listed->relationLoaded('type')
                    && $listed->branch === null
                    && $listed->type === null;
            });
    }

    public function test_create_and_edit_selectors_exclude_foreign_branches_and_types(): void
    {
        $this->createInventory($this->tenant, $this->branch, $this->type, 'Selector edit target');

        $createResponse = $this->get(route('inventories.create'))->assertOk();
        $createResponse->assertSee($this->branch->name)->assertDontSee($this->foreignBranch->name);
        $createResponse->assertSee($this->type->name)->assertDontSee($this->foreignType->name);

        $item = Inventory::where('description', 'Selector edit target')->firstOrFail();
        $editResponse = $this->get(route('inventories.edit', $item->id))->assertOk();
        $editResponse->assertSee($this->branch->name)->assertDontSee($this->foreignBranch->name);
        $editResponse->assertSee($this->type->name)->assertDontSee($this->foreignType->name);
    }

    public function test_update_rejects_foreign_associations_without_mutating_inventory(): void
    {
        $item = $this->createInventory($this->tenant, $this->branch, $this->type, 'Owned update target');
        $payload = $this->payload($this->foreignBranch, $this->type);
        $payload['type_id'] = '';
        $payload['new_type'] = 'Rejected foreign branch type';
        $this->put(route('inventories.update', $item->id), $payload)->assertSessionHasErrors('branch_id');
        $this->assertSame((int) $this->branch->id, (int) $item->fresh()->branch_id);
        $this->assertSame(0, InventoryType::where('name', 'Rejected foreign branch type')->count());

        $payload = $this->payload($this->branch, $this->foreignType);
        $this->put(route('inventories.update', $item->id), $payload)->assertSessionHasErrors('type_id');
        $this->assertSame((int) $this->type->id, (int) $item->fresh()->type_id);
    }

    public function test_missing_inventory_module_or_permission_index_denies_access(): void
    {
        $contents = json_decode($this->role->fresh()->content, true) ?: [];
        unset($contents['Inventory']);
        $this->saveRoleContents($contents);
        $this->get(route('inventories.index'))->assertForbidden();

        $contents['Inventory'] = [0 => 'Inventory', 3 => 1, 4 => 1, 5 => 1];
        $this->saveRoleContents($contents);
        $this->get(route('inventories.index'))->assertForbidden();
    }

    public function test_malformed_inventory_permission_values_deny_access(): void
    {
        $contents = json_decode($this->role->fresh()->content, true) ?: [];
        foreach ([2, 'yes', '1', true, [], null] as $value) {
            $contents['Inventory'] = [0 => 'Inventory', 1 => $value];
            $this->saveRoleContents($contents);
            $this->get(route('inventories.index'))->assertForbidden();
        }
    }

    public function test_missing_role_record_denies_inventory_and_other_configured_modules(): void
    {
        $this->user->role_manage_id = null;
        $this->user->save();

        $this->get(route('inventories.index'))->assertForbidden();
        $this->assertSame(0, config('role_manage.Branch.All'));
        $this->get(route('branch'))->assertRedirect(route('dashboard'));
    }

    public function test_create_edit_and_delete_permissions_are_enforced(): void
    {
        $payload = $this->payload($this->branch, $this->type);
        $this->grantInventoryPermissions(['All']);
        $this->get(route('inventories.create'))->assertForbidden();
        $this->post(route('inventories.store'), $payload)->assertForbidden();
        $this->assertSame(0, Inventory::where('description', $payload['description'])->count());

        $item = $this->createInventory($this->tenant, $this->branch, $this->type, 'Permission target');
        $this->get(route('inventories.edit', $item->id))->assertForbidden();
        $this->put(route('inventories.update', $item->id), $this->payload($this->branch, $this->type))
            ->assertForbidden();
        $this->delete(route('inventories.destroy', $item->id))->assertForbidden();
        $this->assertNull($item->fresh()->deleted_at);

        $this->grantInventoryPermissions(['All', 'Create', 'Edit', 'Delete']);
        $this->get(route('inventories.edit', $item->id))->assertOk();
        $this->put(route('inventories.update', $item->id), $this->payload($this->branch, $this->type, 'Permission target'))
            ->assertRedirect(route('inventories.index'));
        $this->delete(route('inventories.destroy', $item->id))->assertRedirect(route('inventories.index'));
        $this->assertNotNull($item->fresh()->deleted_at);
    }

    public function test_missing_active_tenant_context_fails_closed(): void
    {
        $this->app->instance(TenantContext::class, new class($this->tenant) extends FixedInventoryTenantContext {
            public function currentTenant(Request $request, ?User $user = null): ?Tenant
            {
                return null;
            }
        });

        $this->get(route('inventories.index'))->assertForbidden();
    }

    private function grantInventoryPermissions(array $allowed): void
    {
        $values = [0 => 'Inventory'];
        foreach ([1 => 'All', 2 => 'Show', 3 => 'Create', 4 => 'Edit', 5 => 'Delete', 6 => 'Pdf', 7 => 'TrashShow', 8 => 'Restore', 9 => 'PermanentlyDelete'] as $index => $permission) {
            $values[$index] = in_array($permission, $allowed, true) ? 1 : 0;
        }

        $contents = json_decode($this->role->content, true) ?: [];
        $contents['Inventory'] = $values;
        $this->saveRoleContents($contents);
    }

    private function saveRoleContents(array $contents): void
    {
        $this->role->content = json_encode($contents);
        $this->role->save();
    }

    private function createBranch(Tenant $tenant, string $name): Branch
    {
        $branch = Branch::create(['name' => $name]);
        $branch->tenant_id = $tenant->id;
        $branch->save();

        return $branch;
    }

    private function createType(Tenant $tenant, string $name): InventoryType
    {
        $type = new InventoryType(['name' => $name]);
        $type->tenant_id = $tenant->id;
        $type->save();

        return $type;
    }

    private function createInventory(Tenant $tenant, Branch $branch, InventoryType $type, string $description): Inventory
    {
        $item = new Inventory($this->payload($branch, $type, $description));
        $item->tenant_id = $tenant->id;
        $item->save();

        return $item;
    }

    private function payload(Branch $branch, InventoryType $type, ?string $description = null): array
    {
        return [
            'type_id' => $type->id,
            'branch_id' => $branch->id,
            'new_type' => '',
            'description' => $description ?: 'Inventory isolation ' . bin2hex(random_bytes(6)),
            'sub_item' => 'Test fixture',
            'unit_of_measurement' => 'Pieces',
            'unit_cost' => '12.50',
            'sale_price' => '20.00',
            'quantity_at_hand' => '4',
            'reorder_level' => '1',
            'account' => 'Test account',
        ];
    }
}

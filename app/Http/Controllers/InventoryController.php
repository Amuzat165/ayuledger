<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryType;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use App\Branch;
use App\IncomeExpenseHead;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('inventory.module_show')->only('index');
        $this->middleware('inventory.create')->only(['create', 'store']);
        $this->middleware('inventory.edit')->only(['edit', 'update']);
        $this->middleware('inventory.delete')->only('destroy');
    }

    private function tenantId(Request $request): int
    {
        $tenant = app(TenantContext::class)->currentTenant($request);
        abort_unless($tenant, 403);

        return (int) $tenant->getKey();
    }

    private function inventoryForTenant(int $tenantId)
    {
        return Inventory::query()->where('tenant_id', $tenantId);
    }

    private function findForTenant(int $tenantId, $id): Inventory
    {
        return $this->inventoryForTenant($tenantId)->findOrFail($id);
    }

    public function index(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $inventories = $this->inventoryForTenant($tenantId)
            ->with([
                'type' => function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                },
                'branch' => function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                },
            ])->get();
        return view('pages.inventories.index', compact('inventories'));
    }

    public function create(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $types = InventoryType::where('tenant_id', $tenantId)->get();
        $branches = Branch::where('tenant_id', $tenantId)->get();
        $accounts = IncomeExpenseHead::pluck('name', 'id');
        return view('pages.inventories.create', compact('types', 'accounts', 'branches'));
    }

    public function store(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $request->validate([
            'type_id' => ['nullable', Rule::exists('inventory_types', 'id')->where('tenant_id', $tenantId)],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('tenant_id', $tenantId)],
            'new_type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sub_item' => 'nullable|string',
            'unit_of_measurement' => 'required|string',
            'unit_cost' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'quantity_at_hand' => 'required|integer',
            'reorder_level' => 'required|integer|min:0',
            'track_stock' => 'nullable|in:0,1',
            'account' => 'required|string',
        ]);

        $typeId = $request->input('type_id');
        $newType = $request->input('new_type');

        if (!$typeId && $newType && InventoryType::withTrashed()->where('name', $newType)->exists()) {
            return redirect()->back()->withErrors(['new_type' => 'Inventory type already exists.'])->withInput();
        }

        DB::transaction(function () use ($request, $tenantId, $typeId, $newType) {
            if (!$typeId && $newType) {
                $type = new InventoryType(['name' => $newType]);
                $type->tenant_id = $tenantId;
                $type->save();
                $typeId = $type->id;
            }

            $inventory = new Inventory([
                'type_id' => $typeId,
                'branch_id' => $request->input('branch_id'),
                'description' => $request->input('description'),
                'sub_item' => $request->input('sub_item'),
                'unit_of_measurement' => $request->input('unit_of_measurement'),
                'unit_cost' => $request->input('unit_cost'),
                'sale_price' => $request->input('sale_price'),
                'quantity_at_hand' => $request->input('quantity_at_hand'),
                'reorder_level' => $request->input('reorder_level'),
                'track_stock' => $request->input('track_stock') === null ? null : (int) $request->input('track_stock'),
                'account' => $request->input('account'),
            ]);
            $inventory->tenant_id = $tenantId;
            $inventory->save();
        });

        return redirect()->route('inventories.index')->with('success', 'Inventory created successfully.');
    }



    public function edit(Request $request, $id)
    {
        $tenantId = $this->tenantId($request);
        $inventory = $this->findForTenant($tenantId, $id);
        $types = InventoryType::where('tenant_id', $tenantId)->get();
        $branches = Branch::where('tenant_id', $tenantId)->get();
        $accounts = IncomeExpenseHead::pluck('name', 'id');
        return view('pages.inventories.edit', compact('inventory', 'types', 'accounts','branches'));
    }

    public function update(Request $request, $id)
    {
        $tenantId = $this->tenantId($request);
        $inventory = $this->findForTenant($tenantId, $id);
        $request->validate([
            'type_id' => ['nullable', Rule::exists('inventory_types', 'id')->where('tenant_id', $tenantId)],
            'branch_id' => ['required', Rule::exists('branches', 'id')->where('tenant_id', $tenantId)],
            'new_type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sub_item' => 'nullable|string',
            'unit_of_measurement' => 'required|string',
            'unit_cost' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'quantity_at_hand' => 'required|integer',
            'reorder_level' => 'required|integer|min:0',
            'track_stock' => 'nullable|in:0,1',
            'account' => 'required|string',
        ]);

        $typeId = $request->input('type_id');

        if (!$typeId && $request->input('new_type')) {
            if (InventoryType::withTrashed()->where('name', $request->input('new_type'))->exists()) {
                return redirect()->back()->withErrors(['new_type' => 'Inventory type already exists.'])->withInput();
            }
        }

        DB::transaction(function () use ($request, $tenantId, $inventory, $typeId) {
            if (!$typeId && $request->input('new_type')) {
                $type = new InventoryType(['name' => $request->input('new_type')]);
                $type->tenant_id = $tenantId;
                $type->save();
                $typeId = $type->id;
            }

            $inventory->update([
                'type_id' => $typeId,
                'branch_id' => $request->input('branch_id'),
                'description' => $request->input('description'),
                'sub_item' => $request->input('sub_item'),
                'unit_of_measurement' => $request->input('unit_of_measurement'),
                'unit_cost' => $request->input('unit_cost'),
                'sale_price' => $request->input('sale_price'),
                'quantity_at_hand' => $request->input('quantity_at_hand'),
                'reorder_level' => $request->input('reorder_level'),
                'track_stock' => $request->exists('track_stock')
                    ? ($request->input('track_stock') === null ? null : (int) $request->input('track_stock'))
                    : $inventory->track_stock,
                'account' => $request->input('account'),
            ]);
        });

        return redirect()->route('inventories.index')->with('success', 'Inventory updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $inventory = $this->findForTenant($this->tenantId($request), $id);
        $inventory->delete();
        return redirect()->route('inventories.index')->with('success', 'Inventory deleted successfully.');
    }
}

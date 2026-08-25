<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryType;
use Illuminate\Http\Request;
use App\Branch;
use App\IncomeExpenseHead;

class InventoryController extends Controller
{
    public function index()
    {
        $inventories = Inventory::with('type','branch')->get();
        return view('pages.inventories.index', compact('inventories'));
    }

    public function create()
    {
        $types = InventoryType::all();
        $branches = Branch::all();
        $accounts = IncomeExpenseHead::pluck('name', 'id');
        return view('pages.inventories.create', compact('types', 'accounts', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type_id' => 'nullable|exists:inventory_types,id',
            'branch_id' => 'required|exists:branches,id',
            'new_type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sub_item' => 'nullable|string',
            'unit_of_measurement' => 'required|string',
            'unit_cost' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'quantity_at_hand' => 'required|integer',
            'account' => 'required|string',
        ]);

        $typeId = $request->input('type_id');
        $newType = $request->input('new_type');

        if (!$typeId && $newType) {
            // Check for duplicate inventory type
            if (InventoryType::where('name', $newType)->exists()) {
                return redirect()->back()->withErrors(['new_type' => 'Inventory type already exists.'])->withInput();
            }

            $type = InventoryType::create(['name' => $newType]);
            $typeId = $type->id;
        }

        Inventory::create([
            'type_id' => $typeId,
            'branch_id' => $request->input('branch_id'),
            'description' => $request->input('description'),
            'sub_item' => $request->input('sub_item'),
            'unit_of_measurement' => $request->input('unit_of_measurement'),
            'unit_cost' => $request->input('unit_cost'),
            'sale_price' => $request->input('sale_price'),
            'quantity_at_hand' => $request->input('quantity_at_hand'),
            'account' => $request->input('account'),
        ]);

        return redirect()->route('inventories.index')->with('success', 'Inventory created successfully.');
    }



    public function edit($id)
    {
        $inventory = Inventory::findOrFail($id);
        $types = InventoryType::all();
        $branches = Branch::all();
        $accounts = IncomeExpenseHead::pluck('name', 'id');
        return view('pages.inventories.edit', compact('inventory', 'types', 'accounts','branches'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'type_id' => 'nullable|exists:inventory_types,id',
            'branch_id' => 'required|exists:branches,id',
            'new_type' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'sub_item' => 'nullable|string',
            'unit_of_measurement' => 'required|string',
            'unit_cost' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'quantity_at_hand' => 'required|integer',
            'account' => 'required|string',
        ]);

        $inventory = Inventory::findOrFail($id);

        $typeId = $request->input('type_id');

        if (!$typeId && $request->input('new_type')) {
            $type = InventoryType::create(['name' => $request->input('new_type')]);
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
            'account' => $request->input('account'),
        ]);

        return redirect()->route('inventories.index')->with('success', 'Inventory updated successfully.');
    }

    public function destroy($id)
    {
        $inventory = Inventory::findOrFail($id);
        $inventory->delete();
        return redirect()->route('inventories.index')->with('success', 'Inventory deleted successfully.');
    }
}

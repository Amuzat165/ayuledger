<?php

namespace App\Http\Controllers;

use App\Branch;
use App\Services\TenantContext;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BranchController extends Controller
{
    public $parentModel = Branch::class;
    public $parentRoute = 'branch';
    public $parentView = 'admin.branch';

    /**
     * Branch actions require middleware, but the tenant is resolved again here
     * so controller queries never depend on request-supplied ownership data.
     */
    private function tenantId(Request $request): int
    {
        $tenant = app(TenantContext::class)->currentTenant($request);
        abort_unless($tenant, 403);

        return (int) $tenant->getKey();
    }

    private function queryForTenant(int $tenantId)
    {
        return Branch::query()->where('tenant_id', $tenantId);
    }

    private function findForTenant(Request $request, $id): Branch
    {
        return $this->queryForTenant($this->tenantId($request))->findOrFail($id);
    }

    private function cacheKey(int $tenantId, string $key): string
    {
        return 'tenant:' . $tenantId . ':' . $key;
    }

    private function forgetBranchCaches(int $tenantId): void
    {
        Cache::forget($this->cacheKey($tenantId, 'branches'));
        Cache::forget($this->cacheKey($tenantId, 'total_branches'));
        Cache::forget($this->cacheKey($tenantId, 'total_trashed_branches'));
    }

    private function countsForTenant(int $tenantId): array
    {
        return [
            'total_branches' => Cache::remember(
                $this->cacheKey($tenantId, 'total_branches'),
                now()->addMinutes(5),
                function () use ($tenantId) {
                    return $this->queryForTenant($tenantId)->count();
                }
            ),
            'total_trashed_branches' => Cache::remember(
                $this->cacheKey($tenantId, 'total_trashed_branches'),
                now()->addMinutes(5),
                function () use ($tenantId) {
                    return $this->queryForTenant($tenantId)->onlyTrashed()->count();
                }
            ),
        ];
    }

    private function assertPermission(string $permission): void
    {
        abort_unless((bool) config('role_manage.Branch.' . $permission), 403);
    }

    private function selectedIds(Request $request): array
    {
        $validated = $request->validate([
            'items.id' => ['required', 'array', 'min:1'],
            'items.id.*' => ['required', 'integer', 'distinct'],
        ]);

        return array_map('intval', $validated['items']['id']);
    }

    private function selectedCommand(Request $request): int
    {
        $top = (int) $request->input('apply_comand_top', 0);
        $bottom = (int) $request->input('apply_comand_bottom', 0);
        if ($top !== 0 && $bottom !== 0 && $top !== $bottom) {
            throw ValidationException::withMessages(['items' => 'Choose one action for the selected branches.']);
        }

        $command = $top ?: $bottom;
        if (!in_array($command, [1, 2, 3], true)) {
            throw ValidationException::withMessages(['items' => 'Choose a valid action.']);
        }

        return $command;
    }

    private function ensureAllBelongToTenant(int $tenantId, array $ids, bool $trashed): array
    {
        $query = $this->queryForTenant($tenantId)->whereIn('id', $ids)->lockForUpdate();
        $query = $trashed ? $query->onlyTrashed() : $query->whereNull('deleted_at');
        $branches = $query->get();
        if ($branches->count() !== count($ids)) {
            abort(404);
        }

        return $branches->all();
    }

    /**
     * Foreign keys with SET NULL would otherwise detach history on force delete.
     * Check every currently known inbound branch_id reference before deletion.
     */
    private function hasHistoricalReferences(int $branchId): bool
    {
        foreach ([
            'budgets', 'customers', 'expenses', 'goods_receipts', 'inventories',
            'payrolls', 'purchase_orders', 'supplier_invoices', 'transactions', 'vendors',
        ] as $table) {
            if (DB::table($table)->where('branch_id', $branchId)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function performSoftDelete(Branch $branch): void
    {
        $branch->delete_by = auth()->user()->email;
        // Keep the original name: it is part of the preserved historical record.
        $branch->save();
        $branch->delete();
    }

    private function performPermanentDelete(Branch $branch): void
    {
        if ($this->hasHistoricalReferences((int) $branch->getKey())) {
            throw new HttpException(409, 'This branch has linked business records and cannot be permanently deleted.');
        }

        $branch->forceDelete();
    }

    private function safeMutationFailure(string $message)
    {
        Session::flash('error', $message);

        return redirect()->back();
    }

    public function index(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $items = $this->queryForTenant($tenantId)->orderBy('created_at', 'desc')->paginate(60)->onEachSide(1);

        return view($this->parentView . '.index', $this->countsForTenant($tenantId))->with('items', $items);
    }

    public function create(Request $request)
    {
        $this->tenantId($request);

        return view($this->parentView . '.create');
    }

    public function store(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate([
            'name' => ['required', 'string', Rule::unique('branches', 'name')],
            'location' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ], ['name.unique' => 'A branch with this name already exists.']);

        $branch = new Branch();
        $branch->tenant_id = $tenantId;
        $branch->name = $validated['name'];
        $branch->location = $validated['location'] ?? null;
        $branch->description = $validated['description'] ?? null;
        $branch->create_by = auth()->user()->email;
        $branch->save();

        $this->forgetBranchCaches($tenantId);
        Session::flash('success', 'Successfully Create');

        return redirect()->back();
    }

    public function show(Request $request, $id)
    {
        $item = $this->findForTenant($request, $id);

        return view($this->parentView . '.show')->with('items', $item);
    }

    public function edit(Request $request, $id)
    {
        $item = $this->findForTenant($request, $id);

        return view($this->parentView . '.edit')->with('item', $item);
    }

    public function update(Request $request, $id)
    {
        $tenantId = $this->tenantId($request);
        $item = $this->findForTenant($request, $id);
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', Rule::unique('branches', 'name')->ignore($item->getKey())],
            'location' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string'],
        ], ['name.unique' => 'A branch with this name already exists.']);

        foreach (['name', 'location', 'description'] as $field) {
            if (array_key_exists($field, $validated)) {
                $item->{$field} = $validated[$field];
            }
        }
        $item->update_by = auth()->user()->email;
        $item->save();

        $this->forgetBranchCaches($tenantId);
        Session::flash('success', 'Update Successfully');

        return redirect()->route($this->parentRoute);
    }

    public function pdf(Request $request, $id)
    {
        $item = $this->findForTenant($request, $id);
        $now = new \DateTime();
        $date = $now->format(Config('settings.date_format') . ' h:i:s');
        $extra = ['current_date_time' => $date, 'module_name' => 'Branch Manage'];
        $pdf = PDF::loadView($this->parentView . '.pdf', ['items' => $item, 'extra' => $extra])
            ->setPaper('a4', 'landscape');

        return $pdf->download($extra['current_date_time'] . '_' . $extra['module_name'] . '.pdf');
    }

    public function destroy(Request $request, $id)
    {
        $tenantId = $this->tenantId($request);
        $branch = $this->findForTenant($request, $id);
        $this->performSoftDelete($branch);
        $this->forgetBranchCaches($tenantId);
        Session::flash('success', 'Successfully Trashed');

        return redirect()->back();
    }

    public function trashed(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $items = $this->queryForTenant($tenantId)->onlyTrashed()->paginate(60);

        return view($this->parentView . '.trashed', $this->countsForTenant($tenantId))->with('items', $items);
    }

    public function restore(Request $request, $id)
    {
        $tenantId = $this->tenantId($request);
        $item = $this->queryForTenant($tenantId)->onlyTrashed()->findOrFail($id);
        $item->restore();
        $this->forgetBranchCaches($tenantId);
        Session::flash('success', 'Successfully Restore');

        return redirect()->back();
    }

    public function kill(Request $request, $id)
    {
        $tenantId = $this->tenantId($request);

        try {
            DB::transaction(function () use ($tenantId, $id) {
                $item = $this->queryForTenant($tenantId)->withTrashed()->lockForUpdate()->findOrFail($id);
                $this->performPermanentDelete($item);
            });
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 404) {
                throw $e;
            }
            return $this->safeMutationFailure($e->getMessage());
        } catch (QueryException $e) {
            return $this->safeMutationFailure('This branch is still linked to business records and cannot be permanently deleted.');
        }

        $this->forgetBranchCaches($tenantId);
        Session::flash('success', 'Permanently Delete');

        return redirect()->back();
    }

    public function activeSearch(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate(['search' => ['nullable', 'string', 'min:1']]);
        $search = $validated['search'] ?? '';
        $items = $this->queryForTenant($tenantId)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('location', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            })
            ->paginate(60);

        return view($this->parentView . '.index', $this->countsForTenant($tenantId))->with('items', $items);
    }

    public function trashedSearch(Request $request)
    {
        $tenantId = $this->tenantId($request);
        $validated = $request->validate(['search' => ['nullable', 'string', 'min:1']]);
        $search = $validated['search'] ?? '';
        $items = $this->queryForTenant($tenantId)->onlyTrashed()
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('location', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            })
            ->paginate(60);

        return view($this->parentView . '.trashed', $this->countsForTenant($tenantId))->with('items', $items);
    }

    public function activeAction(Request $request)
    {
        return $this->bulkAction($request, false);
    }

    public function trashedAction(Request $request)
    {
        return $this->bulkAction($request, true);
    }

    private function bulkAction(Request $request, bool $trashed)
    {
        $tenantId = $this->tenantId($request);
        $ids = $this->selectedIds($request);
        $command = $this->selectedCommand($request);

        if ($trashed && $command === 1) {
            $this->assertPermission('Restore');
        } elseif ($command === 2) {
            $this->assertPermission('PermanentlyDelete');
        } elseif ($command === 3 && !$trashed) {
            $this->assertPermission('Delete');
        } else {
            abort(403);
        }

        try {
            DB::transaction(function () use ($tenantId, $ids, $trashed, $command) {
                $branches = $this->ensureAllBelongToTenant($tenantId, $ids, $trashed);

                if ($command === 2) {
                    foreach ($branches as $branch) {
                        if ($this->hasHistoricalReferences((int) $branch->getKey())) {
                            throw new HttpException(409, 'One or more branches have linked business records and cannot be permanently deleted.');
                        }
                    }
                }

                foreach ($branches as $branch) {
                    if ($command === 1) {
                        $branch->restore();
                    } elseif ($command === 2) {
                        $branch->forceDelete();
                    } else {
                        $this->performSoftDelete($branch);
                    }
                }
            });
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 404) {
                throw $e;
            }
            return $this->safeMutationFailure($e->getMessage());
        } catch (QueryException $e) {
            return $this->safeMutationFailure('One or more branches are still linked to business records; no changes were made.');
        }

        $this->forgetBranchCaches($tenantId);
        Session::flash('success', 'Branch action completed successfully.');

        return redirect()->back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceivedItem;
use App\Models\PurchaseOrder;
use App\Services\GoodsReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GoodsReceivedController extends Controller
{
    private $receipts;

    public function __construct(GoodsReceiptService $receipts)
    {
        $this->middleware('auth');
        $this->receipts = $receipts;
    }

    public function index()
    {
        $this->authorizePermission('Show');
        $receipts = GoodsReceipt::with(['purchaseOrder', 'vendor', 'branch', 'receiver'])
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(20);
        return view('admin.goods-received.index', compact('receipts'));
    }

    public function create($purchaseOrderId)
    {
        $this->authorizePermission('Create');
        $order = PurchaseOrder::with(['items.inventoryItem', 'vendor', 'branch'])->findOrFail($purchaseOrderId);
        abort_unless(in_array($order->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_PARTIALLY_RECEIVED], true), 422, __('goods_received.errors.order_not_receivable'));
        $received = $this->confirmedByLine($order->id);
        $lines = $order->items->map(function ($line) use ($received) {
            $previous = (float) ($received[$line->id] ?? 0);
            $ordered = (float) $line->quantity;
            return (object) [
                'id' => $line->id,
                'description' => $line->inventory_item_name_snapshot ?: $line->description,
                'unit' => $line->unit,
                'agreed_unit_price' => $line->agreed_unit_price,
                'quantity' => $line->quantity,
                'previously_received' => number_format($previous, 3, '.', ''),
                'remaining_quantity' => number_format(max(0, $ordered - $previous), 3, '.', ''),
            ];
        });
        $submissionToken = old('submission_token', (string) \Illuminate\Support\Str::uuid());
        return view('admin.goods-received.create', compact('order', 'lines', 'submissionToken'));
    }

    public function store(Request $request, $purchaseOrderId)
    {
        $this->authorizePermission('Create');
        $data = Validator::make($request->all(), [
            'submission_token' => 'required|uuid',
            'received_date' => 'required|date_format:Y-m-d',
            'notes' => 'nullable|string|max:5000',
            'received_quantities' => 'required|array',
            'received_quantities.*' => ['nullable', 'regex:/^\d+(?:\.\d{1,3})?$/'],
        ])->validate();
        $order = PurchaseOrder::findOrFail($purchaseOrderId);
        $receipt = $this->receipts->saveDraft($order, $request->user(), $data);
        return redirect()->route('goods-received.show', $receipt)->with('success', __('goods_received.messages.draft_saved'));
    }

    public function show($id)
    {
        $this->authorizePermission('Show');
        $receipt = GoodsReceipt::with(['purchaseOrder.items', 'vendor', 'branch', 'creator', 'receiver', 'items.purchaseOrderItem'])->findOrFail($id);
        return view('admin.goods-received.show', compact('receipt'));
    }

    public function confirm($id, Request $request)
    {
        $this->authorizePermission('Edit');
        $receipt = $this->receipts->confirm((int) $id, $request->user());
        return redirect()->route('goods-received.show', $receipt)->with('success', __('goods_received.messages.confirmed'));
    }

    private function confirmedByLine(int $purchaseOrderId): array
    {
        return GoodsReceivedItem::query()
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_received_items.goods_receipt_id')
            ->join('purchase_order_items', 'purchase_order_items.id', '=', 'goods_received_items.purchase_order_item_id')
            ->where('purchase_order_items.purchase_order_id', $purchaseOrderId)
            ->where('goods_receipts.status', GoodsReceipt::STATUS_CONFIRMED)
            ->select('goods_received_items.purchase_order_item_id', DB::raw('SUM(goods_received_items.received_quantity) AS received_quantity'))
            ->groupBy('goods_received_items.purchase_order_item_id')
            ->pluck('received_quantity', 'purchase_order_item_id')->all();
    }

    private function authorizePermission(string $action): void
    {
        abort_unless((bool) config('role_manage.PurchaseOrder.' . $action), 403);
    }
}

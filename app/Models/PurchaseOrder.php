<?php

namespace App\Models;

use App\Branch;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends \App\TenantScopedModel
{
    use SoftDeletes;

    const STATUS_DRAFT = 'draft';
    const STATUS_APPROVED = 'approved';
    const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'purchase_order_number', 'submission_token', 'vendor_id',
        'vendor_name_snapshot', 'vendor_address_snapshot', 'vendor_phone_snapshot',
        'vendor_account_snapshot', 'vendor_terms_snapshot', 'vendor_branch_name_snapshot',
        'branch_id', 'branch_name_snapshot', 'currency_code', 'currency_symbol', 'currency_position',
        'order_date', 'expected_delivery_date', 'status', 'approved_by', 'approved_at', 'subtotal', 'order_total',
        'delivery_instructions', 'internal_notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'order_total' => 'decimal:2',
    ];

    public static function statuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_APPROVED, self::STATUS_PARTIALLY_RECEIVED, self::STATUS_RECEIVED, self::STATUS_CANCELLED];
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id')->withTrashed();
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function goodsReceipts()
    {
        return $this->hasMany(GoodsReceipt::class)->orderBy('id');
    }

    public function supplierInvoices()
    {
        return $this->hasMany(SupplierInvoice::class, 'purchase_order_id')->orderBy('id');
    }
}

<?php

namespace App\Models;

use App\Branch;
use App\User;
use Illuminate\Database\Eloquent\Model;

class SupplierInvoice extends \App\TenantScopedModel
{
    const STATUS_DRAFT = 'draft';
    const STATUS_REVIEW_REQUIRED = 'review_required';
    const STATUS_APPROVED = 'approved';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'internal_invoice_number', 'supplier_invoice_number', 'vendor_id', 'purchase_order_id',
        'branch_id', 'vendor_name_snapshot', 'branch_name_snapshot', 'currency_code',
        'currency_symbol', 'currency_position', 'invoice_date', 'due_date', 'status',
        'review_reason', 'subtotal', 'wht_amount', 'total_amount', 'amount_paid',
        'amount_remaining', 'wht_enabled_snapshot', 'wht_mode_snapshot', 'wht_rate_snapshot',
        'wht_base_snapshot', 'wht_calculation_version', 'submission_token', 'created_by',
        'updated_by', 'approved_by', 'approved_at', 'cancelled_by', 'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'invoice_date' => 'date', 'due_date' => 'date', 'approved_at' => 'datetime',
        'cancelled_at' => 'datetime', 'wht_enabled_snapshot' => 'boolean',
        'subtotal' => 'decimal:2', 'wht_amount' => 'decimal:2', 'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2', 'amount_remaining' => 'decimal:2',
        'wht_rate_snapshot' => 'decimal:2', 'wht_base_snapshot' => 'decimal:2',
    ];

    public function vendor() { return $this->belongsTo(Vendor::class)->withTrashed(); }
    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function branch() { return $this->belongsTo(Branch::class, 'branch_id')->withTrashed(); }
    public function items() { return $this->hasMany(SupplierInvoiceItem::class)->orderBy('id'); }
    public function payments() { return $this->hasMany(SupplierInvoicePayment::class)->orderBy('payment_date')->orderBy('id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by')->withTrashed(); }
    public function canceller() { return $this->belongsTo(User::class, 'cancelled_by')->withTrashed(); }

    public function isPayable(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function paymentStatus(): string
    {
        if (!$this->isPayable() || $this->amount_remaining === null) {
            return 'not_due';
        }
        if ((float) $this->amount_remaining <= 0) {
            return 'paid';
        }
        return (float) $this->amount_paid > 0 ? 'part_paid' : 'unpaid';
    }
}

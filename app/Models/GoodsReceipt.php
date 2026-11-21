<?php

namespace App\Models;

use App\Branch;
use App\User;
use Illuminate\Database\Eloquent\Model;

class GoodsReceipt extends \App\TenantScopedModel
{
    const STATUS_DRAFT = 'draft';
    const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'receipt_number', 'submission_token', 'purchase_order_id', 'vendor_id', 'branch_id',
        'received_date', 'status', 'created_by', 'received_by', 'notes',
    ];

    protected $casts = ['received_date' => 'date'];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function vendor() { return $this->belongsTo(Vendor::class)->withTrashed(); }
    public function branch() { return $this->belongsTo(Branch::class)->withTrashed(); }
    public function creator() { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
    public function receiver() { return $this->belongsTo(User::class, 'received_by')->withTrashed(); }
    public function items() { return $this->hasMany(GoodsReceivedItem::class)->orderBy('id'); }
}

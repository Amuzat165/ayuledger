<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\GeneratesInvoiceNumber;

class VendorInvoice extends \App\TenantScopedModel
{
    use HasFactory;
    use SoftDeletes;
    use GeneratesInvoiceNumber;

    protected const INVOICE_SEQUENCE = 'vendor';

    protected $guarded = [];

    protected $casts = [
        'wht_enabled_snapshot' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(VendorInvoiceItem::class, 'invoice_id');
    }
}

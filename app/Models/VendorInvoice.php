<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\GeneratesInvoiceNumber;

class VendorInvoice extends Model
{
    use HasFactory;
    use SoftDeletes;
    use GeneratesInvoiceNumber;

    protected const INVOICE_SEQUENCE = 'vendor';

    protected $guarded = [];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items()
    {
        return $this->hasMany(VendorInvoiceItem::class, 'invoice_id');
    }
}

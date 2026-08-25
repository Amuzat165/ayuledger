<?php

namespace App\Models;

use App\BankCash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerPayments extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'customer_id',
        'invoice_id',
        'total_amount',
        'amount_paid',
        'balance_due',
        'payment_mode_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(CustomerInvoice::class);
    }

    public function paymentMode()
    {
        return $this->belongsTo(BankCash::class);
    }
}

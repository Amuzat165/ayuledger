<?php

namespace App\Models;

use App\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends \App\TenantScopedModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'reg_number',
        'name',
        'address',
        'phone_number',
        'account',
        'terms_of_payment',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->reg_number = self::generateUniqueRegNumber();
        });
    }

    private static function generateUniqueRegNumber()
    {
        do {
            $regNumber = random_int(10000, 99999);
        } while (self::where('reg_number', $regNumber)->exists());

        return $regNumber;
    }

    public function invoices()
    {
        return $this->hasMany(VendorInvoice::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}

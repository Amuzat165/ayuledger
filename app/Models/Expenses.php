<?php

namespace App\Models;

use App\BankCash;
use App\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expenses extends Model
{
    use HasFactory, SoftDeletes;

    // Define fillable properties if you want mass assignment protection
    protected $fillable = [
        'branch_id',
        'type_id',
        'account',
        'vendor',
        'expense_date',
        'amount',
        'description',
        'bank_cash_id',
    ];

    public function type()
    {
        return $this->belongsTo(ExpensesType::class, 'type_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function bankCash()
    {
        return $this->belongsTo(BankCash::class, 'bank_cash_id');
    }
}

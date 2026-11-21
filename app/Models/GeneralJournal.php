<?php

namespace App\Models;

use App\Branch;
use App\IncomeExpenseHead;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GeneralJournal extends \App\TenantScopedModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'debit_account_id',
        'debit',
        'credit_account_id',
        'credit',
        'memo',
        'customer_vendor',
    ];

    public function debitAccount()
    {
        return $this->belongsTo(IncomeExpenseHead::class, 'debit_account_id');
    }

    public function creditAccount()
    {
        return $this->belongsTo(IncomeExpenseHead::class, 'credit_account_id');
    }


    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}

<?php

namespace App\Models;

use App\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends \App\TenantScopedModel
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'employee',
        'basic_salary',
        'housing_allowance',
        'transport_allowance',
        'medical_allowance',
        'other_allowance',
        'gross_salary',
        'deduction_payee',
        'pension',
        'net_pay'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}

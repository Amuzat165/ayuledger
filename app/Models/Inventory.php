<?php

namespace App\Models;

use App\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type_id',
        'branch_id',
        'description',
        'sub_item',
        'unit_of_measurement',
        'unit_cost',
        'sale_price',
        'quantity_at_hand',
        'reorder_level',
        'account',
    ];

    /**
     * Get the inventory type associated with the inventory.
     */
    public function type()
    {
        return $this->belongsTo(InventoryType::class, 'type_id');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}

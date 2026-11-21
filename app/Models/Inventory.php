<?php

namespace App\Models;

use App\Branch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends \App\TenantScopedModel
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
        'track_stock',
        'account',
    ];

    /**
     * Get the inventory type associated with the inventory.
     */
    public function type()
    {
        return $this->belongsTo(InventoryType::class, 'type_id');
    }
    /** Whether this inventory item has an explicit stock-tracking choice. */
    public function hasStockTrackingChoice(): bool
    {
        return $this->track_stock !== null;
    }

    /** Whether Goods Received should increase physical stock for this item. */
    public function tracksStock(): bool
    {
        return (string) $this->track_stock === '1';
    }

    /** Whether this item is explicitly marked as a service/non-stock item. */
    public function isNonStock(): bool
    {
        return (string) $this->track_stock === '0';
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}

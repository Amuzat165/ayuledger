<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'monthly_price', 'yearly_price', 'description',
        'features', 'max_users', 'max_branches', 'is_featured', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'integer',
        'max_users' => 'integer',
        'max_branches' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function featureList()
    {
        $features = json_decode($this->features ?: '[]', true);
        return is_array($features) ? array_values(array_filter($features)) : [];
    }
}

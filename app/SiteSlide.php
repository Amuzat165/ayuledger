<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SiteSlide extends Model
{
    protected $fillable = [
        'eyebrow', 'title', 'description', 'primary_label', 'primary_url',
        'secondary_label', 'secondary_url', 'image', 'is_active', 'sort_order', 'business_id',
    ];
}

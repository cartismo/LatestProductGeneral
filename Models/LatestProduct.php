<?php

namespace Modules\LatestProductGeneral\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LatestProduct extends Model
{
    protected $fillable = [
        'product_id',
        'sort_order',
        'is_active',
        'is_manual',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_manual' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeManual($query)
    {
        return $query->where('is_manual', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
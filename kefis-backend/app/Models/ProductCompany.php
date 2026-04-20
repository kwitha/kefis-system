<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCompany extends Model
{
    protected $fillable = ['product_id', 'company_id',  'buying_price',
            'selling_price',
            'stock',
            'minimum_stock',
            'is_active'];

    protected $casts = [
        'buying_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock'=>'integer',
        'minimum_stock'=>'integer',
        'is_active'      => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function company()
    {
        return $this->belongsTo(company::class);
    }

    public function stockBalances()
    {
        return $this->hasMany(StockBalance::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }
}
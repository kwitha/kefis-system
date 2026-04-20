<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockBalance extends Model
{
    protected $fillable = ['branch_id', 'product_id', 'product_company_id', 'quantity'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The specific company this stock belongs to (null for non-grocery).
     */
    public function productCompany()
    {
        return $this->belongsTo(ProductCompany::class);
    }
}